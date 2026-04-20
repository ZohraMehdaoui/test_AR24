<?php

namespace App\Controller;

use App\DTO\UserDTO;
use App\Service\UserService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


class UserController extends AbstractController
{
    public function __construct(
        private UserService $userService,
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator
    ) {}

    #[Route('/', name: 'app_index')]
    public function index(): Response
    {
        return $this->render('index.html.twig');
    }


    #[Route('/create-user', name: 'create_user', methods: ['POST'])]
    /* Create a new user */
    public function createUser(Request $request): JsonResponse
    {
        try {
            $userDto = $this->serializer->deserialize($request->getContent(), UserDTO::class, 'json');
            $errors = $this->validator->validate($userDto);

            if (count($errors) > 0) {
                return $this->json(['errors' => $errors], 400);
            }

            $result = $this->userService->createUser($userDto);

            return $this->json($result);
        } catch (\InvalidArgumentException $e) {
            // DTO validation errors
            return $this->json([
                'errors' => json_decode($e->getMessage(), true)
            ], 400);
        } catch (\Throwable $e) {
            return $this->json([
                'error' => 'Internal server error - ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/user/{userIdentifier}', name: 'app_user_getinfo', methods: ['GET'])]
    /* Retrieve user information by ID or email */
    public function getInfo(string $userIdentifier): JsonResponse
    {
        try {
            $response = $this->userService->getUserInfo($userIdentifier);

            return $this->json($response);
        } catch (\Exception $e) {

            return new JsonResponse([
                'status' => 'ERROR',
                'message' => 'Failed to retrieve user information'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
