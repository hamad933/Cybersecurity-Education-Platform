<?php

namespace App\Modules\Evidence\Application;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;

final class ProgressEvidenceService
{
    private const FINDINGS = ['SATISFIED', 'PARTIALLY_SATISFIED', 'NOT_SATISFIED', 'NOT_ASSESSABLE'];
    private const DECISIONS = ['ACCEPT', 'ACCEPT_WITH_LIMITATIONS', 'MORE_EVIDENCE_REQUIRED', 'REJECT'];
    private const JUDGMENTS = ['NOT_EVALUATED', 'INSUFFICIENT_EVIDENCE', 'INCONCLUSIVE', 'NOT_MASTERED', 'MASTERED'];
    private const FRESHNESS = ['CURRENT', 'REVALIDATION_REQUIRED'];

    /** @param array<string,mixed> $handoff @return array<string,mixed> */
    public function intakeCandidate(string $subjectId, string $submittedBy, array $handoff): array
    {
        foreach (['source_type', 'source_id', 'source_digest', 'capability_id', 'title', 'summary'] as $key) {
            if (! isset($handoff[$key]) || trim((string) $handoff[$key]) === '') {
                throw new InvalidArgumentException("Missing source handoff field: {$key}.");
            }
        }
        $digest = strtolower((string) $handoff['source_digest']);
        if (! preg_match('/^[a-f0-9]{64}$/', $digest)) {
            throw new InvalidArgumentException('Source digest must be SHA-256 hex.');
        }
        $existing = DB::table('evidence_candidates')
            ->where('subject_actor_id', $subjectId)->where('source_type', $handoff['source_type'])
            ->where('source_id', $handoff['source_id'])->where('source_digest', $digest)->first();
        if ($existing) {
            return $this->array($existing, ['proposed_facts', 'metadata']);
        }

        $id = (string) Str::uuid7();
        $now = now();
        DB::table('evidence_candidates')->insert([
            'id' => $id, 'subject_actor_id' => $subjectId, 'submitted_by' => $submittedBy,
            'source_type' => mb_substr(trim((string) $handoff['source_type']), 0, 64),
            'source_id' => mb_substr(trim((string) $handoff['source_id']), 0, 160),
            'source_revision' => isset($handoff['source_revision']) ? mb_substr(trim((string) $handoff['source_revision']), 0, 80) : null,
            'source_digest' => $digest, 'capability_id' => mb_substr(trim((string) $handoff['capability_id']), 0, 100),
            'proposed_title' => mb_substr(trim((string) $handoff['title']), 0, 180),
            'proposed_summary' => mb_substr(trim((string) $handoff['summary']), 0, 4000),
            'proposed_facts' => $this->json(is_array($handoff['facts'] ?? null) ? $handoff['facts'] : []),
            'metadata' => $this->json(is_array($handoff['metadata'] ?? null) ? $handoff['metadata'] : []),
            'state' => 'CANDIDATE', 'created_at' => $now, 'updated_at' => $now,
        ]);
        return $this->row('evidence_candidates', $id, ['proposed_facts', 'metadata']);
    }

