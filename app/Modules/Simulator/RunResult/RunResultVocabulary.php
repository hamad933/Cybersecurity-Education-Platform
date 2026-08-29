<?php

namespace App\Modules\Simulator\RunResult;

final class RunResultVocabulary
{
    public const OUTCOME_ACHIEVED = 'ACHIEVED';

    public const OUTCOME_INCONCLUSIVE = 'INCONCLUSIVE';

    public const OUTCOME_NOT_ACHIEVED = 'NOT_ACHIEVED';

    public const OUTCOME_NOT_EVALUATED = 'NOT_EVALUATED';

    public const OUTCOME_PARTIAL = 'PARTIAL';

    public const PROVENANCE_SIMULATED = 'SIMULATED';

    public const SCHEMA_V1 = 'cep.simulation.run-result.v1';

    /**
     * @return list<string>
     */
    public static function getOutcomes(): array
    {
        return [
            self::OUTCOME_ACHIEVED,
            self::OUTCOME_PARTIAL,
            self::OUTCOME_NOT_ACHIEVED,
            self::OUTCOME_INCONCLUSIVE,
            self::OUTCOME_NOT_EVALUATED,
        ];
    }
}
