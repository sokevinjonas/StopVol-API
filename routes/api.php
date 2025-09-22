<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\DeclarationController;
use App\Http\Controllers\Api\AdminController;

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

// Public routes (no authentication required)
Route::prefix('auth')->group(function () {
    Route::post('/send-otp', [AuthController::class, 'sendOtp']);
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('/resend-otp', [AuthController::class, 'resendOtp']);
});

// Protected routes (authentication required)
Route::middleware('auth:sanctum')->group(function () {
    
    // Auth routes
    Route::prefix('auth')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });

    // Profile routes
    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'show']);
        Route::post('/complete', [ProfileController::class, 'complete']);
        Route::put('/update', [ProfileController::class, 'update']);
        Route::post('/upload-document', [ProfileController::class, 'uploadDocument']);
    });

    // Declaration routes (for citizens)
    Route::prefix('declarations')->group(function () {
        Route::get('/', [DeclarationController::class, 'index']);
        Route::post('/', [DeclarationController::class, 'store']);
        Route::get('/{id}', [DeclarationController::class, 'show']);
        Route::post('/{id}/pictures', [DeclarationController::class, 'addPictures']);
        
        // Admin only routes for declaration management
        Route::middleware('admin-access')->group(function () {
            Route::put('/{id}/status', [DeclarationController::class, 'updateStatus']);
        });
    });

    // Admin routes (entity_admin role only)
    Route::prefix('admin')->middleware('admin-access')->group(function () {
        
        // Dashboard
        Route::get('/dashboard', [AdminController::class, 'dashboard']);
        
        // Declaration management
        Route::prefix('declarations')->group(function () {
            Route::get('/', [AdminController::class, 'declarations']);
            Route::get('/search', [AdminController::class, 'search']);
            Route::put('/{id}/status', [AdminController::class, 'updateDeclarationStatus']);
            Route::post('/{id}/notify', [AdminController::class, 'notifyOwner']);
        });
    });
});

// Health check route
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toISOString(),
        'service' => 'StopVol API',
        'version' => '1.0.0'
    ]);
});

// API Documentation route
Route::get('/docs', function () {
    return response()->json([
        'service' => 'StopVol API',
        'version' => '1.0.0',
        'description' => 'API pour la déclaration et le suivi des vols d\'engins',
        'endpoints' => [
            'auth' => [
                'POST /api/auth/send-otp' => 'Envoyer un code OTP',
                'POST /api/auth/verify-otp' => 'Vérifier le code OTP et s\'authentifier',
                'POST /api/auth/resend-otp' => 'Renvoyer un code OTP',
                'GET /api/auth/me' => 'Obtenir les informations de l\'utilisateur connecté',
                'POST /api/auth/logout' => 'Se déconnecter'
            ],
            'profile' => [
                'GET /api/profile' => 'Obtenir le profil utilisateur',
                'POST /api/profile/complete' => 'Compléter le profil utilisateur',
                'PUT /api/profile/update' => 'Mettre à jour le profil',
                'POST /api/profile/upload-document' => 'Uploader un document'
            ],
            'declarations' => [
                'GET /api/declarations' => 'Lister les déclarations de l\'utilisateur',
                'POST /api/declarations' => 'Créer une nouvelle déclaration',
                'GET /api/declarations/{id}' => 'Obtenir une déclaration spécifique',
                'POST /api/declarations/{id}/pictures' => 'Ajouter des photos à une déclaration'
            ],
            'admin' => [
                'GET /api/admin/dashboard' => 'Tableau de bord admin',
                'GET /api/admin/declarations' => 'Lister toutes les déclarations',
                'GET /api/admin/declarations/search' => 'Rechercher des déclarations',
                'PUT /api/admin/declarations/{id}/status' => 'Mettre à jour le statut d\'une déclaration',
                'POST /api/admin/declarations/{id}/notify' => 'Notifier le propriétaire'
            ]
        ],
        'authentication' => 'Bearer Token (Laravel Sanctum)',
        'content_type' => 'application/json'
    ]);
});
