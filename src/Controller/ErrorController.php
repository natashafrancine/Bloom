<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\ErrorHandler\ErrorRenderer\HtmlErrorRenderer;

/**
 * Minimal error controller used by framework.error_controller
 * This avoids cascading exceptions when Symfony tries to render an exception
 * and no app controller exists. In dev this will render a simple HTML
 * representation; in prod it returns a plain message. It's intentionally
 * small and low-risk.
 */
class ErrorController
{
    public function show(Request $request, ?\Throwable $exception = null, int $status = 500): Response
    {
        // If no exception provided, create a generic one
        if (null === $exception) {
            $message = Response::$statusTexts[$status] ?? 'An error occurred';
            return new Response($message, $status);
        }

        // Try to determine status code from exception if available
        if ($exception instanceof HttpExceptionInterface) {
            $status = $exception->getStatusCode();
        }

        // In dev environment the HtmlErrorRenderer will give a readable page if available
        if (class_exists(HtmlErrorRenderer::class)) {
            try {
                $renderer = new HtmlErrorRenderer(true);
                $flatten = $renderer->render($exception);
                $content = $renderer->getBody($flatten);
                return new Response($content, $status);
            } catch (\Throwable $e) {
                // fall through to plain text below
            }
        }

        // Fallback: return a simple plain-text response
        $body = sprintf("Error %d: %s", $status, $exception->getMessage());
        return new Response($body, $status);
    }
}
