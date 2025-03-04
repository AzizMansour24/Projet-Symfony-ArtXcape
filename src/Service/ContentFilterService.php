<?php

namespace App\Service;

use App\Repository\BadWordRepository;
use Doctrine\ORM\EntityManagerInterface;

class ContentFilterService
{
    private $badWordRepository;
    private $entityManager;
    private $badWords = [];
    private $replacements = [];

    public function __construct(BadWordRepository $badWordRepository, EntityManagerInterface $entityManager)
    {
        $this->badWordRepository = $badWordRepository;
        $this->entityManager = $entityManager;
        $this->loadBadWords();
    }

    private function loadBadWords(): void
    {
        $badWords = $this->badWordRepository->findAll();
        foreach ($badWords as $badWord) {
            $this->badWords[] = mb_strtolower($badWord->getWord());
            $this->replacements[mb_strtolower($badWord->getWord())] = $badWord->getReplacement() ?? str_repeat('*', mb_strlen($badWord->getWord()));
        }
    }

    public function filterContent(string $content): string
    {
        if (empty($this->badWords)) {
            return $content;
        }

        $filteredContent = mb_strtolower($content);
        
        foreach ($this->badWords as $badWord) {
            // Utiliser une expression régulière pour trouver le mot avec des limites de mot
            $pattern = '/\b' . preg_quote($badWord, '/') . '\b/ui';
            $replacement = $this->replacements[$badWord];
            
            // Remplacer toutes les occurrences du mot (en préservant la casse)
            $filteredContent = preg_replace_callback($pattern, function($matches) use ($replacement) {
                return $replacement;
            }, $filteredContent);
        }

        return $filteredContent;
    }
} 