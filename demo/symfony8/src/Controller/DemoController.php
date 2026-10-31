<?php

declare(strict_types=1);

namespace App\Controller;

use Nowo\ControllerKitBundle\Controller\DownloadResponseTrait;
use Nowo\ControllerKitBundle\Controller\RedirectToRefererTrait;
use Nowo\ControllerKitBundle\Controller\SafeForwardTrait;
use Nowo\ControllerKitBundle\Http\CsvStreamedResponse;
use Nowo\ControllerKitBundle\Http\JsonAttachmentResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DemoController extends AbstractController
{
    use DownloadResponseTrait;
    use RedirectToRefererTrait;
    use SafeForwardTrait;

    #[Route(path: '/', name: 'homepage', methods: ['GET'])]
    public function home(): Response
    {
        // A Symfony form (not raw HTML) posts to the RedirectToRefererTrait demo (REQ-TWIG-005).
        $backForm = $this->createFormBuilder(null, [
            'action' => $this->generateUrl('demo_back'),
            'method' => 'POST',
        ])
            ->add('submit', SubmitType::class, ['label' => 'Test RedirectToRefererTrait'])
            ->getForm();

        return $this->render('demo/home.html.twig', ['back_form' => $backForm->createView()]);
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

    /** DownloadResponseTrait: the formula cells come out prefixed with a quote (open the file in a spreadsheet). */
    #[Route(path: '/download.csv', name: 'demo_download_csv', methods: ['GET'])]
    public function downloadCsv(): CsvStreamedResponse
    {
        return $this->csvDownload('demo export.csv', static function (): iterable {
            yield ['Plain text', 'safe', 42];
            yield ['Formula', '=1+1', -3];
            yield ['DDE payload', "=cmd|' /C calc'!A0", 1.5];
            yield ['Mention', '@everyone', null];
        }, ['label', 'value', 'number']);
    }

    #[Route(path: '/download.json', name: 'demo_download_json', methods: ['GET'])]
    public function downloadJson(): JsonAttachmentResponse
    {
        return $this->jsonDownload(['bundle' => 'nowo-tech/controller-kit-bundle', 'items' => [1, 2, 3]], 'demo-export.json', noStore: true);
    }
}
