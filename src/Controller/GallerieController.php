<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Galerie;
use App\Form\GalerieType;
use App\Repository\ArticleRepository;
use App\Repository\GalerieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\UserRepository;
use App\Entity\User;
use App\Form\GalerieFrontType;
use App\Form\ArticleFrontType;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use App\Entity\ArtLike;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Symfony\Component\HttpFoundation\JsonResponse;
use Dompdf\Dompdf;
use Dompdf\Options;
use App\Entity\Commande;
use App\Repository\ArtLikeRepository;
use App\Repository\CommandeRepository;

final class GallerieController extends AbstractController
{
        //BACK OFFICE **************************************************************************************************************
    //AFFICHAGE Gallerie
    #[Route('/backShowgallerie', name: 'back_showgallerie')]
    public function ShowGallerie(GalerieRepository $gr): Response
    {
        $list=$gr->findAll();
        return $this->render ('backOffice/gallerie/GallerieShow.html.twig', 
        [
           'liste' => $list,
        ]);
    }
    //AJOUT Gallerie
    #[Route('/backAddgallerie', name: 'ajouter_galerie')]
    public function ajouterGallerie(Request $request,EntityManagerInterface $em)
    {
        $galerie = new Galerie();
        $form = $this->createForm(GalerieType::class, $galerie);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($galerie);
            $em->flush();
            return $this->redirectToRoute('back_showgallerie');
        }
        return $this->render('backOffice/gallerie/GallerieAdd.html.twig', [
            'form' => $form,
            'titre'=>"Ajouter",
        ]);
    }
    //MODIFIER gallerie
    #[Route('/backEditgallerie{id}', name: 'edit_galerie')]
    public function modifierGallerie($id,Request $request,EntityManagerInterface $em,GalerieRepository $gr)
    {
        $galerie=$gr->find($id);
        $form = $this->createForm(GalerieType::class, $galerie);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($galerie);
            $em->flush();
            return $this->redirectToRoute('back_showgallerie');
        }
        return $this->render('backOffice/gallerie/GallerieAdd.html.twig', [
            'form' => $form,
            'titre'=>"Modifier",
        ]);
    }
    //DELETE gallerie
    #[Route('/backDelgallerie{id}',name:'delete_galerie')]
    public function DeleteGallerie($id,GalerieRepository $gr,EntityManagerInterface $em)
    {
        $galerie=$gr->find($id);
        $em->remove($galerie);
        $em->flush();
        return $this->redirectToRoute('back_showgallerie');
    }

    //FRONT OFFICE **************************************************************************************************************
    #[Route('/gallerie{page?1}', name: 'app_gallerie')]
    public function index(ArticleRepository $articleRepository, Request $request, $page = 1): Response
    {
        $limit = 6;
        $offset = ($page - 1) * $limit;
        $sort = $request->query->get('sort', 'date');
        
        // Récupérer les valeurs min et max du filtre prix
        $minPrice = $request->query->get('min_price');
        $maxPrice = $request->query->get('max_price');
        
        $queryBuilder = $articleRepository->createQueryBuilder('a')
            ->leftJoin('a.galerie', 'g')
            ->leftJoin('g.user', 'u');
        
        // Ajouter le filtre de prix si défini
        if ($minPrice !== null && $maxPrice !== null) {
            $queryBuilder->andWhere('a.prix BETWEEN :min_price AND :max_price')
                ->setParameter('min_price', $minPrice)
                ->setParameter('max_price', $maxPrice);
        }
        
        // Exclure les articles de l'utilisateur connecté
        if ($this->getUser()) {
            $queryBuilder->andWhere('u.id != :userId')
                ->setParameter('userId', $this->getUser()->getId());
        }
        
        // Appliquer le tri
        switch ($sort) {
            case 'likes':
                $queryBuilder->orderBy('a.nbrlikes', 'DESC');
                break;
            case 'date':
            default:
                $queryBuilder->orderBy('a.date_pub', 'DESC');
                break;
        }
        
        // Récupérer le nombre total d'articles (excluant ceux de l'utilisateur)
        $total = $queryBuilder->select('COUNT(a.id)')
            ->getQuery()
            ->getSingleScalarResult();
        
        // Appliquer la pagination
        $queryBuilder->select('a')
            ->setFirstResult($offset)
            ->setMaxResults($limit);
        
        // Calculer le nombre total de pages
        $pages = ceil($total / $limit);
        
        return $this->render('frontOffice/gallerie/GallerieExplorer.html.twig', [
            'liste' => $queryBuilder->getQuery()->getResult(),
            'page' => $page,
            'pages' => $pages,
            'sort' => $sort
        ]);
    }

    #[Route('/gallerie/{cat}/{page?1}', name: 'cat_article')]
    public function showByCategory(ArticleRepository $articleRepository, Request $request, $cat, $page = 1): Response
    {
        $limit = 6;
        $offset = ($page - 1) * $limit;
        $sort = $request->query->get('sort', 'date');
        
        $queryBuilder = $articleRepository->createQueryBuilder('a')
            ->leftJoin('a.galerie', 'g')
            ->leftJoin('g.user', 'u')
            ->where('a.categorie = :categorie')
            ->setParameter('categorie', $cat);
        
        // Exclure les articles de l'utilisateur connecté
        if ($this->getUser()) {
            $queryBuilder->andWhere('u.id != :userId')
                ->setParameter('userId', $this->getUser()->getId());
        }
        
        // Appliquer le tri
        switch ($sort) {
            case 'likes':
                $queryBuilder->orderBy('a.nbrlikes', 'DESC');
                break;
            case 'date':
            default:
                $queryBuilder->orderBy('a.date_pub', 'DESC');
                break;
        }
        
        // Récupérer le nombre total d'articles de cette catégorie (excluant ceux de l'utilisateur)
        $total = $queryBuilder->select('COUNT(a.id)')
            ->getQuery()
            ->getSingleScalarResult();
        
        // Appliquer la pagination
        $queryBuilder->select('a')
            ->setFirstResult($offset)
            ->setMaxResults($limit);
        
        // Calculer le nombre de pages
        $pages = ceil($total / $limit);
        
        return $this->render('frontOffice/gallerie/GallerieExplorer.html.twig', [
            'liste' => $queryBuilder->getQuery()->getResult(),
            'page' => $page,
            'pages' => $pages,
            'sort' => $sort
        ]);
    }

    #[Route('/Mygallerie/{id}', name: 'ma_galerie', requirements: ['id' => '\d+'])]
    public function Mygallerie(
        int $id, 
        Request $request, 
        GalerieRepository $gr, 
        UserRepository $ur, 
        EntityManagerInterface $em,
        ArtLikeRepository $artLikeRepository
    ): Response
    {
        // Vérifier si l'utilisateur existe
        $user = $ur->find($id);
        if (!$user) {
            throw $this->createNotFoundException("Utilisateur non trouvé.");
        }

        // Vérifier si l'utilisateur connecté accède à sa propre galerie
        if ($this->getUser() !== $user) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas accéder à cette galerie.');
        }

        // Trouver la galerie associée à cet utilisateur
        $galerie = $gr->findOneBy(['user' => $user]);
        
        // Créer le formulaire pour le modal de création
        $newGalerie = new Galerie();
        $newGalerie->setUser($this->getUser());
        $newGalerie->setDatecreation(new \DateTime());
        $createForm = $this->createForm(GalerieFrontType::class, $newGalerie);
        
        // Créer le formulaire pour le modal de modification si une galerie existe
        $editForm = null;
        if ($galerie) {
            $editForm = $this->createForm(GalerieFrontType::class, $galerie);
            $editForm->handleRequest($request);
            
            if ($editForm->isSubmitted() && $editForm->isValid()) {
                $em->flush();
                $this->addFlash('success', 'Votre galerie a été modifiée avec succès !');
                return $this->redirectToRoute('ma_galerie', ['id' => $id]);
            }
        }
        
        // Gérer le formulaire de création
        $createForm->handleRequest($request);
        if ($createForm->isSubmitted() && $createForm->isValid()) {
            $em->persist($newGalerie);
            $em->flush();
            $this->addFlash('success', 'Votre galerie a été créée avec succès !');
            return $this->redirectToRoute('ma_galerie', ['id' => $id]);
        }
        
        // Récupérer les likes de l'utilisateur
        $likedArticles = $artLikeRepository->findBy(['user' => $this->getUser()]);

        return $this->render('frontOffice/gallerie/GallerieMy.html.twig', [
            'galerie' => $galerie,
            'form' => $createForm->createView(),
            'editForm' => $editForm ? $editForm->createView() : null,
            'likedArticles' => $likedArticles
        ]);
    }
    
    #[Route('/create-galerie', name: 'create_galerie_front')]
    public function createGalerieFront(Request $request, EntityManagerInterface $em): Response
    {
        $galerie = new Galerie();
        $galerie->setUser($this->getUser());
        $galerie->setDatecreation(new \DateTime());
        
        $form = $this->createForm(GalerieType::class, $galerie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($galerie);
            $em->flush();
            $this->addFlash('success', 'Votre galerie a été créée avec succès !');
            return $this->redirectToRoute('ma_galerie', ['id' => $this->getUser()->getId()]);
        }

        return $this->render('frontOffice/gallerie/GalerieCreate.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/edit-galerie/{id}', name: 'edit_galerie_front')]
    public function editGalerieFront(int $id, Request $request, GalerieRepository $gr, EntityManagerInterface $em): Response
    {
        $galerie = $gr->find($id);
        
        if (!$galerie) {
            throw $this->createNotFoundException('Galerie non trouvée');
        }
        
        if ($galerie->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à modifier cette galerie');
        }

        $form = $this->createForm(GalerieType::class, $galerie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Votre galerie a été modifiée avec succès !');
            return $this->redirectToRoute('ma_galerie', ['id' => $this->getUser()->getId()]);
        }

        return $this->render('frontOffice/gallerie/GalerieEdit.html.twig', [
            'form' => $form->createView(),
            'galerie' => $galerie
        ]);
    }

    #[Route('/delete-galerie/{id}', name: 'delete_galerie_front')]
    public function deleteGalerieFront(int $id, GalerieRepository $gr, EntityManagerInterface $em): Response
    {
        $galerie = $gr->find($id);
        
        if (!$galerie) {
            throw $this->createNotFoundException('Galerie non trouvée');
        }
        
        // Vérifier si l'utilisateur est connecté et est propriétaire de la galerie
        if (!$this->getUser() || $galerie->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à supprimer cette galerie');
        }
        
        // Récupérer l'ID de l'utilisateur avant de supprimer la galerie
        $userId = $this->getUser()->getId();
        
        try {
            $em->remove($galerie);
            $em->flush();
            $this->addFlash('success', 'Votre galerie a été supprimée avec succès');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de la suppression de la galerie');
            return $this->redirectToRoute('ma_galerie', ['id' => $userId]);
        }
        
        return $this->redirectToRoute('ma_galerie', ['id' => $userId]);
    }

    #[Route('/galerie/{id}/articles', name: 'articles_galerie')]
    public function articlesGalerie(int $id, GalerieRepository $gr, ArticleRepository $ar): Response
    {
        $galerie = $gr->find($id);
        
        if (!$galerie) {
            throw $this->createNotFoundException('Galerie non trouvée');
        }
        
        // Récupérer les articles de la galerie
        $articles = $ar->findBy(['galerie' => $galerie], ['date_pub' => 'DESC']);
        
        return $this->render('frontOffice/gallerie/GalerieArticles.html.twig', [
            'galerie' => $galerie,
            'articles' => $articles
        ]);
    }

    #[Route('/article/delete/{id}', name: 'front_delete_article')]
    public function deleteArticleFront(int $id, ArticleRepository $ar, EntityManagerInterface $em): Response
    {
        $article = $ar->find($id);
        
        if (!$article) {
            throw $this->createNotFoundException('Article non trouvé');
        }
        
        // Vérifier si l'utilisateur est propriétaire de la galerie
        if (!$this->getUser() || $article->getGalerie()->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à supprimer cet article');
        }
        
        // Récupérer l'ID de la galerie avant de supprimer l'article
        $galerieId = $article->getGalerie()->getId();
        
        try {
            $em->remove($article);
            $em->flush();
            $this->addFlash('success', 'Article supprimé avec succès');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de la suppression de l\'article');
        }
        
        return $this->redirectToRoute('articles_galerie', ['id' => $galerieId]);
    }
    //edit article front
    #[Route('/article/edit/{id}', name: 'edit_article_front')]
    public function editArticleFront(Request $request, Article $article, EntityManagerInterface $em): Response
    {
        // Vérifier si l'utilisateur est autorisé à modifier cet article
        if (!$this->getUser() || $article->getGalerie()->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à modifier cet article');
        }

        $form = $this->createForm(ArticleFrontType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gérer l'upload de la nouvelle image si elle existe
            $imageFile = $form->get('contenu')->getData();
            if ($imageFile) {
                $newFilename = uniqid().'.'.$imageFile->guessExtension();
                try {
                    $imageFile->move(
                        $this->getParameter('images_directory'),
                        $newFilename
                    );
                    // Supprimer l'ancienne image si elle existe
                    if ($article->getContenu()) {
                        $oldFilePath = $this->getParameter('images_directory').'/'.$article->getContenu();
                        if (file_exists($oldFilePath)) {
                            unlink($oldFilePath);
                        }
                    }
                    $article->setContenu($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Une erreur est survenue lors de l\'upload de l\'image');
                }
            }

            $em->flush();
            $this->addFlash('success', 'Article modifié avec succès');
            return $this->redirectToRoute('articles_galerie', ['id' => $article->getGalerie()->getId()]);
        }

        return $this->render('frontOffice/gallerie/EditArticle.html.twig', [
            'form' => $form->createView(),
            'article' => $article
        ]);
    }

    //add article front
    #[Route('/article/add/{galerieId}', name: 'add_article_front')]
    public function addArticleFront(Request $request, EntityManagerInterface $em, int $galerieId): Response
    {
        // Récupérer la galerie
        $galerie = $em->getRepository(Galerie::class)->find($galerieId);
        
        // Vérifier si la galerie existe et si l'utilisateur est autorisé
        if (!$galerie) {
            throw $this->createNotFoundException('Galerie non trouvée');
        }
        
        if (!$this->getUser() || $galerie->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à ajouter un article dans cette galerie');
        }

        $article = new Article();
        $article->setGalerie($galerie);
        $article->setDatePub(new \DateTime());
        $article->setNbrlikes(0);
        $article->setDisponible(true);

        $form = $this->createForm(ArticleFrontType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gérer l'upload de l'image
            $imageFile = $form->get('contenu')->getData();
            if ($imageFile) {
                $newFilename = uniqid().'.'.$imageFile->guessExtension();
                try {
                    $imageFile->move(
                        $this->getParameter('images_directory'),
                        $newFilename
                    );
                    $article->setContenu($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Une erreur est survenue lors de l\'upload de l\'image');
                }
            }

            $em->persist($article);
            $em->flush();
            
            $this->addFlash('success', 'Article ajouté avec succès');
            return $this->redirectToRoute('articles_galerie', ['id' => $galerieId]);
        }

        return $this->render('frontOffice/gallerie/AddArticle.html.twig', [
            'form' => $form->createView(),
            'galerie' => $galerie
        ]);
    }



    #[Route('/article/{id}/like', name: 'article_like')]
    public function likeArticle(Article $article, EntityManagerInterface $em, Request $request): Response
    {
        if (!$this->getUser()) {
            return $this->json([
                'code' => 'NEED_LOGIN',
                'message' => 'Vous devez être connecté pour aimer un article'
            ], 403);
        }

        $artLikeRepository = $em->getRepository(ArtLike::class);
        $existingLike = $artLikeRepository->findOneBy([
            'article' => $article,
            'user' => $this->getUser()
        ]);

        if ($existingLike) {
            // Unlike
            $em->remove($existingLike);
            $article->setNbrlikes($article->getNbrlikes() - 1);
            $isLiked = false;
        } else {
            // Like
            $like = new ArtLike();
            $like->setArticle($article);
            $like->setUser($this->getUser());
            $like->setLikedAt(new \DateTime());
            $em->persist($like);
            $article->setNbrlikes($article->getNbrlikes() + 1);
            $isLiked = true;
        }

        $em->flush();

        return $this->json([
            'code' => 'SUCCESS',
            'likes' => $article->getNbrlikes(),
            'isLiked' => $isLiked
        ]);
    }

    #[Route('/payment/success', name: 'payment_success')]
    public function paymentSuccess(Request $request, EntityManagerInterface $entityManager): Response
    {
        $payment_intent = $request->query->get('payment_intent');
        
        try {
            Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);
            $paymentIntent = \Stripe\PaymentIntent::retrieve($payment_intent);
            
            if ($paymentIntent->status === 'succeeded') {
                // Récupérer les métadonnées du paiement
                $metadata = $paymentIntent->metadata->toArray();
                $articleId = $metadata['article_id'];
                $quantity = (int)$metadata['quantity'];
                
                $article = $entityManager
                    ->getRepository(Article::class)
                    ->find($articleId);
                    
                if ($article) {
                    $newStock = $article->getNbrarticle() - $quantity;
                    
                    // Vérifier si le stock ne devient pas négatif
                    if ($newStock >= 0) {
                        // Mettre à jour le stock
                        $article->setNbrarticle($newStock);
                        
                        // Créer une nouvelle commande
                        $commande = new Commande();
                        $commande->setNumero('CMD-' . uniqid());
                        $commande->setUser($this->getUser());
                        $commande->setArticle($article);
                        $commande->setQuantite($quantity);
                        $commande->setPrixUnitaire($article->getPrix());
                        $commande->setDateCommande(new \DateTime());
                        $commande->calculateTotal();

                        // Persister la commande
                        $entityManager->persist($commande);
                        
                        // Si le stock atteint 0, marquer l'article comme indisponible
                        if ($newStock === 0) {
                            $article->setDisponible(false);
                        }
                        
                        $entityManager->flush();
                        
                        $this->addFlash('success', 'Paiement réussi ! Votre commande a été traitée.');
                        return $this->render('frontOffice/gallerie/payment_success.html.twig', [
                            'article' => $article,
                            'quantity' => $quantity,
                            'totalAmount' => $paymentIntent->amount / 100,
                            'commande' => $commande
                        ]);
                    } else {
                        $this->addFlash('error', 'Stock insuffisant pour cette commande.');
                    }
                } else {
                    $this->addFlash('error', 'Article non trouvé.');
                }
            } else {
                $this->addFlash('error', 'Le paiement n\'a pas été validé.');
            }
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors du traitement du paiement : ' . $e->getMessage());
        }
        
        return $this->redirectToRoute('app_gallerie');
    }

    #[Route('/payment/cancel', name: 'payment_cancel')]
    public function paymentCancel(Request $request): Response
    {
        return $this->render('frontOffice/gallerie/payment_cancel.html.twig');
    }

    #[Route('/payment/article/{id}', name: 'article_payment')]
    public function payment(Article $article, Request $request): Response
    {
        $quantity = $request->query->get('quantity', 1);
        
        // Vérifier si l'article est disponible
        if ($article->getNbrarticle() < $quantity) {
            $this->addFlash('error', 'La quantité demandée n\'est pas disponible.');
            return $this->redirectToRoute('app_gallerie');
        }
        
        $totalAmount = $article->getPrix() * $quantity;

        // Initialiser Stripe avec la clé secrète
        Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);

        // Créer l'intention de paiement
        $paymentIntent = PaymentIntent::create([
            'amount' => $totalAmount * 100, // Stripe utilise les centimes
            'currency' => 'eur',
            'automatic_payment_methods' => [
                'enabled' => true,
            ],
            'metadata' => [
                'article_id' => $article->getId(),
                'quantity' => $quantity
            ]
        ]);

        return $this->render('frontOffice/gallerie/payment.html.twig', [
            'article' => $article,
            'quantity' => $quantity,
            'totalAmount' => $totalAmount,
            'clientSecret' => $paymentIntent->client_secret,
            'stripePublicKey' => $_ENV['STRIPE_PUBLIC_KEY']
        ]);
    }

    #[Route('/process-payment', name: 'process_payment', methods: ['POST'])]
    public function processPayment(Request $request)
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            // Vérifier le paiement avec Stripe
            Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);
            $paymentIntent = PaymentIntent::retrieve($data['payment_intent_id']);
            
            if ($paymentIntent->status === 'succeeded') {
                // Traiter la commande réussie
                return new JsonResponse(['success' => true]);
            }
            
            return new JsonResponse(['error' => 'Payment failed'], 400);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/generate-invoice/{articleId}/{quantity}', name: 'generate_invoice_pdf')]
    public function generateInvoicePdf(int $articleId, int $quantity, ArticleRepository $articleRepository): Response
    {
        // Récupérer les données nécessaires
        $article = $articleRepository->find($articleId);
        $user = $this->getUser();
        $totalAmount = $article->getPrix() * $quantity;
        $orderId = random_int(10000, 99999);

        // Configurer Dompdf
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);

        $dompdf = new Dompdf($options);

        // Générer le HTML
        $html = $this->renderView('frontOffice/gallerie/invoice_pdf.html.twig', [
            'article' => $article,
            'quantity' => $quantity,
            'totalAmount' => $totalAmount,
            'user' => $user,
            'orderId' => $orderId
        ]);

        // Charger le HTML dans Dompdf
        $dompdf->loadHtml($html);

        // Configurer le format du papier
        $dompdf->setPaper('A4', 'portrait');

        // Rendre le PDF
        $dompdf->render();

        // Générer un nom de fichier
        $fileName = 'facture-' . $orderId . '.pdf';

        // Retourner le PDF comme réponse
        return new Response(
            $dompdf->output(),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"'
            ]
        );
    }

    #[Route('/mes-ventes/{id}', name: 'mes_ventes')]
    public function mesVentes(
        int $id,
        GalerieRepository $galerieRepository,
        CommandeRepository $commandeRepository
    ): Response {
        $galerie = $galerieRepository->find($id);
        
        if (!$galerie || $galerie->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas accéder à ces ventes.');
        }

        // Récupérer toutes les commandes des articles de cette galerie
        $ventes = $commandeRepository->createQueryBuilder('c')
            ->join('c.article', 'a')
            ->join('a.galerie', 'g')
            ->where('g.id = :galerieId')
            ->setParameter('galerieId', $id)
            ->orderBy('c.dateCommande', 'DESC')
            ->getQuery()
            ->getResult();

        // Calculer les statistiques
        $totalVentes = 0;
        $nombreArticlesVendus = 0;
        foreach ($ventes as $vente) {
            $totalVentes += $vente->getTotal();
            $nombreArticlesVendus += $vente->getQuantite();
        }

        return $this->render('frontOffice/gallerie/MesVentes.html.twig', [
            'ventes' => $ventes,
            'galerie' => $galerie,
            'totalVentes' => $totalVentes,
            'nombreArticlesVendus' => $nombreArticlesVendus
        ]);
    }

    #[Route('/artiste/{id}', name: 'artiste_profile')]
    public function artisteProfile(
        int $id,
        UserRepository $userRepository,
        ArticleRepository $articleRepository
    ): Response {
        $artiste = $userRepository->find($id);
        
        if (!$artiste) {
            throw $this->createNotFoundException('Artiste non trouvé');
        }

        // Récupérer tous les articles de l'artiste à travers sa galerie
        $articles = $articleRepository->createQueryBuilder('a')
            ->join('a.galerie', 'g')
            ->join('g.user', 'u')
            ->where('u.id = :userId')
            ->setParameter('userId', $id)
            ->orderBy('a.date_pub', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('frontOffice/gallerie/Artiste.html.twig', [
            'artiste' => $artiste,
            'articles' => $articles
        ]);
    }
}
