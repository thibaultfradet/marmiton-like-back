<?php

namespace App\Controller\Api;

use App\Repository\UserRepository;
use App\Service\ApiResponseService;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

#[Route('/api/auth')]
final class ApiResetPasswordController extends AbstractController
{
    public function __construct(
        private readonly ApiResponseService $api,
        private readonly ResetPasswordHelperInterface $resetPasswordHelper,
        private readonly MailerInterface $mailer,
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    #[Route('/forgot-password', name: 'api_auth_forgot_password', methods: ['POST'])]
    public function forgotPassword(Request $request): JsonResponse
    {
        $data  = json_decode($request->getContent(), true);
        $email = trim((string) ($data['email'] ?? ''));

        if ($email === '') {
            return $this->api->error('Email requis.', Response::HTTP_BAD_REQUEST);
        }

        // Always return success to avoid email enumeration
        $user = $this->userRepository->findOneBy(['email' => $email]);
        if (!$user) {
            return $this->api->success(null, 'Si cet email existe, un lien a été envoyé.');
        }

        try {
            $resetToken = $this->resetPasswordHelper->generateResetToken($user);
        } catch (ResetPasswordExceptionInterface) {
            return $this->api->success(null, 'Si cet email existe, un lien a été envoyé.');
        }

        $frontendUrl = $_ENV['CORS_ALLOW_ORIGIN'] ?? 'http://localhost:5173';
        $resetLink   = $frontendUrl . '/reset-password?token=' . $resetToken->getToken();
        $expiresAt   = $resetToken->getExpiresAt()->format('H:i');

        $this->mailer->send(
            (new Email())
                ->from('no-reply@thibault-fradet.fr')
                ->to($user->getEmail())
                ->subject('Réinitialisation de votre mot de passe')
                ->html($this->buildResetEmail($user->getFirstName(), $resetLink, $expiresAt))
        );

        return $this->api->success(null, 'Si cet email existe, un lien a été envoyé.');
    }

    #[Route('/reset-password', name: 'api_auth_reset_password', methods: ['POST'])]
    public function resetPassword(Request $request): JsonResponse
    {
        $data     = json_decode($request->getContent(), true);
        $token    = trim((string) ($data['token']    ?? ''));
        $password = trim((string) ($data['password'] ?? ''));

        if ($token === '' || $password === '') {
            return $this->api->error('Token et mot de passe requis.', Response::HTTP_BAD_REQUEST);
        }

        if (mb_strlen($password) < 8) {
            return $this->api->error('Le mot de passe doit contenir au moins 8 caractères.', Response::HTTP_BAD_REQUEST);
        }

        try {
            $user = $this->resetPasswordHelper->validateTokenAndFetchUser($token);
        } catch (ResetPasswordExceptionInterface) {
            return $this->api->error('Ce lien est invalide ou expiré.', Response::HTTP_BAD_REQUEST);
        }

        $this->resetPasswordHelper->removeResetRequest($token);

        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
        $this->userRepository->getEntityManager()->flush();

        return $this->api->success(null, 'Mot de passe réinitialisé avec succès.');
    }

    private function buildResetEmail(string $firstName, string $resetLink, string $expiresAt): string
    {
        return <<<HTML
        <!DOCTYPE html>
        <html lang="fr">
        <head><meta charset="UTF-8" /><title>Réinitialisation du mot de passe</title></head>
        <body style="margin:0;padding:0;background:#f9f5f0;font-family:'Segoe UI',Arial,sans-serif;">
          <table width="100%" cellpadding="0" cellspacing="0" style="background:#f9f5f0;padding:40px 0;">
            <tr><td align="center">
              <table width="560" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08);">
                <tr>
                  <td style="background:#c2440e;padding:32px 40px;text-align:center;">
                    <h1 style="margin:0;color:#ffffff;font-size:22px;font-weight:700;letter-spacing:.5px;">🍳 Ron-recette</h1>
                  </td>
                </tr>
                <tr>
                  <td style="padding:40px;">
                    <p style="margin:0 0 16px;font-size:16px;color:#1c1917;">Bonjour <strong>{$firstName}</strong>,</p>
                    <p style="margin:0 0 24px;font-size:15px;color:#44403c;line-height:1.6;">
                      Vous avez demandé la réinitialisation de votre mot de passe.<br>
                      Cliquez sur le bouton ci-dessous pour en choisir un nouveau.
                    </p>
                    <div style="text-align:center;margin:32px 0;">
                      <a href="{$resetLink}"
                         style="display:inline-block;background:#c2440e;color:#ffffff;text-decoration:none;font-size:15px;font-weight:600;padding:14px 32px;border-radius:8px;">
                        Réinitialiser mon mot de passe
                      </a>
                    </div>
                    <p style="margin:24px 0 0;font-size:13px;color:#78716c;">
                      Ce lien expire à <strong>{$expiresAt}</strong>. Si vous n'êtes pas à l'origine de cette demande, ignorez cet email.
                    </p>
                  </td>
                </tr>
                <tr>
                  <td style="padding:20px 40px;border-top:1px solid #f5f5f4;text-align:center;">
                    <p style="margin:0;font-size:12px;color:#a8a29e;">© Ron-recette — Ne pas répondre à cet email.</p>
                  </td>
                </tr>
              </table>
            </td></tr>
          </table>
        </body>
        </html>
        HTML;
    }
}
