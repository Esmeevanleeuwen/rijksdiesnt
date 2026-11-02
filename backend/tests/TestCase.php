<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use LogicException;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $app = parent::createApplication();

        // This runs before RefreshDatabase can migrate or truncate anything.
        $connection = $app['db']->connection();
        if (! $app->environment('testing') || $connection->getDriverName() !== 'pgsql' || $connection->getDatabaseName() !== 'rijksdienst_testing') {
            throw new LogicException('Tests mogen uitsluitend de PostgreSQL-database rijksdienst_testing gebruiken.');
        }

        return $app;
    }
}
