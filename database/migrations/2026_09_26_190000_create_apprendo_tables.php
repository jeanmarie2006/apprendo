<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('bio', 300)->nullable();
        });

        Schema::create('cours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('formateur_id')->constrained('users')->cascadeOnDelete();
            $table->string('titre', 140);
            $table->string('slug', 160)->unique();
            $table->string('resume', 200);
            $table->text('description');
            $table->string('categorie', 40)->index();
            $table->string('niveau', 15)->default('debutant');     // debutant | intermediaire | avance
            $table->unsignedInteger('prix')->default(0);           // FCFA, 0 = gratuit
            $table->boolean('publie')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cours_id')->constrained('cours')->cascadeOnDelete();
            $table->string('titre', 140);
            $table->unsignedSmallInteger('ordre')->default(0);
        });

        Schema::create('lecons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained('modules')->cascadeOnDelete();
            $table->string('titre', 140);
            $table->string('type', 6)->default('texte');           // video | texte
            $table->text('contenu');                               // adresse YouTube/Vimeo, ou texte de la leçon
            $table->unsignedSmallInteger('duree')->default(5);     // minutes
            $table->boolean('apercu')->default(false);             // leçon visible avant l'inscription
            $table->unsignedSmallInteger('ordre')->default(0);
        });

        Schema::create('quiz', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->unique()->constrained('modules')->cascadeOnDelete();
            $table->string('titre', 140);
        });

        Schema::create('quiz_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained('quiz')->cascadeOnDelete();
            $table->string('question', 300);
            $table->text('choix');                                 // JSON : liste de propositions
            $table->unsignedTinyInteger('bonne');                  // index de la bonne réponse
        });

        Schema::create('inscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cours_id')->constrained('cours')->cascadeOnDelete();
            $table->foreignId('apprenant_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('prix_paye')->default(0);
            $table->string('paiement_mode', 10)->nullable();      // momo | moov
            $table->string('paiement_ref', 24)->nullable();
            $table->timestamp('termine_le')->nullable();
            $table->string('certificat', 24)->nullable()->unique();
            $table->timestamps();
            $table->unique(['cours_id', 'apprenant_id']);
        });

        Schema::create('progressions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inscription_id')->constrained('inscriptions')->cascadeOnDelete();
            $table->foreignId('lecon_id')->constrained('lecons')->cascadeOnDelete();
            $table->timestamp('fait_le')->useCurrent();
            $table->unique(['inscription_id', 'lecon_id']);
        });

        Schema::create('tentatives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inscription_id')->constrained('inscriptions')->cascadeOnDelete();
            $table->foreignId('quiz_id')->constrained('quiz')->cascadeOnDelete();
            $table->unsignedSmallInteger('score');
            $table->unsignedSmallInteger('total');
            $table->boolean('reussi')->default(false);
            $table->timestamps();
        });

        Schema::create('avis_cours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cours_id')->constrained('cours')->cascadeOnDelete();
            $table->foreignId('apprenant_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('note');
            $table->string('commentaire', 400)->nullable();
            $table->timestamps();
            $table->unique(['cours_id', 'apprenant_id']);
        });
    }

    public function down(): void
    {
        foreach (['avis_cours', 'tentatives', 'progressions', 'inscriptions', 'quiz_questions', 'quiz', 'lecons', 'modules', 'cours'] as $t) {
            Schema::dropIfExists($t);
        }
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('bio'));
    }
};
