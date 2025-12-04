<?php

declare(strict_types=1);

namespace CiInbox\App\Controllers;

use CiInbox\App\Services\LabelService;
use CiInbox\Modules\Logger\LoggerService;
use CiInbox\Modules\Label\Exceptions\LabelException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Label Controller
 * 
 * Handles HTTP requests for label management (CRUD operations)
 */
class LabelController
{
    public function __construct(
        private LabelService $labelService,
        private LoggerService $logger
    ) {}

    /**
     * Create new label
     * POST /api/labels
     */
    public function create(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();

            // Validation
            if (empty($data['name'])) {
                return $this->jsonResponse($response, [
                    'error' => 'Label name is required'
                ], 400);
            }

            // Optional: Validate color format (hex)
            if (isset($data['color']) && !preg_match('/^#[0-9A-Fa-f]{6}$/', $data['color'])) {
                return $this->jsonResponse($response, [
                    'error' => 'Invalid color format. Use hex format like #FF5733'
                ], 400);
            }

            $labelId = $this->labelService->createLabel(
                name: $data['name'],
                color: $data['color'] ?? null,
                displayOrder: isset($data['display_order']) ? (int)$data['display_order'] : 0
            );

            return $this->jsonResponse($response, [
                'success' => true,
                'label_id' => $labelId,
                'message' => 'Label created successfully'
            ], 201);

        } catch (LabelException $e) {
            return $this->jsonResponse($response, [
                'error' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            $this->logger->error('Failed to create label', ['error' => $e->getMessage()]);
            return $this->jsonResponse($response, [
                'error' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get all labels
     * GET /api/labels
     */
    public function index(Request $request, Response $response): Response
    {
        try {
            $queryParams = $request->getQueryParams();
            
            // Optional filter: system labels only
            $systemOnly = isset($queryParams['system_only']) 
                ? filter_var($queryParams['system_only'], FILTER_VALIDATE_BOOLEAN) 
                : false;

            $labels = $this->labelService->getAllLabels($systemOnly);

            return $this->jsonResponse($response, [
                'labels' => $labels,
                'total' => count($labels)
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch labels', ['error' => $e->getMessage()]);
            return $this->jsonResponse($response, [
                'error' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get single label by ID
     * GET /api/labels/{id}
     */
    public function show(Request $request, Response $response, array $args): Response
    {
        try {
            $labelId = (int)$args['id'];
            
            $label = $this->labelService->getLabelById($labelId);

            if (!$label) {
                return $this->jsonResponse($response, [
                    'error' => 'Label not found'
                ], 404);
            }

            return $this->jsonResponse($response, [
                'label' => $label
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch label', ['error' => $e->getMessage()]);
            return $this->jsonResponse($response, [
                'error' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Update label
     * PUT /api/labels/{id}
     */
    public function update(Request $request, Response $response, array $args): Response
    {
        try {
            $labelId = (int)$args['id'];
            $data = $request->getParsedBody();

            // Validate at least one field provided
            if (empty($data)) {
                return $this->jsonResponse($response, [
                    'error' => 'No update data provided'
                ], 400);
            }

            // Validate color format if provided
            if (isset($data['color']) && !preg_match('/^#[0-9A-Fa-f]{6}$/', $data['color'])) {
                return $this->jsonResponse($response, [
                    'error' => 'Invalid color format. Use hex format like #FF5733'
                ], 400);
            }

            $success = $this->labelService->updateLabel($labelId, $data);

            if ($success) {
                return $this->jsonResponse($response, [
                    'success' => true,
                    'message' => 'Label updated successfully'
                ]);
            } else {
                return $this->jsonResponse($response, [
                    'error' => 'Failed to update label'
                ], 500);
            }

        } catch (LabelException $e) {
            return $this->jsonResponse($response, [
                'error' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            $this->logger->error('Failed to update label', ['error' => $e->getMessage()]);
            return $this->jsonResponse($response, [
                'error' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Delete label
     * DELETE /api/labels/{id}
     */
    public function delete(Request $request, Response $response, array $args): Response
    {
        try {
            $labelId = (int)$args['id'];

            $success = $this->labelService->deleteLabel($labelId);

            if ($success) {
                return $this->jsonResponse($response, [
                    'success' => true,
                    'message' => 'Label deleted successfully'
                ]);
            } else {
                return $this->jsonResponse($response, [
                    'error' => 'Failed to delete label'
                ], 500);
            }

        } catch (LabelException $e) {
            return $this->jsonResponse($response, [
                'error' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            $this->logger->error('Failed to delete label', ['error' => $e->getMessage()]);
            return $this->jsonResponse($response, [
                'error' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get label statistics (threads count per label)
     * GET /api/labels/stats
     */
    public function stats(Request $request, Response $response): Response
    {
        try {
            $stats = $this->labelService->getLabelStatistics();

            return $this->jsonResponse($response, [
                'statistics' => $stats
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch label stats', ['error' => $e->getMessage()]);
            return $this->jsonResponse($response, [
                'error' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Helper: JSON response
     */
    private function jsonResponse(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}
