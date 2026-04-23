<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api', name: 'api_')]
class HelloController extends AbstractController
{
    #[Route('/hello', name: 'hello', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        return $this->json([
            'message' => 'Hello from Symfony API',
            'project' => 'MyExperiences',
            'basePath' => '/MyExperiences',
            'apiBasePath' => '/MyExperiences/api',
        ]);
    }
}
