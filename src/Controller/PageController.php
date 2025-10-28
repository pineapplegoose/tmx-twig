<?php
// src/Controller/PageController.php

namespace App\Controller;

use App\Repository\TicketRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PageController extends AbstractController
{
    public function __construct(
        private readonly TicketRepository $tickets,
        private readonly RequestStack $requestStack
    ) {
    }

    #[Route('/', name: 'landing')]
    public function landing(): Response
    {
        return $this->render('landing.html.twig');
    }

    #[Route('/dashboard', name: 'dashboard')]
    public function dashboard(): Response
    {
        $session = $this->requestStack->getSession()->get('ticketapp_session');

        if (!$session) {
            $this->addFlash('toast', [
                'message' => 'You must be logged in to access the dashboard.',
                'type' => 'error'
            ]);
            return $this->redirectToRoute('auth_login');
        }

        $tickets = $this->tickets->all();

        $open = 0;
        $closed = 0;
        foreach ($tickets as $ticket) {
            $status = is_array($ticket) ? ($ticket['status'] ?? '') : ($ticket->status ?? '');
            if ($status === 'open')
                $open++;
            if ($status === 'closed')
                $closed++;
        }

        return $this->render('dashboard.html.twig', [
            'tickets' => $tickets,
            'total' => count($tickets),
            'open' => $open,
            'closed' => $closed,
            'session' => $session,
        ]);
    }
}