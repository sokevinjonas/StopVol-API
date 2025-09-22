<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ProfileService;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    protected $profileService;

    public function __construct(ProfileService $profileService)
    {
        $this->profileService = $profileService;
    }

    /**
     * Compléter le profil de l'utilisateur
     * POST /api/profile/complete
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
     * Vérifie si le profil est complet
     * GET /api/profile/is-complete
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
