<?php

namespace Database\Seeders;

use App\Modules\Curriculum\Models\CurriculumPlacement;
use App\Modules\Knowledge\Content\LessonContentContract;
use App\Modules\Knowledge\Models\KnowledgeUnit;
use App\Modules\Knowledge\Models\LessonRevision;
use App\Modules\SourceGovernance\Models\SourceClaim;
use App\Modules\SourceGovernance\Models\SourceRecord;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Ephemeral fixture used only by the W02 Library visual-evidence workflow.
 *
 * It mirrors the persisted model patterns already exercised by the W02 feature
 * tests. It is intentionally absent from DatabaseSeeder and requires an
 * explicit evidence-only environment flag.
 */
final class W02LibraryVisualEvidenceSeeder extends Seeder
{
    private const UNIT_ID = 'KU-W02-VISUAL';

    public function run(): void
    {
        if (getenv('CEP_W02_LIBRARY_VISUAL_EVIDENCE') !== '1') {
            throw new RuntimeException('W02 Library visual fixture requires CEP_W02_LIBRARY_VISUAL_EVIDENCE=1.');
        }

        DB::transaction(function (): void {
            CurriculumPlacement::query()->where('knowledge_unit_id', self::UNIT_ID)->delete();
            LessonRevision::query()->where('knowledge_unit_id', self::UNIT_ID)->delete();
            KnowledgeUnit::query()->where('id', self::UNIT_ID)->delete();

            $visualSources = SourceRecord::query()
                ->where('relative_path', 'like', 'w02-visual-evidence/%')
                ->pluck('id')
                ->all();
            if ($visualSources !== []) {
                SourceClaim::query()->whereIn('source_record_id', $visualSources)->delete();
                SourceRecord::query()->whereIn('id', $visualSources)->delete();
            }

            KnowledgeUnit::query()->create([
                'id' => self::UNIT_ID,
                'title_ar' => 'إدارة الهوية والوصول — دورة حياة الحسابات',
                'title_en' => 'Identity and Access Management — Account Lifecycle',
            ]);

            CurriculumPlacement::query()->create([
                'capability_id' => 'CAP-W02-IAM-01',
                'knowledge_unit_id' => self::UNIT_ID,
                'revision' => 1,
                'lifecycle' => [
                    'state' => 'active',
                    'domain_id' => 'D05',
                    'cluster_id' => 'D05-IAM',
                    'prerequisite_ku_ids' => [],
                    'objectives' => [
                        [
                            'objective_id' => 'OBJ-W02-IAM-01',
                            'objective_text' => 'تطبيق دورة حياة حسابات محكومة مع تحقق تقني قابل للتتبع.',
                            'competency_level' => 'apply',
                        ],
                    ],
                ],
            ]);

            $windows = SourceRecord::query()->create([
                'authority_class' => 'Technical Authority',
                'title' => 'Windows Identity Technical Authority',
                'exact_url' => 'https://learn.microsoft.com/',
                'relative_path' => 'w02-visual-evidence/windows-identity.md',
                'sha256' => str_repeat('a', 64),
                'review_status' => 'reviewed',
                'metadata' => ['fixture' => 'w02-library-visual-evidence'],
            ]);
            SourceClaim::query()->create([
                'source_record_id' => $windows->id,
                'claim_id' => 'WIN-AUTH-901',
                'segment_ref' => 'account-lifecycle',
                'supported_scope' => 'Account lifecycle controls and verification context.',
                'excluded_semantics' => 'Does not establish Evidence Review or Mastery.',
                'assessment' => 'supported',
            ]);

            $web = SourceRecord::query()->create([
                'authority_class' => 'Standards Reference',
                'title' => 'HTTPS Standards Reference',
                'exact_url' => 'https://example.test/reference',
                'relative_path' => 'w02-visual-evidence/standards-reference.md',
                'sha256' => str_repeat('b', 64),
                'review_status' => 'reviewed',
                'metadata' => ['fixture' => 'w02-library-visual-evidence'],
            ]);
            SourceClaim::query()->create([
                'source_record_id' => $web->id,
                'claim_id' => 'WEB-AUTH-002',
                'segment_ref' => 'verification',
                'supported_scope' => 'HTTPS verification and reference context.',
                'excluded_semantics' => 'No persistent Research & Quality decision semantics.',
                'assessment' => 'supported',
            ]);

            $contract = app(LessonContentContract::class);
            $citations = ['WIN-AUTH-901', 'WEB-AUTH-002'];

            $revisionOne = $contract->validateAndNormalize([
                ['id' => 'AAAAAAAAAAAAAAAAAAAAAAAA', 'type' => 'heading', 'body' => 'إدارة الهوية والوصول', 'depth' => 0],
                ['id' => 'BBBBBBBBBBBBBBBBBBBBBBBB', 'type' => 'paragraph', 'body' => 'تبدأ دورة حياة الحساب من طلب محكوم، ثم إنشاء الهوية، ثم التحقق الدوري من الصلاحيات.', 'depth' => 1],
                ['id' => 'CCCCCCCCCCCCCCCCCCCCCCCC', 'type' => 'callout', 'body' => 'لا تُعامل حالة Completion على أنها Mastery؛ لكل منهما مالك دلالي مستقل.', 'depth' => 1],
            ], $citations);

            LessonRevision::query()->create([
                'knowledge_unit_id' => self::UNIT_ID,
                'revision' => 1,
                'state' => 'published',
                'lock_version' => 2,
                'blocks' => $revisionOne['blocks'],
                'citations' => $revisionOne['citations'],
                'authority_baseline_id' => 'W02_VISUAL_EVIDENCE',
                'content_digest' => $contract->contentDigest($revisionOne['blocks'], $revisionOne['citations']),
                'review_decision' => 'APPROVED',
                'published_at' => now()->subDays(2),
            ]);

            $revisionTwo = $contract->validateAndNormalize([
                ['id' => 'AAAAAAAAAAAAAAAAAAAAAAAA', 'type' => 'heading', 'body' => 'إدارة الهوية والوصول', 'depth' => 0],
                ['id' => 'BBBBBBBBBBBBBBBBBBBBBBBB', 'type' => 'paragraph', 'body' => 'تبدأ دورة حياة الحساب من طلب محكوم، ثم إنشاء الهوية، ثم التحقق الدوري من الصلاحيات وفق مبدأ Least Privilege.', 'depth' => 1],
                ['id' => 'CCCCCCCCCCCCCCCCCCCCCCCC', 'type' => 'callout', 'body' => 'تظل سلطة القرار منفصلة عن Evidence Review وMastery، ولا تُستنتج من واجهة المحرر.', 'depth' => 1],
                ['id' => 'DDDDDDDDDDDDDDDDDDDDDDDD', 'type' => 'heading', 'body' => 'التحقق التشغيلي', 'depth' => 0],
                ['id' => 'EEEEEEEEEEEEEEEEEEEEEEEE', 'type' => 'paragraph', 'body' => 'نفّذ فحصًا قابلاً لإعادة الإنتاج، ثم اربط النتيجة بالمرجع المحكوم دون نسخ حقيقة المصدر إلى المسودة.', 'depth' => 1],
                ['id' => 'FFFFFFFFFFFFFFFFFFFFFFFF', 'type' => 'code', 'body' => "curl -I https://example.test/reference\n# verify: HTTP status, CSP, and referrer policy", 'depth' => 1],
            ], $citations);

            LessonRevision::query()->create([
                'knowledge_unit_id' => self::UNIT_ID,
                'revision' => 2,
                'state' => 'published',
                'lock_version' => 3,
                'blocks' => $revisionTwo['blocks'],
                'citations' => $revisionTwo['citations'],
                'authority_baseline_id' => 'W02_VISUAL_EVIDENCE',
                'content_digest' => $contract->contentDigest($revisionTwo['blocks'], $revisionTwo['citations']),
                'review_decision' => 'APPROVED',
                'published_at' => now()->subDay(),
            ]);

            $draft = $contract->validateAndNormalize([
                ['id' => 'AAAAAAAAAAAAAAAAAAAAAAAA', 'type' => 'heading', 'body' => 'إدارة الهوية والوصول', 'depth' => 0],
                ['id' => 'BBBBBBBBBBBBBBBBBBBBBBBB', 'type' => 'paragraph', 'body' => 'تبدأ دورة حياة الحساب من طلب محكوم، ثم إنشاء الهوية، ثم التحقق الدوري من الصلاحيات وفق مبدأ Least Privilege.', 'depth' => 1],
                ['id' => 'CCCCCCCCCCCCCCCCCCCCCCCC', 'type' => 'callout', 'body' => 'تظل سلطة القرار منفصلة عن Evidence Review وMastery، ولا تُستنتج من واجهة المحرر.', 'depth' => 1],
                ['id' => 'DDDDDDDDDDDDDDDDDDDDDDDD', 'type' => 'heading', 'body' => 'التحقق التشغيلي', 'depth' => 0],
                ['id' => 'EEEEEEEEEEEEEEEEEEEEEEEE', 'type' => 'paragraph', 'body' => 'نفّذ فحصًا قابلاً لإعادة الإنتاج، ثم اربط النتيجة بالمرجع المحكوم دون نسخ حقيقة المصدر إلى المسودة.', 'depth' => 1],
                ['id' => 'FFFFFFFFFFFFFFFFFFFFFFFF', 'type' => 'code', 'body' => "curl -I https://example.test/reference\n# verify: HTTP status, CSP, and referrer policy", 'depth' => 1],
                ['id' => 'GGGGGGGGGGGGGGGGGGGGGGGG', 'type' => 'heading', 'body' => 'إغلاق دورة الحياة', 'depth' => 0],
                ['id' => 'HHHHHHHHHHHHHHHHHHHHHHHH', 'type' => 'rules', 'body' => 'تعطيل الحساب فور انتهاء الحاجة، سحب الجلسات النشطة، وتسجيل سبب الإغلاق في سجل قابل للمراجعة.', 'depth' => 1],
                ['id' => 'IIIIIIIIIIIIIIIIIIIIIIII', 'type' => 'paragraph', 'body' => 'يبقى السجل التاريخي immutable؛ أي استعادة تُنشئ Draft جديدًا بدل تعديل Published Revision في مكانه.', 'depth' => 1],
                ['id' => 'JJJJJJJJJJJJJJJJJJJJJJJJ', 'type' => 'log', 'body' => "2026-09-01T12:00:00Z account=svc-demo action=disabled result=PASS\n2026-09-01T12:00:02Z sessions=revoke-all result=PASS", 'depth' => 1],
            ], $citations);

            LessonRevision::query()->create([
                'knowledge_unit_id' => self::UNIT_ID,
                'revision' => 3,
                'state' => 'draft',
                'lock_version' => 1,
                'blocks' => $draft['blocks'],
                'citations' => $draft['citations'],
                'authority_baseline_id' => 'W02_VISUAL_EVIDENCE',
                'content_digest' => $contract->contentDigest($draft['blocks'], $draft['citations']),
                'derived_from_revision_id' => LessonRevision::query()
                    ->where('knowledge_unit_id', self::UNIT_ID)
                    ->where('revision', 2)
                    ->value('id'),
            ]);
        });
    }
}
