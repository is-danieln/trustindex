<?php

declare(strict_types=1);

namespace App\Service;

use App\Domain\CompanyNameNormalizer;
use App\Entity\Company;
use App\Repository\CompanyRepository;
use Doctrine\ORM\EntityManagerInterface;

final class CompanyResolver
{
    /** @var array<string, Company> */
    private array $resolvedCompanies = [];

    public function __construct(
        private readonly CompanyRepository $companyRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function resolve(string $companyName): Company
    {
        $normalizedName = CompanyNameNormalizer::key($companyName);

        if (isset($this->resolvedCompanies[$normalizedName])) {
            return $this->resolvedCompanies[$normalizedName];
        }

        $company = $this->companyRepository->findByNormalizedName($normalizedName);

        if (null === $company) {
            $company = new Company($companyName);
            $this->entityManager->persist($company);
        }

        return $this->resolvedCompanies[$normalizedName] = $company;
    }
}
