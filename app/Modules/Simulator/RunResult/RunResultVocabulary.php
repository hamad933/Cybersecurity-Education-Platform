<?php

namespace App\Modules\Simulator\RunResult;

use DomainException;
use InvalidArgumentException;

final class RunResultVocabulary
{
    public const RUN_STANDALONE_LAB = 'Standalone Lab Run';

    public const RUN_SCENARIO = 'Scenario Run';

    /** @var list<string> */
    public const RUN_TYPES = [self::RUN_STANDALONE_LAB, self::RUN_SCENARIO];

    /** @var list<string> */
    public const LIFECYCLES = ['PREPARING', 'READY', 'RUNNING', 'PAUSED', 'COMPLETED', 'STOPPED', 'FAILED'];

    /** @var list<string> */
    public const TERMINAL_LIFECYCLES = ['COMPLETED', 'STOPPED', 'FAILED'];

    /** @var list<string> */
    public const RESULT_OUTCOMES = ['ACHIEVED', 'PARTIAL', 'NOT_ACHIEVED', 'INCONCLUSIVE', 'NOT_EVALUATED'];

    /** @var array<string,list<string>> */
    public const TRANSITIONS = [
        'PREPARING' => ['READY', 'FAILED'],
        'READY' => ['RUNNING', 'STOPPED', 'FAILED'],
        'RUNNING' => ['PAUSED', 'COMPLETED', 'STOPPED', 'FAILED'],
        'PAUSED' => ['RUNNING', 'STOPPED', 'FAILED'],
        'COMPLETED' => [],
        'STOPPED' => [],
        'FAILED' => [],
    ];

    public static function assertRunType(string $runType): void
    {
        if (in_array($runType, self::RUN_TYPES, true) === false) {
            throw new InvalidArgumentException('Unsupported Run type.');
        }
    }

    public static function assertOutcome(string $outcome): void
    {
        if (in_array($outcome, self::RESULT_OUTCOMES, true) === false) {
            throw new InvalidArgumentException('Unsupported Result outcome.');
        }
    }

    public static function assertScore(?float $score): void
    {
        if ($score !== null && ($score < 0 || $score > 100)) {
            throw new InvalidArgumentException('Result score must be between 0 and 100.');
        }
    }

    public static function assertTransition(string $from, string $to): void
    {
        if (in_array($to, self::TRANSITIONS[$from] ?? [], true) === false) {
            throw new DomainException("Invalid Run lifecycle transition: {$from} -> {$to}.");
        }
    }

    public static function isTerminal(string $lifecycle): bool
    {
        return in_array($lifecycle, self::TERMINAL_LIFECYCLES, true);
    }

    /** @return list<string> */
    public static function availableActions(string $lifecycle): array
    {
        return match ($lifecycle) {
            'PREPARING' => ['ready', 'fail'],
            'READY' => ['start', 'stop', 'fail', 'snapshot'],
            'RUNNING' => ['complete', 'pause', 'stop', 'fail', 'snapshot'],
            'PAUSED' => ['resume', 'stop', 'fail', 'snapshot'],
            default => [],
        };
    }
}
