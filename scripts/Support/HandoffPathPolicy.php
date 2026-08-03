<?php

namespace Task007\Packaging;

final class HandoffPathPolicy
{
    public static function isRuntimeResidual(string $relative): bool
    {
        $relative = str_replace('\\', '/', $relative);
        if (basename($relative) === '.gitignore') {
            return false;
        }

        return preg_match('#(^|/)(public/build|bootstrap/cache|storage/app|storage/framework/(?:cache|sessions|testing|views)|storage/logs)(/|$)#', $relative) === 1
            || preg_match('#(^|/)(?:\.phpunit\.cache|coverage|browser-profiles?|database-volumes?)(/|$)#', $relative) === 1;
    }

    public static function isProhibited(string $relative): bool
    {
        $relative = str_replace('\\', '/', $relative);
        $basename = basename($relative);
        $isEnvironmentFile = $basename === '.env' || (str_starts_with($basename, '.env.') && $basename !== '.env.example');

        return str_contains($relative, '..')
            || preg_match('#(^|/)(\.git|vendor|node_modules|source-vault/originals)(/|$)#i', $relative) === 1
            || $isEnvironmentFile
            || preg_match('#(^|/)(?:TASK_00[1-6].*\.zip|.*REVIEW_HANDOFF\.zip)$#i', $relative) === 1
            || self::isRuntimeResidual($relative);
    }
}
