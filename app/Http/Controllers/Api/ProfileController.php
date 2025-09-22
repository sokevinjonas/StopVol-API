<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Domains\User\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function __construct(
        private UserService $userService
    ) {}

    /**
     * @OA\Post(
     *     path="/api/profile/complete",
     *     tags={"Profile"},
     *     summary="Compléter le profil utilisateur",
     *     description="Complète le profil utilisateur avec les informations personnelles et documents requis",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"name", "city", "district", "id_type"},
     *                 @OA\Property(property="name", type="string", example="John Doe", description="Nom complet"),
     *                 @OA\Property(property="city", type="string", example="Ouagadougou", description="Ville de résidence"),
     *                 @OA\Property(property="district", type="string", example="Secteur 15", description="Quartier/Secteur"),
     *                 @OA\Property(property="id_type", type="string", enum={"cnib", "permis", "passeport"}, example="cnib", description="Type de document d'identité"),
     *                 @OA\Property(property="profile_picture", type="string", format="binary", description="Photo de profil"),
     *                 @OA\Property(property="id_card_front", type="string", format="binary", description="Recto du document d'identité"),
     *                 @OA\Property(property="id_card_back", type="string", format="binary", description="Verso du document d'identité")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Profil complété avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Profil complété avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="profile_status", type="string", example="pending_validation"),
     *                 @OA\Property(property="profile_complete", type="boolean", example=true)
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
     *         response=400,
     *         description="Profil déjà complété",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Le profil est déjà complété")
     *         )
     *     )
     * )
     */
    public function complete(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'city' => 'required|string|max:255',
                'district' => 'required|string|max:255',
                'id_type' => 'required|in:cnib,permis,passeport',
                'profile_picture' => 'required|image|mimes:jpeg,png,jpg|max:5120', // 5MB
                'id_card_front' => 'required|image|mimes:jpeg,png,jpg,pdf|max:5120',
                'id_card_back' => 'nullable|image|mimes:jpeg,png,jpg,pdf|max:5120'
            ]);

            $user = $request->user();

            // Prepare data for profile completion
            $profileData = [
                'name' => $request->name,
                'city' => $request->city,
                'district' => $request->district,
                'id_type' => $request->id_type
            ];

            // Handle file uploads
            $files = [];
            if ($request->hasFile('profile_picture')) {
                $files['profile_picture'] = $request->file('profile_picture');
            }
            if ($request->hasFile('id_card_front')) {
                $files['id_card_front'] = $request->file('id_card_front');
            }
            if ($request->hasFile('id_card_back')) {
                $files['id_card_back'] = $request->file('id_card_back');
            }

            // Complete profile using UserService
            $updatedUser = $this->userService->completeProfile($user, $profileData, $files);

            return response()->json([
                'success' => true,
                'message' => 'Profil complété avec succès',
                'data' => [
                    'id' => $updatedUser->id,
                    'name' => $updatedUser->name,
                    'phone' => $updatedUser->phone,
                    'city' => $updatedUser->city,
                    'district' => $updatedUser->district,
                    'id_type' => $updatedUser->id_type,
                    'profile_status' => $updatedUser->profile_status,
                    'profile_complete' => $updatedUser->isProfileComplete(),
                    'can_create_declaration' => $updatedUser->canCreateDeclaration()
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
                'message' => 'Erreur lors de la completion du profil: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/profile/update",
     *     tags={"Profile"},
     *     summary="Mettre à jour le profil utilisateur",
     *     description="Met à jour les informations du profil utilisateur",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="John Doe Updated", description="Nom complet"),
     *             @OA\Property(property="city", type="string", example="Bobo-Dioulasso", description="Ville de résidence"),
     *             @OA\Property(property="district", type="string", example="Secteur 20", description="Quartier/Secteur")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Profil mis à jour avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Profil mis à jour avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="name", type="string", example="John Doe Updated"),
     *                 @OA\Property(property="city", type="string", example="Bobo-Dioulasso"),
     *                 @OA\Property(property="district", type="string", example="Secteur 20")
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
     *     )
     * )
     */
    public function update(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'sometimes|string|max:255',
                'city' => 'sometimes|string|max:255',
                'district' => 'sometimes|string|max:255',
                'profile_picture' => 'sometimes|image|mimes:jpeg,png,jpg|max:5120'
            ]);

            $user = $request->user();

            // Prepare update data
            $updateData = $request->only(['name', 'city', 'district']);

            // Handle profile picture upload if provided
            $files = [];
            if ($request->hasFile('profile_picture')) {
                $files['profile_picture'] = $request->file('profile_picture');
            }

            // Update profile
            $updatedUser = $this->userService->updateProfile($user, $updateData, $files);

            return response()->json([
                'success' => true,
                'message' => 'Profil mis à jour avec succès',
                'data' => [
                    'id' => $updatedUser->id,
                    'name' => $updatedUser->name,
                    'phone' => $updatedUser->phone,
                    'city' => $updatedUser->city,
                    'district' => $updatedUser->district,
                    'profile_status' => $updatedUser->profile_status,
                    'profile_complete' => $updatedUser->isProfileComplete()
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
                'message' => 'Erreur lors de la mise à jour du profil: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/profile",
     *     tags={"Profile"},
     *     summary="Obtenir le profil utilisateur",
     *     description="Retourne les informations complètes du profil de l'utilisateur connecté",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Profil utilisateur",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="string", example="550e8400-e29b-41d4-a716-446655440000"),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="phone", type="string", example="+22670123456"),
     *                 @OA\Property(property="role", type="string", example="citizen"),
     *                 @OA\Property(property="profile_status", type="string", example="validated"),
     *                 @OA\Property(property="city", type="string", example="Ouagadougou"),
     *                 @OA\Property(property="district", type="string", example="Secteur 15"),
     *                 @OA\Property(property="id_type", type="string", example="cnib"),
     *                 @OA\Property(property="profile_picture", type="string", nullable=true),
     *                 @OA\Property(property="id_card_front", type="string", nullable=true),
     *                 @OA\Property(property="id_card_back", type="string", nullable=true),
     *                 @OA\Property(property="profile_complete", type="boolean", example=true),
     *                 @OA\Property(property="can_create_declaration", type="boolean", example=true),
     *                 @OA\Property(property="entity_id", type="string", nullable=true),
     *                 @OA\Property(property="created_at", type="string", format="datetime"),
     *                 @OA\Property(property="updated_at", type="string", format="datetime")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Non authentifié")
     *         )
     *     )
     * )
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'role' => $user->role,
                'city' => $user->city,
                'district' => $user->district,
                'id_type' => $user->id_type,
                'profile_status' => $user->profile_status,
                'profile_complete' => $user->isProfileComplete(),
                'can_create_declaration' => $user->canCreateDeclaration(),
                'profile_picture' => $user->profile_picture ? url('storage/' . $user->profile_picture) : null,
                'id_card_front' => $user->id_card_front ? url('storage/' . $user->id_card_front) : null,
                'id_card_back' => $user->id_card_back ? url('storage/' . $user->id_card_back) : null,
                'entity_id' => $user->entity_id,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at
            ]
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/profile/upload-document",
     *     tags={"Profile"},
     *     summary="Uploader un document",
     *     description="Upload un document (photo de profil ou pièce d'identité)",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"document", "document_type"},
     *                 @OA\Property(property="document", type="string", format="binary", description="Fichier à uploader"),
     *                 @OA\Property(property="document_type", type="string", enum={"profile_picture", "id_card_front", "id_card_back"}, example="profile_picture", description="Type de document")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Document uploadé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Document uploadé avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="document_type", type="string", example="profile_picture"),
     *                 @OA\Property(property="file_path", type="string", example="stopvol/user123/profile_picture.jpg"),
     *                 @OA\Property(property="file_url", type="string", example="http://localhost:8000/storage/stopvol/user123/profile_picture.jpg"),
     *                 @OA\Property(property="profile_complete", type="boolean", example=true)
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
     *     )
     * )
     */
    public function uploadDocument(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'document_type' => 'required|in:id_card_front,id_card_back,profile_picture',
                'document' => 'required|file|mimes:jpeg,png,jpg,pdf|max:5120'
            ]);

            $user = $request->user();
            $documentType = $request->document_type;
            $file = $request->file('document');

            // Upload document using UserService
            $filePath = $this->userService->uploadUserDocument($user, $documentType, $file);

            // Update user with new document path
            $updatedUser = $this->userService->updateUser($user, [
                $documentType => $filePath
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Document uploadé avec succès',
                'data' => [
                    'document_type' => $documentType,
                    'file_path' => $filePath,
                    'file_url' => url('storage/' . $filePath),
                    'profile_complete' => $updatedUser->isProfileComplete()
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
                'message' => 'Erreur lors de l\'upload du document: ' . $e->getMessage()
            ], 500);
        }
    }
}
