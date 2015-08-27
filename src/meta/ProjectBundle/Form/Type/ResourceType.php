<?php

namespace meta\ProjectBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/* SYMFONY 2.8
use Symfony\Component\Form\Extension\Core\Type\FileType,
    Symfony\Component\Form\Extension\Core\Type\UrlType;
*/

class ResourceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder->add('file', 'file' /* SYMFONY 2.8 FileType::class*/, array('required' => false, 'label' => 'project.resources.createForm.file'));
        $builder->add('url', 'url' /* SYMFONY 2.8 UrlType::class*/, array('label'  => 'project.resources.createForm.url', 'attr' => array( 'placeholder' => 'http://...')));

    }    

    public function getName()
    {
       return $this->getBlockPrefix();
    }

    public function getBlockPrefix()
    {
        return 'resource';
    }
}