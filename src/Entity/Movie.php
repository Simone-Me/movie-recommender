<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\Filter\RangeFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use App\Repository\MovieRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: MovieRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new GetCollection(
            normalizationContext: ['groups' => ['movie:list']]
        ),
        new Get(
            normalizationContext: ['groups' => ['movie:item']]
        ),
        new Post(
            denormalizationContext: ['groups' => ['movie:write']],
            security: "is_granted('ROLE_ADMIN')"
        ),
        new Put(
            denormalizationContext: ['groups' => ['movie:write']],
            security: "is_granted('ROLE_ADMIN')"
        ),
        new Delete(
            security: "is_granted('ROLE_ADMIN')"
        )
    ],
    order: ['vote_average' => 'DESC'],
    paginationEnabled: true,
    paginationItemsPerPage: 10
)]
#[ApiFilter(SearchFilter::class, properties: [
    'title' => 'partial',
    'originalLanguage' => 'exact',
    'productionCountry' => 'exact'
])]
#[ApiFilter(RangeFilter::class, properties: ['vote_average', 'release_date'])]
#[ApiFilter(OrderFilter::class, properties: ['title', 'release_date', 'vote_average', 'voteCount', 'revenue'])]
class Movie
{
    #[ORM\Id]
    #[ORM\Column]
    #[Groups(['movie:list', 'movie:item', 'movie:write'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['movie:list', 'movie:item', 'movie:write'])]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['movie:item', 'movie:write'])]
    private ?string $overview = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['movie:list', 'movie:item', 'movie:write'])]
    private ?string $poster_path = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Groups(['movie:list', 'movie:item', 'movie:write'])]
    private ?\DateTimeInterface $release_date = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 3, scale: 1, nullable: true)]
    #[Groups(['movie:list', 'movie:item', 'movie:write'])]
    private ?float $vote_average = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['movie:item', 'movie:write'])]
    private ?int $voteCount = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    #[Groups(['movie:item', 'movie:write'])]
    private ?float $popularity = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['movie:item', 'movie:write'])]
    private ?string $genre = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['movie:item', 'movie:write'])]
    private ?string $originalLanguage = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['movie:item', 'movie:write'])]
    private ?string $productionCountry = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    #[Groups(['movie:item', 'movie:write'])]
    private ?float $revenue = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['movie:item', 'movie:write'])]
    private ?int $runtime = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['movie:item', 'movie:write'])]
    private ?string $tmdbId = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['movie:item'])]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['movie:item'])]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getOverview(): ?string
    {
        return $this->overview;
    }

    public function setOverview(?string $overview): self
    {
        $this->overview = $overview;
        return $this;
    }

    public function getPoster_path(): ?string
    {
        return $this->poster_path;
    }

    public function setPoster_path(?string $poster_path): self
    {
        $this->poster_path = $poster_path;
        return $this;
    }

    public function getRelease_date(): ?\DateTimeInterface
    {
        return $this->release_date;
    }

    public function setRelease_date(?\DateTimeInterface $release_date): self
    {
        $this->release_date = $release_date;
        return $this;
    }

    public function getVote_average(): ?float
    {
        return $this->vote_average;
    }

    public function setVote_average(?float $vote_average): self
    {
        $this->vote_average = $vote_average;
        return $this;
    }

    public function getVoteCount(): ?int
    {
        return $this->voteCount;
    }

    public function setVoteCount(?int $voteCount): self
    {
        $this->voteCount = $voteCount;
        return $this;
    }

    public function getPopularity(): ?float
    {
        return $this->popularity;
    }

    public function setPopularity(?float $popularity): self
    {
        $this->popularity = $popularity;
        return $this;
    }

    public function getGenre(): ?string
    {
        return $this->genre;
    }

    public function setGenre(?string $genre): self
    {
        $this->genre = $genre;
        return $this;
    }

    public function getOriginalLanguage(): ?string
    {
        return $this->originalLanguage;
    }

    public function setOriginalLanguage(?string $originalLanguage): self
    {
        $this->originalLanguage = $originalLanguage;
        return $this;
    }

    public function getProductionCountry(): ?string
    {
        return $this->productionCountry;
    }

    public function setProductionCountry(?string $productionCountry): self
    {
        $this->productionCountry = $productionCountry;
        return $this;
    }

    public function getRevenue(): ?float
    {
        return $this->revenue;
    }

    public function setRevenue(?float $revenue): self
    {
        $this->revenue = $revenue;
        return $this;
    }

    public function getRuntime(): ?int
    {
        return $this->runtime;
    }

    public function setRuntime(?int $runtime): self
    {
        $this->runtime = $runtime;
        return $this;
    }

    public function getTmdbId(): ?string
    {
        return $this->tmdbId;
    }

    public function setTmdbId(string $tmdbId): self
    {
        $this->tmdbId = $tmdbId;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }
}
