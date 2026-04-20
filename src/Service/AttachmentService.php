<?php

namespace App\Service;

use Exception;
use App\Service\Api\ApiClient;
use Symfony\Component\HttpFoundation\Request;


class AttachmentService
{
    public function __construct(
        protected ApiClient $apiClient
    ) {}

    /**
     * Upload a file as an attachment to AR24
     */
    public function uploadAttachment(Request $request): array
    {
        try {
            $params = [
                'id_user' => $request->request->get('id_user'),
                'file' => fopen($request->files->get('filePath'), 'r'),
            ];

            $response = $this->apiClient->postRequest('api/attachment/', $params);

            return $response;
        } catch (Exception $e) {
            throw new \Exception('Failed to upload attachment', 0, $e);
        }
    }
}
