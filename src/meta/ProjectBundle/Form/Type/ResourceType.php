<?php

namespace meta\ProjectBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class ResourceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('title', null, array('label'  => 'Title of this resource', 'attr' => array( 'class' => 'input-xlarge', 'placeholder' => 'My resource')));
        
        $builder->add('url', null, array('label'  => 'Url of a remote file', 'attr' => array( 'class' => 'input-xlarge', 'placeholder' => 'http://...')));
        
        $builder->add('file', 'file', array('required' => false, 'label' => 'or Choose a file to upload'));

    }    

    public function getName()
    {
        return 'resource';
    }
}