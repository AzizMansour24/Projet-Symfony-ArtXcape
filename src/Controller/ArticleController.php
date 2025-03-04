<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Galerie;
use App\Entity\ArtLike;
use App\Form\ArticleType;
use App\Repository\ArticleRepository;
use App\Repository\ArtLikeRepository;
use App\Repository\GalerieRepository;
use App\Repository\CommandeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Knp\Component\Pager\PaginatorInterface;

final class ArticleController extends AbstractController
{
    #[Route('/article', name: 'app_article')]
    public function index(): Response
    {
        return $this->render('article/index.html.twig', [
            'controller_name' => 'ArticleController',
        ]);
    }
    //**********************************************backOffice**************************************************
    //Affichage
    #[Route('/backShowarticles', name: 'back_showarticle')]
    public function ShowArticles(ArticleRepository $ar): Response
    {
        $list=$ar->findAll();
        return $this->render('backOffice/article/ArticleShow.html.twig', [
            'liste' => $list,
        ]);
    }
    //DELETE 
    #[Route('/backDelarticle{id}',name:'delete_article')]
    public function DeleteArticle($id,ArticleRepository $ar,EntityManagerInterface $em)
    {
        $article=$ar->find($id);
        $em->remove($article);
        $em->flush();
        return $this->redirectToRoute('back_showarticle');
    }
    //Add
    #[Route('/backAddarticle', name: 'ajouter_article')]
public function ajouterArticle(Request $request, EntityManagerInterface $em)
{
    $article = new Article();
    $form = $this->createForm(ArticleType::class, $article);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        /** @var UploadedFile $imageFile */
        $imageFile = $form->get('contenu')->getData();

        if ($imageFile) {
            // Generate a unique filename for the image
            $newFilename = uniqid() . '.' . $imageFile->guessExtension();

            // Move the uploaded file to the specified directory
            $imageFile->move(
                $this->getParameter('images_directory'), // Directory from services.yaml
                $newFilename
            );

            // Save the filename (path) into the article entity
            $article->setContenu($newFilename);
        }

        // Persist the article in the database
        $em->persist($article);
        $em->flush();

        // Redirect to the article list page
        return $this->redirectToRoute('back_showarticle');
    }

    // Render the form view if not submitted or invalid
    return $this->render('backOffice/article/ArticleAdd.html.twig', [
        'form' => $form->createView(),
        'titre' => "Ajouter",
    ]);
}

//Edit
#[Route('/backEditarticle{id}', name: 'edit_article')]
public function modifierArticle($id,Request $request, EntityManagerInterface $em, ArticleRepository $ar)
{
    $article=$ar->find($id);
    $form = $this->createForm(ArticleType::class, $article);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        /** @var UploadedFile $imageFile */
        $imageFile = $form->get('contenu')->getData();

        if ($imageFile) {
            // Generate a unique filename for the image
            $newFilename = uniqid() . '.' . $imageFile->guessExtension();

            // Move the uploaded file to the specified directory
            $imageFile->move(
                $this->getParameter('images_directory'), // Directory from services.yaml
                $newFilename
            );

            // Save the filename (path) into the article entity
            $article->setContenu($newFilename);
        }

        // Persist the article in the database
        $em->persist($article);
        $em->flush();

        // Redirect to the article list page
        return $this->redirectToRoute('back_showarticle');
    }

    // Render the form view if not submitted or invalid
    return $this->render('backOffice/article/ArticleAdd.html.twig', [
        'form' => $form->createView(),
        'titre' => "Modifier",
    ]);
}

//Affichage par gallerie
#[Route('/backShowarticlesby{id}', name: 'back_showarticlebygallerie')]
public function ShowArticlesByGallerie(ArticleRepository $ar,$id,GalerieRepository $gr): Response
{
    $galerie =$gr->find($id);
    $list=$ar->findByGalerie($galerie);
    return $this->render('backOffice/article/ArticleShow.html.twig', [
        'liste' => $list,
    ]);
}
//affichage par categorie
#[Route('/gallerie-{cat}', name: 'cat_article')]
public function categories(ArticleRepository $ar, string $cat): Response
{
$liste = $ar->findBy(['categorie' => $cat]); // Extraction des articles ayant $cat comme catégorie
return $this->render('frontOffice/gallerie/GallerieExplorer.html.twig', [
    'controller_name' => 'GallerieController',
    'liste' => $liste,
]);
}

#[Route('/backShowlikes', name: 'article_likes')]
public function showLikes(ArtLikeRepository $ar, ArticleRepository $articleRepo): Response
{
    $likes = $ar->findAll();
    
    // Récupérer les 3 articles les plus likés
    $topArticles = $articleRepo->findBy([], ['nbrlikes' => 'DESC'], 3);
    
    // Formater les données pour la vue
    $topArticlesData = array_map(function($article) {
        return [
            'id' => $article->getId(),
            'titre' => $article->getTitre(),
            'count' => $article->getNbrlikes()
        ];
    }, $topArticles);
    
    return $this->render('backOffice/article/ArticleLikes.html.twig', [
        'likes' => $likes,
        'topArticles' => $topArticlesData
    ]);
}

#[Route('/backShowCommandes', name: 'back_showcommandes')]
public function ShowCommandes(CommandeRepository $cr): Response
{
    $commandes = $cr->findAll();
    
    // Calculer le total des ventes
    $totalVentes = 0;
    foreach ($commandes as $commande) {
        $totalVentes += $commande->getTotal();
    }
    
    return $this->render('backOffice/article/CommandeShow.html.twig', [
        'commandes' => $commandes,
        'totalVentes' => $totalVentes
    ]);
}

}
