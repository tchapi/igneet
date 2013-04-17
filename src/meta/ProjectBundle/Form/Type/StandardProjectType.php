<?php

namespace meta\ProjectBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class StandardProjectType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', null, array('label'  => 'project.createForm.name', 'attr' => array( 'class' => 'input-xxlarge', 'placeholder' => 'project.createForm.namePlaceholder')));
        $builder->add('slug', null, array('label'  => 'project.createForm.slug', 'attr' => array( 'class' => 'input-xxlarge', 'help' => 'project.createForm.slugHelp', 'placeholder' => 'project.createForm.slugPlaceholder')));
        $builder->add('headline', 'text',  array('required' => false, 'label'  => 'project.createForm.headline', 'attr' => array('class' => 'input-xxlarge', 'help' => 'project.createForm.headlineHelp')));

        $builder->add('private', 'checkbox',  array('required' => false, 'label'  => 'project.createForm.private', 'attr' => array('help' => 'project.createForm.privateHelp')));

        $builder->add('neededSkills', 'entity', array(
            'multiple' => true, 
            'required' => false, 
            'property' => 'slug',
            'class' => 'meta\UserBundle\Entity\Skill',
            'label' => 'project.createForm.skills',
            'attr' => array('class' => 'select2-trigger')
            ));
    }
    
    public function getName()
    {
        return 'standardProject';
    }
}