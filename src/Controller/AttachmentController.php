<?php

namespace App\Controller;

use App\Service\AttachmentService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


class AttachmentController extends AbstractController
{
    public function __construct(
        private AttachmentService $attachmentService
    ) {}

    #[Route('/upload', name: 'upload_attachment', methods: ['POST'])]
    /* Upload an attachment */
    public function upload(Request $request): JsonResponse
    {
        try {
            $dto = $this->attachmentService->uploadAttachment($request);

            return new JsonResponse($dto);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse([
                'status' => 'ERROR',
                'message' => $e->getMessage()
            ], 400);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'status' => 'ERROR',
                'message' => 'Internal error'
            ], 500);
        }
    }
}
