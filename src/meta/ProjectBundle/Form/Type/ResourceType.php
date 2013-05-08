<?php

namespace meta\ProjectBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class ResourceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('title', null, array('label'  => 'project.resources.createForm.title', 'attr' => array( 'class' => 'input-xlarge', 'placeholder' => 'project.resources.createForm.titlePlaceholder')));
        $builder->add('url', null, array('label'  => 'project.resources.createForm.url', 'attr' => array( 'class' => 'input-xlarge', 'placeholder' => 'http://...')));
        $builder->add('file', 'file', array('required' => false, 'label' => 'project.resources.createForm.file'));
    }    

    public function getName()
    {
        return 'resource';
    }
}