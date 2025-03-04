<?php

namespace App\Controller;

use App\Entity\Message;
use App\Entity\User;
use App\Entity\Conversation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException; 
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use App\Service\BadWordFilter;

class MessageController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private BadWordFilter $badWordFilter;

    public function __construct(EntityManagerInterface $entityManager, BadWordFilter $badWordFilter)
    {
        $this->entityManager = $entityManager;
        $this->badWordFilter = $badWordFilter;
    }

    #[Route('/messages', name: 'messages_list')]
    public function index(): Response
    {
        // Check if user is logged in
        if (!$this->getUser()) {
            $this->addFlash('error', 'Vous devez être connecté pour accéder aux messages.');
            return $this->redirectToRoute('app_login');
        }

        $users = $this->entityManager->getRepository(User::class)->findAll();
        
        return $this->render('messages/index.html.twig', [
            'users' => $users
        ]);
    }

    #[Route('/messages/{id}', name: 'messages_chat')]
    public function chat(User $receiver): Response
    {
        // Check if user is logged in
        $sender = $this->getUser();
        if (!$sender) {
            $this->addFlash('error', 'Vous devez être connecté pour accéder aux messages.');
            return $this->redirectToRoute('app_login');
        }

        // Prevent chatting with yourself
        if ($sender === $receiver) {
            $this->addFlash('error', 'Vous ne pouvez pas discuter avec vous-même.');
            return $this->redirectToRoute('messages_list');
        }
        
        // Get conversation messages between sender and receiver
        $messages = $this->entityManager->getRepository(Message::class)
            ->findConversation($sender, $receiver);

        return $this->render('messages/chat.html.twig', [
            'receiver' => $receiver,
            'messages' => $messages
        ]);
    }

    #[Route('/messages/{id}/send', name: 'messages_send', methods: ['POST'])]
    public function send(Request $request, User $receiver): Response
    {
        $sender = $this->getUser();
        if (!$sender) {
            throw new AccessDeniedException('Vous devez être connecté pour envoyer des messages.');
        }

        // Find or create conversation
        $conversation = $this->entityManager->getRepository(Conversation::class)
            ->findOrCreateConversation($sender, $receiver);

        $content = $request->request->get('content');
        $file = $request->files->get('file');
        $image = $request->files->get('image');
        $voiceMessage = $request->files->get('voice_message');

        if (empty(trim($content)) && !$file && !$image && !$voiceMessage) {
            $this->addFlash('error', 'Le message ne peut pas être vide et aucun fichier n\'a été téléchargé.');
            return $this->redirectToRoute('messages_chat', ['id' => $receiver->getId()]);
        }

        // Filter bad words
        $filteredContent = $this->badWordFilter->filter($content);

        // Optionally warn user if bad words were detected
        if ($this->badWordFilter->containsBadWords($content)) {
            $this->addFlash('warning', 'Votre message contenait des mots inappropriés qui ont été filtrés.');
        }

        $message = new Message();
        $message->setSender($sender);
        $message->setReceiver($receiver);
        $message->setContent($filteredContent);
        $message->setCreatedAt(new \DateTime());
        $message->setConversation($conversation);

        // Handle image upload
        if ($image instanceof UploadedFile) {
            $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($image->getMimeType(), $allowedMimeTypes)) {
                $this->addFlash('error', 'Le type de fichier n\'est pas autorisé. Utilisez JPG, PNG ou GIF.');
                return $this->redirectToRoute('messages_chat', ['id' => $receiver->getId()]);
            }

            $fileName = uniqid() . '.' . $image->guessExtension();
            $filePath = 'uploads/images/' . $fileName;
            
            try {
                if (!file_exists('uploads/images')) {
                    mkdir('uploads/images', 0777, true);
                }
                $image->move('uploads/images', $fileName);
                $message->setFilePath($filePath);
                $message->setFileType('image');
            } catch (FileException $e) {
                $this->addFlash('error', 'Erreur lors du téléchargement de l\'image.');
                return $this->redirectToRoute('messages_chat', ['id' => $receiver->getId()]);
            }
        }
        // Handle voice message upload
        elseif ($voiceMessage) {
            $fileName = uniqid() . '.wav';
            $filePath = 'uploads/voice/' . $fileName;
            try {
                if (!file_exists('uploads/voice')) {
                    mkdir('uploads/voice', 0777, true);
                }
                $voiceMessage->move('uploads/voice', $fileName);
                $message->setFilePath($filePath);
                $message->setFileType('voice');
            } catch (FileException $e) {
                $this->addFlash('error', 'Erreur lors du téléchargement du message vocal.');
                return $this->redirectToRoute('messages_chat', ['id' => $receiver->getId()]);
            }
        }
        // Handle regular file upload
        elseif ($file) {
            $filePath = 'uploads/' . uniqid() . '.' . $file->guessExtension();
            try {
                $file->move('uploads', $filePath);
                $message->setFilePath($filePath);
                $message->setFileType('file');
            } catch (FileException $e) {
                $this->addFlash('error', 'Erreur lors du téléchargement du fichier.');
                return $this->redirectToRoute('messages_chat', ['id' => $receiver->getId()]);
            }
        }

        $this->entityManager->persist($message);
        $this->entityManager->flush();

        return $this->redirectToRoute('messages_chat', ['id' => $receiver->getId()]);
    }

    #[Route('/messages/{id}/send-ajax', name: 'messages_send_ajax', methods: ['POST'])]
    public function sendAjax(Request $request, User $receiver): JsonResponse
    {
        $sender = $this->getUser();
        if (!$sender) {
            return new JsonResponse(['error' => 'Not authenticated'], 403);
        }

        $content = json_decode($request->getContent(), true)['content'] ?? '';
        if (empty(trim($content))) {
            return new JsonResponse(['error' => 'Message cannot be empty'], 400);
        }

        // Filter bad words
        $filteredContent = $this->badWordFilter->filter($content);

        $message = new Message();
        $message->setSender($sender);
        $message->setReceiver($receiver);
        $message->setContent($filteredContent);
        $message->setCreatedAt(new \DateTime());
        
        // Find or create conversation
        $conversation = $this->entityManager->getRepository(Conversation::class)
            ->findOrCreateConversation($sender, $receiver);
        $message->setConversation($conversation);

        $this->entityManager->persist($message);
        $this->entityManager->flush();

        return new JsonResponse([
            'id' => $message->getId(),
            'content' => $filteredContent,
            'createdAt' => $message->getCreatedAt()->format('Y-m-d H:i:s'),
            'senderName' => $message->getSender()->getNom() . ' ' . $message->getSender()->getPrenom(),
            'containedBadWords' => $this->badWordFilter->containsBadWords($content)
        ]);
    }

    #[Route('/messages/{id}/mark-read', name: 'messages_mark_read', methods: ['POST'])]
    public function markAsRead(Message $message): JsonResponse
    {
        // Only allow receiver to mark message as read
        if ($message->getReceiver() !== $this->getUser()) {
            return new JsonResponse(['error' => 'Unauthorized'], 403);
        }

        $message->setIsRead(true);
        $message->setReadAt(new \DateTime());
        
        $this->entityManager->persist($message);
        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'readAt' => $message->getReadAt()->format('H:i')
        ]);
    }

    #[Route('/messages/{id}/check-new', name: 'messages_check_new', methods: ['GET'])]
    public function checkNewMessages(Request $request, User $receiver): JsonResponse
    {
        $sender = $this->getUser();
        if (!$sender) {
            return new JsonResponse(['error' => 'Not authenticated'], 403);
        }

        $since = new \DateTime($request->query->get('since', 'now'));
        $messages = $this->entityManager->getRepository(Message::class)
            ->findLatestMessages($sender, $receiver, $since);

        $formattedMessages = array_map(function(Message $message) {
            return [
                'id' => $message->getId(),
                'content' => $message->getContent(),
                'createdAt' => $message->getCreatedAt()->format('Y-m-d H:i:s'),
                'senderName' => $message->getSender()->getNom() . ' ' . $message->getSender()->getPrenom(),
                'isSender' => $message->getSender() === $this->getUser(),
                'isRead' => $message->isRead(),
                'readAt' => $message->getReadAt() ? $message->getReadAt()->format('H:i') : null
            ];
        }, $messages);

        return new JsonResponse($formattedMessages);
    }

    #[Route('/messages/{id}/delete', name: 'messages_delete', methods: ['DELETE'])]
    public function deleteMessage(Message $message): JsonResponse
    {
        // Check if user is authorized to delete this message
        if ($message->getSender() !== $this->getUser()) {
            return new JsonResponse(['error' => 'Vous n\'êtes pas autorisé à supprimer ce message.'], 403);
        }

        try {
            // Delete associated file if exists
            if ($message->getFilePath()) {
                $filePath = $this->getParameter('kernel.project_dir') . '/public/' . $message->getFilePath();
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            $this->entityManager->remove($message);
            $this->entityManager->flush();

            return new JsonResponse(['success' => true]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Une erreur est survenue lors de la suppression.'], 500);
        }
    }

    #[Route('/messages/{id}/edit', name: 'messages_edit', methods: ['POST'])]
    public function editMessage(Request $request, Message $message): JsonResponse
    {
        // Check if user is authorized to edit this message
        if ($message->getSender() !== $this->getUser()) {
            return new JsonResponse(['error' => 'Vous n\'êtes pas autorisé à modifier ce message.'], 403);
        }

        try {
            $messageType = $message->getFileType();

            if ($messageType === 'voice') {
                $voiceFile = $request->files->get('voice_message');
                if (!$voiceFile) {
                    return new JsonResponse(['error' => 'Aucun message vocal fourni.'], 400);
                }

                // Delete old voice file if exists
                if ($message->getFilePath()) {
                    $oldFilePath = $this->getParameter('kernel.project_dir') . '/public/' . $message->getFilePath();
                    if (file_exists($oldFilePath)) {
                        unlink($oldFilePath);
                    }
                }

                // Save new voice file
                $fileName = uniqid() . '.wav';
                $filePath = 'uploads/voice/' . $fileName;
                $voiceFile->move('uploads/voice', $fileName);
                
                $message->setFilePath($filePath);
            } 
            elseif ($messageType === 'file') {
                $file = $request->files->get('file');
                if (!$file) {
                    return new JsonResponse(['error' => 'Aucun fichier fourni.'], 400);
                }

                // Delete old file if exists
                if ($message->getFilePath()) {
                    $oldFilePath = $this->getParameter('kernel.project_dir') . '/public/' . $message->getFilePath();
                    if (file_exists($oldFilePath)) {
                        unlink($oldFilePath);
                    }
                }

                // Save new file
                $filePath = 'uploads/' . uniqid() . '.' . $file->guessExtension();
                $file->move('uploads', $filePath);
                
                $message->setFilePath($filePath);
            }
            else {
                $content = json_decode($request->getContent(), true)['content'] ?? '';
                if (empty(trim($content))) {
                    return new JsonResponse(['error' => 'Le message ne peut pas être vide.'], 400);
                }
                
                // Filter bad words in edited content
                $filteredContent = $this->badWordFilter->filter($content);
                $message->setContent($filteredContent);
            }

            $message->setEditedAt(new \DateTime());
            $this->entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'content' => $message->getContent(),
                'filePath' => $message->getFilePath(),
                'editedAt' => $message->getEditedAt()->format('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Une erreur est survenue lors de la modification.'], 500);
        }
    }
} 