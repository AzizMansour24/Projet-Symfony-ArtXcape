<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class NlpExtension extends AbstractExtension
{
    private array $domainKeywords = [
        // Arts Visuels et Beaux-Arts
        'peinture', 'dessin', 'sculpture', 'gravure', 'illustration', 'aquarelle', 
        'acrylique', 'huile', 'fusain', 'pastel', 'encre', 'crayon', 'esquisse',
        'croquis', 'portrait', 'nature morte', 'paysage', 'abstrait', 'figuratif',
        'contemporain', 'moderne', 'classique', 'avant-garde',

        // Techniques Artistiques
        'composition', 'perspective', 'anatomie', 'couleur', 'lumière', 'ombre',
        'volume', 'texture', 'contraste', 'harmonie', 'proportion', 'mouvement',
        'gestuelle', 'expression', 'style', 'technique', 'méthode', 'processus',

        // Artisanat d'Art
        'céramique', 'poterie', 'verrerie', 'ébénisterie', 'joaillerie', 'textile',
        'tapisserie', 'mosaïque', 'reliure', 'restauration', 'dorure', 'émaillage',
        'marqueterie', 'ferronnerie', 'vitrail', 'broderie', 'tissage',

        // Matériaux
        'toile', 'papier', 'bois', 'métal', 'pierre', 'argile', 'verre', 'textile',
        'cuir', 'bronze', 'marbre', 'plâtre', 'résine', 'pigments', 'supports',
        'matériaux', 'outils', 'pinceaux', 'spatules', 'chevalets',

        // Compétences Professionnelles
        'créativité', 'innovation', 'conception', 'réalisation', 'finition',
        'précision', 'minutie', 'dextérité', 'savoir-faire', 'expertise',
        'expérience', 'maîtrise', 'perfectionnement', 'formation',

        // Environnement Professionnel
        'atelier', 'studio', 'galerie', 'exposition', 'musée', 'conservation',
        'collection', 'commissariat', 'scénographie', 'muséographie', 'patrimoine',
        'vernissage', 'catalogue', 'documentation', 'archive',

        // Gestion de Projet Artistique
        'conception', 'direction artistique', 'coordination', 'planification',
        'budget', 'délais', 'collaboration', 'communication', 'présentation',
        'promotion', 'exposition', 'installation', 'scénographie', 'art'
    ];

    public function getFunctions(): array
    {
        return [
            new TwigFunction('nlp_extract_keywords', [$this, 'extractKeywords']),
        ];
    }

    public function extractKeywords(string $titre, string $description, string $competences): array
    {
        // Combiner tous les textes en donnant plus de poids au titre et aux compétences
        $text = mb_strtolower($titre . ' ' . $titre . ' ' . $description . ' ' . $competences . ' ' . $competences);

        // Supprimer la ponctuation mais garder les tirets pour les mots composés
        $text = preg_replace('/[^\p{L}\p{N}\s-]/u', ' ', $text);

        // Rechercher des mots-clés du domaine dans le texte
        $foundKeywords = [];
        foreach ($this->domainKeywords as $keyword) {
            if (mb_stripos($text, $keyword) !== false && !in_array($keyword, $foundKeywords)) {
                $foundKeywords[] = $keyword;
            }
        }

        // Si on a trouvé des mots-clés du domaine
        if (!empty($foundKeywords)) {
            // Trier par pertinence (longueur du mot-clé)
            usort($foundKeywords, function($a, $b) {
                return mb_strlen($b) - mb_strlen($a);
            });
            
            // Prendre les mots-clés uniques (maximum 8)
            $keywords = array_slice(array_unique($foundKeywords), 0, 8);
        } else {
            // Diviser en mots
            $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);

            // Liste des mots vides
            $stopWords = [
                'le', 'la', 'les', 'un', 'une', 'des', 'et', 'ou', 'de', 'du', 'en', 'à',
                'pour', 'dans', 'par', 'sur', 'avec', 'sans', 'sous', 'entre', 'vers',
                'the', 'and', 'or', 'for', 'to', 'in', 'on', 'at', 'by', 'with'
            ];

            // Filtrer les mots
            $filteredWords = [];
            foreach ($words as $word) {
                if (mb_strlen($word) > 2 && !in_array($word, $stopWords) && !in_array($word, $filteredWords)) {
                    $filteredWords[] = $word;
                }
            }

            // Compter la fréquence
            $wordFrequency = array_count_values($filteredWords);
            arsort($wordFrequency);
            
            // Prendre les mots uniques les plus fréquents (maximum 8)
            $keywords = array_slice(array_keys(array_unique($wordFrequency)), 0, 8);
        }

        // Capitaliser la première lettre de chaque mot-clé
        return array_map(function($keyword) {
            return ucfirst(trim($keyword));
        }, $keywords);
    }
} 