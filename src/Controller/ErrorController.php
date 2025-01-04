<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;

class ErrorController
{
    #[Route('/{route}', name: 'error', requirements: ['route' => '.*'], defaults: ['route' => null])]
    public function index(): Response
    {
        return new Response(file_get_contents('../public/index.html'));
    }

    public function show(\Throwable $exception): Response
    {
        if ($exception instanceof HttpExceptionInterface && $exception->getStatusCode() === 404) {
            return new Response(file_get_contents('../public/index.html'), Response::HTTP_OK);
        }

        return new Response('An error occurred.', Response::HTTP_INTERNAL_SERVER_ERROR);
    }


}