    /** @return array{evidence:array<string,mixed>,revision:array<string,mixed>} */
    public function admitCandidate(string $candidateId, string $actorId): array
    {
        return DB::transaction(function () use ($candidateId, $actorId): array {
            $candidate = $this->lock('evidence_candidates', $candidateId);
            $this->own($candidate, $actorId);
            if ($candidate->state === 'ADMITTED' && $candidate->admitted_evidence_id) {
                return ['evidence' => $this->row('governed_evidence', $candidate->admitted_evidence_id), 'revision' => $this->revision($candidate->admitted_evidence_id, 1)];
            }
            if ($candidate->state !== 'CANDIDATE') {
                throw new LogicException('Candidate is not admissible.');
            }

            $evidenceId = (string) Str::uuid7();
            $now = now();
            DB::table('governed_evidence')->insert([
                'id' => $evidenceId, 'candidate_id' => $candidateId, 'subject_actor_id' => $candidate->subject_actor_id,
                'capability_id' => $candidate->capability_id, 'lifecycle_state' => 'ACTIVE', 'review_status' => 'UNREVIEWED',
                'effective_review_decision' => 'NONE', 'current_revision_number' => 1, 'admitted_by' => $actorId,
                'admitted_at' => $now, 'created_at' => $now, 'updated_at' => $now,
            ]);
            $facts = $this->decode($candidate->proposed_facts);
            $body = ['evidence_id' => $evidenceId, 'revision' => 1, 'title' => $candidate->proposed_title, 'summary' => $candidate->proposed_summary,
                'facts' => $facts, 'source_type' => $candidate->source_type, 'source_id' => $candidate->source_id,
                'source_revision' => $candidate->source_revision, 'source_digest' => $candidate->source_digest];
            DB::table('governed_evidence_revisions')->insert($body + [
                'id' => (string) Str::uuid7(), 'facts' => $this->json($facts), 'content_digest' => $this->digest($body),
                'sealed_by' => $actorId, 'sealed_at' => $now, 'created_at' => $now, 'updated_at' => $now,
            ]);
            DB::table('evidence_candidates')->where('id', $candidateId)->update([
                'state' => 'ADMITTED', 'admitted_evidence_id' => $evidenceId, 'admitted_at' => $now, 'updated_at' => $now,
            ]);
            return ['evidence' => $this->row('governed_evidence', $evidenceId), 'revision' => $this->revision($evidenceId, 1)];
        });
    }

    /** @param array<string,mixed> $data @return array<string,mixed> */
    public function createRevision(string $evidenceId, string $actorId, array $data): array
    {
        return DB::transaction(function () use ($evidenceId, $actorId, $data): array {
            $evidence = $this->lock('governed_evidence', $evidenceId); $this->own($evidence, $actorId);
            if ($evidence->lifecycle_state !== 'ACTIVE') throw new LogicException('Only ACTIVE Evidence can be revised.');
            $current = DB::table('governed_evidence_revisions')->where('evidence_id', $evidenceId)->where('revision', $evidence->current_revision_number)->first();
            if (! $current) throw new LogicException('Current revision is missing.');
            $revision = ((int) $evidence->current_revision_number) + 1;
            $title = trim((string) ($data['title'] ?? '')); $summary = trim((string) ($data['summary'] ?? ''));
            if ($title === '' || $summary === '') throw new InvalidArgumentException('Revision title and summary are required.');
            $facts = is_array($data['facts'] ?? null) ? $data['facts'] : $this->decode($current->facts);
            $body = ['evidence_id' => $evidenceId, 'revision' => $revision, 'title' => mb_substr($title, 0, 180), 'summary' => mb_substr($summary, 0, 4000),
                'facts' => $facts, 'source_type' => $current->source_type, 'source_id' => $current->source_id,
                'source_revision' => $current->source_revision, 'source_digest' => $current->source_digest];
            $now = now();
            DB::table('governed_evidence_revisions')->insert($body + ['id' => (string) Str::uuid7(), 'facts' => $this->json($facts),
                'content_digest' => $this->digest($body), 'sealed_by' => $actorId, 'sealed_at' => $now, 'created_at' => $now, 'updated_at' => $now]);
            DB::table('governed_evidence')->where('id', $evidenceId)->update(['current_revision_number' => $revision, 'updated_at' => $now]);
            return $this->revision($evidenceId, $revision);
        });
    }

    public function transitionLifecycle(string $evidenceId, string $actorId, string $state): void
    {
        if (! in_array($state, ['ACTIVE', 'WITHDRAWN', 'SUPERSEDED'], true)) throw new InvalidArgumentException('Invalid lifecycle.');
        DB::transaction(function () use ($evidenceId, $actorId, $state): void {
            $evidence = $this->lock('governed_evidence', $evidenceId); $this->own($evidence, $actorId);
            if ($evidence->lifecycle_state !== 'ACTIVE' && $evidence->lifecycle_state !== $state) throw new LogicException('Terminal lifecycle cannot reopen.');
            DB::table('governed_evidence')->where('id', $evidenceId)->update(['lifecycle_state' => $state, 'updated_at' => now()]);
        });
    }

