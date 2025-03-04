<?php

namespace App\Controller;

use App\Entity\Candidature;
use App\Form\CandidatureType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Emploi;
use App\Entity\CandidatureOffre;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use App\Repository\CandidatureRepository;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class CandidatureController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/emploi/{id}/postuler', name: 'emploi_postuler')]
    public function postuler(Request $request, int $id): Response
    {
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour postuler.');
            return $this->redirectToRoute('app_login');
        }

        $emploi = $this->entityManager->getRepository(Emploi::class)->find($id);
        if (!$emploi) {
            throw $this->createNotFoundException('Offre non trouvée');
        }

        // Vérifier si l'utilisateur a déjà postulé
        $existingCandidature = $this->entityManager
            ->getRepository(CandidatureOffre::class)
            ->findOneBy([
                'candidat' => $user,
                'emploi' => $emploi
            ]);

        if ($existingCandidature) {
            $this->addFlash('error', 'Vous avez déjà postulé à cette offre.');
            return $this->redirectToRoute('emploi_show', ['id' => $id]);
        }

        // Créer une nouvelle candidature
        $candidature = new Candidature();
        $candidature->setCandidat($user);
        $candidature->setDateCandidature(new \DateTime());

        // Créer une nouvelle candidature offre
        $candidatureOffre = new CandidatureOffre();
        $candidatureOffre->setCandidat($user);
        $candidatureOffre->setEmploi($emploi);
        $candidatureOffre->setDateCandidature(new \DateTime());
        $candidatureOffre->setStatut('En attente');

        $form = $this->createForm(CandidatureType::class, $candidatureOffre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Sauvegarder la candidature principale
            $candidature->setCompetences($candidatureOffre->getCompetences());
            $candidature->setLettreMotivation($candidatureOffre->getLettreMotivation());
            $this->entityManager->persist($candidature);

            // Lier la candidature offre à la candidature principale
            $candidatureOffre->setCandidature($candidature);
            $this->entityManager->persist($candidatureOffre);
            
            $this->entityManager->flush();

            $this->addFlash('success', 'Votre candidature a été envoyée avec succès !');
            return $this->redirectToRoute('mes_candidatures');
        }

        return $this->render('frontOffice/candidature/postuler.html.twig', [
            'form' => $form->createView(),
            'emploi' => $emploi
        ]);
    }

    #[Route('/front/candidatures', name: 'front_candidatures')]
    public function listCandidatures1(): Response
    {
        $candidatures = $this->entityManager->getRepository(Candidature::class)->findAll();
        return $this->render('frontOffice/candidature/list.html.twig', [
            'candidatures' => $candidatures,
        ]);
    }

    #[Route('/front/candidature/delete/{id}', name: 'front_candidature_delete', methods: ['POST'])]
    public function deleteCandidature2(int $id): Response
    {
        $candidature = $this->entityManager->getRepository(Candidature::class)->find($id);

        if (!$candidature) {
            $this->addFlash('error', 'Candidature non trouvée.');
            return $this->redirectToRoute('front_candidatures');
        }

        // Supprimer les enregistrements liés dans CandidatureOffre
        $candidatureOffres = $this->entityManager->getRepository(CandidatureOffre::class)->findBy(['candidature' => $candidature]);
        foreach ($candidatureOffres as $candidatureOffre) {
            $this->entityManager->remove($candidatureOffre);
        }

        // Supprimer le fichier CV s'il existe
        $cvFilePath = $this->getParameter('cv_directory') . '/' . $candidature->getCv();
        if ($candidature->getCv() && file_exists($cvFilePath)) {
            unlink($cvFilePath);
        }

        // Supprimer la candidature
        $this->entityManager->remove($candidature);
        $this->entityManager->flush();

        $this->addFlash('success', 'Candidature supprimée avec succès.');
        return $this->redirectToRoute('front_candidatures');
    }

    #[Route('/front/candidature/{id}', name: 'front_candidature_show', methods: ['GET'])]
    public function showCandidature1(int $id): Response
    {
        $candidature = $this->entityManager->getRepository(Candidature::class)->find($id);
    
        if (!$candidature) {
            $this->addFlash('error', 'Candidature non trouvée.');
            return $this->redirectToRoute('front_candidatures');
        }
    
        return $this->render('backOffice/candidature/show.html.twig', [
            'candidature' => $candidature,
        ]);
    }

    #[Route('/candidature/cv/{filename}', name: 'download_cv')]
    public function downloadCV(string $filename): Response
    {
        $cvDirectory = $this->getParameter('cv_directory');
        $filePath = $cvDirectory.'/'.$filename;

        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('Le CV n\'existe pas.');
        }

        // Create a proper file download response with content type and disposition
        $response = new Response(file_get_contents($filePath));
        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $filename
        );
        
        $response->headers->set('Content-Disposition', $disposition);
        $response->headers->set('Content-Type', mime_content_type($filePath));

        return $response;
    }

    //back 

    #[Route('/backoffice/candidatures', name: 'backoffice_candidatures')]
    public function listCandidatures(): Response
    {
        $candidatures = $this->entityManager
            ->getRepository(CandidatureOffre::class)
            ->createQueryBuilder('co')
            ->leftJoin('co.candidat', 'u')
            ->leftJoin('co.emploi', 'e')
            ->addSelect('u', 'e')
            ->orderBy('co.dateCandidature', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('backOffice/candidature/list.html.twig', [
            'candidatures' => $candidatures
        ]);
    }

    #[Route('/backoffice/candidature/delete/{id}', name: 'backoffice_candidature_delete', methods: ['POST'])]
    public function deleteCandidature(int $id): Response
    {
        $candidature = $this->entityManager->getRepository(Candidature::class)->find($id);

        if (!$candidature) {
            $this->addFlash('error', 'Candidature non trouvée.');
            return $this->redirectToRoute('backoffice_candidatures');
        }

        // Récupérer toutes les candidatures offres associées
        $candidatureOffres = $this->entityManager
            ->getRepository(CandidatureOffre::class)
            ->findBy(['candidature' => $candidature]);

        // Supprimer les fichiers CV pour chaque candidature offre
        foreach ($candidatureOffres as $candidatureOffre) {
            if ($candidatureOffre->getCvFilename()) {
                $cvFilePath = $this->getParameter('cv_directory') . '/' . $candidatureOffre->getCvFilename();
                if (file_exists($cvFilePath)) {
                    unlink($cvFilePath);
                }
            }
        }

        // Supprimer la candidature (les candidatures offres seront supprimées en cascade)
        $this->entityManager->remove($candidature);
        $this->entityManager->flush();

        $this->addFlash('success', 'Candidature supprimée avec succès.');
        return $this->redirectToRoute('backoffice_candidatures');
    }

    #[Route('/backoffice/candidature/{id}', name: 'backoffice_candidature_show', methods: ['GET'])]
    public function showCandidature(int $id): Response
    {
        $candidature = $this->entityManager->getRepository(Candidature::class)->find($id);
    
        if (!$candidature) {
            $this->addFlash('error', 'Candidature non trouvée.');
            return $this->redirectToRoute('backoffice_candidatures');
        }
    
        return $this->render('backOffice/candidature/show.html.twig', [
            'candidature' => $candidature,
        ]);
    }

    #[Route('/candidature/{id}/update-status', name: 'candidature_update_status', methods: ['POST'])]
    public function updateStatus(CandidatureOffre $candidatureOffre, Request $request): Response
    {
        // Check if the current user is the job owner
        if ($candidatureOffre->getEmploi()->getUser() !== $this->getUser()) {
            throw new AccessDeniedException('Vous n\'êtes pas autorisé à modifier cette candidature.');
        }

        $newStatus = $request->request->get('status');
        if (in_array($newStatus, ['Acceptée', 'Rejetée'])) {
            $candidatureOffre->setStatut($newStatus);
            $this->entityManager->flush();

            // Add flash message for the candidate
            $this->addFlash(
                'notification',
                sprintf(
                    'Votre candidature pour le poste "%s" a été %s',
                    $candidatureOffre->getEmploi()->getTitre(),
                    $newStatus
                )
            );
        }

        return $this->redirectToRoute('mes_candidatures');
    }

    #[Route('/emploi/{id}/candidatures', name: 'job_applications')]
    public function showJobApplications(Emploi $emploi): Response
    {
        $user = $this->getUser();
        
        // Check if current user is the employer who posted the job
        if (!$user || $emploi->getUser() !== $user) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à voir ces candidatures.');
        }

        $candidatures = $this->entityManager
            ->getRepository(CandidatureOffre::class)
            ->findBy(['emploi' => $emploi]);

        return $this->render('frontOffice/candidature/job_applications.html.twig', [
            'emploi' => $emploi,
            'candidatures' => $candidatures
        ]);
    }

    #[Route('/mes-candidatures', name: 'mes_candidatures')]
    public function mesCandidatures(): Response
    {
        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedException('Vous devez être connecté pour voir vos candidatures.');
        }

        $candidatures = $this->entityManager
            ->getRepository(CandidatureOffre::class)
            ->findBy(['candidat' => $user]);

        return $this->render('frontOffice/candidature/mes_candidatures.html.twig', [
            'candidatures' => $candidatures
        ]);
    }

    #[Route('/candidature/{id}/status', name: 'candidature_status_update', methods: ['POST'])]
    public function handleStatusUpdate(Request $request, CandidatureOffre $candidature): Response
    {
        $emploi = $candidature->getEmploi();
        $user = $this->getUser();

        if (!$user || $emploi->getUser() !== $user) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à modifier cette candidature.');
        }

        $status = $request->request->get('status') === 'Accepté' 
            ? CandidatureOffre::STATUS_ACCEPTE 
            : CandidatureOffre::STATUS_REJETE;

        $candidature->setStatut($status);
        $candidature->setIsRead(false);
        
        // Create notification message
        $message = $status === CandidatureOffre::STATUS_ACCEPTE 
            ? 'Votre candidature pour l\'offre "' . $emploi->getTitre() . '" a été acceptée!'
            : 'Votre candidature pour l\'offre "' . $emploi->getTitre() . '" a été refusée.';
            
        $this->addFlash('success', 'Le statut de la candidature a été mis à jour.');
        $this->entityManager->flush();

        return $this->redirectToRoute('job_applications', ['id' => $emploi->getId()]);
    }

    #[Route('/notifications', name: 'notifications_list')]
    public function notifications(): Response
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        $candidatures = $this->entityManager
            ->getRepository(CandidatureOffre::class)
            ->findBy(
                ['candidat' => $user, 'isRead' => false],
                ['dateCandidature' => 'DESC']
            );

        return $this->render('frontOffice/notifications/index.html.twig', [
            'notifications' => $candidatures
        ]);
    }

    #[Route('/admin/candidatures', name: 'backoffice_candidature_list')]
    public function list(CandidatureRepository $repository): Response
    {
        $candidatures = $repository->findAll();

        return $this->render('backOffice/candidature/list.html.twig', [
            'candidatures' => $candidatures
        ]);
    }

    #[Route('/candidature/{id}/details', name: 'candidature_details')]
    public function showCandidatureDetails(CandidatureOffre $candidature): Response
    {
        // Check if the current user is the employer who posted the job
        if ($candidature->getEmploi()->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à voir ces détails.');
        }

        return $this->render('frontOffice/candidature/details.html.twig', [
            'candidature' => $candidature
        ]);
    }

    #[Route('/candidature/{id}/download-cv', name: 'download_candidature_cv')]
    public function downloadCandidatureCV(CandidatureOffre $candidature): Response
    {
        if (!$candidature->getCvFilename()) {
            throw $this->createNotFoundException('Aucun CV n\'a été trouvé.');
        }

        $filePath = $this->getParameter('kernel.project_dir').'/public/uploads/cv/'.$candidature->getCvFilename();

        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('Le fichier CV n\'existe pas.');
        }

        return $this->file($filePath, $candidature->getCvFilename(), ResponseHeaderBag::DISPOSITION_ATTACHMENT);
    }

    #[Route('/backoffice/candidature/{id}/download-cv', name: 'backoffice_download_cv')]
    public function downloadCandidatureCVBackoffice(CandidatureOffre $candidature): Response
    {
        if (!$candidature->getCvFilename()) {
            throw $this->createNotFoundException('Aucun CV n\'a été trouvé pour cette candidature.');
        }

        $cvPath = $this->getParameter('cv_directory') . '/' . $candidature->getCvFilename();

        if (!file_exists($cvPath)) {
            throw $this->createNotFoundException('Le fichier CV n\'existe pas.');
        }

        return $this->file($cvPath, $candidature->getCvFilename(), ResponseHeaderBag::DISPOSITION_ATTACHMENT);
    }

    #[Route('/backoffice/candidature/{id}/view-cv', name: 'backoffice_view_cv')]
    public function viewCandidatureCV(CandidatureOffre $candidature): Response
    {
        if (!$candidature->getCvFilename()) {
            throw $this->createNotFoundException('Aucun CV n\'a été trouvé pour cette candidature.');
        }

        $cvPath = $this->getParameter('cv_directory') . '/' . $candidature->getCvFilename();

        if (!file_exists($cvPath)) {
            throw $this->createNotFoundException('Le fichier CV n\'existe pas.');
        }

        // Créer une réponse avec le contenu du fichier
        $response = new Response(file_get_contents($cvPath));
        
        // Définir le type de contenu approprié
        $mimeType = mime_content_type($cvPath);
        $response->headers->set('Content-Type', $mimeType);

        return $response;
    }
}