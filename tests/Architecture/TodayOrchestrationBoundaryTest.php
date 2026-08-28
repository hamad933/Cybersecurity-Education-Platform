<?php

declare(strict_types=1);

namespace Tests\Architecture;

use Tests\TestCase;

class TodayOrchestrationBoundaryTest extends TestCase
{
    public function test_today_does_not_import_foreign_orm_models(): void
    {
        $todayDir = base_path('app/Application/Today');
        $files = \Illuminate\Support\Facades\File::allFiles($todayDir);

        foreach ($files as $file) {
            $content = file_get_contents($file->getPathname());

            // Today should not directly import models from Database/Models or other bounded contexts
            $this->assertDoesNotMatchRegularExpression(
                '/use App\\\\Database\\\\Models\\\\.*?/',
                $content,
                "File {$file->getFilename()} should not import direct ORM models."
            );

            $this->assertDoesNotMatchRegularExpression(
                '/use App\\\\Application\\\\Vs00[1-9]\\\\.*?/',
                $content,
                "File {$file->getFilename()} should not import foreign domains directly."
            );
        }
    }

    public function test_today_controller_does_not_import_foreign_orm_models(): void
    {
        $controllerPath = base_path('app/Http/Controllers/TodayController.php');
        $content = file_get_contents($controllerPath);

        $this->assertDoesNotMatchRegularExpression(
            '/use App\\\\Database\\\\Models\\\\.*?/',
            $content,
            "TodayController should not import direct ORM models."
        );
    }
}
