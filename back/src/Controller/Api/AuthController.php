<?php

namespace App\Controller\Api;

use App\Api\AuthApiPresenter;
use App\Dto\Auth\LoginInput;
use App\Dto\Auth\RegisterInput;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\ApiTokenManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api', name: 'api_auth_')]
class AuthController extends AbstractController
{
    #[Route('/auth/register', name: 'register', methods: ['POST'])]
    public function register(
        Request $request,
        ValidatorInterface $validator,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        ApiTokenManager $apiTokenManager,
        EntityManagerInterface $entityManager,
        AuthApiPresenter $authApiPresenter,
    ): JsonResponse {
        $payload = $this->decodeRequestBody($request);
        if ($payload instanceof JsonResponse) {
            return $payload;
        }

        $input = new RegisterInput();
        $input->email = $this->normalizeString($payload['email'] ?? null);
        $input->password = $this->normalizeString($payload['password'] ?? null);
        $input->firstname = $this->normalizeString($payload['firstname'] ?? null);
        $input->lastname = $this->normalizeString($payload['lastname'] ?? null);

        $fieldErrors = $this->collectValidationErrors($validator->validate($input));
        if ([] !== $fieldErrors) {
            return $this->json(
                $authApiPresenter->presentValidationError($fieldErrors),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        if ($userRepository->findOneBy(['email' => mb_strtolower($input->email)])) {
            return $this->json([
                'error' => [
                    'code' => 'email_already_exists',
                    'message' => 'An account already exists with this email.',
                ],
            ], Response::HTTP_CONFLICT);
        }

        $user = (new User())
            ->setEmail($input->email)
            ->setFirstname($input->firstname)
            ->setLastname($input->lastname)
            ->setRoles(['ROLE_USER']);

        $user->setPassword($passwordHasher->hashPassword($user, $input->password));

        $entityManager->persist($user);
        $entityManager->flush();

        $issuedToken = $apiTokenManager->issueToken($user);
        $entityManager->flush();

        return $this->json(
            $authApiPresenter->presentAuth($user, $issuedToken['plainTextToken'], $issuedToken['apiToken']),
            Response::HTTP_CREATED
        );
    }

    #[Route('/auth/login', name: 'login', methods: ['POST'])]
    public function login(
        Request $request,
        ValidatorInterface $validator,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        ApiTokenManager $apiTokenManager,
        EntityManagerInterface $entityManager,
        AuthApiPresenter $authApiPresenter,
    ): JsonResponse {
        $payload = $this->decodeRequestBody($request);
        if ($payload instanceof JsonResponse) {
            return $payload;
        }

        $input = new LoginInput();
        $input->email = $this->normalizeString($payload['email'] ?? null);
        $input->password = $this->normalizeString($payload['password'] ?? null);

        $fieldErrors = $this->collectValidationErrors($validator->validate($input));
        if ([] !== $fieldErrors) {
            return $this->json(
                $authApiPresenter->presentValidationError($fieldErrors),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $user = $userRepository->findOneBy(['email' => mb_strtolower($input->email)]);
        if (!$user instanceof User || !$passwordHasher->isPasswordValid($user, $input->password)) {
            return $this->json([
                'error' => [
                    'code' => 'invalid_credentials',
                    'message' => 'Invalid email or password.',
                ],
            ], Response::HTTP_UNAUTHORIZED);
        }

        $issuedToken = $apiTokenManager->issueToken($user);
        $entityManager->flush();

        return $this->json($authApiPresenter->presentAuth(
            $user,
            $issuedToken['plainTextToken'],
            $issuedToken['apiToken']
        ));
    }

    #[Route('/me', name: 'me', methods: ['GET'])]
    public function me(AuthApiPresenter $authApiPresenter): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json([
                'error' => [
                    'code' => 'authentication_required',
                    'message' => 'Authentication is required to access this resource.',
                ],
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json([
            'data' => $authApiPresenter->presentUser($user),
        ]);
    }

    #[Route('/auth/logout', name: 'logout', methods: ['POST'])]
    public function logout(
        Request $request,
        ApiTokenManager $apiTokenManager,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $plainTextToken = $this->extractBearerToken($request);

        if (null !== $plainTextToken) {
            $apiTokenManager->revokeToken($plainTextToken);
            $entityManager->flush();
        }

        return $this->json([
            'data' => [
                'loggedOut' => true,
            ],
        ]);
    }

    /**
     * @return array<string, mixed>|JsonResponse
     */
    private function decodeRequestBody(Request $request): array|JsonResponse
    {
        try {
            $payload = json_decode($request->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return $this->json([
                'error' => [
                    'code' => 'invalid_json',
                    'message' => 'The request body must contain valid JSON.',
                ],
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!is_array($payload)) {
            return $this->json([
                'error' => [
                    'code' => 'invalid_payload',
                    'message' => 'The request payload must be a JSON object.',
                ],
            ], Response::HTTP_BAD_REQUEST);
        }

        return $payload;
    }

    private function normalizeString(mixed $value): string
    {
        if (!is_scalar($value)) {
            return '';
        }

        return trim((string) $value);
    }

    /**
     * @param iterable<ConstraintViolationInterface> $violations
     * @return array<string, array<int, string>>
     */
    private function collectValidationErrors(iterable $violations): array
    {
        $errors = [];

        foreach ($violations as $violation) {
            $field = $violation->getPropertyPath();
            $errors[$field] ??= [];
            $errors[$field][] = $violation->getMessage();
        }

        return $errors;
    }

    private function extractBearerToken(Request $request): ?string
    {
        $authorizationHeader = $request->headers->get('Authorization');

        if (!$authorizationHeader || !str_starts_with($authorizationHeader, 'Bearer ')) {
            return null;
        }

        $token = trim(substr($authorizationHeader, 7));

        return '' === $token ? null : $token;
    }
}
