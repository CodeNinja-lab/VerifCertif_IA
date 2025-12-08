<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Routes publiques
Route::prefix('v1')->group(function () {
    // Authentification publique avec rate limiting
    Route::post('/auth/register', [App\Http\Controllers\Api\V1\AuthController::class, 'register'])
        ->middleware('throttle:5,1'); // 5 tentatives par minute
    Route::post('/auth/register-admin', [App\Http\Controllers\Api\V1\AuthController::class, 'registerAdmin'])
        ->middleware('throttle:5,1'); // 5 tentatives par minute
    Route::post('/auth/login', [App\Http\Controllers\Api\V1\AuthController::class, 'login'])
        ->middleware('throttle:5,1'); // 5 tentatives par minute
    Route::post('/auth/forgot-password', [App\Http\Controllers\Api\V1\AuthController::class, 'forgotPassword'])
        ->middleware('throttle:3,1'); // 3 tentatives par minute
    Route::post('/auth/reset-password', [App\Http\Controllers\Api\V1\AuthController::class, 'resetPassword'])
        ->middleware('throttle:3,1'); // 3 tentatives par minute
    
    // Vérification de documents (publique)
    Route::post('/documents/verify', [App\Http\Controllers\Api\V1\DocumentController::class, 'verify']); // UUID, hash ou fichier
    Route::get('/documents/{uuid}/verify', [App\Http\Controllers\Api\V1\DocumentController::class, 'verifyByUuid']); // Par UUID
    Route::get('/documents/{uuid}/qr-code', [App\Http\Controllers\Api\V1\DocumentController::class, 'getQrCode']); // QR code
    Route::get('/verify/{uuid}', [App\Http\Controllers\Api\V1\DocumentController::class, 'verifyByUuid']); // URL courte pour QR code
    
    // Compétences publiques
    Route::get('/competences', [App\Http\Controllers\Api\V1\CompetenceController::class, 'index']);
    Route::get('/competences/{id}', [App\Http\Controllers\Api\V1\CompetenceController::class, 'show']);
    Route::get('/competences/search/{query}', [App\Http\Controllers\Api\V1\CompetenceController::class, 'search']);
    
    // Offres publiques (liste uniquement)
    Route::get('/offres', [App\Http\Controllers\Api\V1\OffreController::class, 'index']);
    Route::get('/offres/{id}', [App\Http\Controllers\Api\V1\OffreController::class, 'show'])->where('id', '[0-9]+');
    
    // Administrations publiques
    Route::get('/administrations', [App\Http\Controllers\Api\V1\AdministrationController::class, 'index']);
    Route::get('/administrations/{id}', [App\Http\Controllers\Api\V1\AdministrationController::class, 'show']);
});

