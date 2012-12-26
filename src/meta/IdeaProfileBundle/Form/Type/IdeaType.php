<?php

namespace meta\IdeaProfileBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class IdeaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name');
        $builder->add('headline', 'text',  array('required' => false));

    }
    
    public function getName()
    {
        return 'idea';
    }
}