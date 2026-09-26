<?php

namespace Tests\Feature;

use App\Models\Cours;
use App\Models\Inscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprendoTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, string $email = null): User
    {
        return User::create(['name' => ucfirst($role), 'email' => $email ?? "$role@test.bj", 'password' => 'motdepasse', 'role' => $role]);
    }

    /** Cours publié : 1 module, 2 leçons (dont 1 aperçu), 1 quiz de 2 questions. */
    private function cours(User $f, int $prix = 0, bool $publie = true): Cours
    {
        $c = Cours::create(['formateur_id' => $f->id, 'titre' => 'Cours de test', 'slug' => Cours::uniqueSlug('Cours de test'), 'resume' => 'Un résumé de test', 'description' => 'Description de test', 'categorie' => 'Développement web', 'niveau' => 'debutant', 'prix' => $prix, 'publie' => $publie]);
        $m = $c->modules()->create(['titre' => 'Module 1', 'ordre' => 1]);
        $m->lecons()->create(['titre' => 'Intro', 'type' => 'texte', 'contenu' => 'Contenu gratuit', 'duree' => 5, 'apercu' => true, 'ordre' => 1]);
        $m->lecons()->create(['titre' => 'Suite', 'type' => 'texte', 'contenu' => 'Contenu réservé', 'duree' => 5, 'apercu' => false, 'ordre' => 2]);
        $quiz = $m->quiz()->create(['titre' => 'Quiz']);
        $quiz->questions()->create(['question' => 'Q1 ?', 'choix' => ['Oui', 'Non'], 'bonne' => 0]);
        $quiz->questions()->create(['question' => 'Q2 ?', 'choix' => ['A', 'B', 'C'], 'bonne' => 2]);

        return $c->fresh(['modules.lecons', 'modules.quiz.questions']);
    }

    public function test_inscription_par_role_et_catalogue_public(): void
    {
        $this->postJson('/api/auth/register', ['name' => 'Awa', 'email' => 'awa@test.bj', 'password' => 'motdepasse', 'role' => 'admin'])->assertStatus(422);
        $this->postJson('/api/auth/register', ['name' => 'Awa', 'email' => 'awa@test.bj', 'password' => 'motdepasse', 'role' => 'apprenant'])->assertCreated();
        $f = $this->user('formateur');
        $this->cours($f);
        $this->cours($f, 5000, false);
        $this->getJson('/api/catalogue')->assertOk()->assertJsonPath('total', 1);
        $this->getJson('/api/catalogue?prix=payant')->assertJsonPath('total', 0);
        $this->getJson('/api/catalogue?q=inexistant')->assertJsonPath('total', 0);
    }

    public function test_le_contenu_des_lecons_est_verrouille_avant_l_inscription(): void
    {
        $c = $this->cours($this->user('formateur'));
        $r = $this->getJson("/api/cours/{$c->slug}")->assertOk();
        $this->assertSame('Contenu gratuit', $r->json('modules.0.lecons.0.contenu'));
        $this->assertNull($r->json('modules.0.lecons.1.contenu'));
        $app = $this->user('apprenant');
        $this->actingAs($app, 'sanctum')->getJson("/api/apprendre/{$c->slug}")->assertNotFound(); // pas inscrit
        $this->actingAs($app, 'sanctum')->postJson("/api/cours/{$c->id}/inscription")->assertCreated();
        $this->actingAs($app, 'sanctum')->getJson("/api/cours/{$c->slug}")->assertJsonPath('modules.0.lecons.1.contenu', 'Contenu réservé');
        $this->actingAs($app, 'sanctum')->postJson("/api/cours/{$c->id}/inscription")->assertStatus(422); // déjà inscrit
    }

    public function test_un_cours_payant_exige_un_paiement_mobile_money(): void
    {
        $c = $this->cours($this->user('formateur'), 15000);
        $app = $this->user('apprenant');
        $this->actingAs($app, 'sanctum')->postJson("/api/cours/{$c->id}/inscription")->assertStatus(422)->assertJsonValidationErrors(['mode', 'numero']);
        $this->actingAs($app, 'sanctum')->postJson("/api/cours/{$c->id}/inscription", ['mode' => 'momo', 'numero' => 'abc'])->assertStatus(422);
        $r = $this->actingAs($app, 'sanctum')->postJson("/api/cours/{$c->id}/inscription", ['mode' => 'momo', 'numero' => '+229 01 96 00 00 00'])->assertCreated();
        $this->assertSame(15000, $r->json('prix_paye'));
        $this->assertStringStartsWith('MOMO-', $r->json('paiement_ref'));
    }

    public function test_seuls_les_apprenants_s_inscrivent_et_un_cours_brouillon_est_invisible(): void
    {
        $f = $this->user('formateur');
        $c = $this->cours($f);
        $b = $this->cours($f, 0, false);
        $this->actingAs($f, 'sanctum')->postJson("/api/cours/{$c->id}/inscription")->assertForbidden();
        $this->actingAs($this->user('apprenant'), 'sanctum')->postJson("/api/cours/{$b->id}/inscription")->assertNotFound();
        $this->getJson("/api/cours/{$b->slug}")->assertNotFound();
        $this->actingAs($f, 'sanctum')->getJson("/api/cours/{$b->slug}")->assertOk()->assertJsonPath('peut_modifier', true);
    }

    public function test_progression_quiz_et_certificat(): void
    {
        $c = $this->cours($this->user('formateur'));
        $app = $this->user('apprenant');
        $this->actingAs($app, 'sanctum')->postJson("/api/cours/{$c->id}/inscription")->assertCreated();
        $insc = Inscription::first();
        $quiz = $c->modules[0]->quiz;

        foreach ($c->modules[0]->lecons as $i => $l) {
            $b = $this->actingAs($app, 'sanctum')->postJson("/api/lecons/{$l->id}/terminer")->assertOk()->json();
            $this->assertSame($i === 0 ? 33 : 66, $b['pourcentage']);
            $this->assertNull($b['certificat']);
        }
        // échec : 1 bonne réponse sur 2 = 50 % < 60 %
        [$q1, $q2] = $quiz->questions->all();
        $r = $this->actingAs($app, 'sanctum')->postJson("/api/quiz/{$quiz->id}/repondre", ['reponses' => [$q1->id => 0, $q2->id => 0]])->assertOk();
        $this->assertFalse($r->json('reussi'));
        $this->assertSame(1, $r->json('score'));
        $this->assertNull($insc->fresh()->certificat);
        $this->actingAs($app, 'sanctum')->getJson("/api/inscriptions/{$insc->id}/certificat")->assertStatus(422);
        // réussite
        $r = $this->actingAs($app, 'sanctum')->postJson("/api/quiz/{$quiz->id}/repondre", ['reponses' => [$q1->id => 0, $q2->id => 2]])->assertOk();
        $this->assertTrue($r->json('reussi'));
        $this->assertNotNull($r->json('certificat'));
        $this->assertSame(100, $r->json('pourcentage'));
        $pdf = $this->actingAs($app, 'sanctum')->get("/api/inscriptions/{$insc->id}/certificat")->assertOk();
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());
        $this->getJson('/api/certificats/'.$insc->fresh()->certificat)->assertOk()->assertJsonPath('valide', true);
        $this->getJson('/api/certificats/APP-0000-XXXXXX')->assertNotFound();
        $this->actingAs($this->user('apprenant', 'autre@test.bj'), 'sanctum')->getJson("/api/inscriptions/{$insc->id}/certificat")->assertForbidden();
    }

    public function test_les_bonnes_reponses_ne_sont_pas_exposees_avant_correction(): void
    {
        $c = $this->cours($this->user('formateur'));
        $app = $this->user('apprenant');
        $this->actingAs($app, 'sanctum')->postJson("/api/cours/{$c->id}/inscription");
        $r = $this->actingAs($app, 'sanctum')->getJson('/api/quiz/'.$c->modules[0]->quiz->id)->assertOk();
        $this->assertArrayNotHasKey('bonne', $r->json('questions.0'));
    }

    public function test_avis_reserve_aux_inscrits_et_moyenne(): void
    {
        $c = $this->cours($this->user('formateur'));
        $app = $this->user('apprenant');
        $this->actingAs($app, 'sanctum')->postJson("/api/cours/{$c->id}/avis", ['note' => 5])->assertNotFound();
        $this->actingAs($app, 'sanctum')->postJson("/api/cours/{$c->id}/inscription");
        $this->actingAs($app, 'sanctum')->postJson("/api/cours/{$c->id}/avis", ['note' => 4, 'commentaire' => 'Bien'])->assertCreated();
        $this->actingAs($app, 'sanctum')->postJson("/api/cours/{$c->id}/avis", ['note' => 9])->assertStatus(422);
        $this->getJson('/api/catalogue')->assertJsonPath('data.0.note', 4);
    }

    public function test_seul_le_formateur_proprietaire_modifie_son_cours(): void
    {
        $f = $this->user('formateur');
        $autre = $this->user('formateur', 'autre@test.bj');
        $c = $this->cours($f);
        $body = ['titre' => 'Nouveau titre du cours', 'resume' => 'Un résumé suffisamment long', 'description' => 'Une description suffisamment longue pour être valide.', 'categorie' => 'Design', 'niveau' => 'avance', 'prix' => 20000];
        $this->actingAs($autre, 'sanctum')->putJson("/api/formateur/cours/{$c->id}", $body)->assertForbidden();
        $this->actingAs($this->user('apprenant'), 'sanctum')->postJson('/api/formateur/cours', $body)->assertForbidden();
        $this->actingAs($f, 'sanctum')->putJson("/api/formateur/cours/{$c->id}", $body)->assertOk()->assertJsonPath('prix', 20000);
        $this->actingAs($autre, 'sanctum')->deleteJson("/api/formateur/lecons/{$c->modules[0]->lecons[0]->id}")->assertForbidden();
    }

    public function test_publication_exige_une_lecon_et_les_videos_doivent_etre_youtube_ou_vimeo(): void
    {
        $f = $this->user('formateur');
        $body = ['titre' => 'Cours vide de test', 'resume' => 'Un résumé suffisamment long', 'description' => 'Une description suffisamment longue pour être valide.', 'categorie' => 'Design', 'niveau' => 'debutant', 'prix' => 0];
        $id = $this->actingAs($f, 'sanctum')->postJson('/api/formateur/cours', $body)->assertCreated()->json('id');
        $this->actingAs($f, 'sanctum')->postJson("/api/formateur/cours/{$id}/publier", ['publie' => true])->assertStatus(422);
        $m = $this->actingAs($f, 'sanctum')->postJson("/api/formateur/cours/{$id}/modules", ['titre' => 'Module'])->assertCreated()->json('id');
        $this->actingAs($f, 'sanctum')->postJson("/api/formateur/modules/{$m}/lecons", ['titre' => 'Vidéo', 'type' => 'video', 'contenu' => 'https://exemple.com/video.mp4', 'duree' => 5])->assertStatus(422);
        $this->actingAs($f, 'sanctum')->postJson("/api/formateur/modules/{$m}/lecons", ['titre' => 'Vidéo', 'type' => 'video', 'contenu' => 'https://www.youtube.com/watch?v=kUMe1FH4CHE', 'duree' => 5])->assertCreated();
        $this->actingAs($f, 'sanctum')->postJson("/api/formateur/cours/{$id}/publier", ['publie' => true])->assertOk()->assertJsonPath('publie', true);
    }

    public function test_validation_d_un_quiz_de_formateur(): void
    {
        $f = $this->user('formateur');
        $c = $this->cours($f);
        $m = $c->modules[0];
        $this->actingAs($f, 'sanctum')->putJson("/api/formateur/modules/{$m->id}/quiz", ['titre' => 'Quiz', 'questions' => [['question' => 'Q ?', 'choix' => ['A', 'B'], 'bonne' => 5]]])->assertStatus(422);
        $this->actingAs($f, 'sanctum')->putJson("/api/formateur/modules/{$m->id}/quiz", ['titre' => 'Nouveau quiz', 'questions' => [['question' => 'Q ?', 'choix' => ['A', 'B'], 'bonne' => 1]]])->assertOk();
        $this->assertSame(1, $m->fresh()->quiz->questions()->count());
    }
}
