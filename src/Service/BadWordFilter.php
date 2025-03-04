<?php

namespace App\Service;

class BadWordFilter
{
    private array $badWords = [
        // English bad words  
        'fuck', 'shit', 'damn', 'bitch', 'ass', 'bastard', 'cunt', 'dick', 'pussy', 
        'asshole', 'whore', 'slut', 'piss', 'cock', 'wanker', 'twat',
        
        // French bad words
        'merde', 'putain', 'connard', 'salope', 'pute', 'enculé', 'connasse', 
        'branler', 'couilles', 'foutre', 'bite', 'chier', 'cul', 'nique',
        
        // Common variations and misspellings
        'fuk', 'fck', 'sh1t', 'b1tch', 'a$$', 'f*ck', 's*it', 'b*tch',
        'merde', 'putain', 'fdp', 'ntm', 'tg', 'stfu', 'gtfo',
        
        // Combined words
        'motherfucker', 'dumbass', 'bullshit', 'jackass', 'douchebag',
        'fils de pute', 'va te faire', 'ta gueule', 'ferme ta gueule',
        
        // Racial and discriminatory slurs (keeping list clean for demonstration)
        'racial_slur1', 'racial_slur2', 'discriminatory_word1', 'discriminatory_word2'
    ];

    public function filter(string $text): string
    {
        // Convert text to lowercase for comparison
        $textLower = mb_strtolower($text, 'UTF-8');
        
        // Split into words while preserving spaces and punctuation
        $words = preg_split('/(\s+|(?=[.,!?]))/', $textLower, -1, PREG_SPLIT_DELIM_CAPTURE);
        
        foreach ($words as &$word) {
            $trimmedWord = trim($word);
            if (in_array($trimmedWord, $this->badWords)) {
                // Replace with asterisks while keeping original length
                $word = str_repeat('*', mb_strlen($trimmedWord)) . substr($word, mb_strlen($trimmedWord));
            }
        }
        
        return implode('', $words);
    }

    public function containsBadWords(string $text): bool
    {
        $textLower = mb_strtolower($text, 'UTF-8');
        
        foreach ($this->badWords as $badWord) {
            if (mb_strpos($textLower, $badWord) !== false) {
                return true;
            }
        }
        
        return false;
    }
} 