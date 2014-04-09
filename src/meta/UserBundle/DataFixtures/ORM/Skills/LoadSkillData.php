<?php

namespace meta\UserBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use meta\UserBundle\Entity\Skill;

class LoadSkillData implements FixtureInterface, ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {

        $skills = $this->container->getParameter('skills');

        foreach($skills as $skill){

            // If skill exist already, update color
            $existingSkill = $this->container->get('doctrine')->getRepository('metaUserBundle:Skill')->findOneBySlug($skill['slug']);
            
            if (!$existingSkill){

                $persistedSkill = new Skill();
                $persistedSkill->setSlug($skill['slug']);
                $persistedSkill->setColor($skill['color']);

                $manager->persist($persistedSkill);

            } else {

                $existingSkill->setColor($skill['color']);
            
            }

        }
        
        $manager->flush();

    }

}
