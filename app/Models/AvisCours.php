<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AvisCours extends Model
{
    protected $table = 'avis_cours';

    protected $guarded = [];

    public function apprenant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'apprenant_id');
    }
}
