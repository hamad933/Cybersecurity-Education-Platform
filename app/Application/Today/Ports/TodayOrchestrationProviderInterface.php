<?php

declare(strict_types=1);

namespace App\Application\Today\Ports;

use App\Application\Today\Values\OrchestrationNode;

/**
 * Port used by Today surface to obtain cross-domain orchestration state.
 * Expected to be implemented in a shared/infrastructure layer since Today cannot directly query foreign domains.
 */
interface TodayOrchestrationProviderInterface
{
    /**
     * @return OrchestrationNode
     */
    public function getContinueSession(): OrchestrationNode;

    /**
     * @return OrchestrationNode
     */
    public function getRecommendation(): OrchestrationNode;

    /**
     * @return OrchestrationNode
     */
    public function getAttentionItems(): OrchestrationNode;

    /**
     * @return OrchestrationNode
     */
    public function getRecentContext(): OrchestrationNode;

    /**
     * @return OrchestrationNode
     */
    public function getProgressProjection(): OrchestrationNode;
}
