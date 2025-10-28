<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ErrorController extends AbstractController
{
    public function show(\Throwable $exception, Request $request): Response
    {
        $statusCode = $exception instanceof HttpExceptionInterface
            ? $exception->getStatusCode()
            : 500;

        $message = match ($statusCode) {
            404 => 'The page you are looking for does not exist.',
            403 => 'You do not have permission to access this page.',
            500 => 'Internal server error. Please try again later.',
            default => 'Something went wrong. Please try again later.',
        };

        // Render error page (don't redirect from error handler)
        return $this->render('bundles/TwigBundle/Exception/error.html.twig', [
            'status_code' => $statusCode,
            'status_text' => Response::$statusTexts[$statusCode] ?? 'Error',
            'message' => $message,
            'exception' => $exception,
        ]);
    }
}