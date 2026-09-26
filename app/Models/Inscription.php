<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Inscription extends Model
{
    protected $table = 'inscriptions';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['termine_le' => 'datetime'];
    }

    public function cours(): BelongsTo
    {
        return $this->belongsTo(Cours::class);
    }

    public function apprenant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'apprenant_id');
    }

    public function progressions(): HasMany
    {
        return $this->hasMany(Progression::class);
    }

    public function tentatives(): HasMany
    {
        return $this->hasMany(Tentative::class);
    }

    /** Éléments à valider : toutes les leçons + un quiz réussi par module qui en possède un. */
    public function bilan(): array
    {
        $cours = $this->cours()->with('modules.lecons:id,module_id', 'modules.quiz:id,module_id')->first();
        $lecons = $cours->modules->flatMap->lecons->pluck('id');
        $quiz = $cours->modules->map->quiz->filter()->pluck('id');
        $faites = $this->progressions()->pluck('lecon_id');
        $reussis = $this->tentatives()->where('reussi', true)->pluck('quiz_id')->unique();
        $total = $lecons->count() + $quiz->count();
        $fait = $lecons->intersect($faites)->count() + $quiz->intersect($reussis)->count();

        return ['total' => $total, 'fait' => $fait, 'pourcentage' => $total ? (int) floor($fait / $total * 100) : 0, 'lecons_faites' => $faites->all(), 'quiz_reussis' => $reussis->values()->all()];
    }

    /** Délivre le certificat dès que tout est validé. */
    public function verifierAchevement(): void
    {
        $b = $this->bilan();
        if ($b['total'] > 0 && $b['fait'] === $b['total'] && ! $this->certificat) {
            do {
                $num = 'APP-'.now()->year.'-'.strtoupper(Str::random(6));
            } while (static::where('certificat', $num)->exists());
            $this->update(['termine_le' => now(), 'certificat' => $num]);
        }
    }
}
