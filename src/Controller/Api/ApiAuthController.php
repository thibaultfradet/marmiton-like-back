<?php

namespace App\Controller\Api;

use App\Repository\UserRepository;
use App\Service\ApiResponseService;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/auth')]
final class ApiAuthController extends AbstractController
{
    private const ACCESS_TOKEN_COOKIE  = 'jwt_token';
    private const REFRESH_TOKEN_COOKIE = 'jwt_refresh_token';
    private const ACCESS_TTL           = 3600;          // 1 hour
    private const REFRESH_TTL          = 30 * 24 * 3600; // 30 days

    public function __construct(
        private readonly ApiResponseService $api,
        private readonly JWTTokenManagerInterface $jwtManager,
        private readonly JWTEncoderInterface $jwtEncoder,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly UserRepository $userRepository,
    ) {
    }

    #[Route('/login', name: 'api_auth_login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $email    = trim((string) ($data['email']    ?? ''));
        $password = trim((string) ($data['password'] ?? ''));

        if ($email === '' || $password === '') {
            return $this->api->error('Email et mot de passe requis.', Response::HTTP_BAD_REQUEST);
        }

        $user = $this->userRepository->findOneBy(['email' => $email]);

        if (!$user || !$this->passwordHasher->isPasswordValid($user, $password)) {
            return $this->api->error('Identifiants invalides.', Response::HTTP_UNAUTHORIZED);
        }

        if ($user->getDisabledAt() !== null) {
            return $this->api->error('Ce compte a été désactivé.', Response::HTTP_FORBIDDEN);
        }

        $accessToken  = $this->jwtManager->create($user);
        $refreshToken = $this->jwtManager->createFromPayload($user, [
            'exp'  => time() + self::REFRESH_TTL,
            'type' => 'refresh',
        ]);

        $response = $this->api->success(null, 'Connexion réussie.');
        $this->setTokenCookies($response, $accessToken, $refreshToken);

        return $response;
    }

    #[Route('/refresh', name: 'api_auth_refresh', methods: ['POST'])]
    public function refresh(Request $request): JsonResponse
    {
        $rawRefresh = $request->cookies->get(self::REFRESH_TOKEN_COOKIE);

        if (!$rawRefresh) {
            return $this->api->error('token-expired', Response::HTTP_UNAUTHORIZED);
        }

        try {
            $payload = $this->jwtEncoder->decode($rawRefresh);
        } catch (JWTDecodeFailureException) {
            return $this->api->error('token-expired', Response::HTTP_UNAUTHORIZED);
        }

        if (($payload['type'] ?? '') !== 'refresh') {
            return $this->api->error('token-expired', Response::HTTP_UNAUTHORIZED);
        }

        $email = $payload[$this->jwtManager->getUserIdClaim()] ?? null;
        $user  = $email ? $this->userRepository->findOneBy(['email' => $email]) : null;

        if (!$user || $user->getDisabledAt() !== null) {
            return $this->api->error('token-expired', Response::HTTP_UNAUTHORIZED);
        }

        $newAccessToken  = $this->jwtManager->create($user);
        $newRefreshToken = $this->jwtManager->createFromPayload($user, [
            'exp'  => time() + self::REFRESH_TTL,
            'type' => 'refresh',
        ]);

        $response = $this->api->success(null, 'Token renouvelé.');
        $this->setTokenCookies($response, $newAccessToken, $newRefreshToken);

        return $response;
    }

    #[Route('/logout', name: 'api_auth_logout', methods: ['POST'])]
    public function logout(): JsonResponse
    {
        $response = $this->api->success(null, 'Déconnexion réussie.');
        $this->clearTokenCookies($response);

        return $response;
    }

    #[Route('/me', name: 'api_auth_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        return $this->api->success([
            'id'        => $user->getId(),
            'email'     => $user->getEmail(),
            'firstName' => $user->getFirstName(),
            'lastName'  => $user->getLastName(),
            'roles'     => $user->getRoles(),
        ]);
    }

    private function setTokenCookies(JsonResponse $response, string $accessToken, string $refreshToken): void
    {
        $secure = $this->getParameter('kernel.environment') === 'prod';

        $response->headers->setCookie(new Cookie(
            name: self::ACCESS_TOKEN_COOKIE,
            value: $accessToken,
            expire: time() + self::ACCESS_TTL,
            path: '/',
            secure: $secure,
            httpOnly: true,
            sameSite: Cookie::SAMESITE_LAX,
        ));

        $response->headers->setCookie(new Cookie(
            name: self::REFRESH_TOKEN_COOKIE,
            value: $refreshToken,
            expire: time() + self::REFRESH_TTL,
            path: '/',
            secure: $secure,
            httpOnly: true,
            sameSite: Cookie::SAMESITE_LAX,
        ));
    }

    private function clearTokenCookies(JsonResponse $response): void
    {
        $response->headers->clearCookie(self::ACCESS_TOKEN_COOKIE,  '/');
        $response->headers->clearCookie(self::REFRESH_TOKEN_COOKIE, '/');
    }
}
