<?php
namespace App\Controller;

use App\Entity\User;
use App\Security\EmailVerifier;
use Symfony\Component\Mime\Email;
use App\Repository\UserRepository;
use Symfony\Component\Mime\Address;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
#[Route('/api')]
class AuthController extends AbstractController
{
    private EmailVerifier $emailVerifier;

    public function __construct(EmailVerifier $emailVerifier)
    {
        $this->emailVerifier = $emailVerifier;
    }

    #[Route('/login', name: 'api_login', methods: ['POST'])]
    public function login(): JsonResponse
    {
        throw $this->createNotFoundException('Use /login for web authentication');
    }

    #[Route('/register', name: 'api_register', methods: ['POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        $user = new User();
        $user->setEmail($data['email']);
        $user->setFirstName($data['firstName']);
        $user->setLastName($data['lastName']);
        $user->setBirthDate(new \DateTime($data['birthDate']));
        $user->setPassword(
            $passwordHasher->hashPassword($user, $data['password'])
        );

        $entityManager->persist($user);
        $entityManager->flush();

        // Envoyer email de vérification
        $this->emailVerifier->sendEmailConfirmation('app_verify_email', $user);

        return $this->json([
            'message' => 'User registered successfully. A confirmation email has been sent.',
            'user' => [
                'email' => $user->getEmail(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName()
            ]
        ], 201);


        
    }

    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager
    ): RedirectResponse {
        $id = $request->get('id');

        if (!$id) {
            return new RedirectResponse('/login');
        }

        $user = $userRepository->find($id);
        if (!$user) {
            return new RedirectResponse('/login');
        }

        try {
            $this->emailVerifier->handleEmailConfirmation($request, $user);
        } catch (VerifyEmailExceptionInterface $exception) {
            return new RedirectResponse('/login?error=invalid_token');
        }

        $user->setIsVerified(true);
        $entityManager->persist($user);
        $entityManager->flush();

        return new RedirectResponse('/login?success=email_verified');
    }
}
