<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DeclarationService;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;

class DeclarationController extends BaseController
{
    protected $declarationService;

    public function __construct(DeclarationService $declarationService)
    {
        $this->declarationService = $declarationService;
    }

    /**
     * @OA\Post(
     *     path="/api/declarations",
     *     summary="Créer une déclaration de vol",
     *     description="Permet de créer une nouvelle déclaration de vol de véhicule avec les documents justificatifs",
     *     tags={"Declarations"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"plaque", "date_vol", "lieu_vol", "images"},
     *                 @OA\Property(property="plaque", type="string", maxLength=20, example="11-BF-1234", description="Numéro de plaque du véhicule volé"),
     *                 @OA\Property(property="description", type="string", example="Véhicule Toyota Corolla blanc volé devant la maison", description="Description détaillée du vol"),
     *                 @OA\Property(property="date_vol", type="string", format="date", example="2024-01-15", description="Date du vol"),
     *                 @OA\Property(property="lieu_vol", type="string", maxLength=255, example="Ouagadougou, Secteur 15", description="Lieu du vol"),
     *                 @OA\Property(
     *                     property="images",
     *                     type="array",
     *                     description="Images des documents justificatifs (minimum 1)",
     *                     @OA\Items(
     *                         type="object",
     *                         required={"document_type", "file"},
     *                         @OA\Property(property="document_type", type="string", enum={"cnib", "permis_conduire", "passport", "photo"}, example="cnib", description="Type de document"),
     *                         @OA\Property(property="type", type="string", enum={"card_front", "card_back"}, example="card_front", description="Face du document"),
     *                         @OA\Property(property="file", type="string", format="binary", description="Fichier image (max 2MB)")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Déclaration créée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Déclaration créée avec succès"),
     *             @OA\Property(property="declaration", ref="#/components/schemas/Declaration")
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
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur serveur",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Erreur lors de la création de la déclaration"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'plaque' => 'required|string|max:20',
            'description' => 'nullable|string',
            'date_vol' => 'required|date',
            'lieu_vol' => 'required|string|max:255',
            'images' => 'required|array|min:1',
            'images.*.document_type' => 'required|in:cnib,permis_conduire,passport,photo',
            'images.*.type' => 'nullable|in:card_front,card_back',
            'images.*.file' => 'required|image|max:2048',
        ], [
            'images.required' => 'Au moins une image est requise',
            'images.*.document_type.required' => 'Le type de document est requis pour chaque image',
            'images.*.file.required' => 'Le fichier image est requis pour chaque image',
            'images.*.file.image' => 'Chaque fichier doit être une image valide',
            'images.*.file.max' => 'Chaque image ne doit pas dépasser 2MB',
            'date_vol.date' => 'La date du vol doit être une date valide',
            'plaque.max' => 'La plaque ne doit pas dépasser 20 caractères',
            'lieu_vol.max' => 'Le lieu du vol ne doit pas dépasser 255 caractères',
        ]);

        $user = Auth::user();

        // Préparer les images pour le service
        $imagesData = [];
        foreach ($request->file('images') as $img) {
            $path = $img['file']->store('declaration_images', 'public');
            $imagesData[] = [
                'document_type' => $img['document_type'],
                'type' => $img['type'] ?? null,
                'path' => $path
            ];
        }

        try {
            $declaration = $this->declarationService->createDeclaration($user, [
                'plaque' => $request->plaque,
                'description' => $request->description,
                'date_vol' => $request->date_vol,
                'lieu_vol' => $request->lieu_vol,
                'images' => $imagesData
            ]);

            return response()->json([
                'message' => 'Déclaration créée avec succès',
                'declaration' => $declaration
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la création de la déclaration',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/declarations/search",
     *     summary="Rechercher une déclaration par plaque",
     *     description="Permet de rechercher une déclaration de vol par numéro de plaque (accès admin)",
     *     tags={"Declarations"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="plaque",
     *         in="query",
     *         required=true,
     *         description="Numéro de plaque à rechercher",
     *         @OA\Schema(type="string", maxLength=20, example="11-BF-1234")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Déclaration trouvée",
     *         @OA\JsonContent(ref="#/components/schemas/Declaration")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Aucune déclaration trouvée",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Aucune déclaration trouvée pour cette plaque")
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
    public function search(Request $request)
    {
        $request->validate([
            'plaque' => 'required|string|max:20',
        ], [
            'plaque.max' => 'La plaque ne doit pas dépasser 20 caractères',
        ]);

        $declaration = $this->declarationService->searchByPlate($request->plaque);

        if (!$declaration) {
            return response()->json([
                'message' => 'Aucune déclaration trouvée pour cette plaque'
            ], 404);
        }

        return response()->json($declaration, 200);
    }
}
