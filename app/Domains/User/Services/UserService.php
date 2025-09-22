<?php

namespace App\Domains\User\Services;

use App\Domains\User\Entities\User;
use App\Domains\User\Repositories\UserRepository;
use App\Domains\User\Events\UserProfileCompleted;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

class UserService
{
    public function __construct(
        private UserRepository $userRepository
    ) {}

    public function createUser(string $phone, string $role = 'citizen'): User
    {
        return $this->userRepository->create([
            'phone' => $phone,
            'role' => $role,
            'profile_status' => 'incomplete'
        ]);
    }

    public function findOrCreateByPhone(string $phone): User
    {
        $user = $this->userRepository->findByPhone($phone);
        
        if (!$user) {
            $user = $this->createUser($phone);
        }
        
        return $user;
    }

    public function completeProfile(User $user, array $profileData): User
    {
        // Validate required fields
        $this->validateProfileData($profileData);
        
        // Handle file uploads
        $profileData = $this->handleFileUploads($user, $profileData);
        
        // Complete profile
        $user->completeProfile($profileData);
        
        // Fire event
        Event::dispatch(new UserProfileCompleted($user));
        
        return $user;
    }

    public function validateUserProfile(User $user): User
    {
        if (!$user->isProfileComplete()) {
            throw new \InvalidArgumentException('Profile is not complete');
        }
        
        $user->validateProfile();
        
        return $user;
    }

    public function verifyPhone(User $user): User
    {
        $user->verifyPhone();
        
        return $user;
    }

    private function validateProfileData(array $data): void
    {
        $required = ['name', 'city', 'district', 'id_type'];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new \InvalidArgumentException("Field {$field} is required");
            }
        }
    }

    private function handleFileUploads(User $user, array $data): array
    {
        $userDir = "stopvol/{$user->id}";
        
        // Handle profile picture
        if (isset($data['profile_picture']) && $data['profile_picture']) {
            $data['profile_picture'] = $data['profile_picture']->store($userDir, 'public');
        }
        
        // Handle ID card front
        if (isset($data['id_card_front']) && $data['id_card_front']) {
            $data['id_card_front'] = $data['id_card_front']->store($userDir, 'public');
        }
        
        // Handle ID card back
        if (isset($data['id_card_back']) && $data['id_card_back']) {
            $data['id_card_back'] = $data['id_card_back']->store($userDir, 'public');
        }
        
        return $data;
    }

    public function getUserProfilePicture(User $user): ?string
    {
        if (!$user->profile_picture) {
            return null;
        }
        
        return Storage::disk('public')->url($user->profile_picture);
    }
}
