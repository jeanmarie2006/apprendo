<?php

namespace App\Http\Controllers;

use App\Models\Cours;
use App\Models\Lecon;
use App\Models\Module;
use App\Models\Quiz;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Espace formateur : création et publication de cours (modules, leçons, quiz) et statistiques. */
class FormateurController extends Controller
{
    private function formateur(Request $request): void
    {
        abort_unless($request->user()->role === 'formateur', 403, 'Réservé aux formateurs.');
    }

    private function mien(Request $request, Cours $cours): Cours
    {
        $this->formateur($request);
        abort_unless($cours->formateur_id === $request->user()->id, 403, 'Ce cours ne vous appartient pas.');

        return $cours;
    }

    private function rules(): array
    {
        return [
            'titre' => ['required', 'string', 'min:5', 'max:140'], 'resume' => ['required', 'string', 'min:10', 'max:200'], 'description' => ['required', 'string', 'min:30', 'max:4000'],
            'categorie' => ['required', 'in:'.implode(',', Cours::CATEGORIES)], 'niveau' => ['required', 'in:debutant,intermediaire,avance'], 'prix' => ['required', 'integer', 'between:0,500000'],
        ];
    }

    public function index(Request $request): JsonResponse
    {
        $this->formateur($request);
        $liste = Cours::where('formateur_id', $request->user()->id)->withCount(['inscriptions', 'lecons', 'modules'])->withAvg('avis', 'note')->withSum('inscriptions as revenus', 'prix_paye')->latest()->get();

        return response()->json($liste->map(fn ($c) => ['id' => $c->id, 'slug' => $c->slug, 'titre' => $c->titre, 'categorie' => $c->categorie, 'prix' => $c->prix, 'publie' => $c->publie, 'inscrits' => $c->inscriptions_count, 'nb_lecons' => $c->lecons_count, 'nb_modules' => $c->modules_count, 'revenus' => (int) $c->revenus, 'note' => $c->avis_avg_note ? round((float) $c->avis_avg_note, 1) : null]));
    }

    public function stats(Request $request): JsonResponse
    {
        $this->formateur($request);
        $ids = Cours::where('formateur_id', $request->user()->id)->pluck('id');
        $base = fn () => DB::table('inscriptions')->whereIn('cours_id', $ids);
        $serie = collect(range(5, 0))->map(function ($i) use ($base) {
            $m = now()->startOfMonth()->subMonths($i);
            $q = $base()->whereYear('created_at', $m->year)->whereMonth('created_at', $m->month);

            return ['mois' => $m->format('Y-m'), 'inscrits' => (clone $q)->count(), 'revenus' => (int) (clone $q)->sum('prix_paye')];
        });

        return response()->json([
            'cours' => $ids->count(), 'inscrits' => $base()->count(), 'revenus' => (int) $base()->sum('prix_paye'), 'certificats' => $base()->whereNotNull('certificat')->count(),
            'note_moyenne' => ($n = DB::table('avis_cours')->whereIn('cours_id', $ids)->avg('note')) ? round((float) $n, 1) : null, 'evolution' => $serie,
        ]);
    }

    public function creer(Request $request): JsonResponse
    {
        $this->formateur($request);
        $d = $request->validate($this->rules());
        $c = Cours::create([...$d, 'formateur_id' => $request->user()->id, 'slug' => Cours::uniqueSlug($d['titre']), 'description' => strip_tags($d['description']), 'publie' => false]);

        return response()->json($c, 201);
    }

    public function detail(Request $request, Cours $cours): JsonResponse
    {
        $this->mien($request, $cours);

        return response()->json($cours->load('modules.lecons', 'modules.quiz.questions'));
    }

    public function modifier(Request $request, Cours $cours): JsonResponse
    {
        $this->mien($request, $cours);
        $d = $request->validate($this->rules());
        $cours->update([...$d, 'description' => strip_tags($d['description'])]);

        return response()->json($cours);
    }

    public function supprimer(Request $request, Cours $cours): JsonResponse
    {
        $this->mien($request, $cours);
        abort_if($cours->inscriptions()->exists(), 422, 'Ce cours a des inscrits : dépubliez-le plutôt que de le supprimer.');
        $cours->delete();

        return response()->json(['message' => 'Cours supprimé.']);
    }

    public function publier(Request $request, Cours $cours): JsonResponse
    {
        $this->mien($request, $cours);
        $publie = $request->validate(['publie' => ['required', 'boolean']])['publie'];
        if ($publie) {
            abort_unless($cours->lecons()->count() >= 1, 422, 'Ajoutez au moins une leçon avant de publier.');
        }
        $cours->update(['publie' => $publie]);

        return response()->json($cours);
    }

