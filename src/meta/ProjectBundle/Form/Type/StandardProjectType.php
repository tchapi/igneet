<?php

namespace meta\ProjectBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class StandardProjectType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', null, array('label'  => 'project.createForm.name', 'attr' => array( 'class' => 'input-xxlarge', 'placeholder' => 'project.createForm.namePlaceholder')));
        $builder->add('slug', null, array('label'  => 'project.createForm.slug', 'attr' => array( 'class' => 'input-xxlarge', 'help' => 'project.createForm.slugHelp', 'placeholder' => 'project.createForm.slugPlaceholder')));
        $builder->add('headline', 'text',  array('required' => false, 'label'  => 'project.createForm.headline', 'attr' => array('class' => 'input-xxlarge', 'help' => 'project.createForm.headlineHelp')));

        $builder->add('private', 'checkbox',  array('required' => false, 'label'  => 'project.createForm.private', 'attr' => array('help' => 'project.createForm.privateHelp')));

        $builder->add('neededSkills', 'entity', array(
            'label' => $options['translator']->trans('project.createForm.skills'),
            'multiple' => true, 
            'required' => false, 
            'property' => 'I18nSlug',
            'class' => 'meta\UserBundle\Entity\Skill',
            'attr' => array('class' => 'select2-trigger', 'data-placeholder' => $options['translator']->trans('project.createForm.skillsPlaceholder')),
            'translation_domain' => 'skills'
            ));
    }
    
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'translator' => null
        ));
    }

    public function getName()
    {
        return 'standardProject';
    }
}