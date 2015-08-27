<?php

namespace meta\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
/* SYMFONY 2.8 
use Symfony\Component\Form\Extension\Core\Type\DateType,
    Symfony\Component\Form\Extension\Core\Type\ChoiceType;
*/

class CommunityType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('type', 'choice' /* SYMFONY 2.8 ChoiceType::class*/, array(
            'choices' => array('demo' => "demo", 'association' => "association", 'entreprise' => "entreprise"),
            'choices_as_values' => true,
            'label' => 'community.createForm.type',
            'attr' => array('help' => 'community.createForm.typeHelp')
            ));

        $builder->add('valid_until', 'date' /* SYMFONY 2.8 DateType::class*/, array('label'  => 'community.createForm.valid_until', 'attr' => array( 'placeholder' => 'community.createForm.validPlaceholder')));
     
    }

    public function getName()
    {
       return $this->getBlockPrefix();
    }

    public function getBlockPrefix()
    {
        return 'community';
    }
}