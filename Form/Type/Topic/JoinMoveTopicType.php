<?php

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

use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class JoinMoveTopicType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->options = $options;
        //dump($options);
        $builder
            ->add('forum', EntityType::class, [
                'class' => 'Zikula\DizkusModule\Entity\ForumEntity',
                'query_builder' => function (EntityRepository $er) {
                    $forums =$er->createQueryBuilder('f')
                        ->where('f.lvl != 0')
                        ->orderBy('f.root', 'ASC')
                        ->addOrderBy('f.lft', 'ASC');
                        return $forums;
                },
                'choice_label' => function ($forum) {
                    return ($forum->getId() == $this->options['forum']) ? str_repeat("--", $forum->getLvl()) . ' ' . $forum->getName() . ' ' .  $this->options['translator']->__('current') : str_repeat("--", $forum->getLvl()) . ' ' . $forum->getName();
                },
                'multiple'      => false,
                'expanded'      => false,
                'choice_attr'   => function ($forum) {
                    return $forum->getId() == $this->options['forum'] ? ['disabled' => 'disabled'] : [];
                }
            ])
            ->add('to_topic_id', IntegerType::class, [
                'required' => false,
                'mapped' => false
            ])
            ->add('createshadowtopic', ChoiceType::class, [
                'choices'   => ['0' => 'Off', '1' => 'On'],
                'multiple'  => false,
                'expanded'  => true,
                'required'  => true,
                'mapped'    => false,
                'data'      => 0,
            ]);
        if ($options['addReason']) {
            $builder->add('reason', TextareaType::class, [
                'mapped' => false,
                'required' =>false,
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'zikula_dizkus_form_topic_joinmove';
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'forum'      => null,
            'forums'     => null,
            'translator' => null,
            'addReason' => false,
            'settings' => null
        ]);
    }
}
