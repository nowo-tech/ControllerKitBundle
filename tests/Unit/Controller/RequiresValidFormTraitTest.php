<?php

declare(strict_types=1);

namespace Nowo\ControllerKitBundle\Tests\Unit\Controller;

use Nowo\ControllerKitBundle\Controller\RequiresValidFormTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Csrf\CsrfExtension;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class RequiresValidFormTraitTest extends TestCase
{
    private RequiresValidFormTestController $controller;

    protected function setUp(): void
    {
        $this->controller = new RequiresValidFormTestController();
    }

    public function testRequireValidFormPassesWhenSubmittedAndValid(): void
    {
        $form = $this->createForm();
        $form->submit(['role' => 'admin', '_token' => 't']);

        $this->controller->runRequireValidForm($form);

        $this->addToAssertionCount(1);
    }

    public function testRequireValidFormDeniesWhenNotSubmitted(): void
    {
        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Invalid form submission.');

        $this->controller->runRequireValidForm($this->createForm());
    }

    public function testRequireValidFormDeniesWithCustomMessageWhenInvalid(): void
    {
        $form = $this->createForm();
        $form->submit(['role' => 'admin', '_token' => 't']);
        $form->addError(new FormError('bad'));

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Nope.');

        $this->controller->runRequireValidForm($form, 'Nope.');
    }

    public function testRequireValidCsrfFormDeniesWithCsrfMessage(): void
    {
        $form = $this->createForm();
        $form->submit(['role' => 'admin', '_token' => 'x']);
        $form->get('_token')->addError(new FormError('bad token'));

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Invalid CSRF token.');

        $this->controller->runRequireValidCsrfForm($form);
    }

    public function testRequireValidCsrfFormPassesWhenValid(): void
    {
        $form = $this->createForm();
        $form->submit(['role' => 'admin', '_token' => 't']);

        $this->controller->runRequireValidCsrfForm($form);

        $this->addToAssertionCount(1);
    }

    public function testAcceptSubmittedFormDeniesWhenNotSubmitted(): void
    {
        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Invalid form submission.');

        $this->controller->runAcceptSubmittedForm($this->createForm(), 'role', 'flash.invalid_role');
    }

    public function testAcceptSubmittedFormReturnsTrueWhenValid(): void
    {
        $form = $this->createForm();
        $form->submit(['role' => 'admin', '_token' => 't']);

        self::assertTrue($this->controller->runAcceptSubmittedForm($form, 'role', 'flash.invalid_role'));
        self::assertSame([], $this->controller->flashes);
    }

    public function testAcceptSubmittedFormDeniesWhenCsrfTokenInvalid(): void
    {
        $form = $this->createForm();
        $form->submit(['role' => 'bogus', '_token' => 'x']);
        $form->get('_token')->addError(new FormError('bad token'));
        $form->get('role')->addError(new FormError('bad role'));

        try {
            $this->controller->runAcceptSubmittedForm($form, 'role', 'flash.invalid_role');
            self::fail('AccessDeniedException expected.');
        } catch (AccessDeniedException $e) {
            self::assertSame('Invalid CSRF token.', $e->getMessage());
        }
        self::assertSame([], $this->controller->flashes);
    }

    public function testAcceptSubmittedFormFlashesAndReturnsFalseWhenOnlyFieldInvalid(): void
    {
        $form = $this->createForm();
        $form->submit(['role' => 'bogus', '_token' => 't']);
        $form->get('role')->addError(new FormError('bad role'));

        self::assertFalse($this->controller->runAcceptSubmittedForm($form, 'role', 'flash.invalid_role'));
        self::assertSame([['error', 'flash.invalid_role']], $this->controller->flashes);
    }

    public function testAcceptSubmittedFormDeniesOnOtherErrors(): void
    {
        $form = $this->createForm();
        $form->submit(['role' => 'admin', '_token' => 't', 'note' => 'x']);
        $form->get('note')->addError(new FormError('bad note'));

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Invalid form submission.');

        $this->controller->runAcceptSubmittedForm($form, 'role', 'flash.invalid_role');
    }

    public function testAcceptSubmittedFormDeniesWhenFieldMissingAndFormInvalid(): void
    {
        $form = $this->createForm();
        $form->submit(['role' => 'admin', '_token' => 't']);
        $form->addError(new FormError('root error'));

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Invalid form submission.');

        $this->controller->runAcceptSubmittedForm($form, 'missing', 'flash.invalid_role');
    }

    public function testAcceptSubmittedFormDeniesOnRealCsrfExtensionFailure(): void
    {
        $tokenManager = $this->createMock(CsrfTokenManagerInterface::class);
        $tokenManager->method('isTokenValid')->willReturn(false);

        $form = Forms::createFormFactoryBuilder()
            ->addExtension(new CsrfExtension($tokenManager))
            ->getFormFactory()
            ->createNamedBuilder('member', FormType::class, null, ['csrf_protection' => true, 'csrf_token_id' => 'member'])
            ->add('role', TextType::class)
            ->getForm();
        $form->submit(['role' => 'admin', '_token' => 'forged']);

        self::assertFalse($form->has('_token'));

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Invalid CSRF token.');

        $this->controller->runAcceptSubmittedForm($form, 'role', 'flash.invalid_role');
    }

    public function testAcceptSubmittedFormDeniesWhenFieldAndAnotherFieldInvalid(): void
    {
        $form = $this->createForm();
        $form->submit(['role' => 'bogus', '_token' => 't', 'note' => 'x']);
        $form->get('role')->addError(new FormError('bad role'));
        $form->get('note')->addError(new FormError('bad note'));

        try {
            $this->controller->runAcceptSubmittedForm($form, 'role', 'flash.invalid_role');
            self::fail('AccessDeniedException expected.');
        } catch (AccessDeniedException $e) {
            self::assertSame('Invalid form submission.', $e->getMessage());
        }
        self::assertSame([], $this->controller->flashes);
    }

    public function testAcceptSubmittedFormUsesCustomCsrfFieldName(): void
    {
        $form = Forms::createFormFactory()->createNamedBuilder('member', FormType::class)
            ->add('role', TextType::class)
            ->add('_csrf', HiddenType::class)
            ->getForm();
        $form->submit(['role' => 'admin', '_csrf' => 'x']);
        $form->get('_csrf')->addError(new FormError('bad token'));
        $form->get('role')->addError(new FormError('bad role'));

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Invalid CSRF token.');

        $this->controller->runAcceptSubmittedForm($form, 'role', 'flash.invalid_role', '_csrf');
    }

    /**
     * @return FormInterface<mixed>
     */
    private function createForm(): FormInterface
    {
        return Forms::createFormFactory()->createNamedBuilder('member', FormType::class)
            ->add('role', TextType::class)
            ->add('note', TextType::class, ['required' => false])
            ->add('_token', HiddenType::class)
            ->getForm();
    }
}

/**
 * AbstractController test double exposing RequiresValidFormTrait helpers; records flashes.
 *
 * @internal
 */
class RequiresValidFormTestController extends AbstractController
{
    use RequiresValidFormTrait;

    /** @var list<array{0: string, 1: mixed}> */
    public array $flashes = [];

    /**
     * @param FormInterface<mixed> $form
     */
    public function runRequireValidForm(FormInterface $form, string $message = 'Invalid form submission.'): void
    {
        $this->requireValidForm($form, $message);
    }

    /**
     * @param FormInterface<mixed> $form
     */
    public function runRequireValidCsrfForm(FormInterface $form): void
    {
        $this->requireValidCsrfForm($form);
    }

    /**
     * @param FormInterface<mixed> $form
     */
    public function runAcceptSubmittedForm(FormInterface $form, string $field, string $flash, string $csrfField = '_token'): bool
    {
        return $this->acceptSubmittedForm($form, $field, $flash, $csrfField);
    }

    protected function addFlash(string $type, mixed $message): void
    {
        $this->flashes[] = [$type, $message];
    }
}
