<?php

namespace Aftab\LaravelCrud\Tests\Unit;

use Aftab\LaravelCrud\Services\Dto\CrudColumn;
use Aftab\LaravelCrud\Services\Dto\CrudTable;
use Aftab\LaravelCrud\Services\MigrationBuilder;
use Aftab\LaravelCrud\Tests\TestCase;

class MigrationBuilderTest extends TestCase
{
    public function test_builds_basic_migration(): void
    {
        $table = new CrudTable('users', 'Users', [
            new CrudColumn('name', 'string'),
        ]);
        $builder = new MigrationBuilder();
        $migs = $builder->buildMigrations([$table]);
        $this->assertNotEmpty($migs);
        $this->assertStringContainsString("Schema::create('users'", $migs[0]['contents']);
    }
}


