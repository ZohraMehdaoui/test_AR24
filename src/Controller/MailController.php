<?php

namespace App\Controller;

use App\DTO\MailDTO;
use App\Service\MailService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


class MailController extends AbstractController
{
    public function __construct(
        private MailService $mailService,
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator
    ) {}

    #[Route('/send-mail', name: 'send_mail', methods: ['POST'])]
    public function send(Request $request): JsonResponse
    {
        try {
            $mailDto = $this->serializer->deserialize($request->getContent(), MailDTO::class, 'json');
            $errors = $this->validator->validate($mailDto);

            if (count($errors) > 0) {
                return $this->json(['errors' => $errors], 400);
            }

            $result = $this->mailService->sendMail($mailDto);

            return $this->json($result);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }


    #[Route('mail/{mailId}', methods: ['GET'])]
    public function getMailInfos(string $mailId): JsonResponse
    {
        try {
            $response = $this->mailService->getMailInfo($mailId);
            return $this->json($response);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }
}
