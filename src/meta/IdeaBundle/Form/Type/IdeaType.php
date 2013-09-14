<?php

namespace meta\IdeaBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class IdeaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', null, array('label'  => 'idea.createForm.name', 'attr' => array( 'class' => 'input-xxlarge', 'placeholder' => 'idea.createForm.namePlaceholder')));
        $builder->add('headline', 'text',  array('required' => false, 'label'  => 'idea.createForm.headline', 'attr' => array('class' => 'input-xxlarge', 'help' => 'idea.createForm.headlinePlaceholder')));

        // In the case where we are in the private space, 
        // we do not allow the creator to add creators to this idea
        // If we are in a community we restrict what users are available
        $community = $options['community'];
        if ( isset($options['allowCreators']) &&  $options['allowCreators'] === true){
            $builder->add('creators', 'entity', array(
                'multiple' => true, 
                'required' => false, // We will add the authenticated user afterwards 
                'property' => 'fullName',
                'class' => 'meta\UserBundle\Entity\User',
                'query_builder' => function(\Doctrine\ORM\EntityRepository $er) use ($community){
                    return $er->createQueryBuilder("u")
                              ->join('u.userCommunities', 'uc')
                              ->join('uc.community', 'c')
                              ->where("u.deleted_at IS NULL")
                              ->andWhere("c = :community")
                              ->andWhere("uc.guest = 0")
                              ->setParameter('community', $community)
                              ->orderBy("u.username", "ASC");
                },
                'label' => 'idea.createForm.creators',
                'attr' => array('class' => 'select2-trigger', 'data-placeholder' => $options['translator']->trans('idea.createForm.creatorsPlaceholder'))
                ));
        }
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'allowCreators' => false,
            'community' => null,
            'translator' => null
        ));
    }
    
    public function getName()
    {
        return 'idea';
    }
}