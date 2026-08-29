<?php

namespace App\Modules\Enterprise\Application;

interface EnterpriseDefinitionAuthoring
{
    /** @param array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    public function createEntity(string $enterpriseId, array $attributes, string $actorId): array;

    /** @param array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    public function createRelationship(string $enterpriseId, array $attributes, string $actorId): array;

    /** @param array<string, mixed> $definition
     * @return array<string, mixed>
     */
    public function createDeviceTemplateDraft(
        string $enterpriseId,
        string $templateKey,
        string $deviceType,
        string $nameAr,
        array $definition,
        string $actorId,
    ): array;

    /** @return array<string, mixed> */
    public function validateDeviceTemplateRevision(string $revisionId, string $actorId): array;

    /** @return array<string, mixed> */
    public function publishDeviceTemplateRevision(string $revisionId, string $actorId): array;

    /** @param array<string, mixed> $behaviorModel
     * @return array<string, mixed>
     */
    public function createDigitalTwinDraft(
        string $enterpriseId,
        string $slug,
        string $nameAr,
        array $behaviorModel,
        string $actorId,
    ): array;

    /** @param array<string, mixed> $component
     * @return array<string, mixed>
     */
    public function addDigitalTwinComponent(string $revisionId, array $component, string $actorId): array;

    /** @param array<string, mixed> $relationship
     * @return array<string, mixed>
     */
    public function addDigitalTwinRelationship(string $revisionId, array $relationship, string $actorId): array;

    /** @return array<string, mixed> */
    public function validateDigitalTwinRevision(string $revisionId, string $actorId): array;

    /** @return array<string, mixed> */
    public function publishDigitalTwinRevision(string $revisionId, string $actorId): array;

    /** @return array<string, mixed> */
    public function cloneDigitalTwinRevision(string $revisionId, string $actorId): array;
}
