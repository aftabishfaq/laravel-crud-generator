<?php

namespace Aftab\LaravelCrud\Tests\Unit;

use Aftab\LaravelCrud\Services\CrudValidator;
use Aftab\LaravelCrud\Tests\TestCase;

class CrudValidatorTest extends TestCase
{
    public function test_detects_missing_tables_key(): void
    {
        $res = (new CrudValidator())->validate([]);
        $this->assertFalse($res['valid']);
    }

    public function test_detects_reserved_names(): void
    {
        $schema = ['tables' => [['name' => 'select', 'columns' => [['name' => 'id', 'type' => 'string']]]]];
        $res = (new CrudValidator())->validate($schema);
        $this->assertFalse($res['valid']);
    }
}


