<?php

namespace Aftab\LaravelCrud\Tests\Unit;

use Aftab\LaravelCrud\Services\CrudParser;
use Aftab\LaravelCrud\Tests\TestCase;

class CrudParserTest extends TestCase
{
    public function test_parses_json_string(): void
    {
        $parser = new CrudParser();
        $data = $parser->parseInput('{"tables": []}');
        $this->assertIsArray($data);
        $this->assertArrayHasKey('tables', $data);
    }

    public function test_throws_on_invalid_json(): void
    {
        $this->expectException(\RuntimeException::class);
        (new CrudParser())->parseInput('{invalid');
    }
}


