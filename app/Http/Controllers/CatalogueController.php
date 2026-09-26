<?php

namespace App\Http\Controllers;

use App\Models\Cours;
use App\Models\Inscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CatalogueController extends Controller
{
    /** Carte d'un cours dans le catalogue. */
    public static function carte(Cours $c): array
    {
        return [
            'id' => $c->id, 'slug' => $c->slug, 'titre' => $c->titre, 'resume' => $c->resume, 'categorie' => $c->categorie, 'niveau' => $c->niveau, 'prix' => $c->prix,
            'formateur' => $c->formateur->name ?? null, 'inscrits' => (int) ($c->inscriptions_count ?? 0),
            'note' => $c->avis_avg_note ? round((float) $c->avis_avg_note, 1) : null, 'nb_avis' => (int) ($c->avis_count ?? 0),
            'nb_lecons' => (int) ($c->lecons_count ?? 0), 'duree' => (int) ($c->duree ?? 0),
        ];
    }

    public function categories(): JsonResponse
    {
        $n = Cours::where('publie', true)->select('categorie', DB::raw('count(*) as total'))->groupBy('categorie')->pluck('total', 'categorie');

        return response()->json(collect(Cours::CATEGORIES)->map(fn ($c) => ['nom' => $c, 'total' => (int) ($n[$c] ?? 0)]));
    }

    public function index(Request $request): JsonResponse
    {
        $request->validate(['q' => 'nullable|string|max:80', 'categorie' => 'nullable|string|max:40', 'niveau' => 'nullable|in:debutant,intermediaire,avance', 'prix' => 'nullable|in:gratuit,payant', 'tri' => 'nullable|in:populaires,recents,prix,note']);
        $q = Cours::with('formateur:id,name')->where('publie', true)->withCount(['inscriptions', 'avis', 'lecons'])->withAvg('avis', 'note')->withSum('lecons as duree', 'duree');
        if ($t = trim((string) $request->query('q'))) {
            $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $t).'%';
            $q->where(fn ($w) => $w->where('titre', 'like', $like)->orWhere('resume', 'like', $like)->orWhere('categorie', 'like', $like));
        }
        $q->when($request->query('categorie'), fn ($w, $v) => $w->where('categorie', $v))->when($request->query('niveau'), fn ($w, $v) => $w->where('niveau', $v));
        match ($request->query('prix')) {
            'gratuit' => $q->where('prix', 0), 'payant' => $q->where('prix', '>', 0), default => null,
        };
        match ($request->query('tri', 'populaires')) {
            'recents' => $q->orderByDesc('id'), 'prix' => $q->orderBy('prix'), 'note' => $q->orderByDesc('avis_avg_note'), default => $q->orderByDesc('inscriptions_count'),
        };

        return response()->json($q->paginate(12)->through(fn ($c) => self::carte($c)));
    }

    /** Fiche d'un cours : le contenu des leçons n'est visible que pour les leçons « aperçu » ou après inscription. */
    public function show(Request $request, string $slug): JsonResponse
    {
        $me = $request->user('sanctum');
        $c = Cours::with(['formateur:id,name,bio', 'modules.lecons', 'modules.quiz'])->withCount(['inscriptions', 'avis', 'lecons'])->withAvg('avis', 'note')->withSum('lecons as duree', 'duree')->where('slug', $slug)->firstOrFail();
        $proprio = $me && $me->id === $c->formateur_id;
        abort_unless($c->publie || $proprio, 404);
        $insc = $me ? Inscription::where('cours_id', $c->id)->where('apprenant_id', $me->id)->first() : null;
        $acces = $proprio || $insc;

        return response()->json([
            ...self::carte($c), 'description' => $c->description, 'publie' => $c->publie, 'formateur_bio' => $c->formateur->bio, 'peut_modifier' => $proprio,
            'modules' => $c->modules->map(fn ($m) => [
                'id' => $m->id, 'titre' => $m->titre, 'a_quiz' => (bool) $m->quiz,
                'lecons' => $m->lecons->map(fn ($l) => ['id' => $l->id, 'titre' => $l->titre, 'type' => $l->type, 'duree' => $l->duree, 'apercu' => $l->apercu, 'contenu' => ($acces || $l->apercu) ? $l->contenu : null]),
            ]),
            'avis' => $c->avis()->with('apprenant:id,name')->latest()->limit(6)->get()->map(fn ($a) => ['id' => $a->id, 'note' => $a->note, 'commentaire' => $a->commentaire, 'auteur' => $a->apprenant->name, 'date' => $a->created_at->toDateString()]),
            'mon_inscription' => $insc ? ['id' => $insc->id, ...collect($insc->bilan())->only(['pourcentage', 'fait', 'total'])->all(), 'termine' => (bool) $insc->certificat] : null,
        ]);
    }
}
