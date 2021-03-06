<?php

declare(strict_types=1);

/**
 * Dizkus
 *
 * @copyright (c) 2001-now, Dizkus Development Team
 *
 * @see https://github.com/zikula-modules/Dizkus
 *
 * @license GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 */

namespace Zikula\DizkusModule\Form\Type\Topic;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EditTopicType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('title', TextType::class, [
                    'required' => true,
                ])
                ->add('locked', CheckboxType::class, [
                    'required' => false,
                    ])
                ->add('sticky', CheckboxType::class, [
                    'required' => false,
                    ]);

        if ($options['settings']['solved_enabled']) {
            $builder->add('solved', CheckboxType::class, [
                'required' => false,
                'data' => false,
                ]);
        }

        if ($options['settings']['topic_subscriptions_enabled']) {
            $builder->add('subscribe', CheckboxType::class, [
                'required' => false,
                'data'     => $options['loggedIn'],
                'disabled' => !$options['loggedIn'],
                ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'zikula_dizkus_form_topic_edit';
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'translator' => null,
            'loggedIn' => false,
            'settings' => false
        ]);
    }
}
