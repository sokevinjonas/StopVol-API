<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Domains\Declaration\Services\DeclarationService;
use App\Domains\User\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class DeclarationController extends Controller
{
    public function __construct(
        private DeclarationService $declarationService,
        private UserService $userService
    ) {}

    /**
     * Create a new declaration
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'plate_number' => 'nullable|string|max:20',
                'chassis_number' => 'nullable|string|max:50',
                'card_number' => 'nullable|string|max:50',
                'brand' => 'required|string|max:100',
                'model' => 'required|string|max:100',
                'color' => 'required|string|max:50',
                'theft_date' => 'required|date|before_or_equal:today',
                'theft_location' => 'required|string|max:255',
                'pictures' => 'nullable|array|max:5',
                'pictures.*' => 'image|mimes:jpeg,png,jpg|max:5120'
            ]);

            $user = $request->user();

            // Check if user can create declaration
            if (!$user->canCreateDeclaration()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous devez compléter votre profil avant de créer une déclaration'
                ], 403);
            }

            // Prepare declaration data
            $declarationData = $request->only([
                'plate_number', 'chassis_number', 'card_number',
                'brand', 'model', 'color', 'theft_date', 'theft_location'
            ]);

            // Handle picture uploads
            $pictures = [];
            if ($request->hasFile('pictures')) {
                $pictures = $request->file('pictures');
            }

            // Create declaration using DeclarationService
            $declaration = $this->declarationService->createDeclaration(
                $user->id,
                $declarationData,
                $pictures
            );

            return response()->json([
                'success' => true,
                'message' => 'Déclaration créée avec succès',
                'data' => [
                    'id' => $declaration->id,
                    'plate_number' => $declaration->plate_number,
                    'chassis_number' => $declaration->chassis_number,
                    'card_number' => $declaration->card_number,
                    'brand' => $declaration->brand,
                    'model' => $declaration->model,
                    'color' => $declaration->color,
                    'theft_date' => $declaration->theft_date,
                    'theft_location' => $declaration->theft_location,
                    'status' => $declaration->status,
                    'pictures' => $declaration->pictures,
                    'created_at' => $declaration->created_at
                ]
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la déclaration: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's declarations
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $declarations = $this->declarationService->getUserDeclarations($user->id);

            return response()->json([
                'success' => true,
                'data' => array_map(function ($declaration) {
                    return [
                        'id' => $declaration['id'],
                        'plate_number' => $declaration['plate_number'],
                        'chassis_number' => $declaration['chassis_number'],
                        'card_number' => $declaration['card_number'],
                        'brand' => $declaration['brand'],
                        'model' => $declaration['model'],
                        'color' => $declaration['color'],
                        'theft_date' => $declaration['theft_date'],
                        'theft_location' => $declaration['theft_location'],
                        'status' => $declaration['status'],
                        'pictures' => $declaration['pictures'],
                        'created_at' => $declaration['created_at'],
                        'updated_at' => $declaration['updated_at']
                    ];
                }, $declarations)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des déclarations'
            ], 500);
        }
    }

    /**
     * Get a specific declaration
     */
    public function show(Request $request, string $id): JsonResponse
    {
        try {
            $user = $request->user();
            $declaration = $this->declarationService->getDeclarationById($id);

            if (!$declaration) {
                return response()->json([
                    'success' => false,
                    'message' => 'Déclaration non trouvée'
                ], 404);
            }

            // Check if user owns this declaration or is admin
            if ($declaration['user_id'] !== $user->id && $user->role !== 'entity_admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé'
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $declaration['id'],
                    'user_id' => $declaration['user_id'],
                    'plate_number' => $declaration['plate_number'],
                    'chassis_number' => $declaration['chassis_number'],
                    'card_number' => $declaration['card_number'],
                    'brand' => $declaration['brand'],
                    'model' => $declaration['model'],
                    'color' => $declaration['color'],
                    'theft_date' => $declaration['theft_date'],
                    'theft_location' => $declaration['theft_location'],
                    'status' => $declaration['status'],
                    'pictures' => $declaration['pictures'],
                    'created_at' => $declaration['created_at'],
                    'updated_at' => $declaration['updated_at']
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de la déclaration'
            ], 500);
        }
    }

    /**
     * Update declaration status (for admins)
     */
    public function updateStatus(Request $request, string $id): JsonResponse
    {
        try {
            $request->validate([
                'status' => 'required|in:pending,found,closed'
            ]);

            $user = $request->user();

            // Check if user is admin
            if ($user->role !== 'entity_admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé'
                ], 403);
            }

            $declaration = $this->declarationService->getDeclarationById($id);

            if (!$declaration) {
                return response()->json([
                    'success' => false,
                    'message' => 'Déclaration non trouvée'
                ], 404);
            }

            // Update status
            $updatedDeclaration = $this->declarationService->updateDeclarationStatus(
                $id,
                $request->status
            );

            return response()->json([
                'success' => true,
                'message' => 'Statut de la déclaration mis à jour',
                'data' => [
                    'id' => $updatedDeclaration['id'],
                    'status' => $updatedDeclaration['status'],
                    'updated_at' => $updatedDeclaration['updated_at']
                ]
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du statut'
            ], 500);
        }
    }

    /**
     * Add pictures to existing declaration
     */
    public function addPictures(Request $request, string $id): JsonResponse
    {
        try {
            $request->validate([
                'pictures' => 'required|array|max:5',
                'pictures.*' => 'image|mimes:jpeg,png,jpg|max:5120'
            ]);

            $user = $request->user();
            $declaration = $this->declarationService->getDeclarationById($id);

            if (!$declaration) {
                return response()->json([
                    'success' => false,
                    'message' => 'Déclaration non trouvée'
                ], 404);
            }

            // Check if user owns this declaration
            if ($declaration['user_id'] !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé'
                ], 403);
            }

            $pictures = $request->file('pictures');
            $updatedDeclaration = $this->declarationService->addPicturesToDeclaration($id, $pictures);

            return response()->json([
                'success' => true,
                'message' => 'Photos ajoutées avec succès',
                'data' => [
                    'id' => $updatedDeclaration['id'],
                    'pictures' => $updatedDeclaration['pictures']
                ]
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'ajout des photos'
            ], 500);
        }
    }
}
