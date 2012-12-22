<?php

namespace meta\StandardProjectProfileBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class StandardProjectType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name');
        $builder->add('slug');
        $builder->add('headline', 'text',  array('required' => false));
        
        $builder->add('neededSkills', 'entity', array(
            'multiple' => true, 
            'required' => false, 
            'property' => 'name',
            'class' => 'meta\UserProfileBundle\Entity\Skill'
            ));
    }
    
    public function getName()
    {
        return 'standardProject';
    }
}