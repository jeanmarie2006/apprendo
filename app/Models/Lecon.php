<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lecon extends Model
{
    protected $table = 'lecons';

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['apercu' => 'boolean'];
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }
}
