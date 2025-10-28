<?php
// src/Controller/TicketController.php

namespace App\Controller;

use App\Repository\TicketRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;  // ← Use this
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TicketController extends AbstractController
{
    public function __construct(
        private readonly TicketRepository $tickets,
        private readonly RequestStack $requestStack  // ← Fixed
    ) {
    }

    private function getSession()
    {
        return $this->requestStack->getSession();
    }

    private function requireAuth(): void
    {
        $user = $this->getSession()->get('ticketapp_session');
        if (!$user) {
            $this->addFlash('toast', [
                'message' => 'You must be logged in.',
                'type' => 'error'
            ]);
            throw $this->createAccessDeniedException('Unauthorized');
        }
    }

    #[Route('/tickets', name: 'tickets_index', methods: ['GET'])]
    public function index(): Response
    {
        $this->requireAuth();

        $tickets = $this->tickets->all();
        $session = $this->getSession()->get('ticketapp_session');

        return $this->render('tickets/index.html.twig', [
            'tickets' => $tickets,
            'session' => $session,
        ]);
    }

    #[Route('/tickets/create', name: 'tickets_create_form', methods: ['GET'])]
    public function showCreateForm(): Response
    {
        $this->requireAuth();
        return $this->render('tickets/form.html.twig', [
            'action' => $this->generateUrl('tickets_create'),
            'session' => $this->getSession()->get('ticketapp_session'),
        ]);
    }

    #[Route('/tickets/create', name: 'tickets_create', methods: ['POST'])]
    #[Route('/tickets/create', name: 'tickets_create', methods: ['POST'])]
    public function create(Request $req): Response
    {
        $this->requireAuth();

        $data = $this->validateTicket($req->request->all());
        if (isset($data['errors'])) {
            $this->addFlash('toast', [
                'message' => implode(' ', $data['errors']),
                'type' => 'error'
            ]);
            return $this->redirectToRoute('tickets_create_form');
        }

        $sessionUser = $this->session->get('ticketapp_session')['user'] ?? null;
        $data['reporter_id'] = $sessionUser['id'] ?? null;

        $this->tickets->create($data);

        $this->addFlash('toast', [
            'message' => 'Ticket created successfully! 🎉',
            'type' => 'success'
        ]);

        return $this->redirectToRoute('tickets_index');
    }

    #[Route('/tickets/edit/{id}', name: 'tickets_edit_form', methods: ['GET'])]
    public function editForm(int $id): Response
    {
        $this->requireAuth();

        $ticket = $this->tickets->find($id);
        if (!$ticket) {
            $this->addFlash('toast', ['message' => 'Ticket not found.', 'type' => 'error']);
            return $this->redirectToRoute('tickets_index');
        }

        return $this->render('tickets/form.html.twig', [
            'ticket' => $ticket,
            'action' => $this->generateUrl('tickets_edit', ['id' => $id]),
            'session' => $this->getSession()->get('ticketapp_session'),
        ]);
    }

    #[Route('/tickets/edit/{id}', name: 'tickets_edit', methods: ['POST'])]
    public function edit(int $id, Request $req): Response
    {
        $this->requireAuth();

        $data = $this->validateTicket($req->request->all());
        if (isset($data['errors'])) {
            $this->addFlash('toast', [
                'message' => implode(' ', $data['errors']),
                'type' => 'error'
            ]);
            return $this->redirectToRoute('tickets_edit_form', ['id' => $id]);
        }

        $this->tickets->update($id, $data);

        $this->addFlash('toast', [
            'message' => 'Ticket updated successfully! ✅',
            'type' => 'success'
        ]);

        return $this->redirectToRoute('tickets_index');
    }

    #[Route('/tickets/delete/{id}', name: 'tickets_delete', methods: ['POST'])]
    public function delete(int $id): Response
    {
        $this->requireAuth();

        $this->tickets->delete($id);

        $this->addFlash('toast', [
            'message' => 'Ticket deleted successfully',
            'type' => 'success'
        ]);

        return $this->redirectToRoute('tickets_index');
    }

    private function validateTicket(array $input): array
    {
        $errors = [];
        $title = trim($input['title'] ?? '');
        $status = $input['status'] ?? '';
        $description = trim($input['description'] ?? '');
        $priority = $input['priority'] ?? null;

        if ($title === '')
            $errors[] = 'Title is required.';
        elseif (strlen($title) < 3)
            $errors[] = 'Title too short.';
        if (!in_array($status, ['open', 'in_progress', 'closed']))
            $errors[] = 'Invalid status.';
        if ($description !== '' && strlen($description) > 2000)
            $errors[] = 'Description too long.';
        if ($priority && !in_array($priority, ['low', 'medium', 'high']))
            $errors[] = 'Invalid priority.';

        return $errors ? ['errors' => $errors] : compact('title', 'status', 'description', 'priority');
    }

}