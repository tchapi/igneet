<?php

namespace meta\GeneralBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class CommunityType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', null, array('label'  => 'community.createForm.name', 'attr' => array( 'class' => 'input-xxlarge', 'placeholder' => 'community.createForm.namePlaceholder')));
        $builder->add('headline', 'text',  array('required' => false, 'label'  => 'community.createForm.headline', 'attr' => array('class' => 'input-xxlarge', 'help' => 'community.createForm.headlinePlaceholder')));
    }

    public function getName()
    {
        return 'community';
    }
}