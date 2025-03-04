<?php

namespace App\Service;

class FraudDetectionService
{
    private array $artKeywords = [
        // Arts Visuels et Beaux-Arts
        'peinture', 'dessin', 'sculpture', 'gravure', 'illustration', 'photographie',
        'art numérique', 'graphisme', 'design', 'art contemporain', 'beaux-arts',
        'art moderne', 'art abstrait', 'art figuratif', 'street art', 'graffiti', 
        'calligraphie', 'enluminure', 'estampe', 'lithographie', 'sérigraphie',
        
        // Arts Numériques et Multimédia
        'animation', '3D', 'modélisation', 'motion design', 'effets spéciaux',
        'réalité virtuelle', 'réalité augmentée', 'art digital', 'web design',
        'concept art', 'character design', 'game art', 'infographie',
        
        // Artisanat d'Art
        'céramique', 'poterie', 'verrerie', 'ébénisterie', 'menuiserie', 'joaillerie',
        'bijouterie', 'textile', 'couture', 'tapisserie', 'reliure', 'restauration',
        'dorure', 'marqueterie', 'ferronnerie', 'vitrail', 'mosaïque', 'vannerie',
        'maroquinerie', 'sellerie', 'cordonnerie', 'chapellerie', 'émaillage',
        'dinanderie', 'lutherie', 'horlogerie', 'coutellerie', 'taille de pierre',
        
        // Techniques et Matériaux
        'aquarelle', 'acrylique', 'huile', 'fusain', 'pastel', 'encre', 'crayon',
        'modelage', 'moulage', 'assemblage', 'soudure', 'patine', 'vernissage',
        'bronze', 'marbre', 'bois', 'métal', 'cuir', 'tissu', 'laine', 'soie',
        'verre', 'argile', 'plâtre', 'résine', 'pierre', 'or', 'argent', 'cuivre',
        
        // Compétences Artistiques
        'créativité', 'composition', 'perspective', 'couleur', 'volume', 'esthétique',
        'conception', 'création', 'design', 'stylisme', 'direction artistique',
        'croquis', 'esquisse', 'maquette', 'prototype', 'rough', 'story-board',
        'mise en page', 'harmonie', 'contraste', 'texture', 'motif', 'pattern',
        
        // Environnement Artistique
        'atelier', 'studio', 'galerie', 'exposition', 'musée', 'salon', 'vernissage',
        'biennale', 'festival', 'artiste', 'artisan', 'créateur', 'designer',
        'conservateur', 'restaurateur', 'commissaire', 'galeriste', 'antiquaire',
        
        // Métiers d'Art Spécifiques
        'luthier', 'verrier', 'doreur', 'enlumineur', 'graveur', 'sculpteur',
        'céramiste', 'ébéniste', 'joaillier', 'bijoutier', 'tapissier', 'relieur',
        'ferronnier', 'mosaïste', 'vannier', 'maroquinier', 'sellier', 'cordonnier',
        'chapelier', 'émailleur', 'dinandier', 'horloger', 'coutelier', 'tailleur',
        
        // Domaines Connexes
        'patrimoine', 'culture', 'tradition', 'savoir-faire', 'artisanat d\'art',
        'métiers d\'art', 'arts décoratifs', 'arts appliqués', 'arts plastiques',
        'conservation', 'restauration', 'transmission', 'apprentissage', 'formation',
        
        // Termes Techniques Spécifiques
        'polychromie', 'chromie', 'pigment', 'glacis', 'émaux', 'patine', 'ciselure',
        'damasquinage', 'niellage', 'champlevé', 'cloisonné', 'repoussé', 'filigrane',
        'granulation', 'guillochage', 'marquage', 'placage', 'tournage', 'ciselage',
        
        // Styles et Mouvements Artistiques
        'impressionnisme', 'expressionnisme', 'cubisme', 'surréalisme', 'minimalisme',
        'art déco', 'art nouveau', 'baroque', 'renaissance', 'modernisme', 'pop art',
        'art brut', 'art naïf', 'art primitif', 'art conceptuel', 'art urbain',
        
        // Arts Numériques et VFX
        'vfx', 'effets visuels', 'effets spéciaux', 'visual effects', 
        'unity', 'unreal', 'shader', 'particle system', 'particules',
        'compositing', 'composition numérique', 'rendu', 'rendering',
        'animation 3d', 'modélisation 3d', '3d modeling', 'texturing',
        'rigging', 'skinning', 'motion capture', 'mocap', 'tracking',
        'simulation', 'dynamics', 'lighting', 'éclairage', 'post-production',
        'character animation', 'animation de personnages', 'fx artist',
        'technical artist', 'artiste technique', 'game engine', 'moteur de jeu',
        'node editor', 'éditeur de nœuds', 'pipeline 3d', 'workflow',
        'houdini', 'maya', 'blender', '3ds max', 'substance', 'nuke',
        'after effects', 'photoshop', 'zbrush', 'mari', 'clarisse',
        
        // Game Art
        'game art', 'art de jeu', 'level art', 'environment art',
        'character art', 'prop art', 'concept art', 'ui art',
        'game design', 'level design', 'environment design',
        'character design', 'asset creation', 'création d\'assets',
        'world building', 'création de monde', 'game artist',
        'artiste de jeu', 'game developer', 'développeur de jeu',
        
        // Termes Techniques 3D
        'topology', 'topologie', 'uv mapping', 'unwrap', 'dépliage uv',
        'normal map', 'carte de normales', 'pbr', 'physically based rendering',
        'baking', 'cuisson de textures', 'vertex', 'polygone', 'mesh',
        'maillage', 'subdivision', 'lod', 'level of detail', 'optimisation'
    ];

