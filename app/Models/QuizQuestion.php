<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuizQuestion extends Model
{
    protected $table = 'quiz_questions';

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['choix' => 'array'];
    }
}
