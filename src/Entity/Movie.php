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
    order: ['voteAverage' => 'DESC'],
    paginationEnabled: true,
    paginationItemsPerPage: 10
)]
#[ApiFilter(SearchFilter::class, properties: [
    'title' => 'partial',
    'originalLanguage' => 'exact',
    'productionCountries' => 'exact'
])]
#[ApiFilter(RangeFilter::class, properties: ['voteAverage', 'releaseDate'])]
#[ApiFilter(OrderFilter::class, properties: ['title', 'releaseDate', 'voteAverage', 'voteCount', 'revenue'])]
class Movie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['movie:list', 'movie:item'])]
    private ?int $id = null;

    #[ORM\Column(unique: true)]
    #[Groups(['movie:list', 'movie:item', 'movie:write'])]
    private ?int $tmdbId = null;

    #[ORM\Column(length: 255)]
    #[Groups(['movie:list', 'movie:item', 'movie:write'])]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['movie:item', 'movie:write'])]
    private ?string $overview = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['movie:list', 'movie:item', 'movie:write'])]
    private ?string $posterPath = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Groups(['movie:list', 'movie:item', 'movie:write'])]
    private ?\DateTimeInterface $releaseDate = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 3, scale: 1, nullable: true)]
    #[Groups(['movie:list', 'movie:item', 'movie:write'])]
    private ?float $voteAverage = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['movie:item', 'movie:write'])]
    private ?int $voteCount = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    #[Groups(['movie:item', 'movie:write'])]
    private ?float $revenue = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['movie:item', 'movie:write'])]
    private array $genres = [];

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['movie:item', 'movie:write'])]
    private ?string $originalLanguage = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['movie:item', 'movie:write'])]
    private array $productionCountries = [];

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2, nullable: true)]
    #[Groups(['movie:item', 'movie:write'])]
    private ?float $budget = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Groups(['movie:item', 'movie:write'])]
    private ?int $runtime = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['movie:item'])]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['movie:item'])]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->genres = [];
        $this->productionCountries = [];
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

    public function getTmdbId(): ?int
    {
        return $this->tmdbId;
    }

    public function setTmdbId(int $tmdbId): static
    {
        $this->tmdbId = $tmdbId;
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getOverview(): ?string
    {
        return $this->overview;
    }

    public function setOverview(?string $overview): static
    {
        $this->overview = $overview;
        return $this;
    }

    public function getPosterPath(): ?string
    {
        return $this->posterPath;
    }

    public function setPosterPath(?string $posterPath): static
    {
        $this->posterPath = $posterPath;
        return $this;
    }

    public function getReleaseDate(): ?\DateTimeInterface
    {
        return $this->releaseDate;
    }

    public function setReleaseDate(?\DateTimeInterface $releaseDate): static
    {
        $this->releaseDate = $releaseDate;
        return $this;
    }

    public function getVoteAverage(): ?float
    {
        return $this->voteAverage;
    }

    public function setVoteAverage(?float $voteAverage): static
    {
        $this->voteAverage = $voteAverage;
        return $this;
    }

    public function getVoteCount(): ?int
    {
        return $this->voteCount;
    }

    public function setVoteCount(?int $voteCount): static
    {
        $this->voteCount = $voteCount;
        return $this;
    }

    public function getRevenue(): ?float
    {
        return $this->revenue;
    }

    public function setRevenue(?float $revenue): static
    {
        $this->revenue = $revenue;
        return $this;
    }

    public function getGenres(): array
    {
        return $this->genres;
    }

    public function setGenres(?array $genres): static
    {
        $this->genres = $genres ?? [];
        return $this;
    }

    public function getOriginalLanguage(): ?string
    {
        return $this->originalLanguage;
    }

    public function setOriginalLanguage(?string $originalLanguage): static
    {
        $this->originalLanguage = $originalLanguage;
        return $this;
    }

    public function getProductionCountries(): array
    {
        return $this->productionCountries;
    }

    public function setProductionCountries(?array $productionCountries): static
    {
        $this->productionCountries = $productionCountries ?? [];
        return $this;
    }

    public function getBudget(): ?float
    {
        return $this->budget;
    }

    public function setBudget(?float $budget): static
    {
        $this->budget = $budget;
        return $this;
    }

    public function getRuntime(): ?int
    {
        return $this->runtime;
    }

    public function setRuntime(?int $runtime): static
    {
        $this->runtime = $runtime;
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
