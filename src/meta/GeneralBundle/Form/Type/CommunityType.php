<?php

namespace meta\GeneralBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/* SYMFONY 2.8
use Symfony\Component\Form\Extension\Core\Type\TextType;
*/

class CommunityType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', null, array('label'  => 'community.createForm.name', 'attr' => array( 'autofocus' => "autofocus", 'placeholder' => 'community.createForm.namePlaceholder')));
        $builder->add('headline', 'textarea'/* SYMFONY 2.8 TextType::class*/,  array('required' => false, 'label'  => 'community.createForm.headline', 'attr' => array( 'help' => 'community.createForm.headlinePlaceholder')));
    }

    public function getName()
    {
       return $this->getBlockPrefix();
    }

    public function getBlockPrefix()
    {
        return 'community';
    }
}