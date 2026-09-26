<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Str;

class Cours extends Model
{
    protected $table = 'cours';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['publie' => 'boolean'];
    }

    public const CATEGORIES = ['Développement web', 'Design', 'Marketing digital', 'Entrepreneuriat', 'Bureautique', 'Langues', 'Comptabilité'];

    public function formateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'formateur_id');
    }

    public function modules(): HasMany
    {
        return $this->hasMany(Module::class)->orderBy('ordre');
    }

    public function lecons(): HasManyThrough
    {
        return $this->hasManyThrough(Lecon::class, Module::class);
    }

    public function inscriptions(): HasMany
    {
        return $this->hasMany(Inscription::class);
    }

    public function avis(): HasMany
    {
        return $this->hasMany(AvisCours::class);
    }

    public static function uniqueSlug(string $titre): string
    {
        $base = Str::slug($titre) ?: 'cours';
        $slug = $base;
        for ($i = 2; static::where('slug', $slug)->exists(); $i++) {
            $slug = $base.'-'.$i;
        }

        return $slug;
    }
}
