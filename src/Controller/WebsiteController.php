<?php

namespace App\Controller;

use App\Service\DemoCatalog;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class WebsiteController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function home(DemoCatalog $catalog): Response
    {
        return $this->render('home/index.html.twig', ['courses' => $catalog->featured(), 'active' => 'home']);
    }

    #[Route('/formations', name: 'app_catalog', methods: ['GET'])]
    public function catalog(Request $request, DemoCatalog $catalog): Response
    {
        $filters = [];
        foreach (['q', 'domaine', 'programme', 'modalite'] as $key) {
            $value = $request->query->all()[$key] ?? '';
            $filters[$key] = is_string($value) ? mb_substr($value, 0, 200) : '';
        }
        return $this->render('formation/catalog.html.twig', [
            'active' => 'catalog', 'courses' => $catalog->filter($filters), 'filters' => $filters,
            'domains' => DemoCatalog::DOMAINS, 'programs' => DemoCatalog::PROGRAMS,
            'modalities' => DemoCatalog::MODALITIES,
        ]);
    }

    #[Route('/formations/{slug}', name: 'app_course', requirements: ['slug' => '[a-z0-9-]+'], methods: ['GET'])]
    public function course(string $slug, DemoCatalog $catalog): Response
    {
        $course = $catalog->find($slug);
        if ($course === null) {
            throw $this->createNotFoundException('Cette formation n’existe pas.');
        }
        return $this->render('formation/detail.html.twig', ['active' => 'catalog', 'course' => $course]);
    }

    #[Route('/etablissements', name: 'app_establishments', methods: ['GET'])]
    public function establishments(): Response
    {
        return $this->render('establishment/index.html.twig', ['active' => 'establishments']);
    }

    #[Route('/demo/{role}', name: 'app_demo', requirements: ['role' => 'apprenant|formateur|etablissement'], methods: ['GET'])]
    public function demo(string $role, DemoCatalog $catalog): Response
    {
        $response = $this->render('demo/'.$role.'.html.twig', [
            'active' => 'demo', 'role' => $role, 'courses' => $catalog->featured(),
        ]);
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        $response->headers->set('Cache-Control', 'no-store');
        return $response;
    }

    #[Route('/_app/health', name: 'app_health', methods: ['GET'])]
    public function health(): Response
    {
        return $this->json(['status' => 'ok', 'framework' => 'Symfony', 'mode' => 'template']);
    }
}
