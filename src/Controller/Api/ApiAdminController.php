<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\ApiResponseService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin')]
#[IsGranted('ROLE_ADMIN')]
final class ApiAdminController extends AbstractController
{
    public function __construct(
        private readonly ApiResponseService $api,
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    #[Route('/users', name: 'api_admin_users_list', methods: ['GET'])]
    public function users(): JsonResponse
    {
        $users = $this->userRepository->findBy([], ['lastName' => 'ASC']);

        return $this->api->success(array_map(
            fn (User $u) => $this->normalizeUser($u),
            $users
        ));
    }

    #[Route('/users', name: 'api_admin_users_create', methods: ['POST'])]
    public function createUser(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        $error = $this->validateUserPayload($data);
        if ($error) {
            return $this->api->error($error);
        }

        if ($this->userRepository->findOneBy(['email' => $data['email']])) {
            return $this->api->error('Un utilisateur avec cet email existe déjà.');
        }

        $user = new User();
        $this->hydrateUser($user, $data);
        $user->setPassword($this->passwordHasher->hashPassword($user, bin2hex(random_bytes(16))));

        $this->em->persist($user);
        $this->em->flush();

        return $this->api->success($this->normalizeUser($user), 'Utilisateur créé.', Response::HTTP_CREATED);
    }

    #[Route('/users/{id}', name: 'api_admin_users_update', requirements: ['id' => '\d+'], methods: ['PUT'])]
    public function updateUser(int $id, Request $request): JsonResponse
    {
        $user = $this->userRepository->find($id);
        if (!$user) {
            return $this->api->error('Utilisateur introuvable.', Response::HTTP_NOT_FOUND);
        }

        $data  = json_decode($request->getContent(), true) ?? [];
        $error = $this->validateUserPayload($data, $user);
        if ($error) {
            return $this->api->error($error);
        }

        $this->hydrateUser($user, $data);
        $this->em->flush();

        return $this->api->success($this->normalizeUser($user), 'Utilisateur mis à jour.');
    }

    #[Route('/users/{id}/toggle-disable', name: 'api_admin_users_toggle_disable', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function toggleDisable(int $id): JsonResponse
    {
        $user = $this->userRepository->find($id);
        if (!$user) {
            return $this->api->error('Utilisateur introuvable.', Response::HTTP_NOT_FOUND);
        }

        if ($user->getDisabledAt() === null) {
            $user->setDisabledAt(new \DateTimeImmutable());
            $message = 'Utilisateur désactivé.';
        } else {
            $user->setDisabledAt(null);
            $message = 'Utilisateur réactivé.';
        }

        $this->em->flush();

        return $this->api->success($this->normalizeUser($user), $message);
    }

    private function normalizeUser(User $user): array
    {
        return [
            'id'         => $user->getId(),
            'email'      => $user->getEmail(),
            'firstName'  => $user->getFirstName(),
            'lastName'   => $user->getLastName(),
            'roles'      => $user->getRoles(),
            'disabledAt' => $user->getDisabledAt()?->format(\DateTimeInterface::ATOM),
        ];
    }

    private function validateUserPayload(array $data, ?User $existingUser = null): ?string
    {
        if (empty(trim((string) ($data['firstName'] ?? '')))) {
            return 'Le prénom est requis.';
        }
        if (empty(trim((string) ($data['lastName'] ?? '')))) {
            return 'Le nom est requis.';
        }
        $email = trim((string) ($data['email'] ?? ''));
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'Un email valide est requis.';
        }
        // On update, check unique email only if changed
        if (!$existingUser || $existingUser->getEmail() !== $email) {
            if ($this->userRepository->findOneBy(['email' => $email])) {
                return 'Un utilisateur avec cet email existe déjà.';
            }
        }
        return null;
    }

    private function hydrateUser(User $user, array $data): void
    {
        $user->setFirstName(trim((string) $data['firstName']));
        $user->setLastName(trim((string) $data['lastName']));
        $user->setEmail(trim((string) $data['email']));

        $roles = [];
        if (!empty($data['isAdmin'])) {
            $roles[] = 'ROLE_ADMIN';
        }
        $user->setRoles($roles);
    }
}
