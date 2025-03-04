<?php

namespace App\Controller;

use App\Entity\Emploi;
use App\Form\EmploiType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use App\Repository\EmploiRepository;
use App\Entity\User;
use App\Entity\CandidatureOffre;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Twig\NlpExtension;
use App\Service\FraudDetectionService;
use App\Service\JobAnalyticsService;

class EmploiController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private FraudDetectionService $fraudDetectionService;
    private JobAnalyticsService $jobAnalyticsService;

    public function __construct(
        EntityManagerInterface $entityManager,
        FraudDetectionService $fraudDetectionService,
        JobAnalyticsService $jobAnalyticsService
    ) {
        $this->entityManager = $entityManager;
        $this->fraudDetectionService = $fraudDetectionService;
        $this->jobAnalyticsService = $jobAnalyticsService;
    }

    #[Route('/emploi', name: 'app_emploi')]
    public function index(Request $request): Response
    {
        // Get search parameters from request
        $search = $request->query->get('search');
        $budget = $request->query->get('budget');
        $lieu = $request->query->get('lieu');
        $statut = $request->query->get('statut');

        // Create query builder
        $queryBuilder = $this->entityManager->getRepository(Emploi::class)
            ->createQueryBuilder('e');

        // Add search conditions
        if ($search) {
            $queryBuilder->andWhere('e.titre LIKE :search OR e.description LIKE :search OR e.competences_requises LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($budget) {
            $queryBuilder->andWhere('e.budget <= :budget')
                ->setParameter('budget', $budget);
        }

        if ($lieu) {
            $queryBuilder->andWhere('e.lieu LIKE :lieu')
                ->setParameter('lieu', '%' . $lieu . '%');
        } 

        if ($statut) {
            $queryBuilder->andWhere('e.statut = :statut')
                ->setParameter('statut', $statut);
        }

        // Get results
        $emplois = $queryBuilder->getQuery()->getResult();
        $users = $this->entityManager->getRepository(User::class)->findAll();

        return $this->render('frontOffice/emploi/index.html.twig', [
            'emplois' => $emplois,
            'users' => $users,
            'search' => $search,
            'budget' => $budget,
            'lieu' => $lieu,
            'statut' => $statut
        ]);
    }
    
#[Route('/user/{user_id}/emplois', name: 'user_emplois')]
    public function userEmplois(int $user_id): Response
    {
        // Fetch the user by user_id
        $user = $this->entityManager->getRepository(User::class)->find($user_id);

        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non trouvé pour l\'ID ' . $user_id);
        }

        // Fetch jobs posted by the user
        $emplois = $this->entityManager->getRepository(Emploi::class)->findBy(['user' => $user]);

        $currentUser = $this->getUser();

        return $this->render('frontOffice/emploi/user_emplois.html.twig', [
            'emplois' => $emplois,
            'user' => $user,
        ]);
    }
    #[Route('/emploi/add', name: 'emploi_add')]
    public function add(Request $request): Response
    {
        // Check if user is logged in
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour ajouter une offre d\'emploi.');
            return $this->redirectToRoute('app_login');
        }

        $emploi = new Emploi();
        $form = $this->createForm(EmploiType::class, $emploi);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if (!$form->isValid()) {
                foreach ($form->getErrors(true) as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
            } else {
                try {
                    // Analyse de fraude avec les compétences
                    $fraudAnalysis = $this->fraudDetectionService->analyzeListing(
                        $emploi->getTitre(),
                        $emploi->getDescription(),
                        $emploi->getCompetencesRequises()
                    );

                    // Si une fraude potentielle est détectée
                    if ($fraudAnalysis['fraudFound']) {
                        foreach ($fraudAnalysis['warnings'] as $warning) {
                            $this->addFlash('warning', $warning);
                        }
                        
                        // Si le score est très bas ou si l'offre n'est pas liée à l'art
                        if ($fraudAnalysis['score'] < 40 || $fraudAnalysis['artScore'] < 20) {
                            $this->addFlash('error', 'Cette offre ne peut pas être publiée car elle ne correspond pas aux critères de notre plateforme artistique ou présente des signes suspects de fraude.');
                            return $this->render('frontOffice/emploi/add.html.twig', [
                                'form' => $form->createView(),
                            ]);
                        }
                    }

                    // Set the current user as the job owner
                    $emploi->setUser($user);
                    $emploi->setDatePublication(new \DateTime());
                    
                    if (!$emploi->getStatut()) {
                        $emploi->setStatut('Ouvert');
                    }

                    $this->entityManager->persist($emploi);
                    $this->entityManager->flush();

                    $this->addFlash('success', 'Offre d\'emploi ajoutée avec succès !');
                    return $this->redirectToRoute('app_emploi');
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur lors de l\'ajout: ' . $e->getMessage());
                }
            }
        }

        return $this->render('frontOffice/emploi/add.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    #[Route('/emploii/{id}', name: 'emploi_show2')]
    public function show2(int $id): Response
    {
        $emploi = $this->entityManager->getRepository(Emploi::class)->find($id);

        if (!$emploi) {
            throw $this->createNotFoundException('Aucune offre trouvée pour l\'id ' . $id);
        }

        return $this->render('frontOffice/emploi/show2.html.twig', [
            'emploi' => $emploi,
        ]);
    }
    #[Route('/emploi/mes-emplois', name: 'mes_emplois')]
    public function mesEmplois(): Response
    {
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour voir vos offres.');
            return $this->redirectToRoute('app_login');
        }

        $emplois = $this->entityManager
            ->getRepository(Emploi::class)
            ->findBy(['user' => $user]);

        return $this->render('frontOffice/emploi/mes_emplois.html.twig', [
            'emplois' => $emplois
        ]);
    }

    #[Route('/emploi/show/{id}', name: 'emploi_show')]
    public function show(int $id): Response
    {
        $emploi = $this->entityManager->getRepository(Emploi::class)->find($id);
        if (!$emploi) {
            throw $this->createNotFoundException('Aucune offre trouvée pour l\'id ' . $id);
        }

        return $this->render('frontOffice/emploi/show.html.twig', [
            'emploi' => $emploi
        ]);
    }

    #[Route('/emploi/edit/{id}', name: 'emploi_edit', methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request): Response
    {
        $emploi = $this->entityManager->getRepository(Emploi::class)->find($id);

        if (!$emploi) {
            throw $this->createNotFoundException('Aucune offre trouvée pour l\'id ' . $id);
        }

        $user = $this->getUser();
        if ($emploi->getUser() !== $user) {
            throw new AccessDeniedException('Vous ne pouvez modifier que vos propres offres d\'emploi.');
        }

        $form = $this->createForm(EmploiType::class, $emploi);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Offre d\'emploi mise à jour avec succès !');
            return $this->redirectToRoute('app_emploi');
        }

        return $this->render('frontOffice/emploi/edit.html.twig', [
            'form' => $form->createView(),
            'emploi' => $emploi,
        ]);
    }

    #[Route('/emploi/delete/{id}', name: 'emploi_delete', methods: ['POST'])]
    public function delete(int $id, Request $request): Response
    {
        $emploi = $this->entityManager->getRepository(Emploi::class)->find($id);

        if (!$emploi) {
            throw $this->createNotFoundException('Aucune offre trouvée pour l\'id ' . $id);
        }

        $user = $this->getUser();
        if ($emploi->getUser() !== $user) {
            throw new AccessDeniedException('Vous ne pouvez supprimer que vos propres offres d\'emploi.');
        }

        if ($this->isCsrfTokenValid('delete' . $emploi->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($emploi);
            $this->entityManager->flush();

            $this->addFlash('success', 'Offre d\'emploi supprimée avec succès !');
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('app_emploi');
    }

//backoffice

#[Route('/backoffice/emploi', name: 'backoffice_emploi')]
public function backofficeIndex(Request $request, EmploiRepository $emploiRepository): Response
{
    $queryBuilder = $emploiRepository->createQueryBuilder('e');

    // Search filter
    if ($search = $request->query->get('search')) {
        $queryBuilder->andWhere('e.titre LIKE :search OR e.description LIKE :search')
            ->setParameter('search', '%' . $search . '%');
    }

    // Budget maximum filter
    if ($budgetMax = $request->query->get('budget_max')) {
        $queryBuilder->andWhere('e.budget <= :budgetMax')
            ->setParameter('budgetMax', $budgetMax);
    }

    // Location filter
    if ($lieu = $request->query->get('lieu')) {
        $queryBuilder->andWhere('e.lieu LIKE :lieu')
            ->setParameter('lieu', '%' . $lieu . '%');
    }

    // Status filter
    if ($statut = $request->query->get('statut')) {
        $queryBuilder->andWhere('e.statut = :statut')
            ->setParameter('statut', $statut);
    }

    // Utiliser date_publication au lieu de datePublication
    $emplois = $queryBuilder->orderBy('e.date_publication', 'DESC')
        ->getQuery()
        ->getResult();

    return $this->render('backOffice/emploi/index.html.twig', [
        'emplois' => $emplois
    ]);
}

#[Route('/backoffice/emploi/edit/{id}', name: 'backoffice_emploi_edit', methods: ['GET', 'POST'])]
public function edit_back(int $id, Request $request): Response
{
    $emploi = $this->entityManager->getRepository(Emploi::class)->find($id);

    if (!$emploi) {
        throw $this->createNotFoundException('Aucune offre trouvée pour l\'id ' . $id);
    }

    $form = $this->createForm(EmploiType::class, $emploi);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $this->entityManager->flush();

        $this->addFlash('success', 'Offre d\'emploi mise à jour avec succès !');
        return $this->redirectToRoute('backoffice_emploi');
    }

    return $this->render('backOffice/emploi/edit.html.twig', [
        'form' => $form->createView(),
        'emploi' => $emploi,
    ]);
}

#[Route('/backoffice/emploi/delete/{id}', name: 'backoffice_emploi_delete', methods: ['POST'])]
public function delete_back(int $id): Response
{
    $emploi = $this->entityManager->getRepository(Emploi::class)->find($id);

    if (!$emploi) {
        $this->addFlash('error', 'Aucune offre trouvée pour l\'id ' . $id);
        return $this->redirectToRoute('backoffice_emploi');
    }

    // Delete related CandidatureOffre records
    $candidatureOffres = $this->entityManager->getRepository(CandidatureOffre::class)->findBy(['emploi' => $emploi]);
    foreach ($candidatureOffres as $candidatureOffre) {
        $this->entityManager->remove($candidatureOffre);
    }

    // Delete the Emploi entity
    $this->entityManager->remove($emploi);
    $this->entityManager->flush();

    $this->addFlash('success', 'Offre d\'emploi supprimée avec succès !');
    return $this->redirectToRoute('backoffice_emploi');
}

#[Route('/{id}/candidatures', name: 'emploi_candidatures')]
public function showCandidatures(Emploi $emploi): Response
{
    // Vérifier que l'utilisateur actuel est bien le propriétaire de l'emploi
    if ($this->getUser() !== $emploi->getUser()) {
        throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette page.');
    }

    return $this->render('emploi/show_candidatures.html.twig', [
        'emploi' => $emploi
    ]);
}

#[Route('/api/emploi/{id}/keywords', name: 'api_emploi_keywords', methods: ['GET'])]
public function getKeywords(int $id): Response
{
    $emploi = $this->entityManager->getRepository(Emploi::class)->find($id);
    
    if (!$emploi) {
        return new JsonResponse(['error' => 'Offre non trouvée'], 404);
    }
    
    // Utiliser le service NLP pour extraire les mots-clés
    $keywords = $this->container->get('twig')->getExtension(NlpExtension::class)
        ->extractKeywords($emploi->getTitre(), $emploi->getDescription(), $emploi->getCompetencesRequises());
    
    return new JsonResponse([
        'keywords' => $keywords
    ]);
}

#[Route('/api/emploi/{id}/stats', name: 'api_emploi_stats', methods: ['GET'])]
public function getJobStats(Emploi $emploi): JsonResponse
{
    $stats = $this->jobAnalyticsService->getJobStats($emploi);
    return new JsonResponse($stats);
}

#[Route('/backoffice/emploi/add', name: 'backoffice_emploi_add')]
public function addBack(Request $request): Response
{
    $emploi = new Emploi();
    $form = $this->createForm(EmploiType::class, $emploi);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $emploi->setDatePublication(new \DateTime());
        
        $this->entityManager->persist($emploi);
        $this->entityManager->flush();

        $this->addFlash('success', 'Offre d\'emploi ajoutée avec succès !');
        return $this->redirectToRoute('backoffice_emploi');
    }

    return $this->render('backOffice/emploi/add.html.twig', [
        'form' => $form->createView(),
    ]);
}

#[Route('/backoffice/emploi/show/{id}', name: 'backoffice_emploi_show', methods: ['GET'])]
public function showBack(int $id): Response
{
    $emploi = $this->entityManager->getRepository(Emploi::class)->find($id);

    if (!$emploi) {
        throw $this->createNotFoundException('Aucune offre trouvée pour l\'id ' . $id);
    }

    return $this->render('backOffice/emploi/show.html.twig', [
        'emploi' => $emploi,
    ]);
}

}