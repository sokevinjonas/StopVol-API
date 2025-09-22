<?php
namespace App\Services;

use App\Models\User;

class ProfileService
{
    public function completeProfile(User $user, $data)
    {
        $user->update([
            'name' => $data['name'],
            'photo' => $data['photo'] ?? null,
            'document_type' => $data['document_type'] ?? null,
            'document_number' => $data['document_number'] ?? null,
            'document_front' => $data['document_front'] ?? null,
            'document_back' => $data['document_back'] ?? null,
            'city' => $data['city'],
            'district' => $data['district'],
        ]);

        return $user;
    }

    public function isProfileComplete(User $user)
    {
        return $user->name && $user->document_type && $user->document_front && $user->document_back && $user->city && $user->district;
    }
}
