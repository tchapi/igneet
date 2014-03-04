<?php

namespace meta\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class CommunityType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('type', 'choice', array(
            'choices' => array('demo' => "demo", 'association' => "association", 'entreprise' => "entreprise"), 
            'label' => 'community.createForm.type',
            'attr' => array('help' => 'community.createForm.typeHelp')
            ));

        $builder->add('valid_until', 'date', array('label'  => 'community.createForm.valid_until', 'attr' => array( 'placeholder' => 'community.createForm.validPlaceholder')));
     
    }

    public function getName()
    {
        return 'community';
    }
}