// Routes authentifiées
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    // Authentification
    Route::post('/auth/logout', [App\Http\Controllers\Api\V1\AuthController::class, 'logout']);
    Route::get('/auth/me', [App\Http\Controllers\Api\V1\AuthController::class, 'me']);
    Route::put('/auth/profile', [App\Http\Controllers\Api\V1\AuthController::class, 'updateProfile']);
    Route::post('/auth/change-password', [App\Http\Controllers\Api\V1\AuthController::class, 'changePassword']);

    // Codes d'accès admin (génération par les admins / universités)
    Route::prefix('admin-access-codes')->middleware('role:admin')->group(function () {
        Route::post('/', [App\Http\Controllers\Api\V1\AdminAccessCodeController::class, 'store']);
    });
    
    // Utilisateurs (admin uniquement)
    Route::middleware('role:admin')->prefix('users')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\V1\UserController::class, 'index']);
        Route::get('/{id}', [App\Http\Controllers\Api\V1\UserController::class, 'show']);
        Route::post('/', [App\Http\Controllers\Api\V1\UserController::class, 'store']);
        Route::put('/{id}', [App\Http\Controllers\Api\V1\UserController::class, 'update']);
        Route::delete('/{id}', [App\Http\Controllers\Api\V1\UserController::class, 'destroy']);
        Route::post('/{id}/activate', [App\Http\Controllers\Api\V1\UserController::class, 'activate']);
        Route::post('/{id}/deactivate', [App\Http\Controllers\Api\V1\UserController::class, 'deactivate']);
    });
    
    // Documents
    Route::prefix('documents')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\V1\DocumentController::class, 'index']);
        Route::post('/', [App\Http\Controllers\Api\V1\DocumentController::class, 'store']);
        Route::get('/{id}', [App\Http\Controllers\Api\V1\DocumentController::class, 'show']);
        Route::put('/{id}', [App\Http\Controllers\Api\V1\DocumentController::class, 'update']);
        Route::delete('/{id}', [App\Http\Controllers\Api\V1\DocumentController::class, 'destroy']);
        Route::get('/{id}/download', [App\Http\Controllers\Api\V1\DocumentController::class, 'download']);
        Route::get('/{id}/logs', [App\Http\Controllers\Api\V1\DocumentController::class, 'getVerificationLogs']);
        Route::post('/{id}/revoke', [App\Http\Controllers\Api\V1\DocumentController::class, 'revoke'])->middleware('role:administration,admin');
    });
    
    // Administrations
    Route::prefix('administrations')->middleware('role:admin')->group(function () {
        Route::post('/', [App\Http\Controllers\Api\V1\AdministrationController::class, 'store']);
        Route::put('/{id}', [App\Http\Controllers\Api\V1\AdministrationController::class, 'update']);
        Route::delete('/{id}', [App\Http\Controllers\Api\V1\AdministrationController::class, 'destroy']);
        Route::post('/{id}/verify', [App\Http\Controllers\Api\V1\AdministrationController::class, 'verify']);
        Route::post('/{id}/suspend', [App\Http\Controllers\Api\V1\AdministrationController::class, 'suspend']);
    });
    
    // Compétences (CRUD pour admin)
    Route::prefix('competences')->middleware('role:admin')->group(function () {
        Route::post('/', [App\Http\Controllers\Api\V1\CompetenceController::class, 'store']);
        Route::put('/{id}', [App\Http\Controllers\Api\V1\CompetenceController::class, 'update']);
        Route::delete('/{id}', [App\Http\Controllers\Api\V1\CompetenceController::class, 'destroy']);
    });
    
    // Profil étudiant
    Route::prefix('profil-etudiant')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\V1\ProfilEtudiantController::class, 'show']);
        Route::post('/', [App\Http\Controllers\Api\V1\ProfilEtudiantController::class, 'store']);
        Route::put('/', [App\Http\Controllers\Api\V1\ProfilEtudiantController::class, 'update']);
        Route::delete('/', [App\Http\Controllers\Api\V1\ProfilEtudiantController::class, 'destroy']);
        
        // Compétences du profil
        Route::get('/competences', [App\Http\Controllers\Api\V1\ProfilEtudiantController::class, 'getCompetences']);
        Route::post('/competences', [App\Http\Controllers\Api\V1\ProfilEtudiantController::class, 'addCompetence']);
        Route::put('/competences/{competenceId}', [App\Http\Controllers\Api\V1\ProfilEtudiantController::class, 'updateCompetence']);
        Route::delete('/competences/{competenceId}', [App\Http\Controllers\Api\V1\ProfilEtudiantController::class, 'removeCompetence']);
    });
    
    // Offres d'emploi
    Route::prefix('offres')->group(function () {
        // Route pour les offres du recruteur (authentifiée) - DOIT être AVANT les routes avec {id}
        Route::get('/my-offres', [App\Http\Controllers\Api\V1\OffreController::class, 'myOffres'])->middleware('role:recruteur,admin');
        
        Route::post('/', [App\Http\Controllers\Api\V1\OffreController::class, 'store'])->middleware('role:recruteur,admin');
        
        // Routes avec paramètres {id} - DOIVENT être APRÈS les routes spécifiques
        Route::put('/{id}', [App\Http\Controllers\Api\V1\OffreController::class, 'update'])->middleware('role:recruteur,admin')->where('id', '[0-9]+');
        Route::delete('/{id}', [App\Http\Controllers\Api\V1\OffreController::class, 'destroy'])->middleware('role:recruteur,admin')->where('id', '[0-9]+');
        Route::post('/{id}/publish', [App\Http\Controllers\Api\V1\OffreController::class, 'publish'])->middleware('role:recruteur,admin')->where('id', '[0-9]+');
        Route::post('/{id}/archive', [App\Http\Controllers\Api\V1\OffreController::class, 'archive'])->middleware('role:recruteur,admin')->where('id', '[0-9]+');
        
        // Compétences de l'offre
        Route::get('/{id}/competences', [App\Http\Controllers\Api\V1\OffreController::class, 'getCompetences'])->where('id', '[0-9]+');
        Route::post('/{id}/competences', [App\Http\Controllers\Api\V1\OffreController::class, 'addCompetence'])->where('id', '[0-9]+');
        Route::put('/{id}/competences/{competenceId}', [App\Http\Controllers\Api\V1\OffreController::class, 'updateCompetence'])->where(['id' => '[0-9]+', 'competenceId' => '[0-9]+']);
        Route::delete('/{id}/competences/{competenceId}', [App\Http\Controllers\Api\V1\OffreController::class, 'removeCompetence'])->where(['id' => '[0-9]+', 'competenceId' => '[0-9]+']);
        
        // Matchings pour recruteurs
        Route::get('/{id}/matchings', [App\Http\Controllers\Api\V1\OffreController::class, 'getMatchings'])->middleware('role:recruteur,admin')->where('id', '[0-9]+');
    });
    
    // Matchings (pour étudiants)
    Route::prefix('matchings')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\V1\MatchingController::class, 'index']);
        Route::get('/{id}', [App\Http\Controllers\Api\V1\MatchingController::class, 'show']);
        Route::post('/{id}/view', [App\Http\Controllers\Api\V1\MatchingController::class, 'markAsViewed']);
        Route::post('/{id}/interest', [App\Http\Controllers\Api\V1\MatchingController::class, 'setInterest']);
        Route::post('/calculate', [App\Http\Controllers\Api\V1\MatchingController::class, 'calculate'])->middleware('role:admin');
    });
    
    // Notifications
    Route::prefix('notifications')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\V1\NotificationController::class, 'index']);
        Route::get('/unread', [App\Http\Controllers\Api\V1\NotificationController::class, 'unread']);
        Route::get('/{id}', [App\Http\Controllers\Api\V1\NotificationController::class, 'show']);
        Route::post('/{id}/read', [App\Http\Controllers\Api\V1\NotificationController::class, 'markAsRead']);
        Route::post('/read-all', [App\Http\Controllers\Api\V1\NotificationController::class, 'markAllAsRead']);
        Route::post('/{id}/archive', [App\Http\Controllers\Api\V1\NotificationController::class, 'archive']);
        Route::delete('/{id}', [App\Http\Controllers\Api\V1\NotificationController::class, 'destroy']);
    });
    
    // IA - Génération de contenu
    Route::prefix('ai')->middleware('role:recruteur,admin')->group(function () {
        Route::post('/generate-job-content', [App\Http\Controllers\Api\V1\AIController::class, 'generateJobContent']);
    });

    // Messages
    Route::prefix('messages')->group(function () {
        Route::get('/conversations', [App\Http\Controllers\Api\V1\MessageController::class, 'conversations'])->middleware('role:recruteur,admin');
        Route::get('/conversation/{id}', [App\Http\Controllers\Api\V1\MessageController::class, 'getConversation']);
        Route::get('/conversation/{etudiantId}/create', [App\Http\Controllers\Api\V1\MessageController::class, 'getOrCreateConversation'])->middleware('role:recruteur,admin');
        Route::get('/conversation/{etudiantId}/{offreId}/create', [App\Http\Controllers\Api\V1\MessageController::class, 'getOrCreateConversation'])->middleware('role:recruteur,admin');
        Route::post('/conversation/{conversationId}/send', [App\Http\Controllers\Api\V1\MessageController::class, 'sendMessage']);
        Route::post('/conversation/{conversationId}/read', [App\Http\Controllers\Api\V1\MessageController::class, 'markAsRead']);
    });
    
    // Vérification logs (lecture seule)
    Route::prefix('verification-logs')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\V1\VerificationLogController::class, 'index']);
        Route::get('/{id}', [App\Http\Controllers\Api\V1\VerificationLogController::class, 'show']);
    });
    
    // Révocations
    Route::prefix('revocations')->middleware('role:administration,admin')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\V1\RevocationController::class, 'index']);
        Route::get('/{id}', [App\Http\Controllers\Api\V1\RevocationController::class, 'show']);
        Route::post('/', [App\Http\Controllers\Api\V1\RevocationController::class, 'store']);
    });
    
    // Audit logs (admin uniquement)
    Route::prefix('audit-logs')->middleware('role:admin')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\V1\AuditLogController::class, 'index']);
        Route::get('/{id}', [App\Http\Controllers\Api\V1\AuditLogController::class, 'show']);
    });
    
    // Statistiques (admin uniquement)
    Route::prefix('statistics')->middleware('role:admin')->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\Api\V1\StatisticsController::class, 'dashboard']);
        Route::get('/documents', [App\Http\Controllers\Api\V1\StatisticsController::class, 'documents']);
        Route::get('/matchings', [App\Http\Controllers\Api\V1\StatisticsController::class, 'matchings']);
        Route::get('/users', [App\Http\Controllers\Api\V1\StatisticsController::class, 'users']);
    });
    
    // Statistiques recruteur
    Route::prefix('recruiters/statistics')->middleware('role:recruteur,admin')->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\Api\V1\RecruiterStatisticsController::class, 'dashboard']);
        Route::get('/offres', [App\Http\Controllers\Api\V1\RecruiterStatisticsController::class, 'offres']);
        Route::get('/candidates', [App\Http\Controllers\Api\V1\RecruiterStatisticsController::class, 'candidates']);
    });
});

