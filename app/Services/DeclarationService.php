<?php
namespace App\Services;

use App\Models\Declaration;
use App\Models\DeclarationImage;
use App\Jobs\SendNotificationJob;

class DeclarationService 
{
    public function createDeclaration($user, $data)
    {
        // Vérifier que le profil est complet
        if (! app(ProfileService::class)->isProfileComplete($user)) {
            throw new \Exception("Profil incomplet. Impossible de déclarer.");
        }

        $declaration = Declaration::create([
            'user_id' => $user->id,
            'plaque' => $data['plaque'],
            'description' => $data['description'] ?? null,
            'date_vol' => $data['date_vol'],
            'lieu_vol' => $data['lieu_vol'],
            'status' => 'en_attente',
        ]);

        // Ajouter les images
        foreach ($data['images'] as $img) {
            DeclarationImage::create([
                'declaration_id' => $declaration->id,
                'document_type' => $img['document_type'],
                'type' => $img['type'] ?? null,
                'path' => $img['path']
            ]);
        }

        // Notifier les admins
        dispatch(new SendNotificationJob(
            "Nouvelle déclaration à valider",
            "admin"
        ));

        return $declaration;
    }

    public function searchByPlate($plaque)
    {
        return Declaration::where('plaque', $plaque)->whereIn('status', ['en_attente','valide'])->first();
    }

    public function updateStatus(Declaration $declaration, $status)
    {
        $declaration->update(['status' => $status]);

        // Notifier le propriétaire si retrouvé/rejeté
        if (in_array($status, ['retrouve','rejete'])) {
            dispatch(new SendNotificationJob(
                "Mise à jour de votre déclaration",
                $declaration->user_id
            ));
        }

        return $declaration;
    }
}
