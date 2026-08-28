<?php

declare(strict_types=1);

namespace App\Application\Today\Ports;

/**
 * Port used by Today surface to obtain cross-domain orchestration state.
 * Expected to be implemented in a shared/infrastructure layer since Today cannot directly query foreign domains.
 */
interface TodayOrchestrationProviderInterface
{
    /**
     * @return array{status: string, data: array|null, message?: string}
     */
    public function getContinueSession(): array;

    /**
     * @return array{status: string, data: array|null, message?: string}
     */
    public function getNextAction(): array;

    /**
     * @return array{status: string, data: array|null, message?: string}
     */
    public function getRationale(): array;

    /**
     * @return array{status: string, data: array, message?: string}
     */
    public function getAttentionItems(): array;

    /**
     * @return array{status: string, data: array, message?: string}
     */
    public function getRecentContext(): array;

    /**
     * @return array{status: string, data: array|null, message?: string}
     */
    public function getProgressProjection(): array;
}
