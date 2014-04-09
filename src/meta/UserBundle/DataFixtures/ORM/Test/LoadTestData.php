<?php

namespace meta\UserBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use meta\UserBundle\Entity\User;
use meta\UserBundle\Entity\UserCommunity;
use meta\GeneralBundle\Entity\Community\Community;

class LoadTestData implements FixtureInterface, ContainerAwareInterface
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

        // Check if test user is already in
        $user = $this->container->get('doctrine')->getRepository('metaUserBundle:User')->findOneByUsername('test');

        if (!$user){
            // A New test user
            $user = new User();
            $manager->persist($user);
        }

        $user->setUsername("test");
        $user->setFirstname("Jean-Test");
        $user->setLastname("Du Test");

        $user->setHeadline("Je suis là pour vous servir. Et tester.");
        $user->setCity("TestVille");
        $user->setEmail("test@igneet.com");

        $user->setAbout("<h2>Test!</h2><p>Oui, je teste.</p>"); // FIXME

        $user->setSalt(md5(uniqid()));

        $encoder = $this->container
            ->get('security.encoder_factory')
            ->getEncoder($user);
        $user->setPassword($encoder->encodePassword('test', $user->getSalt()));

        

        // New communities the user is in / is out
        // Check if test communities are already in
        $community = $this->container->get('doctrine')->getRepository('metaGeneralBundle:Community\Community')->findOneByName('test_in');

        if (!$community){
            // A New test community
            $community = new Community();
            $userCommunity = new UserCommunity();
            $manager->persist($community);
            $manager->persist($userCommunity);
        } else {
            $userCommunity = $this->container->get('doctrine')->getRepository('metaUserBundle:UserCommunity')->findOneBy(array('user' => $user->getId(), 'community' => $community->getId()));
            if (!$userCommunity){
                $userCommunity = new UserCommunity();
                $manager->persist($userCommunity);
            }
        }

        $userCommunity->setUser($user);
        $userCommunity->setCommunity($community);
        $userCommunity->setGuest(false);
        $community->setName('test_in');
        $community->setHeadline('User test should be here.');

        // --
        $community = $this->container->get('doctrine')->getRepository('metaGeneralBundle:Community\Community')->findOneByName('test_out');

        if (!$community){
            // A New test community
            $community = new Community();
            $manager->persist($community);
        } else {
            $userCommunity = $this->container->get('doctrine')->getRepository('metaUserBundle:UserCommunity')->findOneBy(array('user' => $user->getId(), 'community' => $community->getId()));
            if ($userCommunity){
                $manager->remove($userCommunity);
            }
        }

        $community->setName('test_out');
        $community->setHeadline('User test should NOT be here.');

        // FIXME 
        // FIXME 
        // FIXME 
        // FIXME 
        // FIXME 
        

        // Flushes all that shit
        $manager->flush();
        
    }

}
