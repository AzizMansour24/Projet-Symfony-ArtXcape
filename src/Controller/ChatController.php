<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\GeminiChatbot;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ChatController extends AbstractController
{
    private $chatbot;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->chatbot = new GeminiChatbot(
            $_ENV['GEMINI_API_KEY'],
            $httpClient
        );
    }

    #[Route('/chat', name: 'chat_message', methods: ['POST'])]
    public function chat(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $message = $data['message'] ?? '';
            
            if (empty($message)) {
                return new JsonResponse([
                    'error' => 'Message cannot be empty'
                ], 400);
            }
            
            // Get user ID if user is logged in
            $userId = $this->getUser() ? $this->getUser()->getId() : 'guest_' . session_id();
            
            $response = $this->chatbot->get_response($message, $userId);
            
            if (strpos($response, 'Error:') === 0) {
                return new JsonResponse([
                    'error' => $response
                ], 500);
            }
            
            return new JsonResponse(['response' => $response]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }
} 