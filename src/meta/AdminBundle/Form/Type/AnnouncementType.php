<?php

namespace meta\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class AnnouncementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('text', null, array('label'  => 'announcement.createForm.text', 'attr' => array( 'placeholder' => 'announcement.createForm.textPlaceholder')));
        $builder->add('type', null, array(
            'data' => array('info', 'warning', 'technical'), 
            'required' => true, 
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