    /** @return array<string,mixed> */
    public function requestReview(string $evidenceId, string $actorId): array
    {
        return DB::transaction(function () use ($evidenceId, $actorId): array {
            $evidence = $this->lock('governed_evidence', $evidenceId); $this->own($evidence, $actorId);
            if ($evidence->lifecycle_state !== 'ACTIVE') throw new LogicException('Only ACTIVE Evidence can be reviewed.');
            $existing = DB::table('evidence_review_requests')->where('evidence_id', $evidenceId)->whereIn('status', ['REQUESTED', 'ADMITTED'])->first();
            if ($existing) return (array) $existing;
            $id = (string) Str::uuid7(); $now = now();
            DB::table('evidence_review_requests')->insert(['id' => $id, 'evidence_id' => $evidenceId, 'requested_by' => $actorId,
                'status' => 'REQUESTED', 'requested_at' => $now, 'created_at' => $now, 'updated_at' => $now]);
            return $this->row('evidence_review_requests', $id);
        });
    }

    /** @return array<string,mixed> */
    public function admitReviewRequest(string $requestId, string $reviewerId): array
    {
        return DB::transaction(function () use ($requestId, $reviewerId): array {
            $request = $this->lock('evidence_review_requests', $requestId);
            $evidence = $this->lock('governed_evidence', $request->evidence_id); $this->own($evidence, $reviewerId);
            $existing = DB::table('evidence_reviews')->where('review_request_id', $requestId)->first(); if ($existing) return (array) $existing;
            if ($request->status !== 'REQUESTED') throw new LogicException('Review Request is not awaiting admission.');
            $id = (string) Str::uuid7(); $now = now();
            DB::table('evidence_reviews')->insert(['id' => $id, 'review_request_id' => $requestId, 'evidence_id' => $request->evidence_id,
                'reviewer_id' => $reviewerId, 'status' => 'IN_REVIEW', 'started_at' => $now, 'created_at' => $now, 'updated_at' => $now]);
            DB::table('evidence_review_requests')->where('id', $requestId)->update(['status' => 'ADMITTED', 'admitted_by' => $reviewerId, 'admitted_at' => $now, 'updated_at' => $now]);
            DB::table('governed_evidence')->where('id', $request->evidence_id)->update(['review_status' => 'IN_REVIEW', 'updated_at' => $now]);
            return $this->row('evidence_reviews', $id);
        });
    }

    /** @return array<string,mixed> */
    public function recordFinding(string $reviewId, string $actorId, string $criterion, string $finding, string $statement): array
    {
        if (! in_array($finding, self::FINDINGS, true)) throw new InvalidArgumentException('Invalid Finding.');
        return DB::transaction(function () use ($reviewId, $actorId, $criterion, $finding, $statement): array {
            $review = $this->lock('evidence_reviews', $reviewId);
            if ($review->reviewer_id !== $actorId || $review->status !== 'IN_REVIEW') throw new LogicException('Review is not writable by this reviewer.');
            if (DB::table('evidence_review_findings')->where('review_id', $reviewId)->where('criterion_key', $criterion)->exists()) throw new LogicException('Finding already exists for criterion.');
            $id = (string) Str::uuid7(); $now = now();
            DB::table('evidence_review_findings')->insert(['id' => $id, 'review_id' => $reviewId, 'criterion_key' => mb_substr(trim($criterion), 0, 120),
                'finding' => $finding, 'statement' => mb_substr(trim($statement), 0, 4000), 'recorded_by' => $actorId,
                'recorded_at' => $now, 'created_at' => $now, 'updated_at' => $now]);
            return $this->row('evidence_review_findings', $id);
        });
    }

