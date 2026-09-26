<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tentative extends Model
{
    protected $table = 'tentatives';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['reussi' => 'boolean'];
    }
}
