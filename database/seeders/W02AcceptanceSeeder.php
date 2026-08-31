<?php

namespace Database\Seeders;

use App\Modules\Curriculum\Models\CurriculumPlacement;
use App\Modules\Knowledge\Content\LessonContentContract;
use App\Modules\Knowledge\Models\KnowledgeUnit;
use App\Modules\Knowledge\Models\LessonRevision;
use App\Modules\Learning\Models\MicroPractice;
use App\Modules\Learning\Models\PracticeAttempt;
use App\Modules\SourceGovernance\Models\SourceClaim;
use App\Modules\SourceGovernance\Models\SourceRecord;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Explicit local/test acceptance reset for W02-A.
 *
 * This adapter consumes the Controller-prepared six-KU payload. It is never
 * called by DatabaseSeeder, never imports B09/B10 archives, and refuses to run
 * in production or beside unrelated Knowledge Units.
 */
final class W02AcceptanceSeeder extends Seeder
{
    private const DATASET_SCHEMA = 'cep.w02a.default-seed-dataset.v2';

    private const DATASET_SHA256 = '5289dff9f22fc4ff918e04c5dd90adeea8f0c6e314674a5c2e5acfb84aab752b';

    private const PROFILE = 'ACCEPTANCE_BALANCED_6';

    /** @var list<string> */
    private const KNOWLEDGE_UNIT_IDS = [
        'KU-D03-0001',
        'KU-D03-0004',
        'KU-D03-0011',
        'KU-D05-0021',
        'KU-D05-0023',
        'KU-D09-0002',
    ];

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('W02 acceptance data is restricted to local and testing environments.');
        }

        $datasetPath = $this->datasetPath();
        $expectedHash = self::DATASET_SHA256;
        $testingHash = getenv('CEP_W02_ACCEPTANCE_DATASET_SHA256');
        if (app()->environment('testing') && is_string($testingHash) && $testingHash !== '') {
            $expectedHash = strtolower($testingHash);
        }

        if (! hash_equals($expectedHash, hash_file('sha256', $datasetPath))) {
            throw new RuntimeException('W02 acceptance dataset hash does not match the prepared execution packet.');
        }

        /** @var mixed $decoded */
        $decoded = json_decode((string) file_get_contents($datasetPath), true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($decoded)) {
            throw new RuntimeException('W02 acceptance dataset must be a JSON object.');
        }
        $this->assertDataset($decoded);

        $unexpectedUnits = KnowledgeUnit::query()
            ->whereNotIn('id', self::KNOWLEDGE_UNIT_IDS)
            ->orderBy('id')
            ->pluck('id')
            ->all();
        if ($unexpectedUnits !== []) {
            throw new RuntimeException(
                'W02 acceptance reset requires an isolated database; unrelated Knowledge Units were found: '
                .implode(', ', $unexpectedUnits),
            );
        }

        /** @var list<array<string, mixed>> $records */
        $records = $decoded['records'];

        DB::transaction(function () use ($records): void {
            $this->resetPreparedProfile();

            foreach ($records as $offset => $record) {
                $this->seedRecord($record, $offset);
            }
        });
    }

    /** @param array<string, mixed> $dataset */
    private function assertDataset(array $dataset): void
    {
        if (($dataset['schema'] ?? null) !== self::DATASET_SCHEMA) {
            throw new RuntimeException('Unsupported W02 acceptance dataset schema.');
        }
        if (($dataset['canonical_runtime_import_authorized'] ?? null) !== false) {
            throw new RuntimeException('Canonical runtime import must remain disabled for W02 acceptance data.');
        }
        if (($dataset['legacy_b10_runtime_mapping_authorized'] ?? null) !== false) {
            throw new RuntimeException('Legacy B10 runtime mapping must remain disabled for W02 acceptance data.');
        }
        if (($dataset['selected_ku_ids'] ?? null) !== self::KNOWLEDGE_UNIT_IDS) {
            throw new RuntimeException('W02 acceptance dataset must contain the exact ACCEPTANCE_BALANCED_6 selection.');
        }
        if (! is_array($dataset['records'] ?? null) || count($dataset['records']) !== count(self::KNOWLEDGE_UNIT_IDS)) {
            throw new RuntimeException('W02 acceptance dataset must contain exactly six records.');
        }

        $recordIds = array_map(
            static fn (mixed $record): mixed => is_array($record) ? ($record['ku_id'] ?? null) : null,
            $dataset['records'],
        );
        if ($recordIds !== self::KNOWLEDGE_UNIT_IDS) {
            throw new RuntimeException('W02 acceptance records must preserve the prepared KU order and identities.');
        }
    }

    private function datasetPath(): string
    {
        $override = getenv('CEP_W02_ACCEPTANCE_DATASET');
        $relative = 'authority-cache/CEP/CURRENT/EXECUTION_PREP/W02_A_EXECUTOR_PACKET_483e3a7d/W02_A_DEFAULT_SEED_DATASET.json';
        $candidates = [
            is_string($override) && $override !== '' ? $override : null,
            base_path($relative),
            dirname(base_path(), 2).DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative),
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && is_file($candidate) && is_readable($candidate)) {
                $resolved = realpath($candidate);
                if (is_string($resolved)) {
                    return $resolved;
                }
            }
        }

        throw new RuntimeException(
            'Prepared W02 dataset was not found. Set CEP_W02_ACCEPTANCE_DATASET to the execution-packet JSON path.',
        );
    }

    private function resetPreparedProfile(): void
    {
        $practiceIds = MicroPractice::query()
            ->whereIn('knowledge_unit_id', self::KNOWLEDGE_UNIT_IDS)
            ->pluck('id')
            ->all();
        if ($practiceIds !== []) {
            PracticeAttempt::query()->whereIn('micro_practice_id', $practiceIds)->delete();
        }

        MicroPractice::query()->whereIn('knowledge_unit_id', self::KNOWLEDGE_UNIT_IDS)->delete();
        CurriculumPlacement::query()->whereIn('knowledge_unit_id', self::KNOWLEDGE_UNIT_IDS)->delete();
        LessonRevision::query()->whereIn('knowledge_unit_id', self::KNOWLEDGE_UNIT_IDS)->delete();
        KnowledgeUnit::query()->whereIn('id', self::KNOWLEDGE_UNIT_IDS)->delete();
        SourceRecord::query()->where('relative_path', 'like', 'w02-acceptance/%')->delete();
    }

    /** @param array<string, mixed> $record */
    private function seedRecord(array $record, int $offset): void
    {
        $unitId = $this->requiredString($record, 'ku_id');
        $title = $this->repairMojibake($this->requiredString($record, 'canonical_title'));
        $claims = is_array($record['claims'] ?? null) ? $record['claims'] : [];
        $citationIds = [];

        foreach ($claims as $claim) {
            if (! is_array($claim)) {
                continue;
            }
            $claimId = $this->requiredString($claim, 'claim_id');
            $citationIds[] = $claimId;
            $this->seedClaim($claim);
        }
        if ($citationIds === []) {
            throw new RuntimeException("Prepared W02 record {$unitId} has no claim references.");
        }

        KnowledgeUnit::query()->create([
            'id' => $unitId,
            // The prepared payload supplies one approved canonical title. Do
            // not invent or silently translate a second canonical title.
            'title_ar' => $title,
            'title_en' => $title,
        ]);

        $contract = app(LessonContentContract::class);
        $knowledgeBody = $contract->validateAndNormalize(
            $this->markdownBlocks($this->repairMojibake($this->requiredString($record, 'b09_knowledge_body_markdown'))),
            $citationIds,
        );
        $lessonBody = $contract->validateAndNormalize(
            $this->markdownBlocks(
                $this->learnerMarkdown(
                    $this->repairMojibake($this->requiredString($record, 'b10_lesson_markdown')),
                    $unitId,
                ),
            ),
            $citationIds,
        );

        $publishedAt = CarbonImmutable::parse('2026-08-30T12:00:00Z')->addMinutes($offset * 10);
        $sourceRevision = LessonRevision::query()->create([
            'knowledge_unit_id' => $unitId,
            'revision' => 1,
            'state' => 'published',
            'lock_version' => 2,
            'blocks' => $knowledgeBody['blocks'],
            'citations' => $knowledgeBody['citations'],
            'authority_baseline_id' => self::PROFILE,
            'content_digest' => $contract->contentDigest($knowledgeBody['blocks'], $knowledgeBody['citations']),
            'review_decision' => 'APPROVED',
            'published_at' => $publishedAt,
        ]);
        $learnerRevision = LessonRevision::query()->create([
            'knowledge_unit_id' => $unitId,
            'revision' => 2,
            'state' => 'published',
            'lock_version' => 3,
            'blocks' => $lessonBody['blocks'],
            'citations' => $lessonBody['citations'],
            'authority_baseline_id' => self::PROFILE,
            'content_digest' => $contract->contentDigest($lessonBody['blocks'], $lessonBody['citations']),
            'review_decision' => 'APPROVED',
            'published_at' => $publishedAt->addMinute(),
            'derived_from_revision_id' => $sourceRevision->id,
        ]);
        LessonRevision::query()->create([
            'knowledge_unit_id' => $unitId,
            'revision' => 3,
            'state' => 'draft',
            'lock_version' => 1,
            'blocks' => $lessonBody['blocks'],
            'citations' => $lessonBody['citations'],
            'authority_baseline_id' => self::PROFILE,
            'content_digest' => $contract->contentDigest($lessonBody['blocks'], $lessonBody['citations']),
            'derived_from_revision_id' => $learnerRevision->id,
        ]);

        $capabilityId = $this->claimCapabilityId($claims, $record);
        CurriculumPlacement::query()->create([
            'capability_id' => $capabilityId,
            'knowledge_unit_id' => $unitId,
            'revision' => 1,
            'lifecycle' => [
                'fixture_profile' => self::PROFILE,
                'fixture_classification' => $record['fixture_classification'] ?? null,
                'domain_id' => $record['domain_id'] ?? null,
                'cluster_id' => $record['cluster_id'] ?? null,
                'capability_id' => $capabilityId,
                'knowledge_type' => $record['knowledge_type'] ?? null,
                'competency_level' => $record['competency_level'] ?? null,
                'pathway' => [
                    'id' => $record['pathway_id'] ?? null,
                    'title' => $this->repairLearnerPayload($record['pathway_title'] ?? null),
                ],
                'prerequisite_ku_ids' => array_values(array_filter(
                    is_array($record['prerequisite_ku_ids'] ?? null) ? $record['prerequisite_ku_ids'] : [],
                    'is_string',
                )),
                'objectives' => is_array($record['objectives'] ?? null)
                    ? $this->repairLearnerPayload(array_values($record['objectives']))
                    : [],
                'assessment_blueprints' => is_array($record['assessment_blueprints'] ?? null)
                    ? $this->repairLearnerPayload(array_values($record['assessment_blueprints']))
                    : [],
                'lab_blueprints' => is_array($record['lab_blueprints'] ?? null)
                    ? $this->repairLearnerPayload(array_values($record['lab_blueprints']))
                    : [],
                'limitation_count' => count(is_array($record['b09_limitations'] ?? null) ? $record['b09_limitations'] : []),
                'current_runtime_mapping_authorized' => false,
            ],
        ]);
    }

    /** @param array<string, mixed> $claim */
    private function seedClaim(array $claim): void
    {
        $claimId = $this->requiredString($claim, 'claim_id');
        $metadata = [
            'fixture_profile' => self::PROFILE,
            'source_ref' => $claim['source_ref'] ?? null,
            'source_artifact' => $claim['source_artifact'] ?? null,
            'exact_locator' => $claim['exact_locator'] ?? null,
            'authority_version_freshness' => $claim['authority_version_freshness'] ?? null,
            'support_state' => $claim['support_state'] ?? null,
        ];
        $source = SourceRecord::query()->create([
            // The packet's lineage vocabulary is fixture metadata, not the
            // persisted source-governance enum. Keep it in metadata and map
            // the prepared, reviewed support into the current runtime schema.
            'authority_class' => 'Internal Reviewed Support',
            'title' => mb_substr(
                $this->repairMojibake((string) ($claim['claim_text'] ?? $claimId)),
                0,
                220,
            ),
            'exact_url' => null,
            'relative_path' => 'w02-acceptance/'.$claimId,
            'sha256' => hash('sha256', json_encode($metadata, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)),
            'review_status' => 'approved',
            'metadata' => $metadata,
        ]);
        SourceClaim::query()->create([
            'source_record_id' => $source->id,
            'claim_id' => $claimId,
            'segment_ref' => mb_substr((string) ($claim['exact_locator'] ?? $claimId), 0, 300),
            'supported_scope' => $this->repairMojibake((string) ($claim['claim_text'] ?? '')),
            'excluded_semantics' => 'Prepared acceptance fixture; no production effectiveness or governed Evidence decision is asserted.',
            'assessment' => $this->sourceAssessment((string) ($claim['support_state'] ?? '')),
        ]);
    }

    private function sourceAssessment(string $supportState): string
    {
        $normalized = strtoupper($supportState);

        return match (true) {
            str_contains($normalized, 'EXCLUDED') => 'excluded',
            str_contains($normalized, 'UNRESOLVED') => 'unresolved',
            str_contains($normalized, 'LIMITATION'), str_contains($normalized, 'PARTIAL') => 'partial',
            default => 'supported',
        };
    }

    /**
     * Convert a Markdown document into bounded, logically ordered blocks while
     * preserving the prepared text. Heading depth drives block nesting; prose
     * remains continuous instead of becoming a form/card per paragraph.
     *
     * @return list<array{type: string, body: string, depth: int}>
     */
    private function markdownBlocks(string $markdown): array
    {
        $lines = preg_split('/\R/u', trim($markdown));
        if (! is_array($lines) || $lines === []) {
            throw new RuntimeException('Prepared W02 Markdown body is empty.');
        }

        $blocks = [];
        $buffer = [];
        $sectionDepth = 0;
        $baseHeadingLevel = null;
        $fenceLanguage = null;
        $fenceLines = [];
        $flush = function () use (&$buffer, &$blocks, &$sectionDepth): void {
            $body = trim(implode("\n", $buffer));
            $buffer = [];
            if ($body === '') {
                return;
            }

            foreach ($this->boundedChunks($body, LessonContentContract::MAX_BLOCK_BODY_LENGTH) as $chunk) {
                $blocks[] = ['type' => 'paragraph', 'body' => $chunk, 'depth' => min(3, $sectionDepth + 1)];
            }
        };

        foreach ($lines as $line) {
            if (preg_match('/^```\s*([a-z0-9_-]*)\s*$/iu', trim($line), $fence) === 1) {
                if ($fenceLanguage === null) {
                    $flush();
                    $fenceLanguage = strtolower($fence[1]);
                    $fenceLines = [];
                } else {
                    $body = trim(implode("\n", $fenceLines));
                    if ($body !== '') {
                        foreach ($this->boundedChunks($body, LessonContentContract::MAX_BLOCK_BODY_LENGTH) as $chunk) {
                            $blocks[] = [
                                'type' => $this->technicalBlockType($fenceLanguage, $chunk),
                                'body' => $chunk,
                                'depth' => min(3, $sectionDepth + 1),
                            ];
                        }
                    }
                    $fenceLanguage = null;
                    $fenceLines = [];
                }

                continue;
            }

            if ($fenceLanguage !== null) {
                $fenceLines[] = $line;

                continue;
            }

            if (preg_match('/^(#{1,6})\s+(.+)$/u', $line, $matches) === 1) {
                $flush();
                $headingLevel = strlen($matches[1]);
                $baseHeadingLevel ??= $headingLevel;
                $desiredDepth = min(3, max(0, $headingLevel - $baseHeadingLevel));
                if ($blocks !== []) {
                    $previousDepth = (int) $blocks[array_key_last($blocks)]['depth'];
                    $desiredDepth = min($desiredDepth, $previousDepth + 1);
                }
                $sectionDepth = $desiredDepth;
                $blocks[] = ['type' => 'heading', 'body' => trim($matches[2]), 'depth' => $sectionDepth];

                continue;
            }

            if (preg_match('/^\s*\|?\s*:?-{3,}:?\s*(?:\|\s*:?-{3,}:?\s*)+\|?\s*$/u', $line) === 1) {
                continue;
            }
            if (preg_match('/^\s*\|(.+)\|\s*$/u', $line, $tableRow) === 1) {
                $cells = array_values(array_filter(
                    array_map('trim', explode('|', $tableRow[1])),
                    static fn (string $cell): bool => $cell !== '',
                ));
                if ($cells !== []) {
                    $buffer[] = '• '.implode(' — ', $cells);
                }

                continue;
            }
            if (preg_match('/^\s*[-*]\s+(.+)$/u', $line, $listItem) === 1) {
                $buffer[] = '• '.trim($listItem[1]);

                continue;
            }

            $buffer[] = $line;
        }
        if ($fenceLanguage !== null) {
            throw new RuntimeException('Prepared W02 Markdown contains an unterminated technical block.');
        }
        $flush();

        if (count($blocks) > LessonContentContract::MAX_BLOCKS) {
            throw new RuntimeException('Prepared W02 Markdown exceeds the bounded lesson block contract.');
        }

        return $blocks;
    }

    /** @return list<string> */
    private function boundedChunks(string $body, int $limit): array
    {
        $chunks = [];
        $remaining = $body;
        while (mb_strlen($remaining) > $limit) {
            $window = mb_substr($remaining, 0, $limit + 1);
            $breakAt = max((int) mb_strrpos($window, "\n\n"), (int) mb_strrpos($window, "\n"));
            if ($breakAt < (int) floor($limit * 0.55)) {
                $breakAt = $limit;
            }
            $chunks[] = trim(mb_substr($remaining, 0, $breakAt));
            $remaining = ltrim(mb_substr($remaining, $breakAt));
        }
        if ($remaining !== '') {
            $chunks[] = trim($remaining);
        }

        return array_values(array_filter($chunks, static fn (string $chunk): bool => $chunk !== ''));
    }

    private function technicalBlockType(string $language, string $body): string
    {
        $normalized = strtolower($language);
        if (in_array($normalized, ['log', 'logs'], true)) {
            return 'log';
        }
        if (in_array($normalized, ['request', 'http-request'], true)
            || preg_match('/^(?:GET|POST|PUT|PATCH|DELETE|HEAD|OPTIONS)\s+\S+/i', $body) === 1) {
            return 'request';
        }
        if (in_array($normalized, ['response', 'http-response'], true)
            || preg_match('/^HTTP\/\d(?:\.\d)?\s+\d{3}/i', $body) === 1) {
            return 'response';
        }

        return 'code';
    }

    private function learnerMarkdown(string $markdown, string $unitId): string
    {
        $lines = preg_split('/\R/u', trim($markdown));
        if (! is_array($lines)) {
            return $markdown;
        }

        $included = false;
        $result = [];
        foreach ($lines as $line) {
            if (preg_match('/^(#{1,2})\s+(.+)$/u', $line, $heading) === 1) {
                if (strlen($heading[1]) === 1) {
                    $included = false;

                    continue;
                }
                $included = $this->isLearnerSection($unitId, trim($heading[2]));
                if ($included) {
                    $line = '## '.preg_replace('/^\d+\.\s*/u', '', trim($heading[2]));
                }
            }

            if ($included) {
                $cleanLine = $this->stripLearnerGovernance($line);
                if ($cleanLine !== '') {
                    $result[] = $cleanLine;
                }
            }
        }

        $filtered = trim(implode("\n", $result));
        if ($filtered === '') {
            throw new RuntimeException("Prepared W02 learner body {$unitId} has no delivery sections.");
        }

        return $filtered;
    }

    private function isLearnerSection(string $unitId, string $heading): bool
    {
        if (in_array($unitId, ['KU-D05-0021', 'KU-D05-0023'], true)) {
            return preg_match('/^(?:1|2|5|7|8|9|10|11|12|16)\./u', $heading) === 1;
        }
        if ($unitId === 'KU-D09-0002') {
            return preg_match('/^(?:1|4|5|11)\./u', $heading) === 1;
        }

        return preg_match(
            '/(?:هوية الوحدة|أهداف التعلم|المتطلبات السابقة|المطالبات.*الأدلة|سياق التشغيل|القيود الحالية|تقييم التغطية|ملخص قابل للحفظ|علاقات الوحدة|تتبع المنتج|Authority, version|Conflicts and variants|B09 limitations|M5 carried-forward|Review triggers)/iu',
            $heading,
        ) !== 1;
    }

    private function stripLearnerGovernance(string $line): string
    {
        if (preg_match('/^#{1,6}\s/u', $line) === 1) {
            return $line;
        }

        if (preg_match('/^\s*\d+\.\s*/u', $line) === 1) {
            $items = preg_split('/\s+(?=\d+\.\s)/u', trim($line));
            if (is_array($items)) {
                $cleanItems = [];
                foreach ($items as $item) {
                    $body = preg_replace('/^\d+\.\s*/u', '', $item);
                    if (is_string($body) && $body !== '' && ! $this->containsGovernanceToken($body)) {
                        $cleanItems[] = '• '.$body;
                    }
                }

                return implode("\n", $cleanItems);
            }
        }

        $sentences = preg_split('/(?<=[.!؟])\s+/u', trim($line));
        if (! is_array($sentences)) {
            return $line;
        }

        $clean = array_filter(
            $sentences,
            fn (string $sentence): bool => ! $this->containsGovernanceToken($sentence),
        );

        return trim(str_ireplace(
            ['الـfixture', 'fixture', 'Runtime'],
            ['بيانات الاختبار', 'بيانات اختبار', 'التشغيل الفعلي'],
            implode(' ', $clean),
        ));
    }

    private function containsGovernanceToken(string $value): bool
    {
        return preg_match(
            '/(?:\bB0?9\b|\bB10\b|\bM5\b|MISSION-005|FINAL_GOVERNED|review\s+trigger|authority\s+baseline)/iu',
            $value,
        ) === 1;
    }

    private function repairPayload(mixed $value): mixed
    {
        if (is_string($value)) {
            return $this->repairMojibake($value);
        }
        if (! is_array($value)) {
            return $value;
        }

        foreach ($value as $key => $item) {
            $value[$key] = $this->repairPayload($item);
        }

        return $value;
    }

    private function repairLearnerPayload(mixed $value): mixed
    {
        $value = $this->repairPayload($value);
        if (is_string($value)) {
            return str_ireplace(
                ['B09 claim trace', 'review-trigger statement', 'MISSION-005', 'fixture'],
                [
                    'canonical claim trace',
                    'residual-uncertainty statement',
                    'prepared learning objectives',
                    'test dataset',
                ],
                $value,
            );
        }
        if (! is_array($value)) {
            return $value;
        }

        foreach ($value as $key => $item) {
            $value[$key] = $this->repairLearnerPayload($item);
        }

        return $value;
    }

    private function repairMojibake(string $value): string
    {
        if (! str_contains($value, 'Ø') && ! str_contains($value, 'Ù') && ! str_contains($value, 'â')) {
            return $value;
        }

        $candidate = mb_convert_encoding($value, 'Windows-1252', 'UTF-8');

        return mb_check_encoding($candidate, 'UTF-8') ? $candidate : $value;
    }

    /** @param array<string, mixed> $record */
    private function requiredString(array $record, string $key): string
    {
        $value = $record[$key] ?? null;
        if (! is_string($value) || trim($value) === '') {
            throw new RuntimeException("Prepared W02 record is missing required string {$key}.");
        }

        return trim($value);
    }

    /**
     * @param  list<mixed>  $claims
     * @param  array<string, mixed>  $record
     */
    private function claimCapabilityId(array $claims, array $record): string
    {
        foreach ($claims as $claim) {
            if (is_array($claim) && is_string($claim['capability_id'] ?? null) && $claim['capability_id'] !== '') {
                return $claim['capability_id'];
            }
        }

        return $this->requiredString($record, 'pathway_id');
    }
}
