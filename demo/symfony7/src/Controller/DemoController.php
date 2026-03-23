<?php

declare(strict_types=1);

namespace App\Controller;

use Nowo\ControllerKitBundle\Controller\RedirectToRefererTrait;
use Nowo\ControllerKitBundle\Controller\SafeForwardTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DemoController extends AbstractController
{
    use RedirectToRefererTrait;
    use SafeForwardTrait;

    #[Route(path: '/', name: 'homepage', methods: ['GET'])]
    public function home(): Response
    {
        return $this->render('demo/home.html.twig');
    }

    #[Route(path: '/forward', name: 'demo_forward', methods: ['GET'])]
    public function forwardDemo(): Response
    {
        return $this->safeForward(self::class, 'target');
    }

    #[Route(path: '/target', name: 'demo_target', methods: ['GET'])]
    public function target(): Response
    {
        return new Response('SafeForwardTrait target action reached.');
    }

    #[Route(path: '/back', name: 'demo_back', methods: ['POST'])]
    public function back(Request $request): RedirectResponse
    {
        return $this->redirectToReferer($request, ['from_demo' => 1], 303);
    }
}
