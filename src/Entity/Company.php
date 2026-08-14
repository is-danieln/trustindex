<?php

declare(strict_types=1);

namespace App\Entity;

use App\Domain\CompanyNameNormalizer;
use App\Repository\CompanyRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CompanyRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_company_normalized_name', columns: ['normalized_name'])]
class Company
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(length: 255)]
    private string $normalizedName;

    public function __construct(string $name)
    {
        $this->name = CompanyNameNormalizer::displayName($name);
        $this->normalizedName = CompanyNameNormalizer::key($name);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getNormalizedName(): string
    {
        return $this->normalizedName;
    }
}
