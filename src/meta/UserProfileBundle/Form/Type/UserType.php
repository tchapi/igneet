<?php

namespace meta\UserProfileBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('username', null, array('label'  => 'Username', 'attr' => array('class' => 'input-xxlarge', 'help' => 'Your profile page will be available at /who/{username}')));
        $builder->add('first_name', null, array('label'  => 'First Name', 'attr' => array('class' => 'input-xxlarge')));
        $builder->add('last_name', null, array('label'  => 'Last Name', 'attr' => array('class' => 'input-xxlarge')));
        $builder->add('password', 'password', array( 'label'  => 'Password', 'attr' => array('class' => 'input-xxlarge')));
        $builder->add('email', 'email', array('label'  => 'E-mail', 'attr' => array('class' => 'input-xxlarge')));
        $builder->add('city', 'text', array('label'  => 'Your city', 'required' => false, 'attr' => array('class' => 'input-xxlarge')));

        $builder->add('file', 'file', array('label'  => 'Your avatar', 'required' => false, 'attr' => array('help' => 'If no file is provided, your default profile picture will be retrieved from gravatar.com')));

        $builder->add('headline', 'text',  array('label'  => 'Headline', 'required' => false, 'attr' => array('class' => 'input-xxlarge', 'help' => 'How would you sum up who you are ?' )));
        $builder->add('about', 'textarea', array('label'  => 'About you', 'required' => false, 'attr' => array('class' => 'input-xxlarge', 'help' => 'Tell us a bit more about you ...')));

        $builder->add('skills', 'entity', array(
            'label'  => 'Your skills', 
            'multiple' => true, 
            'required' => false, 
            'property' => 'name',
            'class' => 'meta\UserProfileBundle\Entity\Skill',
            'attr' => array('class' => 'select2-trigger')
            ));
    }
    
    public function getName()
    {
        return 'user';
    }
}