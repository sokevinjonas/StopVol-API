<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Domains\Declaration\Services\DeclarationService;
use App\Domains\Notification\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class AdminController extends Controller
{
    public function __construct(
        private DeclarationService $declarationService,
        private NotificationService $notificationService
    ) {}

    /**
     * Get all declarations (admin only)
     */
    public function declarations(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if ($user->role !== 'entity_admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé'
                ], 403);
            }

            // Get query parameters for filtering
            $filters = [];
            if ($request->has('status')) {
                $filters['status'] = $request->status;
            }
            if ($request->has('brand')) {
                $filters['brand'] = $request->brand;
            }
            if ($request->has('model')) {
                $filters['model'] = $request->model;
            }
            if ($request->has('color')) {
                $filters['color'] = $request->color;
            }
            if ($request->has('theft_date_from')) {
                $filters['theft_date_from'] = $request->theft_date_from;
            }
            if ($request->has('theft_date_to')) {
                $filters['theft_date_to'] = $request->theft_date_to;
            }

            $limit = $request->get('limit', 20);
            $offset = $request->get('offset', 0);

            $declarations = $this->declarationService->getAllDeclarations($filters, $limit, $offset);
            $total = $this->declarationService->countDeclarations($filters);

            return response()->json([
                'success' => true,
                'data' => [
                    'declarations' => array_map(function ($declaration) {
                        return [
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
                            'created_at' => $declaration['created_at'],
                            'updated_at' => $declaration['updated_at']
                        ];
                    }, $declarations),
                    'pagination' => [
                        'total' => $total,
                        'limit' => $limit,
                        'offset' => $offset,
                        'has_more' => ($offset + $limit) < $total
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des déclarations'
            ], 500);
        }
    }

    /**
     * Search declarations by identifier
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if ($user->role !== 'entity_admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé'
                ], 403);
            }

            $request->validate([
                'query' => 'required|string|min:3'
            ]);

            $query = $request->query;
            $searchType = $request->get('type', 'all'); // all, plate, chassis, card

            $results = [];

            switch ($searchType) {
                case 'plate':
                    $results = $this->declarationService->searchByPlateNumber($query);
                    break;
                case 'chassis':
                    $results = $this->declarationService->searchByChassisNumber($query);
                    break;
                case 'card':
                    $results = $this->declarationService->searchByCardNumber($query);
                    break;
                case 'all':
                default:
                    $results = $this->declarationService->searchByIdentifier($query);
                    break;
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'query' => $query,
                    'search_type' => $searchType,
                    'results' => array_map(function ($declaration) {
                        return [
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
                            'created_at' => $declaration['created_at']
                        ];
                    }, $results),
                    'count' => count($results)
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
                'message' => 'Erreur lors de la recherche'
            ], 500);
        }
    }

    /**
     * Notify declaration owner
     */
    public function notifyOwner(Request $request, string $declarationId): JsonResponse
    {
        try {
            $user = $request->user();

            if ($user->role !== 'entity_admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé'
                ], 403);
            }

            $request->validate([
                'message' => 'required|string|max:500',
                'channel' => 'required|in:sms,app'
            ]);

            $declaration = $this->declarationService->getDeclarationById($declarationId);

            if (!$declaration) {
                return response()->json([
                    'success' => false,
                    'message' => 'Déclaration non trouvée'
                ], 404);
            }

            // Send notification
            $notification = $this->notificationService->sendNotificationToDeclarationOwner(
                $declarationId,
                $user->id,
                $request->message,
                $request->channel
            );

            return response()->json([
                'success' => true,
                'message' => 'Notification envoyée avec succès',
                'data' => [
                    'notification_id' => $notification->id,
                    'declaration_id' => $declarationId,
                    'message' => $request->message,
                    'channel' => $request->channel,
                    'sent_at' => $notification->sent_at
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
                'message' => 'Erreur lors de l\'envoi de la notification: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update declaration status
     */
    public function updateDeclarationStatus(Request $request, string $declarationId): JsonResponse
    {
        try {
            $user = $request->user();

            if ($user->role !== 'entity_admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé'
                ], 403);
            }

            $request->validate([
                'status' => 'required|in:pending,found,closed',
                'notify_owner' => 'boolean',
                'notification_message' => 'required_if:notify_owner,true|string|max:500'
            ]);

            $declaration = $this->declarationService->getDeclarationById($declarationId);

            if (!$declaration) {
                return response()->json([
                    'success' => false,
                    'message' => 'Déclaration non trouvée'
                ], 404);
            }

            // Update status
            $updatedDeclaration = $this->declarationService->updateDeclarationStatus(
                $declarationId,
                $request->status
            );

            // Send notification if requested
            $notification = null;
            if ($request->get('notify_owner', false)) {
                $message = $request->notification_message;
                $notification = $this->notificationService->sendNotificationToDeclarationOwner(
                    $declarationId,
                    $user->id,
                    $message,
                    'sms'
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Statut mis à jour avec succès',
                'data' => [
                    'declaration' => [
                        'id' => $updatedDeclaration['id'],
                        'status' => $updatedDeclaration['status'],
                        'updated_at' => $updatedDeclaration['updated_at']
                    ],
                    'notification' => $notification ? [
                        'id' => $notification->id,
                        'message' => $notification->message,
                        'channel' => $notification->channel,
                        'sent_at' => $notification->sent_at
                    ] : null
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
                'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get dashboard statistics
     */
    public function dashboard(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if ($user->role !== 'entity_admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé'
                ], 403);
            }

            // Get statistics
            $totalDeclarations = $this->declarationService->countDeclarations();
            $pendingDeclarations = $this->declarationService->countDeclarations(['status' => 'pending']);
            $foundDeclarations = $this->declarationService->countDeclarations(['status' => 'found']);
            $closedDeclarations = $this->declarationService->countDeclarations(['status' => 'closed']);

            // Get recent declarations
            $recentDeclarations = $this->declarationService->getRecentDeclarations(7);

            return response()->json([
                'success' => true,
                'data' => [
                    'statistics' => [
                        'total_declarations' => $totalDeclarations,
                        'pending_declarations' => $pendingDeclarations,
                        'found_declarations' => $foundDeclarations,
                        'closed_declarations' => $closedDeclarations
                    ],
                    'recent_declarations' => array_map(function ($declaration) {
                        return [
                            'id' => $declaration['id'],
                            'plate_number' => $declaration['plate_number'],
                            'brand' => $declaration['brand'],
                            'model' => $declaration['model'],
                            'status' => $declaration['status'],
                            'created_at' => $declaration['created_at']
                        ];
                    }, $recentDeclarations)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques'
            ], 500);
        }
    }
}
