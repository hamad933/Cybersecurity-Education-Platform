<?php

namespace Tests\Feature\KnowledgeLearning\Library;

use App\Modules\Knowledge\Application\Library\LibraryCapabilityManifest;
use App\Modules\Knowledge\Application\Library\LibraryHierarchyProjector;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class LibraryHierarchyProjectorTest extends TestCase
{
    #[Test]
    public function it_projects_domain_cluster_capability_and_canonical_knowledge_unit_references(): void
    {
        $projection = (new LibraryHierarchyProjector)->project(
            [
                [
                    'id' => 'KU-AUTH-001',
                    'title_ar' => 'المصادقة',
                    'title_en' => 'Authentication',
                    'latest_revision' => 4,
                    'latest_state' => 'published',
                ],
                [
                    'id' => 'KU-CRYPTO-001',
                    'title_ar' => 'التشفير',
                    'title_en' => 'Cryptography',
                    'latest_revision' => 2,
                    'latest_state' => 'draft',
                ],
            ],
            [
                [
                    'id' => 'PLACEMENT-OLD',
                    'capability_id' => 'CAP-IAM-001',
                    'knowledge_unit_id' => 'KU-AUTH-001',
                    'revision' => 1,
                    'lifecycle' => ['state' => 'active'],
                ],
                [
                    'id' => 'PLACEMENT-NEW',
                    'capability_id' => 'CAP-IAM-001',
                    'knowledge_unit_id' => 'KU-AUTH-001',
                    'revision' => 3,
                    'lifecycle' => ['state' => 'active'],
                ],
                [
                    'id' => 'PLACEMENT-SECOND',
                    'capability_id' => 'CAP-SEC-001',
                    'knowledge_unit_id' => 'KU-AUTH-001',
                    'revision' => 1,
                    'lifecycle' => ['state' => 'active'],
                ],
                [
                    'id' => 'PLACEMENT-MISSING-CONTEXT',
                    'capability_id' => 'CAP-UNRESOLVED',
                    'knowledge_unit_id' => 'KU-CRYPTO-001',
                    'revision' => 1,
                    'lifecycle' => ['state' => 'active'],
                ],
            ],
            [
                [
                    'domain_id' => 'DOM-SEC',
                    'domain_title_ar' => 'الأمن السيبراني',
                    'domain_title_en' => 'Cybersecurity',
                    'capability_cluster_id' => 'CL-IAM',
                    'capability_cluster_title_ar' => 'الهوية والوصول',
                    'capability_cluster_title_en' => 'Identity and Access',
                    'capability_id' => 'CAP-IAM-001',
                    'capability_title_ar' => 'المصادقة',
                    'capability_title_en' => 'Authentication',
                ],
                [
                    'domain_id' => 'DOM-SEC',
                    'domain_title_ar' => 'الأمن السيبراني',
                    'domain_title_en' => 'Cybersecurity',
                    'capability_cluster_id' => 'CL-IAM',
                    'capability_cluster_title_ar' => 'الهوية والوصول',
                    'capability_cluster_title_en' => 'Identity and Access',
                    'capability_id' => 'CAP-SEC-001',
                    'capability_title_ar' => 'ضوابط الوصول',
                    'capability_title_en' => 'Access Controls',
                ],
            ],
        );

        $this->assertCount(1, $projection['domains']);
        $this->assertSame('DOM-SEC', $projection['domains'][0]['id']);
        $this->assertSame('CL-IAM', $projection['domains'][0]['clusters'][0]['id']);
        $this->assertCount(2, $projection['domains'][0]['clusters'][0]['capabilities']);

        $firstCapabilityItem = $projection['domains'][0]['clusters'][0]['capabilities'][0]['items'][0];
        $secondCapabilityItem = $projection['domains'][0]['clusters'][0]['capabilities'][1]['items'][0];

        $this->assertSame('KU-AUTH-001', $firstCapabilityItem['canonical_ref']['id']);
        $this->assertSame('KU-AUTH-001', $secondCapabilityItem['canonical_ref']['id']);
        $this->assertSame('curriculum_placement', $firstCapabilityItem['projection_reason']);
        $this->assertSame(3, $firstCapabilityItem['placement']['revision']);
        $this->assertSame('PLACEMENT-NEW', $firstCapabilityItem['placement']['id']);

        $this->assertCount(1, $projection['unresolved_capabilities']);
        $this->assertSame('CAP-UNRESOLVED', $projection['unresolved_capabilities'][0]['capability_id']);
        $this->assertSame(
            'KU-CRYPTO-001',
            $projection['unresolved_capabilities'][0]['items'][0]['canonical_ref']['id'],
        );
        $this->assertSame([], $projection['unplaced']);
    }

    #[Test]
    public function it_keeps_unplaced_units_truthful_instead_of_inventing_hierarchy(): void
    {
        $projection = (new LibraryHierarchyProjector)->project(
            [[
                'id' => 'KU-FREE-001',
                'title_ar' => 'معرفة حرة',
                'title_en' => 'Free Knowledge',
                'latest_revision' => null,
                'latest_state' => null,
            ]],
            [],
            [],
        );

        $this->assertSame([], $projection['domains']);
        $this->assertSame([], $projection['unresolved_capabilities']);
        $this->assertSame('KU-FREE-001', $projection['unplaced'][0]['canonical_ref']['id']);
        $this->assertSame('unplaced_canonical_object', $projection['unplaced'][0]['projection_reason']);
        $this->assertNull($projection['unplaced'][0]['placement']);
    }

    #[Test]
    public function capability_manifest_names_current_truth_and_parent_integration_gaps(): void
    {
        $manifest = (new LibraryCapabilityManifest)->current();

        $this->assertSame('persisted', $manifest['canonical_store']['knowledge_unit']);
        $this->assertSame(
            ['domain', 'capability_cluster'],
            $manifest['hierarchy']['requires_parent_context'],
        );
        $this->assertSame('restore_as_new_revision', $manifest['unified_editor']['restore_policy']);
        $this->assertSame(
            ['note', 'free_knowledge', 'research_document', 'project_context'],
            $manifest['canonical_object_families_requiring_schema_or_parent_integration'],
        );
        $this->assertSame(
            'reference_canonical_objects_without_silent_copy',
            $manifest['projection_policy'],
        );
    }
}
