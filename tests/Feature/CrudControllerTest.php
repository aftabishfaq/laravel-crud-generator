<?php

namespace Aftab\LaravelCrud\Tests\Feature;

use Aftab\LaravelCrud\Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;

class CrudControllerTest extends TestCase
{
    protected function defineEnvironment($app)
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        // App key for encryption middleware/session
        $app['config']->set('app.cipher', 'AES-256-CBC');
        $app['config']->set('app.key', 'base64:' . base64_encode(random_bytes(32)));
        $app['config']->set('crud.allowed_tables', ['users']);
    }

    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        // Seed a row
        DB::table('users')->insert(['name' => 'Alice', 'created_at' => now(), 'updated_at' => now()]);
    }

    public function test_index_route_loads(): void
    {
        $res = $this->get('/' . (config('crud.route_prefix') ?? 'crud') . '/users');
        $res->assertStatus(200);
        $res->assertSee('Alice');
    }
}


