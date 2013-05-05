<?php

namespace meta\UserBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use meta\UserBundle\Entity\Skill;

class LoadSkillData implements FixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {

        // Do not forget to add a translation if a skill is added here
        $skills = array(
            'marketResearch' => array('color' => ""),
            'productMarketing' => array('color' => ""),
            'communication' => array('color' => ""),
            'creativeThinking' => array('color' => ""),
            'finance' => array('color' => ""),
            'commercial' => array('color' => ""),
            'negotation' => array('color' => ""),
            'management' => array('color' => ""),
            'problemSolving' => array('color' => ""),
            'businessKnowledge' => array('color' => ""),
            'entrepreneurialKnowledge' => array('color' => ""),
            'productDevelopment' => array('color' => ""),
            'productionManagement' => array('color' => ""),
            'supplyChain' => array('color' => ""),
            'code' => array('color' => ""),
            'electronics' => array('color' => ""),
            'maths' => array('color' => ""),
            'physics' => array('color' => ""),
            'chemical' => array('color' => "")
        );

        foreach($skills as $slug => $skill){

            $persistedSkill = new Skill();
            $persistedSkill->setSlug($slug);
            $persistedSkill->setColor($skill['color']);

            $manager->persist($persistedSkill);
            $manager->flush();
        }

    }
}