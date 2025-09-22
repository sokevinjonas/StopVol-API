<?php

namespace App\Domains\Declaration\Services;

use App\Domains\Declaration\Entities\Declaration;
use App\Domains\Declaration\Repositories\DeclarationRepository;
use App\Domains\Declaration\ValueObjects\PlateNumber;
use App\Domains\Declaration\Events\DeclarationCreated;
use App\Domains\User\Entities\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

class DeclarationService
{
    public function __construct(
        private DeclarationRepository $declarationRepository
    ) {}

    public function createDeclaration(User $user, array $declarationData): Declaration
    {
        // Verify user can create declaration
        if (!$user->canCreateDeclaration()) {
            throw new \InvalidArgumentException('User profile must be validated to create a declaration');
        }

        // Validate declaration data
        $this->validateDeclarationData($declarationData);

        // Handle file uploads
        $declarationData = $this->handlePictureUploads($user, $declarationData);

        // Add user ID
        $declarationData['user_id'] = $user->id;
        $declarationData['status'] = 'pending';

        // Create declaration
        $declaration = $this->declarationRepository->create($declarationData);

        // Fire event
        Event::dispatch(new DeclarationCreated($declaration));

        return $declaration;
    }

    public function updateDeclarationStatus(Declaration $declaration, string $status): Declaration
    {
        $validStatuses = ['pending', 'found', 'closed'];
        
        if (!in_array($status, $validStatuses)) {
            throw new \InvalidArgumentException("Invalid status: {$status}");
        }

        switch ($status) {
            case 'found':
                $declaration->markAsFound();
                break;
            case 'closed':
                $declaration->markAsClosed();
                break;
            default:
                $declaration->status = $status;
                $declaration->save();
        }

        return $declaration;
    }

    public function searchByPlateNumber(string $plateNumber): array
    {
        try {
            $plateNumberVO = new PlateNumber($plateNumber);
            return $this->declarationRepository->findByPlateNumber($plateNumberVO->getValue());
        } catch (\InvalidArgumentException $e) {
            throw new \InvalidArgumentException("Invalid plate number format: {$e->getMessage()}");
        }
    }

    public function searchByChassisNumber(string $chassisNumber): array
    {
        if (empty(trim($chassisNumber))) {
            throw new \InvalidArgumentException('Chassis number cannot be empty');
        }

        return $this->declarationRepository->findByChassisNumber(strtoupper(trim($chassisNumber)));
    }

    public function searchByIdentifier(string $identifier): array
    {
        $identifier = strtoupper(trim($identifier));
        
        if (empty($identifier)) {
            throw new \InvalidArgumentException('Search identifier cannot be empty');
        }

        return $this->declarationRepository->searchByIdentifier($identifier);
    }

    public function getUserDeclarations(User $user): array
    {
        return $this->declarationRepository->findByUserId($user->id);
    }

    public function getPendingDeclarations(): array
    {
        return $this->declarationRepository->findPendingDeclarations();
    }

    public function getFoundDeclarations(): array
    {
        return $this->declarationRepository->findFoundDeclarations();
    }

    public function addPictureToDeclaration(Declaration $declaration, $pictureFile): Declaration
    {
        $userDir = "stopvol/{$declaration->user_id}/declarations/{$declaration->id}";
        $picturePath = $pictureFile->store($userDir, 'public');
        
        $declaration->addPicture($picturePath);
        
        return $declaration;
    }

    public function removePictureFromDeclaration(Declaration $declaration, string $picturePath): Declaration
    {
        // Delete file from storage
        if (Storage::disk('public')->exists($picturePath)) {
            Storage::disk('public')->delete($picturePath);
        }
        
        $declaration->removePicture($picturePath);
        
        return $declaration;
    }

    public function getDeclarationPictureUrls(Declaration $declaration): array
    {
        $pictures = $declaration->pictures ?? [];
        
        return array_map(function ($picturePath) {
            return Storage::disk('public')->url($picturePath);
        }, $pictures);
    }

    private function validateDeclarationData(array $data): void
    {
        // At least one identifier is required
        if (empty($data['plate_number']) && empty($data['chassis_number']) && empty($data['card_number'])) {
            throw new \InvalidArgumentException('At least one identifier (plate number, chassis number, or card number) is required');
        }

        // Validate plate number if provided
        if (!empty($data['plate_number'])) {
            new PlateNumber($data['plate_number']); // Will throw if invalid
        }

        // Basic validation for other fields
        if (!empty($data['theft_date']) && !strtotime($data['theft_date'])) {
            throw new \InvalidArgumentException('Invalid theft date format');
        }
    }

    private function handlePictureUploads(User $user, array $data): array
    {
        if (isset($data['pictures']) && is_array($data['pictures'])) {
            $uploadedPictures = [];
            $userDir = "stopvol/{$user->id}/declarations";
            
            foreach ($data['pictures'] as $picture) {
                if ($picture) {
                    $uploadedPictures[] = $picture->store($userDir, 'public');
                }
            }
            
            $data['pictures'] = $uploadedPictures;
        }
        
        return $data;
    }
}