    /** @return array<string,mixed> */
    public function recordReviewDecision(string $reviewId, string $actorId, string $decision, string $rationale): array
    {
        if (! in_array($decision, self::DECISIONS, true)) throw new InvalidArgumentException('Invalid Review Decision.');
        return DB::transaction(function () use ($reviewId, $actorId, $decision, $rationale): array {
            $review = $this->lock('evidence_reviews', $reviewId);
            if ($review->reviewer_id !== $actorId) throw new LogicException('Reviewer mismatch.');
            $existing = DB::table('evidence_review_decisions')->where('review_id', $reviewId)->first(); if ($existing) return (array) $existing;
            if ($review->status !== 'IN_REVIEW' || ! DB::table('evidence_review_findings')->where('review_id', $reviewId)->exists()) throw new LogicException('Decision requires an open Review with Findings.');
            $id = (string) Str::uuid7(); $now = now();
            DB::table('evidence_review_decisions')->insert(['id' => $id, 'review_id' => $reviewId, 'evidence_id' => $review->evidence_id,
                'decision' => $decision, 'rationale' => mb_substr(trim($rationale), 0, 4000), 'decided_by' => $actorId,
                'decided_at' => $now, 'created_at' => $now, 'updated_at' => $now]);
            DB::table('evidence_reviews')->where('id', $reviewId)->update(['status' => 'COMPLETED', 'completed_at' => $now, 'updated_at' => $now]);
            DB::table('evidence_review_requests')->where('id', $review->review_request_id)->update(['status' => 'COMPLETED', 'completed_at' => $now, 'updated_at' => $now]);
            DB::table('governed_evidence')->where('id', $review->evidence_id)->update(['review_status' => 'REVIEWED', 'effective_review_decision' => $decision, 'updated_at' => $now]);
            return $this->row('evidence_review_decisions', $id);
        });
    }

    /** @param list<string> $supporting @param list<string> $contradicting @return array<string,mixed> */
    public function evaluateMastery(string $subjectId, string $capabilityId, string $evaluatorId, string $policyRevision, string $judgment, string $freshness, array $supporting, array $contradicting, string $rationale): array
    {
        if (! in_array($judgment, self::JUDGMENTS, true) || ! in_array($freshness, self::FRESHNESS, true)) throw new InvalidArgumentException('Invalid Mastery dimensions.');
        if ($judgment === 'MASTERED' && $supporting === []) throw new LogicException('MASTERED requires supporting Evidence.');
        foreach ($supporting as $id) {
            $e = DB::table('governed_evidence')->where('id', $id)->first();
            if (! $e || $e->subject_actor_id !== $subjectId || $e->capability_id !== $capabilityId || $e->review_status !== 'REVIEWED' || ! in_array($e->effective_review_decision, ['ACCEPT', 'ACCEPT_WITH_LIMITATIONS'], true)) throw new LogicException('Supporting Evidence is not eligible.');
        }
        $id = (string) Str::uuid7(); $now = now();
        $body = ['subject_actor_id' => $subjectId, 'target_type' => 'CAPABILITY', 'target_id' => $capabilityId, 'policy_revision_id' => $policyRevision,
            'judgment' => $judgment, 'freshness_status' => $freshness, 'supporting_evidence_ids' => $supporting, 'contradicting_evidence_ids' => $contradicting, 'rationale' => $rationale];
        return DB::transaction(function () use ($id, $now, $body, $evaluatorId): array {
            DB::table('evidence_mastery_evaluations')->insert($body + ['id' => $id, 'supporting_evidence_ids' => $this->json($body['supporting_evidence_ids']),
                'contradicting_evidence_ids' => $this->json($body['contradicting_evidence_ids']), 'content_digest' => $this->digest($body),
                'evaluator_id' => $evaluatorId, 'evaluated_at' => $now, 'created_at' => $now, 'updated_at' => $now]);
            $state = DB::table('evidence_mastery_states')->where('subject_actor_id', $body['subject_actor_id'])->where('target_type', 'CAPABILITY')->where('target_id', $body['target_id'])->lockForUpdate()->first();
            $values = ['judgment' => $body['judgment'], 'freshness_status' => $body['freshness_status'], 'latest_evaluation_id' => $id, 'evaluated_at' => $now, 'updated_at' => $now];
            if ($state) DB::table('evidence_mastery_states')->where('id', $state->id)->update($values);
            else { $state = (object) ['id' => (string) Str::uuid7()]; DB::table('evidence_mastery_states')->insert($values + ['id' => $state->id, 'subject_actor_id' => $body['subject_actor_id'], 'target_type' => 'CAPABILITY', 'target_id' => $body['target_id'], 'created_at' => $now]); }
            return $this->row('evidence_mastery_states', $state->id);
        });
    }

