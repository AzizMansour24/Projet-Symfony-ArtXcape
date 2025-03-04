<?php

namespace App\Controller;

use App\Entity\Categorie;
use App\Entity\Formation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use App\Form\CategorieType;

final class CategorieController extends AbstractController
{
    #[Route('/backcategorie', name: 'back_showcategorie')]
    public function show(Request $request, EntityManagerInterface $entityManager): Response
    {
        $categories = $entityManager->getRepository(Categorie::class)->findAll();
        
        // Get statistics for categories
        $categoryStats = [];
        foreach ($categories as $category) {
            $formationCount = $entityManager->getRepository(Formation::class)
                ->count(['categorie' => $category]);
            $categoryStats[] = [
                'name' => $category->getNom(),
                'count' => $formationCount
            ];
        }
        
        // Create new category instance
        $categorie = new Categorie();
        $categorie->setDateCreation(new \DateTime());
        
        // Create form
        $form = $this->createForm(CategorieType::class, $categorie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($categorie);
            $entityManager->flush();

            $this->addFlash('success', 'La catégorie a été créée avec succès!');
            return $this->redirectToRoute('back_showcategorie');
        }
        
        return $this->render('backOffice/formation/categorie.html.twig', [
            'categories' => $categories,
            'categoryStats' => $categoryStats,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/backcategorie/edit/{id}', name: 'back_editcategorie')]
    public function edit(Request $request, EntityManagerInterface $entityManager, int $id): Response
    {
        $categorie = $entityManager->getRepository(Categorie::class)->find($id);
        
        if (!$categorie) {
            throw $this->createNotFoundException('Catégorie non trouvée');
        }
        
        $form = $this->createForm(CategorieType::class, $categorie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            
            $this->addFlash('success', 'La catégorie a été modifiée avec succès!');
            return $this->redirectToRoute('back_showcategorie');
        }
        
        return $this->render('backOffice/categorie/edit.html.twig', [
            'form' => $form->createView(),
            'categorie' => $categorie
        ]);
    }

    #[Route('/backcategorie/delete/{id}', name: 'back_deletecategorie')]
    public function delete(EntityManagerInterface $entityManager, Categorie $categorie): Response
    {
        // Check if category has related formations
        $formationsCount = $entityManager->getRepository(Formation::class)
            ->count(['categorie' => $categorie]);
            
        if ($formationsCount > 0) {
            $this->addFlash('error', 'Impossible de supprimer cette catégorie car elle contient des formations.');
            return $this->redirectToRoute('back_showcategorie');
        }

        $entityManager->remove($categorie);
        $entityManager->flush();

        $this->addFlash('success', 'La catégorie a été supprimée avec succès!');
        return $this->redirectToRoute('back_showcategorie');
    }
}