    // ---- Modules
    public function ajouterModule(Request $request, Cours $cours): JsonResponse
    {
        $this->mien($request, $cours);
        $d = $request->validate(['titre' => ['required', 'string', 'max:140']]);
        $m = $cours->modules()->create(['titre' => strip_tags($d['titre']), 'ordre' => ($cours->modules()->max('ordre') ?? 0) + 1]);

        return response()->json($m->load('lecons'), 201);
    }

    private function module(Request $request, Module $module): Module
    {
        $this->mien($request, $module->cours);

        return $module;
    }

    public function majModule(Request $request, Module $module): JsonResponse
    {
        $this->module($request, $module);
        $module->update(['titre' => strip_tags($request->validate(['titre' => ['required', 'string', 'max:140']])['titre'])]);

        return response()->json($module);
    }

    public function supprimerModule(Request $request, Module $module): JsonResponse
    {
        $this->module($request, $module);
        $module->delete();

        return response()->json(['message' => 'Module supprimé.']);
    }

    // ---- Leçons
    private function reglesLecon(): array
    {
        return [
            'titre' => ['required', 'string', 'max:140'], 'type' => ['required', 'in:video,texte'],
            'contenu' => ['required', 'string', 'max:20000'], 'duree' => ['required', 'integer', 'between:1,600'], 'apercu' => ['sometimes', 'boolean'],
        ];
    }

    private function verifierContenu(array $d): void
    {
        if ($d['type'] === 'video' && ! preg_match('~^https?://(www\.)?(youtube\.com/(watch\?v=|embed/)|youtu\.be/|vimeo\.com/)[A-Za-z0-9_\-/]+~i', $d['contenu'])) {
            abort(422, 'Pour une vidéo, saisissez une adresse YouTube ou Vimeo valide.');
        }
    }

    public function ajouterLecon(Request $request, Module $module): JsonResponse
    {
        $this->module($request, $module);
        $d = $request->validate($this->reglesLecon());
        $this->verifierContenu($d);
        $l = $module->lecons()->create([...$d, 'titre' => strip_tags($d['titre']), 'contenu' => $d['type'] === 'texte' ? strip_tags($d['contenu']) : trim($d['contenu']), 'ordre' => ($module->lecons()->max('ordre') ?? 0) + 1]);

        return response()->json($l, 201);
    }

    public function majLecon(Request $request, Lecon $lecon): JsonResponse
    {
        $this->module($request, $lecon->module);
        $d = $request->validate($this->reglesLecon());
        $this->verifierContenu($d);
        $lecon->update([...$d, 'titre' => strip_tags($d['titre']), 'contenu' => $d['type'] === 'texte' ? strip_tags($d['contenu']) : trim($d['contenu'])]);

        return response()->json($lecon);
    }

    public function supprimerLecon(Request $request, Lecon $lecon): JsonResponse
    {
        $this->module($request, $lecon->module);
        $lecon->delete();

        return response()->json(['message' => 'Leçon supprimée.']);
    }

    // ---- Quiz de fin de module
    public function definirQuiz(Request $request, Module $module): JsonResponse
    {
        $this->module($request, $module);
        $d = $request->validate([
            'titre' => ['required', 'string', 'max:140'],
            'questions' => ['required', 'array', 'min:1', 'max:20'],
            'questions.*.question' => ['required', 'string', 'max:300'],
            'questions.*.choix' => ['required', 'array', 'min:2', 'max:5'], 'questions.*.choix.*' => ['required', 'string', 'max:200'],
            'questions.*.bonne' => ['required', 'integer', 'min:0'],
        ]);
        foreach ($d['questions'] as $q) {
            abort_if($q['bonne'] >= count($q['choix']), 422, 'La bonne réponse doit correspondre à l’une des propositions.');
        }
        DB::transaction(function () use ($module, $d) {
            $quiz = Quiz::updateOrCreate(['module_id' => $module->id], ['titre' => strip_tags($d['titre'])]);
            $quiz->questions()->delete();
            foreach ($d['questions'] as $q) {
                $quiz->questions()->create(['question' => strip_tags($q['question']), 'choix' => array_map('strip_tags', $q['choix']), 'bonne' => $q['bonne']]);
            }
        });

        return response()->json($module->quiz()->with('questions')->first());
    }

    public function supprimerQuiz(Request $request, Module $module): JsonResponse
    {
        $this->module($request, $module);
        $module->quiz()->delete();

        return response()->json(['message' => 'Quiz supprimé.']);
    }
}
