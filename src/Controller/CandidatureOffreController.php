<?php

namespace App\Controller;

use App\Entity\CandidatureOffre;
use App\Entity\Candidature;
use App\Entity\Emploi;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\CandidatureOffreRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use App\Form\CandidatureOffreType;

class CandidatureOffreController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/backoffice/candidature-offre', name: 'backoffice_candidature_offre_list')]
    public function list(CandidatureOffreRepository $repository): Response
    {
        $candidatureOffres = $repository->createQueryBuilder('co')
            ->leftJoin('co.candidature', 'c')
            ->addSelect('c')
            ->getQuery()
            ->getResult();

        return $this->render('backOffice/candidature_offre/list.html.twig', [
            'candidatureOffres' => $candidatureOffres
        ]);
    }

    #[Route('/backoffice/candidature-offre/{id}', name: 'backoffice_candidature_offre_show')]
    public function show(CandidatureOffre $candidatureOffre): Response
    {
        return $this->render('backOffice/candidature_offre/show.html.twig', [
            'candidature' => $candidatureOffre
        ]);
    }

    #[Route('/backoffice/candidature-offre/{id}/update-status', name: 'backoffice_candidature_offre_update_status', methods: ['POST'])]
    public function updateStatus(Request $request, CandidatureOffre $candidatureOffre): Response
    {
        $status = $request->request->get('status');
        
        if (!in_array($status, ['Acceptée', 'Refusée'])) {
            throw new BadRequestHttpException('Statut invalide');
        }

        $candidatureOffre->setStatut($status);
        $this->entityManager->flush();

        // Envoyer une notification au candidat
        $this->addFlash('success', 'Le statut de la candidature a été mis à jour.');

        return new JsonResponse(['success' => true]);
    }

    #[Route('/candidature-offre/{id}/rate', name: 'backoffice_candidature_offre_rate', methods: ['POST'])]
    public function rate(Request $request, CandidatureOffre $candidatureOffre, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $rating = $data['rating'] ?? null;
        
        if ($rating !== null) {
            $candidatureOffre->setRating($rating);
            $entityManager->flush();
            
            return new JsonResponse(['success' => true]);
        }
        
        return new JsonResponse(['error' => 'Rating is required'], 400);
    }

    #[Route('/candidature-offre/new/{emploi_id}', name: 'candidature_offre_new')]
    public function new(Request $request, int $emploi_id): Response
    {
        $emploi = $this->entityManager->getRepository(Emploi::class)->find($emploi_id);
        
        if (!$emploi) {
            throw $this->createNotFoundException('Offre non trouvée');
        }

        // Vérifier si l'offre est toujours ouverte
        if ($emploi->getStatut() !== 'Ouvert') {
            $this->addFlash('error', 'Cette offre n\'est plus disponible');
            return $this->redirectToRoute('app_emploi');
        }

        // Vérifier si l'utilisateur a déjà postulé
        $existingCandidature = $this->entityManager->getRepository(CandidatureOffre::class)->findOneBy([
            'candidat' => $this->getUser(),
            'emploi' => $emploi
        ]);

        if ($existingCandidature) {
            $this->addFlash('error', 'Vous avez déjà postulé à cette offre');
            return $this->redirectToRoute('app_emploi');
        }

        $candidature = new CandidatureOffre();
        $candidature->setEmploi($emploi);
        $candidature->setCandidat($this->getUser());
        
        $form = $this->createForm(CandidatureOffreType::class, $candidature);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->entityManager->persist($candidature);
                $this->entityManager->flush();

                $this->addFlash('success', 'Votre candidature a été envoyée avec succès !');
                return $this->redirectToRoute('app_emploi');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de l\'envoi de votre candidature');
            }
        }

        return $this->render('frontOffice/candidature_offre/new.html.twig', [
            'form' => $form->createView(),
            'emploi' => $emploi
        ]);
    }
}