<?php

namespace meta\IdeaProfileBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class IdeaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', null, array('label'  => 'Name of this idea', 'attr' => array( 'class' => 'input-xxlarge', 'placeholder' => 'My new idea')));
        $builder->add('headline', 'text',  array('required' => false, 'label'  => 'Headline', 'attr' => array('class' => 'input-xxlarge', 'help' => 'Give your idea some nice catchline')));

    }
    
    public function getName()
    {
        return 'idea';
    }
}