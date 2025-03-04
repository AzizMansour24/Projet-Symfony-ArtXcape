<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Twilio\Rest\Client;
use Twilio\Exceptions\TwilioException;

class TwilioVerificationController extends AbstractController
{
    private $twilioSid;
    private $twilioToken;
    private $serviceSid;

    public function __construct()
    {
        $this->twilioSid = $_ENV['TWILIO_ACCOUNT_SID'];
        $this->twilioToken = $_ENV['TWILIO_AUTH_TOKEN'];
        $this->serviceSid = $_ENV['TWILIO_VERIFY_SERVICE_SID'];
    }

    #[Route('/send-verification', name: 'send_verification', methods: ['POST'])]
    public function sendVerification(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            if (!isset($data['phone'])) {
                return new JsonResponse(['error' => 'Phone number is required'], 400);
            }
            
            $phoneNumber = $data['phone'];
            
            // Basic phone number validation
            if (!preg_match('/^\+?[1-9]\d{1,14}$/', $phoneNumber)) {
                return new JsonResponse(['error' => 'Invalid phone number format'], 400);
            }

            $twilio = new Client($this->twilioSid, $this->twilioToken);

            $verification = $twilio->verify->v2->services($this->serviceSid)
                ->verifications
                ->create($phoneNumber, "sms");

            return new JsonResponse([
                'status' => $verification->status,
                'message' => 'Verification code sent successfully'
            ]);
            
        } catch (TwilioException $e) {
            return new JsonResponse([
                'error' => 'Twilio error: ' . $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/verify-code', name: 'verify_code', methods: ['POST'])]
    public function verifyCode(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            if (!isset($data['phone']) || !isset($data['code'])) {
                return new JsonResponse(['error' => 'Phone number and code are required'], 400);
            }
            
            $phoneNumber = $data['phone'];
            $code = $data['code'];

            $twilio = new Client($this->twilioSid, $this->twilioToken);

            $verification_check = $twilio->verify->v2->services($this->serviceSid)
                ->verificationChecks
                ->create([
                    'to' => $phoneNumber,
                    'code' => $code
                ]);

            if ($verification_check->status === 'approved') {
                return new JsonResponse([
                    'status' => 'approved',
                    'message' => 'Code verified successfully'
                ]);
            } else {
                return new JsonResponse([
                    'error' => 'Invalid verification code',
                    'status' => $verification_check->status
                ], 400);
            }
            
        } catch (TwilioException $e) {
            return new JsonResponse([
                'error' => 'Twilio error: ' . $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }
} 