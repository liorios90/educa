<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUpTraits(): array
    {
        if (
            config('database.default') !== 'sqlite'
            || config('database.connections.sqlite.database') !== ':memory:'
        ) {
            throw new \RuntimeException(
                'Feature tests must use the sqlite in-memory database. Refusing to run RefreshDatabase against '.
                config('database.default').'.',
            );
        }

        return parent::setUpTraits();
    }
}
