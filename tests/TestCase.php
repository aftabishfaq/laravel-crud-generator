<?php

namespace Aftab\LaravelCrud\Tests;

use Aftab\LaravelCrud\CrudGeneratorServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [CrudGeneratorServiceProvider::class];
    }
}


