<?php

namespace meta\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('username', null, array('label' => 'user.createForm.username', 'attr' => array('class' => 'input-xxlarge', 'help' => 'user.createForm.usernameHelp')));
        $builder->add('first_name', null, array('label' => 'user.createForm.firstname', 'attr' => array('class' => 'input-xxlarge')));
        $builder->add('last_name', null, array('label' => 'user.createForm.lastname', 'attr' => array('class' => 'input-xxlarge')));
        $builder->add('email', 'email', array('label' => 'user.createForm.email', 'attr' => array('class' => 'input-xxlarge')));
        $builder->add('password', 'password', array( 'label' => 'user.createForm.password', 'attr' => array('class' => 'input-xxlarge')));
        $builder->add('city', 'text', array('label' => 'user.createForm.city', 'attr' => array('class' => 'input-xxlarge')));

        $builder->add('headline', 'text',  array('label' => 'user.createForm.headline', 'required' => false, 'attr' => array('class' => 'input-xxlarge', 'help' => 'user.createForm.headlineHelp' )));
        
        $builder->add('skills', 'entity', array(
            'label'  => 'user.createForm.skills', 
            'multiple' => true, 
            'required' => false, 
            'property' => 'slug',
            'class' => 'meta\UserBundle\Entity\Skill',
            'attr' => array('class' => 'select2-trigger')
            ));
    }
    
    public function getName()
    {
        return 'user';
    }
}