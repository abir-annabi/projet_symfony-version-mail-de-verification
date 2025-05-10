<?php
namespace App\Security;

use App\Entity\User;
use Twig\Environment;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

class EmailVerifier
{
    private VerifyEmailHelperInterface $verifyEmailHelper;
    private MailerInterface $mailer;
    private UrlGeneratorInterface $router;
    private Environment $twig;

    public function __construct(
        VerifyEmailHelperInterface $helper,
        MailerInterface $mailer,
        UrlGeneratorInterface $router,
        Environment $twig
    ) {
        $this->verifyEmailHelper = $helper;
        $this->mailer = $mailer;
        $this->router = $router;
        $this->twig = $twig;
    }

    public function sendEmailConfirmation(string $routeName, User $user): void
    {
        $signatureComponents = $this->verifyEmailHelper->generateSignature(
            $routeName,
            $user->getId(),
            $user->getEmail(),
            ['id' => $user->getId()] // Ensure ID is in URL
        );

        // Manually render the template
        $emailContent = $this->twig->render('emails/confirmation_email.html.twig', [
            'signedUrl' => $signatureComponents->getSignedUrl(),
            'expiresAt' => $signatureComponents->getExpiresAt(),
            'user' => $user
        ]);

        $email = (new Email())
            ->from(new Address('no-reply@kteby.com', 'Kteby'))
            ->to($user->getEmail())
            ->subject('Please Confirm your Email')
            ->html($emailContent);

        $this->mailer->send($email);
    }

    public function handleEmailConfirmation(Request $request, User $user): void
    {
        $this->verifyEmailHelper->validateEmailConfirmation(
            $request->getUri(),
            $user->getId(),
            $user->getEmail()
        );
    }
}