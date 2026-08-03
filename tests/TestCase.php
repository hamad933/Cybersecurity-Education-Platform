<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Support\DestructiveDatabaseGuard;

abstract class TestCase extends BaseTestCase
{
    protected function setUpTraits()
    {
        DestructiveDatabaseGuard::fromRuntime()->assertSafe();

        return parent::setUpTraits();
    }
}
