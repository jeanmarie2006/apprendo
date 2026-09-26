<?php

namespace App\Http\Controllers;

use App\Models\AvisCours;
use App\Models\Cours;
use App\Models\Inscription;
use App\Models\Lecon;
use App\Models\Progression;
use App\Models\Quiz;
use App\Models\Tentative;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/** Côté apprenant : inscription, lecteur de cours, progression, quiz, certificat, avis. */
class ApprentissageController extends Controller
{
    private function inscription(Request $request, Cours $cours): Inscription
    {
        return Inscription::where('cours_id', $cours->id)->where('apprenant_id', $request->user()->id)->firstOrFail();
    }

    public function inscrire(Request $request, Cours $cours): JsonResponse
    {
        abort_unless($request->user()->role === 'apprenant', 403, 'Seuls les apprenants peuvent s’inscrire à un cours.');
        abort_unless($cours->publie, 404);
        abort_if(Inscription::where('cours_id', $cours->id)->where('apprenant_id', $request->user()->id)->exists(), 422, 'Vous êtes déjà inscrit à ce cours.');
        $data = ['cours_id' => $cours->id, 'apprenant_id' => $request->user()->id, 'prix_paye' => $cours->prix];
        if ($cours->prix > 0) {
            $d = $request->validate(['mode' => ['required', 'in:momo,moov'], 'numero' => ['required', 'regex:/^\+?[0-9 .\-]{8,20}$/']], ['numero.regex' => 'Numéro Mobile Money invalide.']);
            $data += ['paiement_mode' => $d['mode'], 'paiement_ref' => strtoupper(($d['mode'] === 'momo' ? 'MOMO-' : 'MOOV-').Str::random(8))];
        }

        return response()->json(Inscription::create($data), 201);
    }

    public function mesCours(Request $request): JsonResponse
    {
        abort_unless($request->user()->role === 'apprenant', 403);
        $liste = Inscription::with(['cours' => fn ($q) => $q->with('formateur:id,name')->withCount('lecons')])->where('apprenant_id', $request->user()->id)->latest()->get();

        return response()->json($liste->map(fn ($i) => ['id' => $i->id, 'slug' => $i->cours->slug, 'titre' => $i->cours->titre, 'categorie' => $i->cours->categorie, 'formateur' => $i->cours->formateur->name, 'paye' => $i->prix_paye, 'certificat' => $i->certificat, 'termine_le' => $i->termine_le?->toDateString(), ...collect($i->bilan())->only(['pourcentage', 'fait', 'total'])->all()]));
    }

    /** Contenu complet du cours pour le lecteur (apprenant inscrit ou formateur propriétaire). */
    public function lecteur(Request $request, string $slug): JsonResponse
    {
        $c = Cours::with(['formateur:id,name', 'modules.lecons', 'modules.quiz.questions'])->where('slug', $slug)->firstOrFail();
        $proprio = $c->formateur_id === $request->user()->id;
        $insc = $proprio ? null : $this->inscription($request, $c);
        $bilan = $insc?->bilan() ?? ['total' => 0, 'fait' => 0, 'pourcentage' => 0, 'lecons_faites' => [], 'quiz_reussis' => []];
        $meilleurs = $insc ? $insc->tentatives()->selectRaw('quiz_id, max(score) as score, max(total) as total')->groupBy('quiz_id')->get()->keyBy('quiz_id') : collect();

        return response()->json([
            'cours' => ['id' => $c->id, 'slug' => $c->slug, 'titre' => $c->titre, 'formateur' => $c->formateur->name],
            'apercu_formateur' => $proprio,
            'inscription' => $insc ? ['id' => $insc->id, 'certificat' => $insc->certificat, 'termine_le' => $insc->termine_le?->toDateString()] : null,
            'bilan' => $bilan,
            'modules' => $c->modules->map(fn ($m) => [
                'id' => $m->id, 'titre' => $m->titre,
                'lecons' => $m->lecons->map(fn ($l) => ['id' => $l->id, 'titre' => $l->titre, 'type' => $l->type, 'duree' => $l->duree, 'contenu' => $l->contenu, 'fait' => in_array($l->id, $bilan['lecons_faites'], true)]),
                'quiz' => $m->quiz ? ['id' => $m->quiz->id, 'titre' => $m->quiz->titre, 'nb_questions' => $m->quiz->questions->count(), 'reussi' => in_array($m->quiz->id, $bilan['quiz_reussis'], true), 'meilleur' => isset($meilleurs[$m->quiz->id]) ? ['score' => (int) $meilleurs[$m->quiz->id]->score, 'total' => (int) $meilleurs[$m->quiz->id]->total] : null] : null,
            ]),
        ]);
    }

