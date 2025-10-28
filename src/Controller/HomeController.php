<?php
// src/Controller/HomeController.php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    private $session;

    public function __construct(
        private UserRepository $users,
        RequestStack $requestStack
    ) {
        $this->session = $requestStack->getSession();
    }


    #[Route('/', name: 'app_home')]  // ← FIXED
    public function landing(): Response
    {
        if ($this->session->get('ticketapp_session')) {
            return $this->redirectToRoute('dashboard');
        }
        return $this->render('landing.html.twig');
    }

    #[Route('/home', name: 'home')]
    public function home(): Response
    {
        return $this->redirectToRoute('dashboard');
    }

    #[Route('/auth/login', name: 'auth_login', methods: ['GET'])]
    public function showLogin(): Response
    {
        $this->addFlash('toast', [
            'message' => 'Welcome back!',
            'type' => 'success'
        ]);
        return $this->render('auth/login.html.twig');
    }

    #[Route('/auth/login', name: 'auth_login_post', methods: ['POST'])]
    public function login(Request $req): Response
    {
        $email = $req->request->get('email', '');
        $pass = $req->request->get('password', '');
        $user = $this->users->findByEmail($email);

        if (!$user || !password_verify($pass, $user['password'])) {
            $this->addFlash('toast', [
                'message' => 'Invalid credentials',
                'type' => 'error'
            ]);
            return $this->redirectToRoute('auth_login');
        }

        $sessionData = [
            'token' => bin2hex(random_bytes(16)),
            'user' => ['id' => $user['id'], 'name' => $user['name'], 'email' => $user['email']],
            'expiresAt' => date(DATE_ATOM, time() + 86400)
        ];

        $this->session->set('ticketapp_session', $sessionData);
        $this->addFlash('toast', [
            'message' => 'Welcome back, ' . $user['name'] . '!',
            'type' => 'success'
        ]);
        return $this->redirectToRoute('dashboard');
    }

    #[Route('/auth/signup', name: 'auth_signup', methods: ['GET'])]
    public function showSignup(): Response
    {
        return $this->render('auth/signup.html.twig');
    }

    #[Route('/auth/signup', name: 'auth_signup_post', methods: ['POST'])]
    public function signup(Request $req): Response
    {
        $name = trim($req->request->get('name', ''));
        $email = trim($req->request->get('email', ''));
        $password = $req->request->get('password', '');

        if (!$name || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {
            $this->addFlash('toast', ['message' => 'Please provide valid details (password 6+ chars).', 'type' => 'error']);
            return $this->redirectToRoute('auth_signup');
        }

        if ($this->users->findByEmail($email)) {
            $this->addFlash('toast', ['message' => 'Email already in use.', 'type' => 'error']);
            return $this->redirectToRoute('auth_signup');
        }

        $id = $this->users->create($name, $email, $password);

        $sessionData = [
            'token' => bin2hex(random_bytes(16)),
            'user' => ['id' => $id, 'name' => $name, 'email' => $email],
            'expiresAt' => date(DATE_ATOM, time() + 86400)
        ];

        $this->session->set('ticketapp_session', $sessionData);
        return $this->redirectToRoute('dashboard');
    }

    #[Route('/auth/logout', name: 'auth_logout')]
    public function logout(): Response
    {
        $this->session->remove('ticketapp_session');
        $this->session->invalidate();
        $this->addFlash('toast', [
            'message' => 'Logged out!',
            'type' => 'success'
        ]);

        return $this->redirectToRoute('app_home'); // ← ALSO UPDATE HERE
    }


}