    private array $fraudIndicators = [
        'payment_required' => [
            'paiement initial',
            'frais d\'inscription',
            'investissement requis',
            'payer pour commencer',
        ],
        'unrealistic_promises' => [
            'richesse rapide',
            'gains garantis',
            'revenus illimités',
            'fortune facile',
            'argent facile',
        ],
        'suspicious_contact' => [
            'whatsapp seulement',
            'telegram uniquement',
            'contact personnel uniquement',
        ],
        'data_collection' => [
            'numéro de sécurité sociale',
            'informations bancaires',
            'scan passeport',
            'documents personnels',
        ],
        'urgency_pressure' => [
            'offre limitée',
            'urgent',
            'dernier jour',
            'places limitées',
        ]
    ];

    private array $legitimacyIndicators = [
        'company_details' => [
            'siret',
            'numéro siret',
            'registre du commerce',
            'rcs',
        ],
        'professional_terms' => [
            'convention collective',
            'cdd',
            'cdi',
            'temps plein',
            'temps partiel',
        ],
        'clear_process' => [
            'entretien',
            'processus de recrutement',
            'candidature',
            'cv',
        ]
    ];

    public function analyzeListing(string $title, string $description, string $competences): array
    {
        $text = mb_strtolower($title . ' ' . $description . ' ' . $competences);
        $score = 100;
        $warnings = [];
        $fraudFound = false;

        // Vérifier la pertinence artistique
        $artKeywordsFound = 0;
        $digitalArtKeywords = 0;
        
        foreach ($this->artKeywords as $keyword) {
            if (mb_stripos($text, $keyword) !== false) {
                $artKeywordsFound++;
                // Donner plus de poids aux mots-clés liés aux arts numériques
                if (mb_stripos($keyword, '3d') !== false || 
                    mb_stripos($keyword, 'vfx') !== false || 
                    mb_stripos($keyword, 'game') !== false ||
                    mb_stripos($keyword, 'digital') !== false ||
                    mb_stripos($keyword, 'numérique') !== false) {
                    $digitalArtKeywords++;
                }
            }
        }

        // Calculer le score de pertinence artistique
        $artScore = (($artKeywordsFound + $digitalArtKeywords) / 3) * 20; // Bonus pour les arts numériques
        
        if ($artScore < 20 && $digitalArtKeywords == 0) {
            $warnings[] = 'Cette offre ne semble pas correspondre au domaine de l\'art ou de l\'artisanat.';
            $fraudFound = true;
            $score -= (20 - $artScore);
        }

        // Vérifier les indicateurs de fraude
        foreach ($this->fraudIndicators as $category => $indicators) {
            foreach ($indicators as $indicator) {
                if (mb_stripos($text, $indicator) !== false) {
                    $score -= 20;
                    $warnings[] = $this->getFraudWarningMessage($category);
                    $fraudFound = true;
                    break;
                }
            }
        }

        // Bonus pour les indicateurs de légitimité
        foreach ($this->legitimacyIndicators as $category => $indicators) {
            foreach ($indicators as $indicator) {
                if (mb_stripos($text, $indicator) !== false) {
                    $score += 10;
                    break;
                }
            }
        }

        // Limiter le score entre 0 et 100
        $score = max(0, min(100, $score));

        return [
            'score' => $score,
            'warnings' => array_unique($warnings),
            'status' => $this->getStatusFromScore($score),
            'fraudFound' => $fraudFound,
            'artScore' => $artScore
        ];
    }

    private function getFraudWarningMessage(string $category): string
    {
        return match ($category) {
            'payment_required' => 'Attention : Cette offre demande un paiement initial, ce qui est souvent un signe de fraude.',
            'unrealistic_promises' => 'Attention : Cette offre contient des promesses irréalistes de gains.',
            'suspicious_contact' => 'Attention : Les méthodes de contact proposées sont inhabituelles.',
            'data_collection' => 'Attention : Cette offre demande des informations personnelles sensibles.',
            'urgency_pressure' => 'Attention : Cette offre utilise des tactiques de pression et d\'urgence.',
            default => 'Attention : Cette offre présente des signes suspects.'
        };
    }

    private function getStatusFromScore(int $score): string
    {
        return match (true) {
            $score >= 80 => 'Légitime',
            $score >= 60 => 'Probablement légitime',
            $score >= 40 => 'Suspect',
            default => 'Très suspect'
        };
    }
} 