    public function terminerLecon(Request $request, Lecon $lecon): JsonResponse
    {
        $cours = $lecon->module->cours;
        $insc = $this->inscription($request, $cours);
        Progression::firstOrCreate(['inscription_id' => $insc->id, 'lecon_id' => $lecon->id]);
        $insc->verifierAchevement();

        return response()->json([...$insc->fresh()->bilan(), 'certificat' => $insc->fresh()->certificat]);
    }

    public function quiz(Request $request, Quiz $quiz): JsonResponse
    {
        $cours = $quiz->module->cours;
        if ($cours->formateur_id !== $request->user()->id) {
            $this->inscription($request, $cours);
        }

        return response()->json(['id' => $quiz->id, 'titre' => $quiz->titre, 'seuil' => Quiz::SEUIL, 'questions' => $quiz->questions->map(fn ($q) => ['id' => $q->id, 'question' => $q->question, 'choix' => $q->choix])]);
    }

    public function repondre(Request $request, Quiz $quiz): JsonResponse
    {
        $insc = $this->inscription($request, $quiz->module->cours);
        $data = $request->validate(['reponses' => ['required', 'array'], 'reponses.*' => ['nullable', 'integer', 'min:0', 'max:9']]);
        $questions = $quiz->questions;
        $juste = 0;
        $corrections = [];
        foreach ($questions as $q) {
            $rep = $data['reponses'][$q->id] ?? null;
            $ok = $rep !== null && (int) $rep === (int) $q->bonne;
            $juste += $ok ? 1 : 0;
            $corrections[] = ['id' => $q->id, 'bonne' => (int) $q->bonne, 'donnee' => $rep, 'ok' => $ok];
        }
        $total = $questions->count();
        $reussi = $total > 0 && ($juste / $total * 100) >= Quiz::SEUIL;
        Tentative::create(['inscription_id' => $insc->id, 'quiz_id' => $quiz->id, 'score' => $juste, 'total' => $total, 'reussi' => $reussi]);
        $insc->verifierAchevement();

        return response()->json(['score' => $juste, 'total' => $total, 'reussi' => $reussi, 'seuil' => Quiz::SEUIL, 'corrections' => $corrections, 'certificat' => $insc->fresh()->certificat, ...collect($insc->fresh()->bilan())->only(['pourcentage'])->all()]);
    }

    public function certificat(Request $request, Inscription $inscription)
    {
        abort_unless($inscription->apprenant_id === $request->user()->id, 403);
        abort_unless($inscription->certificat, 422, 'Terminez le cours (leçons et quiz) pour obtenir votre certificat.');
        $inscription->load('cours.formateur', 'apprenant');

        return Pdf::loadView('pdf.certificat', ['i' => $inscription])->setPaper('a4', 'landscape')->stream('certificat-'.$inscription->certificat.'.pdf');
    }

    /** Vérification publique d'un certificat par son numéro. */
    public function verifier(string $numero): JsonResponse
    {
        $i = Inscription::with('cours:id,titre', 'apprenant:id,name')->where('certificat', $numero)->firstOrFail();

        return response()->json(['valide' => true, 'apprenant' => $i->apprenant->name, 'cours' => $i->cours->titre, 'date' => $i->termine_le->toDateString(), 'numero' => $i->certificat]);
    }

    public function noter(Request $request, Cours $cours): JsonResponse
    {
        $this->inscription($request, $cours);
        $d = $request->validate(['note' => ['required', 'integer', 'between:1,5'], 'commentaire' => ['nullable', 'string', 'max:400']]);
        $a = AvisCours::updateOrCreate(['cours_id' => $cours->id, 'apprenant_id' => $request->user()->id], ['note' => $d['note'], 'commentaire' => isset($d['commentaire']) ? strip_tags($d['commentaire']) : null]);

        return response()->json($a, 201);
    }
}
