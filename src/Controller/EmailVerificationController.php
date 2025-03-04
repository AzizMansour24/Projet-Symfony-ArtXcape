<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mailer\Mailer;

class EmailVerificationController extends AbstractController
{
    private $mailer;

    public function __construct(TransportInterface $transport)
    {
        $this->mailer = new Mailer($transport);
    }

    #[Route('/verify-email', name: 'verify_email', methods: ['POST'])]
    public function sendVerification(Request $request): Response
    {
        try {
            $data = json_decode($request->getContent(), true);
            $email = $data['email'] ?? null;
            $donorname = $data['donorname'] ?? null;
            $montant = $data['montant'] ?? null;
            $num_tlf = $data['num_tlf'] ?? null;

            if (!$email) {
                return $this->json(['error' => 'Email is required'], 400);
            }

            // Store payment data in session
            $session = $request->getSession();
            $session->set('email', $email);
            $session->set('donorname', $donorname);
            $session->set('amount', $montant);
            $session->set('phone', $num_tlf);

            // Generate verification code
            $verificationCode = sprintf('%06d', random_int(0, 999999));
            
            // Store verification code in session
            $session->set('verification_code', $verificationCode);
            $session->set('verification_email', $email);

            try {
                // Send email using Symfony Mailer
                $emailMessage = (new Email())
                    ->from('tahahkiri69@gmail.com')
                    ->to($email)
                    ->subject('ArtVista - Your Verification Code')
                    ->html("
                        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                            <h2 style='color: #2a5298;'>Email Verification</h2>
                            <p>Your verification code is:</p>
                            <h1 style='color: #1e3c72; font-size: 32px; letter-spacing: 5px;'>{$verificationCode}</h1>
                            <p>Please enter this code to complete your payment verification.</p>
                            <p style='color: #666;'>This code will expire in 10 minutes.</p>
                        </div>
                    ");

                $this->mailer->send($emailMessage);

                return $this->json(['message' => 'Verification email sent']);
            } catch (\Exception $e) {
                error_log('Mailer Error: ' . $e->getMessage());
                return $this->json([
                    'error' => 'Failed to send verification email',
                    'details' => $e->getMessage()
                ], 500);
            }
        } catch (\Exception $e) {
            error_log('General Error: ' . $e->getMessage());
            return $this->json([
                'error' => 'Failed to process request',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/verify-code', name: 'verify_code_page')]
    public function verificationPage(): Response
    {
        return $this->render('verification/verify-code.html.twig');
    }

    #[Route('/check-code', name: 'check_verification_code', methods: ['POST'])]
    public function checkCode(Request $request): Response
    {
        try {
            $data = json_decode($request->getContent(), true);
            $submittedCode = $data['code'] ?? null;
            
            if (!$submittedCode) {
                return $this->json(['error' => 'Verification code is required'], 400);
            }

            $storedCode = $request->getSession()->get('verification_code');
            $storedEmail = $request->getSession()->get('verification_email');

            if (!$storedCode || !$storedEmail) {
                return $this->json(['error' => 'Verification session expired'], 400);
            }

            if ($submittedCode === $storedCode) {
                // Clear verification data
                $request->getSession()->remove('verification_code');
                $request->getSession()->remove('verification_email');
                
                // Store verification success in session
                $request->getSession()->set('email_verified', true);
                
                // Return success response
                return $this->json(['success' => true]);
            }

            return $this->json(['error' => 'Invalid verification code'], 400);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Error processing verification'], 500);
        }
    }
} 