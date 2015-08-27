<?php

namespace meta\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/* SYMFONY 2.8
use Symfony\Component\Form\Extension\Core\Type\EmailType,
    Symfony\Component\Form\Extension\Core\Type\PasswordType,
    Symfony\Component\Form\Extension\Core\Type\HiddenType;
*/

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('username', null, array('label' => 'user.createForm.username', 'attr' => array('class' => 'input-xxlarge', 'help' => 'user.createForm.usernameHelp')));
        
        if ($options['openid'] == false) {

            $builder->add('first_name', null, array('label' => 'user.createForm.firstname', 'attr' => array('class' => 'input-xxlarge')));
            $builder->add('last_name', null, array('label' => 'user.createForm.lastname', 'attr' => array('class' => 'input-xxlarge')));
            $builder->add('email', 'email' /* SYMFONY 2.8 EmailType::class*/, array('label' => 'user.createForm.email', 'attr' => array('class' => 'input-xxlarge')));
            $builder->add('password', 'password' /* SYMFONY 2.8 PasswordType::class*/, array( 'label' => 'user.createForm.password', 'attr' => array('class' => 'input-xxlarge')));

        } else {

            $builder->add('first_name', $options['openid_firstname_set']?'hidden' /* SYMFONY 2.8 HiddenType::class*/:null, array('label' => 'user.createForm.firstname', 'attr' => array('class' => 'input-xxlarge')));
            $builder->add('last_name', $options['openid_lastname_set']?'hidden' /* SYMFONY 2.8 HiddenType::class*/:null, array('label' => 'user.createForm.lastname', 'attr' => array('class' => 'input-xxlarge')));
            
            $builder->add('email', 'hidden' /* SYMFONY 2.8 HiddenType::class*/, array('label' => 'user.createForm.email', 'attr' => array('class' => 'input-xxlarge')));
            $builder->add('password', 'hidden' /* SYMFONY 2.8 HiddenType::class*/, array( 'label' => 'user.createForm.password', 'attr' => array('class' => 'input-xxlarge')));

        }
        
        /* We don't ask for skills right now
        $builder->add('skills', 'entity', array(
            'label'  => $options['translator']->trans('user.createForm.skills'), 
            'multiple' => true, 
            'required' => false, 
            'choice_label' => 'I18nSlug',
            'class' => 'meta\UserBundle\Entity\Skill',
            'attr' => array('class' => 'select2-trigger', 'data-placeholder' => $options['translator']->trans('user.createForm.skillsPlaceholder')),
            'translation_domain' => 'skills'
            ));
        */
    }
    
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'translator' => null,
            'openid' => false,
            'openid_firstname_set' => false,
            'openid_lastname_set' => false,
        ));
    }

    public function getName()
    {
       return $this->getBlockPrefix();
    }

    public function getBlockPrefix()
    {
        return 'user';
    }

}