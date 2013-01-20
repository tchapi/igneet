<?php

namespace meta\UserProfileBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use meta\UserProfileBundle\Entity\Skill;

class LoadSkillData implements FixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $skill = new Skill();
        $skill->setSlug('development');
        $skill->setName('Development');
        $skill->setDescription('Web / Application development');
        $skill->setColor('00CCBB');

        $manager->persist($skill);
        $manager->flush();

        $skill = new Skill();
        $skill->setSlug('commercial');
        $skill->setName('Commercial');
        $skill->setDescription('Selling Stuff');
        $skill->setColor('00CCBB');

        $manager->persist($skill);
        $manager->flush();

        $skill = new Skill();
        $skill->setSlug('business-dev');
        $skill->setName('Business Dev');
        $skill->setDescription('Developing the business');
        $skill->setColor('00CCBB');

        $manager->persist($skill);
        $manager->flush();

        $skill = new Skill();
        $skill->setSlug('management');
        $skill->setName('Management');
        $skill->setDescription('Managing people');
        $skill->setColor('00CCBB');

        $manager->persist($skill);
        $manager->flush();

        $skill = new Skill();
        $skill->setSlug('tech-consulting');
        $skill->setName('Tech Consulting');
        $skill->setDescription('Consulting & Technical expertise');
        $skill->setColor('00CCBB')

        $manager->persist($skill);
        $manager->flush();
    }
}