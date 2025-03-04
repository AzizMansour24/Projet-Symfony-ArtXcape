<?php

namespace App\Controller;

use App\Entity\Formation;
use App\Entity\Categorie;
use App\Entity\Inscription;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use App\Form\FormationType;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\JsonResponse;

final class FormationController extends AbstractController
{
    private $projectDir;

    public function __construct(ParameterBagInterface $params)
    {
        $this->projectDir = $params->get('kernel.project_dir');
    }

    #[Route('/backformation', name: 'back_showformation')]
    public function show(EntityManagerInterface $entityManager): Response
    {
        $formations = $entityManager->getRepository(Formation::class)->findAll();
        
        return $this->render('backOffice/formation/formation.html.twig', [
            'formations' => $formations,
        ]);
    }

    #[Route('/new', name: 'back_newformation')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $formation = new Formation();
        $formation->setDateCreation(new \DateTime());

        $form = $this->createForm(FormationType::class, $formation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($formation);
            $entityManager->flush();

            $this->addFlash('success', 'La formation a été créée avec succès!');
            return $this->redirectToRoute('back_showformation');
        }

        return $this->render('backOffice/formation/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/formation/edit/{id}', name: 'back_editformation')]
    public function edit(Request $request, EntityManagerInterface $entityManager, Formation $formation): Response
    {
        $form = $this->createFormBuilder($formation)
            ->add('titre', TextType::class, [
                'label' => 'Titre de la formation',
                'attr' => [
                    'class' => 'form-control form-control-lg',
                    'placeholder' => 'Entrez le titre de la formation'
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'class' => 'form-control form-control-lg',
                    'rows' => 4,
                    'placeholder' => 'Décrivez le contenu de la formation...'
                ]
            ])
            ->add('date_debut', DateType::class, [
                'label' => 'Date de début',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control form-control-lg']
            ])
            ->add('date_fin', DateType::class, [
                'label' => 'Date de fin',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control form-control-lg']
            ])
            ->add('nbrpart', NumberType::class, [
                'label' => 'Nombre de participants',
                'attr' => [
                    'class' => 'form-control form-control-lg',
                    'min' => 1
                ]
            ])
            ->add('prix', MoneyType::class, [
                'label' => 'Prix',
                'currency' => 'TND',
                'attr' => ['class' => 'form-control form-control-lg']
            ])
            ->add('video', UrlType::class, [
                'label' => 'Lien vidéo',
                'required' => false,
                'attr' => [
                    'class' => 'form-control form-control-lg',
                    'placeholder' => 'URL de la vidéo (YouTube, Vimeo, etc.)'
                ]
            ])
            ->add('categorie', EntityType::class, [
                'class' => Categorie::class,
                'choice_label' => 'nom',
                'label' => 'Catégorie',
                'attr' => ['class' => 'form-control form-control-lg']
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Mettre à jour la formation',
                'attr' => ['class' => 'btn btn-gradient-primary btn-lg font-weight-medium']
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'La formation a été mise à jour avec succès!');
            return $this->redirectToRoute('back_showformation');
        }

        return $this->render('backOffice/formation/edit.html.twig', [
            'form' => $form->createView(),
            'formation' => $formation
        ]);
    }

    #[Route('/formation/delete/{id}', name: 'back_deleteformation')]
    public function delete(EntityManagerInterface $entityManager, Formation $formation): Response
    {
        // Delete the formation
        $entityManager->remove($formation);
        $entityManager->flush();

        $this->addFlash('success', 'La formation a été supprimée avec succès!');
        return $this->redirectToRoute('back_showformation');
    }

    #[Route('/formation', name: 'front_formation')]
    public function frontShow(EntityManagerInterface $entityManager, Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $limit = 6; // formations per page

        $formationRepository = $entityManager->getRepository(Formation::class);
        
        // Get total formations
        $totalFormations = $formationRepository->count([]);
        
        // Calculate total pages
        $totalPages = ceil($totalFormations / $limit);
        
        // Get paginated formations
        $formations = $formationRepository->findBy(
            [], // criteria
            ['date_debut' => 'DESC'], // order by
            $limit, // limit
            ($page - 1) * $limit // offset
        );

        $userInscriptions = [];
        if ($this->getUser()) {
            $userInscriptions = $entityManager->getRepository(Inscription::class)->findBy([
                'user' => $this->getUser()
            ]);
        }

        return $this->render('frontOffice/formation/formation.html.twig', [
            'formations' => $formations,
            'userInscriptions' => $userInscriptions,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'limit' => $limit
        ]);
    }

    #[Route('/formation/{id}/inscription', name: 'formation_inscription', methods: ['GET'])]
    public function inscription(Request $request, Formation $formation, EntityManagerInterface $entityManager): Response
    {
        // Vérifier si l'utilisateur est connecté
        if (!$this->getUser()) {
            $this->addFlash('error', 'Vous devez être connecté pour vous inscrire à une formation');
            return $this->redirectToRoute('app_login');
        }

        // Vérifier le token CSRF
        $submittedToken = $request->query->get('_token');
        if (!$this->isCsrfTokenValid('inscription' . $formation->getId(), $submittedToken)) {
            $this->addFlash('error', 'Token invalide');
            return $this->redirectToRoute('front_formation');
        }

        // Vérifier si l'utilisateur n'est pas déjà inscrit
        $existingInscription = $entityManager->getRepository(Inscription::class)->findOneBy([
            'user' => $this->getUser(),
            'formation' => $formation
        ]);

        if ($existingInscription) {
            $this->addFlash('warning', 'Vous êtes déjà inscrit à cette formation');
            return $this->redirectToRoute('front_formation');
        }

        // Vérifier s'il reste des places
        if ($formation->getNbrpart() <= 0) {
            $this->addFlash('error', 'Désolé, il n\'y a plus de places disponibles pour cette formation');
            return $this->redirectToRoute('front_formation');
        }

        // Créer l'inscription
        $inscription = new Inscription();
        $inscription->setUser($this->getUser());
        $inscription->setFormation($formation);
        $inscription->setDateInscription(new \DateTime());
        $inscription->setDateCreation(new \DateTime());
        $inscription->setStatut('Pending');

        // Décrémenter le nombre de places disponibles
        $formation->setNbrpart($formation->getNbrpart() - 1);

        $entityManager->persist($inscription);
        $entityManager->flush();

        $this->addFlash('success', 'Votre inscription a été enregistrée avec succès !');
        return $this->redirectToRoute('front_formation');
    }

    private function makeOpenAIRequest(HttpClientInterface $httpClient, string $audioFilePath, string $boundary, int $attempt = 1): string
    {
        try {
            if ($attempt > 1) {
                sleep(5);
            }

            $response = $httpClient->request('POST', 'https://api.openai.com/v1/audio/transcriptions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->getParameter('app.openai_api_key'),
                    'Content-Type' => 'multipart/form-data; boundary=' . $boundary,
                ],
                'body' => $this->createMultipartContent($audioFilePath, $boundary),
                'timeout' => 180,
            ]);

            return $response->getContent();
        } catch (HttpExceptionInterface $e) {
            if ($e->getResponse()->getStatusCode() === 429) {
                return $this->fallbackTranscription($audioFilePath);
            }
            throw $e;
        }
    }

    private function createMultipartContent(string $audioFilePath, string $boundary): string
    {
        $content = "--{$boundary}\r\n";
        $content .= "Content-Disposition: form-data; name=\"file\"; filename=\"audio.mp3\"\r\n";
        $content .= "Content-Type: audio/mpeg\r\n\r\n";
        $content .= file_get_contents($audioFilePath) . "\r\n";
        $content .= "--{$boundary}\r\n";
        $content .= "Content-Disposition: form-data; name=\"model\"\r\n\r\n";
        $content .= "whisper-1\r\n";
        $content .= "--{$boundary}\r\n";
        $content .= "Content-Disposition: form-data; name=\"response_format\"\r\n\r\n";
        $content .= "text\r\n";
        $content .= "--{$boundary}--\r\n";
        
        return $content;
    }

    private function fallbackTranscription(string $audioFilePath): string
    {
        try {
            // Utiliser AssemblyAI pour la transcription
            $client = HttpClient::create();
            
            // Upload the file
            $uploadResponse = $client->request('POST', 'https://api.assemblyai.com/v2/upload', [
                'headers' => [
                    'Authorization' => $this->getParameter('app.assemblyai_api_key')
                ],
                'body' => fopen($audioFilePath, 'r')
            ]);

            $uploadData = json_decode($uploadResponse->getContent(), true);
            if (!isset($uploadData['upload_url'])) {
                throw new \Exception('Erreur lors de l\'upload du fichier audio');
            }

            // Create transcription
            $transcribeResponse = $client->request('POST', 'https://api.assemblyai.com/v2/transcript', [
                'headers' => [
                    'Authorization' => $this->getParameter('app.assemblyai_api_key'),
                    'Content-Type' => 'application/json'
                ],
                'json' => [
                    'audio_url' => $uploadData['upload_url'],
                    'language_code' => 'fr'
                ]
            ]);

            $transcribeData = json_decode($transcribeResponse->getContent(), true);
            if (!isset($transcribeData['id'])) {
                throw new \Exception('Erreur lors de la création de la transcription');
            }

            // Poll for completion
            $id = $transcribeData['id'];
            do {
                sleep(3);
                $pollResponse = $client->request('GET', "https://api.assemblyai.com/v2/transcript/{$id}", [
                    'headers' => [
                        'Authorization' => $this->getParameter('app.assemblyai_api_key')
                    ]
                ]);
                
                $pollData = json_decode($pollResponse->getContent(), true);
                
            } while ($pollData['status'] === 'processing' || $pollData['status'] === 'queued');

            if ($pollData['status'] !== 'completed') {
                throw new \Exception('Erreur lors de la transcription: ' . ($pollData['error'] ?? 'Erreur inconnue'));
            }

            return $pollData['text'];
        } catch (\Exception $e) {
            throw new \Exception('Erreur AssemblyAI: ' . $e->getMessage());
        }
    }

    #[Route('/formation/{id}/extract-text', name: 'formation_extract_text')]
    public function extractText(
        Request $request, 
        Formation $formation,
        HttpClientInterface $httpClient,
        SluggerInterface $slugger
    ): Response {
        // Augmenter le temps d'exécution maximum et la limite de mémoire
        set_time_limit(600); // 10 minutes
        ini_set('memory_limit', '512M');

        try {
            // Check if user is logged in and has access to the formation
            if (!$this->getUser()) {
                $this->addFlash('error', 'Vous devez être connecté pour accéder à cette fonctionnalité');
                return $this->redirectToRoute('app_login');
            }

            // Verify if user is enrolled in this formation
            $isEnrolled = false;
            foreach ($formation->getInscriptions() as $inscription) {
                if ($inscription->getUser() === $this->getUser()) {
                    $isEnrolled = true;
                    break;
                }
            }

            if (!$isEnrolled) {
                $this->addFlash('error', 'Vous devez être inscrit à cette formation pour extraire le texte');
                return $this->redirectToRoute('front_formation');
            }

            $videoUrl = $formation->getVideo();
            if (!$videoUrl) {
                throw new \Exception('Aucune vidéo associée à cette formation');
            }

            // Extract YouTube video ID
            $videoId = null;
            if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $videoUrl, $matches)) {
                $videoId = $matches[1];
            }

            if (!$videoId) {
                throw new \Exception('Format de vidéo non supporté. Seules les vidéos YouTube sont prises en charge pour le moment.');
            }

            // Set up paths
            $outputDir = sys_get_temp_dir();
            $outputFile = $outputDir . '/' . uniqid('audio_') . '.mp3';
            
            // Définir le chemin vers yt-dlp
            $ytDlpPath = $this->projectDir . '/bin/tools/yt-dlp';
            if (PHP_OS_FAMILY === 'Windows') {
                $ytDlpPath .= '.exe';
            }

            if (!file_exists($ytDlpPath)) {
                throw new \Exception('yt-dlp n\'est pas installé. Veuillez exécuter la commande : php bin/console app:install-yt-dlp');
            }

            // Vérifier FFmpeg
            $ffmpegPath = $this->projectDir . '/bin/tools';
            if (PHP_OS_FAMILY === 'Windows') {
                if (!file_exists($ffmpegPath . '/ffmpeg.exe') || !file_exists($ffmpegPath . '/ffprobe.exe')) {
                    throw new \Exception('FFmpeg n\'est pas installé correctement. Les fichiers ffmpeg.exe et ffprobe.exe doivent être dans le dossier bin/tools');
                }
            }

            // Télécharger d'abord en format léger
            $process = new Process([
                $ytDlpPath,
                '-x',
                '--audio-format', 'mp3',
                '--audio-quality', '96K', // Qualité réduite dès le départ
                '--max-filesize', '20M',
                '--ffmpeg-location', $ffmpegPath,
                '-o', $outputFile,
                '--',
                'https://www.youtube.com/watch?v=' . $videoId
            ]);
            
            $process->setTimeout(300); // 5 minutes
            $process->setIdleTimeout(60); // 1 minute d'inactivité maximum

            // Exécuter avec feedback
            $process->run(function ($type, $buffer) {
                if (Process::ERR === $type) {
                    error_log('ERR > '.$buffer);
                } else {
                    error_log('OUT > '.$buffer);
                }
            });

            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            // Vérifier la taille du fichier
            if (!file_exists($outputFile) || filesize($outputFile) > 25 * 1024 * 1024) {
                // Réduire davantage si nécessaire
                $tempFile = $outputFile . '.temp.mp3';
                $process = new Process([
                    $ffmpegPath . '/ffmpeg',
                    '-i', $outputFile,
                    '-b:a', '48k',
                    '-ac', '1', // Mono
                    '-ar', '16000', // Fréquence d'échantillonnage réduite
                    $tempFile
                ]);
                
                $process->setTimeout(120);
                $process->run();

                if ($process->isSuccessful() && file_exists($tempFile)) {
                    rename($tempFile, $outputFile);
                }
            }

            // Lire le fichier audio
            $audioContent = file_get_contents($outputFile);
            if ($audioContent === false) {
                throw new \Exception('Erreur lors de la lecture du fichier audio');
            }

            // Préparer la requête multipart
            $boundary = uniqid();
            $content = "--{$boundary}\r\n";
            $content .= "Content-Disposition: form-data; name=\"file\"; filename=\"audio.mp3\"\r\n";
            $content .= "Content-Type: audio/mpeg\r\n\r\n";
            $content .= $audioContent . "\r\n";
            $content .= "--{$boundary}\r\n";
            $content .= "Content-Disposition: form-data; name=\"model\"\r\n\r\n";
            $content .= "whisper-1\r\n";
            $content .= "--{$boundary}\r\n";
            $content .= "Content-Disposition: form-data; name=\"response_format\"\r\n\r\n";
            $content .= "text\r\n";
            $content .= "--{$boundary}--\r\n";

            // Faire la requête avec retry
            try {
                $transcription = $this->makeOpenAIRequest($httpClient, $outputFile, $boundary);
                
                if (empty($transcription)) {
                    throw new \Exception('La transcription est vide');
                }

                // Sauvegarder la transcription
                $safeFilename = $slugger->slug($formation->getTitre()) . '-' . date('Y-m-d-His');
                $transcriptionDir = $this->projectDir . '/public/uploads/transcriptions';
                $transcriptionFile = $transcriptionDir . '/' . $safeFilename . '.txt';

                $filesystem = new Filesystem();
                if (!$filesystem->exists($transcriptionDir)) {
                    $filesystem->mkdir($transcriptionDir, 0777);
                }

                file_put_contents($transcriptionFile, $transcription);

                return $this->file($transcriptionFile, $safeFilename . '.txt', ResponseHeaderBag::DISPOSITION_ATTACHMENT);

            } catch (HttpExceptionInterface $e) {
                error_log('Erreur OpenAI: ' . $e->getMessage());
                throw new \Exception(
                    'Erreur lors de la transcription. ' . 
                    ($e->getResponse()->getStatusCode() === 429 ? 
                    'Le service est temporairement surchargé, veuillez réessayer dans quelques minutes.' : 
                    $e->getMessage())
                );
            } catch (\Exception $e) {
                error_log('Erreur générale: ' . $e->getMessage());
                throw new \Exception('Erreur lors de la transcription: ' . $e->getMessage());
            }

        } catch (\Exception $e) {
            error_log($e->getMessage());
            error_log($e->getTraceAsString());
            
            $this->addFlash('error', 'Erreur lors de l\'extraction du texte: ' . $e->getMessage());
            return $this->redirectToRoute('front_formation');
        } finally {
            // Nettoyage
            if (isset($outputFile) && file_exists($outputFile)) {
                @unlink($outputFile);
            }
            if (isset($tempFile) && file_exists($tempFile)) {
                @unlink($tempFile);
            }
        }
    }

    #[Route('/formation/transcriptions', name: 'formation_transcriptions')]
    public function listTranscriptions(): Response
    {
        try {
            $transcriptionDir = $this->projectDir . '/public/uploads/transcriptions';
            $filesystem = new Filesystem();
            
            if (!$filesystem->exists($transcriptionDir)) {
                $filesystem->mkdir($transcriptionDir, 0777);
            }

            // Vérifier les permissions
            if (!is_readable($transcriptionDir) || !is_writable($transcriptionDir)) {
                throw new \Exception('Le dossier des transcriptions n\'est pas accessible en lecture/écriture');
            }

            $files = glob($transcriptionDir . '/*.txt');
            $transcriptions = [];
            
            if ($files === false) {
                throw new \Exception('Erreur lors de la lecture du dossier des transcriptions');
            }
            
            foreach ($files as $file) {
                if (is_readable($file)) {
                    $transcriptions[] = [
                        'name' => basename($file),
                        'date' => new \DateTime('@' . filemtime($file)),
                        'size' => filesize($file),
                        'path' => '/uploads/transcriptions/' . basename($file)
                    ];
                }
            }

            return $this->render('frontOffice/formation/transcriptions.html.twig', [
                'transcriptions' => $transcriptions
            ]);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la lecture des transcriptions: ' . $e->getMessage());
            return $this->redirectToRoute('front_formation');
        }
    }

    #[Route('/formation/generate-summary/{filename}', name: 'formation_generate_summary')]
    public function generateSummary(
        string $filename,
        SluggerInterface $slugger
    ): Response {
        try {
            // Verify file exists and read content
            $transcriptionPath = $this->projectDir . '/public/uploads/transcriptions/' . $filename;
            if (!file_exists($transcriptionPath)) {
                throw new FileNotFoundException('Transcription file not found');
            }

            $content = file_get_contents($transcriptionPath);
            
            // Générer le résumé
            $summary = $this->generateBasicSummary($content);
            
            // Save summary to file
            $safeFilename = $slugger->slug(pathinfo($filename, PATHINFO_FILENAME)) . '-resume-' . date('Y-m-d-His') . '.txt';
            $summaryPath = $this->projectDir . '/public/uploads/transcriptions/' . $safeFilename;

            file_put_contents($summaryPath, $summary);

            // Return file as download
            return $this->file($summaryPath, $safeFilename, ResponseHeaderBag::DISPOSITION_ATTACHMENT);

        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la génération du résumé: ' . $e->getMessage());
            return $this->redirectToRoute('formation_transcriptions');
        }
    }

    private function generateBasicSummary(string $text): string {
        // Nettoyer le texte
        $text = preg_replace('/\s+/', ' ', trim($text));
        
        // Diviser en phrases
        $sentences = preg_split('/(?<=[.!?])\s+/', $text);
        
        // Initialiser les variables pour le résumé
        $summary = "Résumé de la formation:\n\n";
        $currentStep = 1;
        $paragraphs = [];
        $currentParagraph = '';
        
        // Regrouper les phrases en paragraphes
        foreach ($sentences as $sentence) {
            $currentParagraph .= $sentence . ' ';
            
            // Si la phrase termine un concept ou si on a plus de 3 phrases
            if (
                str_word_count($currentParagraph) > 50 || 
                preg_match('/(conclusion|finalement|en résumé|pour conclure)/i', $sentence)
            ) {
                $paragraphs[] = trim($currentParagraph);
                $currentParagraph = '';
            }
        }
        
        // Ajouter le dernier paragraphe s'il existe
        if (!empty($currentParagraph)) {
            $paragraphs[] = trim($currentParagraph);
        }
        
        // Créer le résumé structuré
        foreach ($paragraphs as $paragraph) {
            // Ignorer les paragraphes trop courts
            if (str_word_count($paragraph) < 10) {
                continue;
            }
            
            // Identifier les points clés
            if (preg_match('/(introduction|présentation|début)/i', $paragraph)) {
                $summary .= "Étape {$currentStep}: Introduction\n";
            } elseif (preg_match('/(conclusion|fin|résumé)/i', $paragraph)) {
                $summary .= "Étape {$currentStep}: Conclusion\n";
            } else {
                $summary .= "Étape {$currentStep}: Point Principal\n";
            }
            
            // Ajouter le contenu du paragraphe
            $summary .= "- " . $this->summarizeParagraph($paragraph) . "\n\n";
            $currentStep++;
        }
        
        // Ajouter une note de fin
        $summary .= "\nNote: Ce résumé a été généré automatiquement à partir de la transcription.";
        
        return $summary;
    }
    
    private function summarizeParagraph(string $paragraph): string {
        // Extraire les mots clés (mots qui apparaissent plus fréquemment)
        $words = str_word_count(strtolower($paragraph), 1, 'àáâãäçèéêëìíîïñòóôõöùúûüýÿ');
        $wordCount = array_count_values($words);
        
        // Filtrer les mots vides
        $stopWords = ['le', 'la', 'les', 'un', 'une', 'des', 'et', 'ou', 'mais', 'donc', 'car', 'ni', 'or', 'que', 'qui'];
        foreach ($stopWords as $word) {
            unset($wordCount[$word]);
        }
        
        // Prendre les phrases contenant les mots clés les plus fréquents
        arsort($wordCount);
        $keyWords = array_slice(array_keys($wordCount), 0, 5);
        
        $sentences = preg_split('/(?<=[.!?])\s+/', $paragraph);
        $relevantSentences = [];
        
        foreach ($sentences as $sentence) {
            foreach ($keyWords as $word) {
                if (stripos($sentence, $word) !== false) {
                    $relevantSentences[] = $sentence;
                    break;
                }
            }
        }
        
        // Retourner une version condensée
        return implode(' ', array_slice($relevantSentences, 0, 2));
    }

    #[Route('/formation/search', name: 'formation_search', methods: ['GET'])]
    public function search(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $query = $request->query->get('q');
        $filter = $request->query->get('filter', 'titre'); // Default filter is 'titre'
        
        $formations = $entityManager->getRepository(Formation::class)->searchFormations($query);
        
        // Get user inscriptions for checking enrollment status
        $userInscriptions = [];
        if ($this->getUser()) {
            $userInscriptions = $entityManager->getRepository(Inscription::class)->findBy([
                'user' => $this->getUser()
            ]);
        }

        // Prepare formations data for JSON response
        $formationsData = [];
        foreach ($formations as $formation) {
            $isInscrit = false;
            foreach ($userInscriptions as $inscription) {
                if ($inscription->getFormation()->getId() === $formation->getId()) {
                    $isInscrit = true;
                    break;
                }
            }

            // Extract YouTube video ID if exists
            $videoId = null;
            if ($formation->getVideo()) {
                preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $formation->getVideo(), $matches);
                $videoId = $matches[1] ?? null;
            }

            $formationsData[] = [
                'id' => $formation->getId(),
                'titre' => $formation->getTitre(),
                'description' => $formation->getDescription(),
                'dateDebut' => $formation->getDateDebut()->format('d/m/Y'),
                'dateFin' => $formation->getDateFin()->format('d/m/Y'),
                'prix' => $formation->getPrix(),
                'nbrpart' => $formation->getNbrpart(),
                'categorie' => $formation->getCategorie()->getNom(),
                'video' => $formation->getVideo(),
                'videoId' => $videoId,
                'isInscrit' => $isInscrit
            ];
        }

        return new JsonResponse($formationsData);
    }
}
