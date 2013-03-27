<?php

namespace meta\IdeaProfileBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class IdeaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', null, array('label'  => 'Name of this idea', 'attr' => array( 'class' => 'input-xxlarge', 'placeholder' => 'My new idea')));
        $builder->add('headline', 'text',  array('required' => false, 'label'  => 'Headline', 'attr' => array('class' => 'input-xxlarge', 'help' => 'Give your idea some nice catchline')));

        if ( isset($options['allowCreators']) &&  $options['allowCreators'] === true){
            $builder->add('creators', 'entity', array(
                'multiple' => true, 
                'required' => false, // We will add the authenticated user afterwards 
                'property' => 'fullName',
                'class' => 'meta\UserProfileBundle\Entity\User',
                'query_builder' => function(\Doctrine\ORM\EntityRepository $er){
                    return $er->createQueryBuilder("u")->where("u.deleted_at IS NULL")->orderBy("u.username", "ASC");
                },
                'label' => 'Creators',
                'attr' => array('class' => 'select2-trigger')
                ));
        }
    }

    public function getDefaultOptions(array $options)
    {
        return array(
            'allowCreators' => false
        );
    }
    
    public function getName()
    {
        return 'idea';
    }
}