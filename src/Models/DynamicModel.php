<?php

namespace Aftab\LaravelCrud\Models;

use Illuminate\Database\Eloquent\Model;

class DynamicModel extends Model
{
    protected $guarded = [];

    public function setTableName(string $table): void
    {
        $this->setTable($table);
    }
}


