<?php

declare(strict_types=1);

namespace Nowo\ControllerKitBundle\Controller;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Csrf\CsrfToken;

/**
 * Trait that denies access (HTTP 403) when a submitted form is missing or invalid.
 *
 * Meant for controllers extending AbstractController (uses createAccessDeniedException()
 * and addFlash()). Controllers must still call handleRequest() or submit() before these helpers.
 * Requires symfony/form and symfony/security-core in the application (suggested, not required,
 * by this bundle). CSRF detection works with symfony/security-csrf when installed.
 *
 * Stateless and FrankenPHP worker-safe (including when the kernel is not reset between
 * requests): no properties, statics, or request data retained on the controller.
 *
 * @method AccessDeniedException createAccessDeniedException(string $message = 'Access Denied.', ?\Throwable $previous = null)
 * @method void addFlash(string $type, mixed $message)
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2026 Nowo.tech
 */
trait RequiresValidFormTrait
{
    /**
     * Denies access unless the form was submitted and is valid.
     *
     * @param FormInterface<mixed> $form Form after handleRequest()/submit()
     * @param string $message Message of the AccessDeniedException
     *
     * @throws AccessDeniedException when the form is not submitted or invalid
     */
    protected function requireValidForm(FormInterface $form, string $message = 'Invalid form submission.'): void
    {
        if (!$form->isSubmitted() || !$form->isValid()) {
            throw $this->createAccessDeniedException($message);
        }
    }

    /**
     * Denies access with "Invalid CSRF token." unless the form was submitted and is valid.
     *
     * Use for CSRF-only (action) forms where any validation error means a bad token.
     *
     * @param FormInterface<mixed> $form Form after handleRequest()/submit()
     *
     * @throws AccessDeniedException when the form is not submitted or invalid
     */
    protected function requireValidCsrfForm(FormInterface $form): void
    {
        $this->requireValidForm($form, 'Invalid CSRF token.');
    }

    /**
     * Accepts a submitted form, or flashes an error when only one user-facing field is invalid.
     *
     * - not submitted: 403 ("Invalid form submission.");
     * - valid: returns true;
     * - CSRF failure: 403 ("Invalid CSRF token."). Detected from the error added by Symfony's
     *   CsrfValidationListener (cause is a CsrfToken) or from any error originating in the
     *   $csrfField child (default `_token`);
     * - every error originates in the named $field (or its children): adds an `error` flash with
     *   $invalidFieldFlash and returns false (e.g. a tampered ChoiceType value), so the caller can redirect;
     * - any other error (root errors, other fields, missing $field): 403 ("Invalid form submission.").
     *
     * @param FormInterface<mixed> $form Form after handleRequest()/submit()
     * @param string $field Name of the child whose errors become a flash instead of a 403
     * @param string $invalidFieldFlash Flash message (or translation key) for an invalid $field
     * @param string $csrfField Name of the CSRF token child (framework.form.csrf_protection.field_name)
     *
     * @throws AccessDeniedException when the form is not submitted, the CSRF token is invalid, or another error exists
     *
     * @return bool true when the form is valid; false when only $field is invalid (flash already added)
     */
    protected function acceptSubmittedForm(
        FormInterface $form,
        string $field,
        string $invalidFieldFlash,
        string $csrfField = '_token',
    ): bool {
        if (!$form->isSubmitted()) {
            throw $this->createAccessDeniedException('Invalid form submission.');
        }

        if ($form->isValid()) {
            return true;
        }

        $fieldForm      = $form->has($field) ? $form->get($field) : null;
        $csrfForm       = $form->has($csrfField) ? $form->get($csrfField) : null;
        $onlyFieldError = $fieldForm instanceof FormInterface;

        foreach ($form->getErrors(true) as $error) {
            $origin = $error->getOrigin();
            if ($error->getCause() instanceof CsrfToken || ($csrfForm instanceof FormInterface && $this->isFormOrDescendant($origin, $csrfForm))) {
                throw $this->createAccessDeniedException('Invalid CSRF token.');
            }
            if (!$fieldForm instanceof FormInterface || !$this->isFormOrDescendant($origin, $fieldForm)) {
                $onlyFieldError = false;
            }
        }

        if ($onlyFieldError) {
            $this->addFlash('error', $invalidFieldFlash);

            return false;
        }

        throw $this->createAccessDeniedException('Invalid form submission.');
    }

    /**
     * @param FormInterface<mixed>|null $candidate
     * @param FormInterface<mixed> $ancestor
     */
    private function isFormOrDescendant(?FormInterface $candidate, FormInterface $ancestor): bool
    {
        for ($current = $candidate; $current instanceof FormInterface; $current = $current->getParent()) {
            if ($current === $ancestor) {
                return true;
            }
        }

        return false;
    }
}