    /** @return array<string,mixed> */
    public function createPortfolio(string $actorId, string $name, ?string $scope, string $grouping, array $filters = [], array $annotations = []): array
    {
        $id = (string) Str::uuid7(); $now = now();
        DB::table('evidence_portfolios')->insert(['id' => $id, 'owner_actor_id' => $actorId, 'name' => mb_substr(trim($name), 0, 180),
            'view_scope' => $scope ? mb_substr(trim($scope), 0, 120) : null, 'grouping' => mb_substr(trim($grouping), 0, 80),
            'filters' => $this->json($filters), 'annotations' => $this->json($annotations), 'created_at' => $now, 'updated_at' => $now]);
        return $this->row('evidence_portfolios', $id, ['filters', 'annotations']);
    }

    public function addEvidenceToPortfolio(string $portfolioId, string $evidenceId, string $actorId, ?string $annotation = null, int $sort = 0): void
    {
        $portfolio = DB::table('evidence_portfolios')->where('id', $portfolioId)->first(); $evidence = DB::table('governed_evidence')->where('id', $evidenceId)->first();
        if (! $portfolio || $portfolio->owner_actor_id !== $actorId || ! $evidence || $evidence->subject_actor_id !== $actorId) throw new LogicException('Portfolio boundary mismatch.');
        $mastery = DB::table('evidence_mastery_states')->where('subject_actor_id', $actorId)->where('target_type', 'CAPABILITY')->where('target_id', $evidence->capability_id)->first();
        $now = now();
        $existing = DB::table('evidence_portfolio_items')->where('portfolio_id', $portfolioId)->where('evidence_id', $evidenceId)->first();
        $values = ['mastery_state_id' => $mastery?->id, 'sort_order' => max(0, $sort), 'annotation' => $annotation, 'updated_at' => $now];
        if ($existing) DB::table('evidence_portfolio_items')->where('id', $existing->id)->update($values);
        else DB::table('evidence_portfolio_items')->insert($values + ['id' => (string) Str::uuid7(), 'portfolio_id' => $portfolioId, 'evidence_id' => $evidenceId, 'created_at' => $now]);
    }

    public function removeEvidenceFromPortfolio(string $portfolioId, string $evidenceId, string $actorId): void
    {
        $portfolio = DB::table('evidence_portfolios')->where('id', $portfolioId)->first();
        if (! $portfolio || $portfolio->owner_actor_id !== $actorId) throw new LogicException('Portfolio boundary mismatch.');
        DB::table('evidence_portfolio_items')->where('portfolio_id', $portfolioId)->where('evidence_id', $evidenceId)->delete();
    }

