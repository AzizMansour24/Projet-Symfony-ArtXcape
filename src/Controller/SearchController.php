<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\EmploiRepository;

class SearchController extends AbstractController
{
    #[Route('/search', name: 'app_search')]
    public function search(Request $request, EmploiRepository $emploiRepository): Response
    {
        // Récupérer le terme de recherche depuis la requête
        $query = $request->query->get('query');

        // Effectuer la recherche en utilisant le repository
        $results = [];
        if ($query) {
            $results = $emploiRepository->findByTitre($query);
        }

        // Afficher les résultats de la recherche
        return $this->render('results.html.twig', [
            'query' => $query,
            'results' => $results,
        ]);
    }
}