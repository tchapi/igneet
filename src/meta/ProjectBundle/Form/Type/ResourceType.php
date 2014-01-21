<?php

namespace meta\ProjectBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class ResourceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder->add('file', 'file', array('required' => false, 'label' => 'project.resources.createForm.file'));
        $builder->add('url', null, array('label'  => 'project.resources.createForm.url', 'attr' => array( 'placeholder' => 'http://...')));

    }    

    public function getName()
    {
        return 'resource';
    }
}