<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ProfileService;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;

class ProfileController extends BaseController
{
    protected $profileService;

    public function __construct(ProfileService $profileService)
    {
        $this->profileService = $profileService;
    }

    /**
     * @OA\Post(
     *     path="/api/profile/complete",
     *     summary="Compléter le profil utilisateur",
     *     description="Permet à l'utilisateur de compléter son profil avec ses informations personnelles et documents",
     *     tags={"Profile"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"name", "city", "district"},
     *                 @OA\Property(property="name", type="string", maxLength=255, example="Jean Dupont", description="Nom complet"),
     *                 @OA\Property(property="photo", type="string", format="binary", description="Photo de profil (max 2MB)"),
     *                 @OA\Property(property="document_type", type="string", enum={"cnib", "permis_conduire", "passport"}, example="cnib", description="Type de document d'identité"),
     *                 @OA\Property(property="document_number", type="string", maxLength=50, example="B123456789", description="Numéro du document"),
     *                 @OA\Property(property="document_front", type="string", format="binary", description="Photo recto du document (max 2MB)"),
     *                 @OA\Property(property="document_back", type="string", format="binary", description="Photo verso du document (max 2MB)"),
     *                 @OA\Property(property="city", type="string", maxLength=255, example="Ouagadougou", description="Ville"),
     *                 @OA\Property(property="district", type="string", maxLength=255, example="Secteur 15", description="District/Secteur")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Profil complété avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Profil complété avec succès"),
     *             @OA\Property(property="user", ref="#/components/schemas/User")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function complete(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'photo' => 'nullable|image|max:2048',
            'document_type' => 'nullable|in:cnib,permis_conduire,passport',
            'document_number' => 'nullable|string|max:50',
            'document_front' => 'nullable|image|max:2048',
            'document_back' => 'nullable|image|max:2048',
            'city' => 'required|string|max:255',
            'district' => 'required|string|max:255',
        ], [
            'name.required' => 'Le nom est requis',
            'city.required' => 'La ville est requise',
            'district.required' => 'Le district est requis',
            'photo.image' => 'La photo doit être une image valide',
            'photo.max' => 'La photo ne doit pas dépasser 2MB',
            'document_front.image' => 'Le document avant doit être une image valide',
            'document_front.max' => 'Le document avant ne doit pas dépasser 2MB',
            'document_back.image' => 'Le document arrière doit être une image valide',
            'document_back.max' => 'Le document arrière ne doit pas dépasser 2MB',
        ]);

        $user = Auth::user();

        // Sauvegarde ou mise à jour du profil
        $profile = $this->profileService->completeProfile($user, $request->all());

        return response()->json([
            'message' => 'Profil complété avec succès',
            'user' => $profile
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/profile/is-complete",
     *     summary="Vérifier si le profil est complet",
     *     description="Vérifie si l'utilisateur a complété son profil",
     *     tags={"Profile"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Statut du profil",
     *         @OA\JsonContent(
     *             @OA\Property(property="is_complete", type="boolean", example=true, description="Indique si le profil est complet")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     )
     * )
     */
    public function isComplete()
    {
        $user = Auth::user();

        $isComplete = $this->profileService->isProfileComplete($user);

        return response()->json([
            'is_complete' => $isComplete
        ], 200);
    }
}
