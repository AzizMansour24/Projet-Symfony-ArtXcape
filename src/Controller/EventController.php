<?php

namespace App\Controller;

use App\Entity\Event;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Form\EventaddmodifyType;
use Symfony\Component\HttpFoundation\Request;
use App\Form\EventeditType;
use App\Entity\Donation;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Service\GeminiChatbot;
use Symfony\Component\HttpClient\HttpClient;

final class EventController extends AbstractController
{
    #[Route('/event', name: 'app_event')]
    public function index(): Response
    {
        return $this->render('event/index.html.twig', [
            'controller_name' => 'EventController',
        ]);
    }

    #[Route('/event/{idevent}', name: 'event_details')]
    public function details(EntityManagerInterface $entityManager, int $idevent): Response
    {
        $event = $entityManager
            ->getRepository(Event::class)
            ->find($idevent);

        if (!$event) {
            throw $this->createNotFoundException('Event not found');
        }

        return $this->render('frontOffice/details.html.twig', [
            'event' => $event,
        ]);
    }

    #[Route('/events', name: 'events_list')]
    public function listEvents(EntityManagerInterface $entityManager): Response
    {
        $events = $entityManager
            ->getRepository(Event::class)
            ->findAll();

        // Calculate donation progress for each event
        foreach ($events as $event) {
            $donations = $entityManager->getRepository(Donation::class)
                ->createQueryBuilder('d')
                ->select('SUM(d.montant) as total')
                ->where('d.idevent = :eventId')
                ->setParameter('eventId', $event->getIdevent())
                ->getQuery()
                ->getSingleScalarResult() ?? 0;

            // Calculate percentage
            $objective = $event->getDonationObjective();
            $percentage = $objective > 0 ? min(100, ($donations / $objective) * 100) : 0;
            
            // Add these values to the event object temporarily
            $event->currentDonations = $donations;
            $event->donationPercentage = round($percentage, 1);
        }

        return $this->render('frontOffice/event/event-4.html.twig', [
            'events' => $events,
            'here_maps_api_key' => $this->getParameter('here_maps_api_key'),
        ]);
    }

    #[Route('/backevent', name: 'back_showevent')]
    public function show(EntityManagerInterface $entityManager): Response
    {
        $events = $entityManager->getRepository(Event::class)->findAll();
        
        // Create forms for each event
        $forms = [];
        foreach ($events as $event) {
            $forms[$event->getIdevent()] = $this->createForm(EventeditType::class, $event)->createView();
        }
        
        return $this->render('backOffice/events/eventshow.html.twig', [
            'events' => $events,
            'forms' => $forms,
        ]);
    }

    #[Route('/event/delete/{id}', name: 'app_event_delete')]
    public function delete(Event $event, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($event);
        $entityManager->flush();

        $this->addFlash('success', 'Event deleted successfully');

        return $this->redirectToRoute('back_showevent');
    }

    #[Route('/eventadd', name: 'app_event_new')]
    public function createOrEditEvent(
        Request $request, 
        EntityManagerInterface $entityManager,
        Event $event = null
    ): Response
    {
        $event = $event ?? new Event();
        $form = $this->createForm(EventaddmodifyType::class, $event);
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $imageFile */
        $imageFile = $form->get('image')->getData();

        if ($imageFile) {
            // Generate a unique filename for the image
            $newFilename = uniqid() . '.' . $imageFile->guessExtension();

            // Move the uploaded file to the specified directory
            $imageFile->move(
                $this->getParameter('images_directory'), // Directory from services.yaml
                $newFilename
            );

            // Save the filename (path) into the article entity
            $event->setImage($newFilename);
        }
            
            
            $entityManager->persist($event);
            $entityManager->flush();
            
            $this->addFlash('success', 'Event has been saved successfully!');
            return $this->redirectToRoute('back_showevent'); // Adjust this route to your events listing page
        }
        