    /** @return array<string,mixed> */
    public function workspace(string $actorId): array
    {
        $candidates = DB::table('evidence_candidates')->where('subject_actor_id', $actorId)->latest()->get()->map(fn ($r) => $this->array($r, ['proposed_facts', 'metadata']))->all();
        $evidence = DB::table('governed_evidence as e')->join('governed_evidence_revisions as r', fn ($j) => $j->on('r.evidence_id', '=', 'e.id')->on('r.revision', '=', 'e.current_revision_number'))
            ->where('e.subject_actor_id', $actorId)->latest('e.admitted_at')->select('e.*', 'r.id as current_revision_id', 'r.title', 'r.summary', 'r.facts', 'r.source_type', 'r.source_id', 'r.source_revision', 'r.source_digest', 'r.content_digest', 'r.sealed_at')
            ->get()->map(fn ($r) => $this->array($r, ['facts']))->all();
        $ids = array_column($evidence, 'id');
        $requests = $ids ? DB::table('evidence_review_requests')->whereIn('evidence_id', $ids)->latest('requested_at')->get()->map(fn ($r) => (array) $r)->all() : [];
        $reviews = $ids ? DB::table('evidence_reviews')->whereIn('evidence_id', $ids)->latest('started_at')->get()->map(fn ($r) => (array) $r)->all() : [];
        foreach ($reviews as &$review) { $review['findings'] = DB::table('evidence_review_findings')->where('review_id', $review['id'])->orderBy('recorded_at')->get()->map(fn ($r) => (array) $r)->all(); $decision = DB::table('evidence_review_decisions')->where('review_id', $review['id'])->first(); $review['decision'] = $decision ? (array) $decision : null; } unset($review);
        $mastery = DB::table('evidence_mastery_states as s')->join('evidence_mastery_evaluations as v', 'v.id', '=', 's.latest_evaluation_id')->where('s.subject_actor_id', $actorId)
            ->select('s.*', 'v.policy_revision_id', 'v.supporting_evidence_ids', 'v.contradicting_evidence_ids', 'v.rationale', 'v.content_digest')->get()->map(fn ($r) => $this->array($r, ['supporting_evidence_ids', 'contradicting_evidence_ids']))->all();
        $portfolios = DB::table('evidence_portfolios')->where('owner_actor_id', $actorId)->latest('updated_at')->get()->map(fn ($r) => $this->array($r, ['filters', 'annotations']))->all();
        foreach ($portfolios as &$portfolio) { $portfolio['items'] = DB::table('evidence_portfolio_items as i')->join('governed_evidence as e', 'e.id', '=', 'i.evidence_id')->join('governed_evidence_revisions as r', fn ($j) => $j->on('r.evidence_id', '=', 'e.id')->on('r.revision', '=', 'e.current_revision_number'))->where('i.portfolio_id', $portfolio['id'])->orderBy('i.sort_order')->select('i.*', 'e.capability_id', 'e.lifecycle_state', 'e.review_status', 'e.effective_review_decision', 'r.title', 'r.summary', 'r.id as current_revision_id')->get()->map(fn ($r) => (array) $r)->all(); } unset($portfolio);
        return ['summary' => ['candidate_count' => count(array_filter($candidates, fn ($c) => $c['state'] === 'CANDIDATE')), 'evidence_count' => count($evidence), 'review_in_progress_count' => count(array_filter($reviews, fn ($r) => $r['status'] === 'IN_REVIEW')), 'mastery_count' => count($mastery), 'portfolio_count' => count($portfolios)],
            'candidates' => $candidates, 'evidence' => $evidence, 'review_requests' => $requests, 'reviews' => $reviews, 'mastery' => $mastery, 'portfolios' => $portfolios];
    }

    private function lock(string $table, string $id): object { $row = DB::table($table)->where('id', $id)->lockForUpdate()->first(); if (! $row) throw new InvalidArgumentException("{$table} record not found."); return $row; }
    private function own(object $row, string $actorId): void { if (($row->subject_actor_id ?? null) !== $actorId) throw new LogicException('Record is outside actor boundary.'); }
    /** @return array<string,mixed> */ private function row(string $table, string $id, array $json = []): array { $row = DB::table($table)->where('id', $id)->first(); if (! $row) throw new InvalidArgumentException("{$table} record not found."); return $this->array($row, $json); }
    /** @return array<string,mixed> */ private function revision(string $evidenceId, int $revision): array { $row = DB::table('governed_evidence_revisions')->where('evidence_id', $evidenceId)->where('revision', $revision)->first(); if (! $row) throw new LogicException('Revision missing.'); return $this->array($row, ['facts']); }
    /** @return array<string,mixed> */ private function array(object $row, array $json = []): array { $out = (array) $row; foreach ($json as $key) $out[$key] = $this->decode($out[$key] ?? null); return $out; }
    private function decode(mixed $value): array { if (is_array($value)) return $value; $decoded = is_string($value) ? json_decode($value, true, 512, JSON_THROW_ON_ERROR) : []; return is_array($decoded) ? $decoded : []; }
    private function json(array $value): string { return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); }
    private function digest(array $value): string { return hash('sha256', $this->json($value)); }
}
