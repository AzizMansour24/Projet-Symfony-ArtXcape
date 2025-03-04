<?php

namespace App\Entity;

use App\Repository\ArticleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: ArticleRepository::class)]
class Article
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::FLOAT)]
    private ?float $prix = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $date_pub = null;

    #[ORM\Column]
    private ?bool $disponible = null;

    #[ORM\Column]
    private ?int $nbrarticle = null;

    #[ORM\Column]
    private ?int $nbrlikes = 0;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $contenu = null;
    private ?string $statut = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $categorie = null;

    #[ORM\ManyToOne(targetEntity: Galerie::class, inversedBy: 'articles')]
    #[ORM\JoinColumn(name: "galerie_id", referencedColumnName: "id", onDelete: "CASCADE")]
    private ?Galerie $galerie = null;

    #[ORM\OneToMany(mappedBy: 'article', targetEntity: ArtLike::class, cascade: ['persist', 'remove'])]
    private Collection $artLikes;

    public function __construct()
    {
        $this->artLikes = new ArrayCollection();
        $this->nbrlikes = 0;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getPrix(): ?float
    {
        return $this->prix;
    }

    public function setPrix(float $prix): static
    {
        $this->prix = $prix;
        return $this;
    }

    public function getDatePub(): ?\DateTimeInterface
    {
        return $this->date_pub;
    }

    public function setDatePub(\DateTimeInterface $date_pub): static
    {
        $this->date_pub = $date_pub;
        return $this;
    }

    public function isDisponible(): ?bool
    {
        return $this->disponible;
    }

    public function setDisponible(bool $disponible): static
    {
        $this->disponible = $disponible;
        return $this;
    }

    public function getNbrarticle(): ?int
    {
        return $this->nbrarticle;
    }

    public function setNbrarticle(int $nbrarticle): static
    {
        $this->nbrarticle = $nbrarticle;
        return $this;
    }

    public function getNbrlikes(): ?int
    {
        return $this->nbrlikes;
    }

    public function setNbrlikes(int $nbrlikes): static
    {
        $this->nbrlikes = $nbrlikes;
        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(?string $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    public function getCategorie(): ?string
    {
        return $this->categorie;
    }

    public function setCategorie(?string $categorie): static
    {
        $this->categorie = $categorie;
        return $this;
    }

    public function getGalerie(): ?Galerie
    {
        return $this->galerie;
    }

    public function setGalerie(?Galerie $galerie): static
    {
        $this->galerie = $galerie;
        return $this;
    }

    public function getContenu(): ?string
    {
        return $this->contenu;
    }

    public function setContenu(?string $contenu): self
    {
        $this->contenu = $contenu;

        return $this;
    }

    /**
     * @return Collection<int, ArtLike>
     */
    public function getArtLikes(): Collection
    {
        return $this->artLikes;
    }

    public function addArtLike(ArtLike $artLike): static
    {
        if (!$this->artLikes->contains($artLike)) {
            $this->artLikes->add($artLike);
            $artLike->setArticle($this);
            $this->nbrlikes++;
        }

        return $this;
    }

    public function removeArtLike(ArtLike $artLike): static
    {
        if ($this->artLikes->removeElement($artLike)) {
            if ($artLike->getArticle() === $this) {
                $artLike->setArticle(null);
            }
            $this->nbrlikes--;
        }

        return $this;
    }
} 