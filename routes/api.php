<?php

use App\Http\Controllers\ApprentissageController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CatalogueController;
use App\Http\Controllers\FormateurController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

// Public (la connexion est facultative : elle ajoute « mon inscription »)
Route::get('/categories', [CatalogueController::class, 'categories']);
Route::get('/catalogue', [CatalogueController::class, 'index']);
Route::get('/cours/{slug}', [CatalogueController::class, 'show']);
Route::get('/certificats/{numero}', [ApprentissageController::class, 'verifier']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // Apprenant
    Route::post('/cours/{cours}/inscription', [ApprentissageController::class, 'inscrire'])->whereNumber('cours')->middleware('throttle:20,1');
    Route::get('/mes-cours', [ApprentissageController::class, 'mesCours']);
    Route::get('/apprendre/{slug}', [ApprentissageController::class, 'lecteur']);
    Route::post('/lecons/{lecon}/terminer', [ApprentissageController::class, 'terminerLecon']);
    Route::get('/quiz/{quiz}', [ApprentissageController::class, 'quiz']);
    Route::post('/quiz/{quiz}/repondre', [ApprentissageController::class, 'repondre']);
    Route::get('/inscriptions/{inscription}/certificat', [ApprentissageController::class, 'certificat']);
    Route::post('/cours/{cours}/avis', [ApprentissageController::class, 'noter'])->whereNumber('cours');

    // Formateur
    Route::prefix('formateur')->group(function () {
        Route::get('/cours', [FormateurController::class, 'index']);
        Route::get('/stats', [FormateurController::class, 'stats']);
        Route::post('/cours', [FormateurController::class, 'creer']);
        Route::get('/cours/{cours}', [FormateurController::class, 'detail']);
        Route::put('/cours/{cours}', [FormateurController::class, 'modifier']);
        Route::delete('/cours/{cours}', [FormateurController::class, 'supprimer']);
        Route::post('/cours/{cours}/publier', [FormateurController::class, 'publier']);
        Route::post('/cours/{cours}/modules', [FormateurController::class, 'ajouterModule']);
        Route::put('/modules/{module}', [FormateurController::class, 'majModule']);
        Route::delete('/modules/{module}', [FormateurController::class, 'supprimerModule']);
        Route::post('/modules/{module}/lecons', [FormateurController::class, 'ajouterLecon']);
        Route::put('/lecons/{lecon}', [FormateurController::class, 'majLecon']);
        Route::delete('/lecons/{lecon}', [FormateurController::class, 'supprimerLecon']);
        Route::put('/modules/{module}/quiz', [FormateurController::class, 'definirQuiz']);
        Route::delete('/modules/{module}/quiz', [FormateurController::class, 'supprimerQuiz']);
    });
});
