<?php

use Illuminate\Support\Facades\Route;

$prefix = (string) (config('crud.route_prefix') ?? 'crud');
$middleware = (array) (config('crud.middleware') ?? ['web']);

Route::group([
    'prefix' => $prefix,
    'middleware' => $middleware,
], function () {
    Route::get('/{table}', [\Aftab\LaravelCrud\Http\Controllers\CrudController::class, 'index'])->name('crud.index');
    Route::get('/{table}/create', [\Aftab\LaravelCrud\Http\Controllers\CrudController::class, 'create'])->name('crud.create');
    Route::post('/{table}', [\Aftab\LaravelCrud\Http\Controllers\CrudController::class, 'store'])->name('crud.store');
    Route::get('/{table}/{id}', [\Aftab\LaravelCrud\Http\Controllers\CrudController::class, 'show'])->name('crud.show');
    Route::get('/{table}/{id}/edit', [\Aftab\LaravelCrud\Http\Controllers\CrudController::class, 'edit'])->name('crud.edit');
    Route::put('/{table}/{id}', [\Aftab\LaravelCrud\Http\Controllers\CrudController::class, 'update'])->name('crud.update');
    Route::delete('/{table}/{id}', [\Aftab\LaravelCrud\Http\Controllers\CrudController::class, 'destroy'])->name('crud.destroy');
});


