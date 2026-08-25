<?php

namespace App\Modules\Knowledge\Application\Library;

final class LibraryCapabilityManifest
{
    /** @return array<string, mixed> */
    public function current(): array
    {
        return [
            'canonical_store' => [
                'knowledge_unit' => 'persisted',
                'lesson_revision' => 'persisted_revisioned_content',
            ],
            'hierarchy' => [
                'available' => ['capability', 'knowledge_unit'],
                'requires_parent_context' => ['domain', 'capability_cluster'],
            ],
            'unified_editor' => [
                'block_oriented' => true,
                'mixed_direction' => true,
                'undo_redo' => true,
                'explicit_save_apply' => true,
                'draft_recovery' => true,
                'revision_history' => true,
                'comparison' => true,
                'restore_policy' => 'restore_as_new_revision',
            ],
            'canonical_object_families_requiring_schema_or_parent_integration' => [
                'note',
                'free_knowledge',
                'research_document',
                'project_context',
            ],
            'projection_policy' => 'reference_canonical_objects_without_silent_copy',
        ];
    }
}
