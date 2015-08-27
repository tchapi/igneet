<?php

namespace meta\ProjectBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/* SYMFONY 2.8 
use Symfony\Component\Form\Extension\Core\Type\CheckboxType,
    Symfony\Component\Form\Extension\Core\Type\TextType;
*/
    
class StandardProjectType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', null, array('label'  => 'project.createForm.name', 'attr' => array( 'autofocus' => "autofocus", 'class' => 'input-xxlarge', 'placeholder' => 'project.createForm.namePlaceholder')));
        $builder->add('headline', 'text' /* SYMFONY 2.8 TextType::class*/,  array('required' => false, 'label'  => 'project.createForm.headline', 'attr' => array('class' => 'input-xxlarge', 'help' => 'project.createForm.headlineHelp')));

        $builder->add('private', 'checkbox' /* SYMFONY 2.8 CheckboxType::class*/,  array('required' => false, 'data' => $options['isPrivate'], 'disabled' => $options['isPrivate'], 'label'  => 'project.createForm.private', 'attr' => array('help' => 'project.createForm.privateHelp')));

        // $builder->add('neededSkills', 'entity', array(
        //     'label' => $options['translator']->trans('project.createForm.skills'),
        //     'multiple' => true, 
        //     'required' => false, 
        //     'choice_label' => 'I18nSlug',
        //     'class' => 'meta\UserBundle\Entity\Skill',
        //     'attr' => array('class' => 'select2-trigger', 'data-placeholder' => $options['translator']->trans('project.createForm.skillsPlaceholder')),
        //     'translation_domain' => 'skills'
        //     ));
    }
    
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'translator' => null,
            'isPrivate' => false
        ));
    }

    public function getName()
    {
       return $this->getBlockPrefix();
    }

    public function getBlockPrefix()
    {
        return 'standardProject';
    }
}