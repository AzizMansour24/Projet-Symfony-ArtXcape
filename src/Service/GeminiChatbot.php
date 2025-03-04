<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;

class GeminiChatbot
{
    private $apiKey;
    private $baseUrl;
    private $httpClient;
    private $chatHistoryDir;

    public function __construct(string $apiKey, HttpClientInterface $httpClient)
    {
        $this->apiKey = $apiKey;
        $this->baseUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent";
        $this->httpClient = $httpClient;
        $this->chatHistoryDir = __DIR__ . '/../../public/chathistory';
    }

    private function saveChatHistory(string $userId, string $message, string $response): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $filename = $this->chatHistoryDir . '/chat_' . $userId . '.txt';
        
        $logEntry = sprintf(
            "[%s]\nUser: %s\nBot: %s\n%s\n\n",
            $timestamp,
            $message,
            $response,
            str_repeat('-', 50)
        );
        
        file_put_contents($filename, $logEntry, FILE_APPEND);
    }

    public function get_response(string $prompt, ?string $userId = null): string
    {
        try {
            $url = $this->baseUrl . "?key=" . $this->apiKey;
            
            error_log("Attempting API call to: " . $url);
            
            $data = [
                "contents" => [[
                    "parts" => [[
                        "text" => $prompt
                    ]]
                ]]
            ];
            
            error_log("Request data: " . json_encode($data));
            
            $response = $this->httpClient->request('POST', $url, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ],
                'json' => $data,
                'verify_peer' => true,
                'timeout' => 30
            ]);
            
            $rawContent = $response->getContent();
            error_log("Gemini API Response: " . $rawContent);
            
            $result = $response->toArray();
            
            if (!isset($result['candidates'][0]['content']['parts'][0]['text'])) {
                error_log("Unexpected API response structure: " . json_encode($result));
                return "Error: Unexpected API response structure";
            }
            
            $botResponse = $result['candidates'][0]['content']['parts'][0]['text'];
            
            if ($userId !== null) {
                $this->saveChatHistory($userId, $prompt, $botResponse);
            }
            
            return $botResponse;
            
        } catch (HttpExceptionInterface $e) {
            error_log("HTTP Exception: " . $e->getMessage());
            return "Error: API request failed - " . $e->getMessage();
        } catch (TransportExceptionInterface $e) {
            error_log("Transport Exception: " . $e->getMessage());
            return "Error: Connection failed - " . $e->getMessage();
        } catch (\Exception $e) {
            error_log("General Exception: " . $e->getMessage());
            return "Error: " . $e->getMessage();
        }
    }
} 