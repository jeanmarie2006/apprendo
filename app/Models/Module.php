<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Module extends Model
{
    protected $table = 'modules';

    public $timestamps = false;

    protected $guarded = [];

    public function cours(): BelongsTo
    {
        return $this->belongsTo(Cours::class);
    }

    public function lecons(): HasMany
    {
        return $this->hasMany(Lecon::class)->orderBy('ordre');
    }

    public function quiz(): HasOne
    {
        return $this->hasOne(Quiz::class);
    }
}
