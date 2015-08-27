<?php

namespace meta\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/* SYMFONY 2.8
use Symfony\Component\Form\Extension\Core\Type\ChoiceType,
    Symfony\Component\Form\Extension\Core\Type\DateType,
    Symfony\Component\Form\Extension\Core\Type\CheckboxType,
    Symfony\Component\Form\Extension\Core\Type\TextareaType;
*/
class AnnouncementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('text', 'textarea' /* SYMFONY 2.8 TextareaType::class */, array('label'  => 'announcement.createForm.text', 'attr' => array( 'placeholder' => 'announcement.createForm.textPlaceholder')));
        $builder->add('type', 'choice' /* SYMFONY 2.8  ChoiceType::class */, array(
            'choices' => array('Info' => "info", 'Warning' => "warning", 'Technical' => "technical"),
            'choices_as_values' => true,
            'label' => 'announcement.createForm.type',
            'attr' => array('help' => 'announcement.createForm.typeHelp')
            ));

        $builder->add('valid_from', 'date' /* SYMFONY 2.8 DateType::class */, array('label'  => 'announcement.createForm.valid_from', 'attr' => array( 'placeholder' => 'announcement.createForm.validPlaceholder')));
        $builder->add('valid_until', 'date' /* SYMFONY 2.8 DateType::class */, array('label'  => 'announcement.createForm.valid_until', 'attr' => array( 'placeholder' => 'announcement.createForm.validPlaceholder')));
     
        $builder->add('targetedUsers', 'entity', array(
                'multiple' => true, 
                'required' => false, // We will add the authenticated user afterwards 
                'choice_label' => 'fullName',
                'class' => 'meta\UserBundle\Entity\User',
                'query_builder' => function(\Doctrine\ORM\EntityRepository $er) {
                    return $er->createQueryBuilder("u")
                              ->where("u.deleted_at IS NULL")
                              ->orderBy("u.username", "ASC");
                },
                'label' => 'announcement.createForm.targeted'
                ));
        $builder->add('active', 'checkbox' /* SYMFONY 2.8 CheckboxType::class */, array('label'  => 'announcement.createForm.active', 'required' => false));
     
    }

    public function getName()
    {
       return $this->getBlockPrefix();
    }

    public function getBlockPrefix()
    {
        return 'announcement';
    }
}