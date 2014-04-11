<?php

namespace meta\UserBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use meta\UserBundle\Entity\User,
    meta\UserBundle\Entity\UserCommunity,
    meta\GeneralBundle\Entity\Community\Community,
    meta\ProjectBundle\Entity\StandardProject,
    meta\IdeaBundle\Entity\Idea;

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

         // Check if other test user is already in
        $otherUser = $this->container->get('doctrine')->getRepository('metaUserBundle:User')->findOneByUsername('other_test');

        if (!$otherUser){
            // A New test user
            $otherUser = new User();
            $manager->persist($otherUser);
        }

        $otherUser->setUsername("other_test");
        $otherUser->setFirstname("Michel-Test");
        $otherUser->setLastname("De la Testie");

        $otherUser->setHeadline("Je suis là pour aider Jean-Michel.");
        $otherUser->setCity("CityTest");
        $otherUser->setEmail("test+test@igneet.com");

        $otherUser->setAbout("<h2>Test!</h2><p>Oui, je teste aussi.</p>"); // FIXME

        $otherUser->setSalt(md5(uniqid()));

        $encoder = $this->container
            ->get('security.encoder_factory')
            ->getEncoder($otherUser);
        $otherUser->setPassword($encoder->encodePassword('test', $otherUser->getSalt()));

        // They follow each other
        $user->addFollowing($otherUser);
        $otherUser->addFollowing($user);

        // New communities the user is in / is out
        // Check if test communities are already in
        $community = $this->container->get('doctrine')->getRepository('metaGeneralBundle:Community\Community')->findOneByName('test_in');

        if (!$community){
            // A New test community
            $community = new Community();
            $userCommunity = new UserCommunity();
            $otherUserCommunity = new UserCommunity();
            $manager->persist($community);
            $manager->persist($userCommunity);
            $manager->persist($otherUserCommunity);
        } else {
            $userCommunity = $this->container->get('doctrine')->getRepository('metaUserBundle:UserCommunity')->findOneBy(array('user' => $user->getId(), 'community' => $community->getId()));
            if (!$userCommunity){
                $userCommunity = new UserCommunity();
                $manager->persist($userCommunity);
            }
            $otherUserCommunity = $this->container->get('doctrine')->getRepository('metaUserBundle:UserCommunity')->findOneBy(array('user' => $otherUser->getId(), 'community' => $community->getId()));
            if (!$otherUserCommunity){
                $otherUserCommunity = new UserCommunity();
                $manager->persist($otherUserCommunity);
            }
        }

        $userCommunity->setUser($user);
        $userCommunity->setCommunity($community);
        $userCommunity->setGuest(false);
        $otherUserCommunity->setUser($otherUser);
        $otherUserCommunity->setCommunity($community);
        $otherUserCommunity->setGuest(false);
        $community->setName('test_in');
        $community->setHeadline('User test should be here.');

        // --
        $communityOut = $this->container->get('doctrine')->getRepository('metaGeneralBundle:Community\Community')->findOneByName('test_out');

        if (!$communityOut){
            // A New test community
            $communityOut = new Community();
            $manager->persist($communityOut);
        } else {
            $userCommunity = $this->container->get('doctrine')->getRepository('metaUserBundle:UserCommunity')->findOneBy(array('user' => $user->getId(), 'community' => $communityOut->getId()));
            if ($userCommunity){
                $manager->remove($userCommunity);
            }
        }

        $communityOut->setName('test_out');
        $communityOut->setHeadline('User test should NOT be here.');

        // New private space project and idea for user
        $idea = $this->container->get('doctrine')->getRepository('metaIdeaBundle:Idea')->findOneByName('test_idea_private_space');

        if (!$idea){
            // A New test idea
            $idea = new Idea();
            $manager->persist($idea);
        }

        $idea->setName('test_idea_private_space');
        if (!$idea->getCreators()->contains($user)){
            $idea->addCreator($user);
        }
        $idea->setCommunity(null);

        $project = $this->container->get('doctrine')->getRepository('metaProjectBundle:StandardProject')->findOneByName('test_project_private_space');

        if (!$project){
            // A New test project
            $project = new StandardProject();
            $manager->persist($project);
        }

        $project->setName('test_project_private_space');
        if (!$user->isOwning($project)){
            $user->addProjectsOwned($project);
        }
        $project->setCommunity(null);
        $project->setPrivate(true);

        // New project where test is owner  in community
        $project = $this->container->get('doctrine')->getRepository('metaProjectBundle:StandardProject')->findOneByName('test_project_community_owner');

        if (!$project){
            // A New test project
            $project = new StandardProject();
            $manager->persist($project);
        }

        $project->setName('test_project_community_owner');
        if (!$user->isOwning($project)){
            $user->addProjectsOwned($project);
        }
        if (!$community->getProjects()->contains($project)){
            $community->addProject($project);
        }
        $project->setPrivate(false);

        // New project where test is participant in community
        $project = $this->container->get('doctrine')->getRepository('metaProjectBundle:StandardProject')->findOneByName('test_project_community_participant');

        if (!$project){
            // A New test project
            $project = new StandardProject();
            $manager->persist($project);
        }

        $project->setName('test_project_community_participant');
        if (!$otherUser->isOwning($project)){
            $otherUser->addProjectsOwned($project);
        }
        if (!$user->isParticipatingIn($project)){
            $user->addProjectsParticipatedIn($project);
        }
        if (!$community->getProjects()->contains($project)){
            $community->addProject($project);
        }
        $project->setPrivate(false);


        // New idea owned by test in community
        $idea = $this->container->get('doctrine')->getRepository('metaIdeaBundle:Idea')->findOneByName('test_idea_community_owner');

        if (!$idea){
            // A New test idea
            $idea = new Idea();
            $manager->persist($idea);
        }

        $idea->setName('test_idea_community_owner');
        if (!$idea->getCreators()->contains($user)){
            $idea->addCreator($user);
        }
        if (!$community->getIdeas()->contains($idea)){
            $community->addIdea($idea);
        }

        // New idea participated in by test in community
        $idea = $this->container->get('doctrine')->getRepository('metaIdeaBundle:Idea')->findOneByName('test_idea_community_participant');

        if (!$idea){
            // A New test idea
            $idea = new Idea();
            $manager->persist($idea);
        }

        $idea->setName('test_idea_community_participant');
        if (!$idea->getCreators()->contains($otherUser)){
            $idea->addCreator($otherUser);
        }
        if (!$idea->getParticipants()->contains($user)){
            $idea->addParticipant($user);
        }
        if (!$community->getIdeas()->contains($idea)){
            $community->addIdea($idea);
        }

        // Flushes all that shit
        $manager->flush();
        
    }

}
