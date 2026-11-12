<?php

declare(strict_types=1);

namespace App\Form;

use Nowo\FormKitBundle\Attribute\FormKitConfig;
use Nowo\FormKitBundle\Form\FormOptionsTrait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * REQ-DEMO-015: a FormKit form (profile `demo`) that posts to the RedirectToRefererTrait demo.
 *
 * @extends AbstractType<null>
 */
#[FormKitConfig('demo')]
final class BackForm extends AbstractType
{
    use FormOptionsTrait;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addWithDefaults($builder, 'submit', SubmitType::class, [
            'label' => 'Test RedirectToRefererTrait',
            'attr'  => ['class' => 'nowo-ui-btn nowo-ui-btn--primary'],
        ]);
    }
}
