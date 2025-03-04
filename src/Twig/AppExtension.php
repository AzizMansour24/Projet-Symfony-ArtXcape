<?php
//verifier si un article est liké par un utilisateur
namespace App\Twig;

use App\Entity\Article;
use App\Entity\User;
use App\Entity\ArtLike;
use Doctrine\ORM\EntityManagerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('is_liked', [$this, 'isLiked']),
        ];
    }

    public function isLiked(Article $article, ?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return null !== $this->em->getRepository(ArtLike::class)->findOneBy([
            'article' => $article,
            'user' => $user
        ]);
    }
} 