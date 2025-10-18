<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class FaviconController
{
    public function __invoke(Request $request): RedirectResponse
    {
        // Redirect browsers requesting /favicon.ico to the project's upload image.
        // The file path contains a space; browsers will request the encoded URL.
        return new RedirectResponse('/uploads/bloom%202.png', 301);
    }
}
