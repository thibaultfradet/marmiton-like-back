<?php

namespace App\Controller\Api;

use App\Repository\UserRepository;
use App\Service\ApiResponseService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/profile')]
final class ApiProfileController extends AbstractController
{
    public function __construct(
        private readonly ApiResponseService $api,
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    #[Route('', name: 'api_profile_update', methods: ['PUT'])]
    public function updateProfile(Request $request): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true) ?? [];

        $firstName = trim((string) ($data['firstName'] ?? ''));
        $lastName  = trim((string) ($data['lastName']  ?? ''));
        $email     = trim((string) ($data['email']     ?? ''));

        if ($firstName === '') {
            return $this->api->error('Le prénom est requis.', Response::HTTP_BAD_REQUEST);
        }
        if ($lastName === '') {
            return $this->api->error('Le nom est requis.', Response::HTTP_BAD_REQUEST);
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->api->error('Un email valide est requis.', Response::HTTP_BAD_REQUEST);
        }

        $existing = $this->userRepository->findOneBy(['email' => $email]);
        if ($existing && $existing->getId() !== $user->getId()) {
            return $this->api->error('Un utilisateur avec cet email existe déjà.', Response::HTTP_CONFLICT);
        }

        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setEmail($email);
        $this->em->flush();

        return $this->api->success([
            'id'        => $user->getId(),
            'email'     => $user->getEmail(),
            'firstName' => $user->getFirstName(),
            'lastName'  => $user->getLastName(),
            'roles'     => $user->getRoles(),
        ]);
    }

    #[Route('/password', name: 'api_profile_update_password', methods: ['PUT'])]
    public function updatePassword(Request $request): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user            = $this->getUser();
        $data            = json_decode($request->getContent(), true) ?? [];
        $password        = trim((string) ($data['password']        ?? ''));
        $confirmPassword = trim((string) ($data['confirmPassword'] ?? ''));

        if ($password === '' || $confirmPassword === '') {
            return $this->api->error('Les deux champs sont requis.', Response::HTTP_BAD_REQUEST);
        }
        if (mb_strlen($password) < 8) {
            return $this->api->error('Le mot de passe doit contenir au moins 8 caractères.', Response::HTTP_BAD_REQUEST);
        }
        if ($password !== $confirmPassword) {
            return $this->api->error('Les mots de passe ne correspondent pas.', Response::HTTP_BAD_REQUEST);
        }

        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
        $this->em->flush();

        return $this->api->success(null, 'Mot de passe mis à jour.');
    }
}