        return $this->render('backOffice/events/event_form.html.twig', [
            'form' => $form->createView(),
            'event' => $event
        ]);
    }

    #[Route('/event/edit/{id}', name: 'app_event_edit', methods: ['POST'])]
    public function editEvent(
        Request $request, 
        Event $event, 
        EntityManagerInterface $entityManager
    ): Response 
    {
        $form = $this->createForm(EventeditType::class, $event);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->flush();
                return $this->json([
                    'success' => true,
                    'message' => 'Event updated successfully'
                ]);
            } catch (\Exception $e) {
                return $this->json([
                    'success' => false,
                    'message' => 'Error updating event: ' . $e->getMessage()
                ], 500);
            }
        }
        
        return $this->json([
            'success' => false,
            'message' => 'Invalid form data'
        ], 400);
    }

    #[Route('/event/quick-view/{idevent}', name: 'event_quick_view')]
    public function quickView(EntityManagerInterface $entityManager, int $idevent): Response
    {
        $event = $entityManager
            ->getRepository(Event::class)
            ->find($idevent);

        if (!$event) {
            throw $this->createNotFoundException('Event not found');
        }

        // Calculate donation progress
        $donations = $entityManager->getRepository(Donation::class)
            ->createQueryBuilder('d')
            ->select('SUM(d.montant) as total')
            ->where('d.idevent = :eventId')
            ->setParameter('eventId', $event->getIdevent())
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        // Calculate percentage
        $objective = $event->getDonationObjective();
        $percentage = 0;
        if ($objective > 0) {
            $percentage = min(100, ($donations / $objective) * 100);
        }
        
        // Add these values to the event object temporarily
        $event->currentDonations = (float)$donations;
        $event->donationPercentage = round($percentage, 1);

        return $this->render('frontOffice/event/_quick_view_modal.html.twig', [
            'event' => $event
        ]);
    }

    #[Route('/generate-event', name: 'generate_event', methods: ['POST'])]
    public function generateEvent(Request $request, GeminiChatbot $chatbot): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $prompt = $data['prompt'] ?? '';

            if (!$prompt) {
                throw new \Exception('Prompt is required');
            }

            $aiPrompt = "You are an event planning assistant. Generate a JSON object for an event based on this description: {$prompt}. 
                         Format your response as a valid JSON object with NO additional text or explanation. Use this exact structure:
                         {
                             \"titre\": \"[title]\",
                             \"description\": \"[description]\",
                             \"eventMission\": \"[mission]\",
                             \"lieu\": \"[location]\",
                             \"nombreBillets\": [number],
                             \"seatprice\": [price],
                             \"donation_objective\": [goal]
                         }
                         Replace the placeholders with appropriate values. nombreBillets should be between 50-500, seatprice between 10-200, and donation_objective between 1000-50000.";

            $response = $chatbot->get_response($aiPrompt);
            
            // Log the raw response for debugging
            error_log('Raw AI Response: ' . $response);

            // Try to extract JSON from the response
            if (preg_match('/\{.*\}/s', $response, $matches)) {
                $jsonStr = $matches[0];
                // Clean the JSON string
                $jsonStr = preg_replace('/[\x00-\x1F\x7F]/u', '', $jsonStr);
                $eventData = json_decode($jsonStr, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \Exception('Invalid JSON format: ' . json_last_error_msg());
                }

                // Validate required fields
                $requiredFields = ['titre', 'description', 'eventMission', 'lieu', 'nombreBillets', 'seatprice', 'donation_objective'];
                foreach ($requiredFields as $field) {
                    if (!isset($eventData[$field])) {
                        throw new \Exception("Missing required field: {$field}");
                    }
                }

                // Ensure numerical fields are numbers
                $eventData['nombreBillets'] = (int)$eventData['nombreBillets'];
                $eventData['seatprice'] = (float)$eventData['seatprice'];
                $eventData['donation_objective'] = (float)$eventData['donation_objective'];

                return new JsonResponse([
                    'success' => true,
                    'event' => $eventData
                ]);
            }

            throw new \Exception('No valid JSON found in AI response');

        } catch (\Exception $e) {
            error_log('Generation error: ' . $e->getMessage());
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    #[Route('/extract-event-from-url', name: 'extract_event_from_url', methods: ['POST'])]
    public function extractEventFromUrl(Request $request, GeminiChatbot $geminiChatbot): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $url = $data['url'] ?? null;

        if (!$url) {
            return new JsonResponse(['success' => false, 'message' => 'URL is required'], 400);
        }

        try {
            // Create a prompt for Gemini to extract event information from the URL
            $prompt = "You are an event information extractor. Extract event information from this URL: {$url}. 
                      Format your response as a valid JSON object with NO additional text or explanation. Use this exact structure:
                      {
                          \"titre\": \"[title]\",
                          \"description\": \"[description]\",
                          \"eventMission\": \"[mission]\",
                          \"lieu\": \"[location]\",
                          \"nombreBillets\": [number],
                          \"seatprice\": [price],
                          \"donation_objective\": [goal]
                      }
                      Replace the placeholders with appropriate values. nombreBillets should be between 50-500, seatprice between 10-200, and donation_objective between 1000-50000.";

            $response = $geminiChatbot->get_response($prompt);
            
            // Log the raw response for debugging
            error_log('Raw AI Response: ' . $response);

            // Try to extract JSON from the response
            if (preg_match('/\{.*\}/s', $response, $matches)) {
                $jsonStr = $matches[0];
                // Clean the JSON string
                $jsonStr = preg_replace('/[\x00-\x1F\x7F]/u', '', $jsonStr);
                $eventDetails = json_decode($jsonStr, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \Exception('Invalid JSON format: ' . json_last_error_msg());
                }

                // Validate required fields
                $requiredFields = ['titre', 'description', 'eventMission', 'lieu', 'nombreBillets', 'seatprice', 'donation_objective'];
                foreach ($requiredFields as $field) {
                    if (!isset($eventDetails[$field])) {
                        throw new \Exception("Missing required field: {$field}");
                    }
                }

                // Ensure numerical fields are numbers
                $eventDetails['nombreBillets'] = (int)$eventDetails['nombreBillets'];
                $eventDetails['seatprice'] = (float)$eventDetails['seatprice'];
                $eventDetails['donation_objective'] = (float)$eventDetails['donation_objective'];

                return new JsonResponse([
                    'success' => true,
                    'event' => $eventDetails
                ]);
            }

            throw new \Exception('No valid JSON found in AI response');
        } catch (\Exception $e) {
            error_log('URL Extraction error: ' . $e->getMessage());
            return new JsonResponse([
                'success' => false,
                'message' => 'Failed to extract event details: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/generate-image', name: 'generate_image', methods: ['POST'])]
    public function generateImage(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $prompt = $data['prompt'] ?? null;
            $apiKey = $data['api_key'] ?? null;

            if (!$prompt || !$apiKey) {
                throw new \Exception('Missing required parameters');
            }

            $client = HttpClient::create();
            $response = $client->request('POST', 'https://api.edenai.run/v2/image/generation', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'providers' => 'stabilityai',
                    'text' => $prompt,
                    'resolution' => '512x512',
                    'num_images' => 1
                ]
            ]);

            $result = json_decode($response->getContent(), true);

            if (isset($result['stabilityai']['items'][0]['image_resource_url'])) {
                return $this->json([
                    'success' => true,
                    'imageUrl' => $result['stabilityai']['items'][0]['image_resource_url']
                ]);
            } else {
                throw new \Exception('Failed to generate image');
            }
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
