<?php

namespace meta\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class AnnouncementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('text', 'textarea', array('label'  => 'announcement.createForm.text', 'attr' => array( 'placeholder' => 'announcement.createForm.textPlaceholder')));
        $builder->add('type', 'choice', array(
            'choices' => array('info' => "Info", 'warning' => "Warning", 'technical' => "Technical"), 
            'label' => 'announcement.createForm.type',
            'attr' => array('help' => 'announcement.createForm.typeHelp')
            ));

        $builder->add('valid_from', 'date', array('label'  => 'announcement.createForm.valid_from', 'attr' => array( 'placeholder' => 'announcement.createForm.validPlaceholder')));
        $builder->add('valid_until', 'date', array('label'  => 'announcement.createForm.valid_until', 'attr' => array( 'placeholder' => 'announcement.createForm.validPlaceholder')));
        
    }

    public function getName()
    {
        return 'announcement';
    }
}