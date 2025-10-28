<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SomeController extends AbstractController
{
    #[Route('/some-page', name: 'app_some_page')]
    public function index(): Response
    {
        try {
            throw new \Exception("Test error!");
        } catch (\Exception $e) {
            $this->addFlash('toast', [
                'message' => $e->getMessage(),
                'type' => 'error',
            ]);
        }

        return $this->render('some/index.html.twig');
    }
}