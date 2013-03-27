<?php

namespace meta\StandardProjectProfileBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class StandardProjectType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', null, array('label'  => 'Name of this project', 'attr' => array( 'class' => 'input-xxlarge', 'placeholder' => 'My new project')));
        $builder->add('slug', null, array('label'  => 'URL Slug', 'attr' => array( 'class' => 'input-xxlarge', 'help' => 'Accepted characters : alphanumeric and hyphens', 'placeholder' => 'my-new-project')));
        $builder->add('headline', 'text',  array('required' => false, 'label'  => 'Headline', 'attr' => array('class' => 'input-xxlarge', 'help' => 'Give your project some nice catchline')));

        $builder->add('private', 'checkbox',  array('required' => false, 'label'  => 'Private project', 'attr' => array('help' => 'Decide wether your project should be seen by non-participants. Private space projects are always private')));

        $builder->add('neededSkills', 'entity', array(
            'multiple' => true, 
            'required' => false, 
            'property' => 'name',
            'class' => 'meta\UserProfileBundle\Entity\Skill',
            'label' => 'Skills needed for this project (if any)',
            'attr' => array('class' => 'select2-trigger')
            ));
    }
    
    public function getName()
    {
        return 'standardProject';
    }
}