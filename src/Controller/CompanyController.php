<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Company;
use App\Repository\ReviewRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CompanyController extends AbstractController
{
    #[Route('/companies', name: 'app_company_index', methods: ['GET'])]
    public function index(Request $request, ReviewRepository $reviewRepository): Response
    {
        $query = trim((string) $request->query->get('q', ''));

        return $this->render('company/index.html.twig', [
            'companies' => $reviewRepository->findCompanyStatistics($query),
            'query' => $query,
        ]);
    }

    #[Route('/companies/{id}', name: 'app_company_show', requirements: ['id' => '\\d+'], methods: ['GET'])]
    public function show(Company $company, ReviewRepository $reviewRepository): Response
    {
        $statistics = $reviewRepository->findCompanyStatisticsByCompany($company);

        if (null === $statistics) {
            throw $this->createNotFoundException('A cég nem található.');
        }

        return $this->render('company/show.html.twig', [
            'company' => $statistics,
            'reviews' => $reviewRepository->findLatestByCompany($company),
        ]);
    }
}
