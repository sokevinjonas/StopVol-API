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
     * @OA\Post(
     *     path="/api/declarations",
     *     tags={"Declarations"},
     *     summary="Créer une déclaration de vol",
     *     description="Permet à l'utilisateur connecté de créer une déclaration de moto volée",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"brand","model","color","theft_date","theft_location"},
     *                 @OA\Property(property="plate_number", type="string", example="1234AB56"),
     *                 @OA\Property(property="chassis_number", type="string", example="CHS123456789"),
     *                 @OA\Property(property="card_number", type="string", example="CAR123456"),
     *                 @OA\Property(property="brand", type="string", example="Honda"),
     *                 @OA\Property(property="model", type="string", example="CBR500R"),
     *                 @OA\Property(property="color", type="string", example="Rouge"),
     *                 @OA\Property(property="theft_date", type="string", format="date", example="2025-09-21"),
     *                 @OA\Property(property="theft_location", type="string", example="Secteur 15, Ouagadougou"),
     *                 @OA\Property(property="pictures", type="array", @OA\Items(type="string", format="binary"), description="Photos de la moto volée (max 5)")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Déclaration créée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Déclaration créée avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="string", example="1"),
     *                 @OA\Property(property="plate_number", type="string", example="1234AB56"),
     *                 @OA\Property(property="chassis_number", type="string", example="CHS123456789"),
     *                 @OA\Property(property="card_number", type="string", example="CAR123456"),
     *                 @OA\Property(property="brand", type="string", example="Honda"),
     *                 @OA\Property(property="model", type="string", example="CBR500R"),
     *                 @OA\Property(property="color", type="string", example="Rouge"),
     *                 @OA\Property(property="theft_date", type="string", format="date", example="2025-09-21"),
     *                 @OA\Property(property="theft_location", type="string", example="Secteur 15, Ouagadougou"),
     *                 @OA\Property(property="status", type="string", example="pending"),
     *                 @OA\Property(property="pictures", type="array", @OA\Items(type="string")),
     *                 @OA\Property(property="created_at", type="string", format="datetime")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Données invalides"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Utilisateur non autorisé à créer une déclaration",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Vous devez compléter votre profil avant de créer une déclaration")
     *         )
     *     )
     * )
     */
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
     * @OA\Get(
     *     path="/api/declarations",
     *     tags={"Declarations"},
     *     summary="Lister les déclarations de l'utilisateur",
     *     description="Retourne toutes les déclarations de l'utilisateur connecté",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Liste des déclarations",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="string"),
     *                     @OA\Property(property="plate_number", type="string"),
     *                     @OA\Property(property="chassis_number", type="string"),
     *                     @OA\Property(property="card_number", type="string"),
     *                     @OA\Property(property="brand", type="string"),
     *                     @OA\Property(property="model", type="string"),
     *                     @OA\Property(property="color", type="string"),
     *                     @OA\Property(property="theft_date", type="string", format="date"),
     *                     @OA\Property(property="theft_location", type="string"),
     *                     @OA\Property(property="status", type="string"),
     *                     @OA\Property(property="pictures", type="array", @OA\Items(type="string")),
     *                     @OA\Property(property="created_at", type="string", format="datetime"),
     *                     @OA\Property(property="updated_at", type="string", format="datetime")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
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
     * @OA\Get(
     *     path="/api/declarations/{id}",
     *     tags={"Declarations"},
     *     summary="Voir une déclaration spécifique",
     *     description="Retourne les détails d'une déclaration par ID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response=200, description="Détails de la déclaration"),
     *     @OA\Response(response=403, description="Accès non autorisé"),
     *     @OA\Response(response=404, description="Déclaration non trouvée")
     * )
     */

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
     * @OA\Put(
     *     path="/api/declarations/{id}/status",
     *     tags={"Declarations"},
     *     summary="Mettre à jour le statut d'une déclaration",
     *     description="Permet à un administrateur d'entité de mettre à jour le statut",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", enum={"pending","found","closed"}, example="pending")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Statut mis à jour"),
     *     @OA\Response(response=403, description="Accès non autorisé"),
     *     @OA\Response(response=404, description="Déclaration non trouvée")
     * )
     */

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
     * @OA\Post(
     *     path="/api/declarations/{id}/pictures",
     *     tags={"Declarations"},
     *     summary="Ajouter des photos à une déclaration",
     *     description="Permet à l'utilisateur de compléter sa déclaration avec de nouvelles photos",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"pictures"},
     *                 @OA\Property(property="pictures", type="array", @OA\Items(type="string", format="binary"), description="Photos à ajouter (max 5)")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Photos ajoutées avec succès"),
     *     @OA\Response(response=403, description="Accès non autorisé"),
     *     @OA\Response(response=404, description="Déclaration non trouvée"),
     *     @OA\Response(response=422, description="Erreur de validation")
     * )
     */

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
