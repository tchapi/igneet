<?php

namespace meta\UserProfileBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('username');
        $builder->add('first_name');
        $builder->add('last_name');
        $builder->add('password', 'password');
        $builder->add('email', 'email');
        $builder->add('city', 'text', array('required' => false));

        $builder->add('file', 'file', array('required' => false));

        $builder->add('headline', 'text',  array('required' => false));
        $builder->add('about', 'textarea', array('required' => false));

        $builder->add('skills', 'entity', array(
            'multiple' => true, 
            'required' => false, 
            'property' => 'name',
            'class' => 'meta\UserProfileBundle\Entity\Skill'
            ));
    }
    
    public function getName()
    {
        return 'user';
    }
}