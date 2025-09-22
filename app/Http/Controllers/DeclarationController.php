<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DeclarationService;
use Illuminate\Support\Facades\Auth;

class DeclarationController extends Controller
{
    protected $declarationService;

    public function __construct(DeclarationService $declarationService)
    {
        $this->declarationService = $declarationService;
    }

    /**
     * Créer une nouvelle déclaration
     * POST /api/declarations
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
     * Rechercher une déclaration par plaque (pour admin)
     * GET /api/declarations/search?plaque=XXX
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
