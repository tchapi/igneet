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
    meta\IdeaBundle\Entity\Idea,
    meta\UserBundle\Entity\UserInviteToken;

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

        /**
            Communauté test_in :
              test appartient à la communauté test_in
              other_test appartient à la communauté test_in
              test_manager appartient à la communauté test_in et en est manager
              test_admin appartient à la communauté test_in et a un ROLE_ADMIN


            Communauté test_out : 
                other_test appartient à la communauté test_out
        
            Communauté test_guest :
                 other_test appartient à la communauté test_guest
                 test est guest dans la communauté test_guest sur le projet test_guest_project

            Projets / Idées : 
              test_project_private_space : projet privé dans le private space de test
              test_idea_private_space : idée privée dans le private space de test

              test_project_community_owner : projet public dans test_in, test est owner, other_test n'est pas dedans
              test_project_community_owner_private : projet privé dans test_in, test est owner, other_test n'est pas dedans
              test_project_community_participant : projet public dans test_in, test est participant, other_test est owner
              test_project_community_not_in : projet public dans test_in, test n'est pas dans le projet, other_test est owner
              test_project_community_not_in_private : projet privé dans test_in, test n'est pas dedans, other_test est owner

              test_idea_community_owner : idée dans test_in, test est creator, other_test n'est pas dedans
              test_idea_community_participant : idée dans test_in, test est participant, other_test est creator
              test_idea_community_not_in : idée dans test_in, test n'est pas dedans, other_test est creator

              test_out_project : project dans test_out, other_test est owner
              test_out_idea : idée dans test_out, other_test est creator

              test_guest_project : projet dans test_guest, test est guest owner, other_test est owner
              test_guest_project_not_in : projet dans test_guest, test n'est pas dedans, other_test est owner
              test_guest_idea : idée dans test_guest, test n'est pas dedans (il est guest, normal), other_test est creator


        **/

        /* DONE

            0.
                test a accès à test_in OK
                other_test a accès à test_in OK
                test n'a pas accès à test_out OK
                other_test a accès à test_out OK
                test a accès à test_guest OK
                other_test a accès à test_guest OK

            1.
                test a accès à test_project_private_space OK
                    Switch auto quand accès alors que dans une communauté OK
                other_test n'a pas accès à test_project_private_space OK
                toutes modifs (POST) etc, marchent avec test OK
                "add participant" dans test_project_private_space ne marche pas avec test
                "add owner" dans test_project_private_space ne marche pas avec test
                "add participant" et "add_owner" et leurs combinaisons

            2.
                test a accès à test_idea_private_space OK
                    Switch auto quand accès alors que dans une communauté OK
                other_test n'a pas accès à test_idea_private_space OK
                toutes modifs (POST) etc, marchent avec test OK
                "add participant" dans test_idea_private_space ne marche pas avec test
                "add owner" dans test_idea_private_space ne marche pas avec test
                

            3. 
                test a accès à test_project_community_owner OK
                other_test a accès à test_project_community_owner OK
                    Switch auto quand accès alors que dans une communauté OK
                test peut modifier tout dans test_project_community_owner OK
                other_test ne peut pas mofidier test_project_community_owner OK
                sauf commenter OK

            4.  
                test a accès à test_project_community_owner_private OK
                other_test n'a pas accès à test_project_community_owner_private OK
                    Switch auto quand accès alors que dans une communauté OK

            5.
                test a accès à test_project_community_participant OK
                other_test a accès à test_project_community_participant OK
                    Switch auto quand accès alors que dans une communauté OK
                test peut modifier tout ce que peut faire un participant dans test_project_community_participant OK

            6.
                test a accès à test_project_community_not_in OK
                test ne peut pas modifier test_project_community_not_in OK
                sauf commenter OK

            7.
                test n'a pas accès à test_project_community_not_in_private OK

            8.
                test a accès à test_idea_community_owner OK
                other_test a accès à test_idea_community_owner OK
                 test peut modifier test_idea_community_owner OK
                other_test ne peut pas modifier test_idea_community_owner OK

            9.
                test a accès à test_idea_community_participant OK
                other_test a accès à test_idea_community_participant OK 
                test peut modifier en tant que participant test_idea_community_participant OK
                other_test ne peut pas modifier test_idea_community_participant OK


            10.
                test a accès à test_idea_community_not_in OK
                test ne peut pas modifier test_idea_community_not_in  OK

            11. 
                test n'a pas accès à test_out_project (404) OK

            12. 
                test n'a pas accès à test_out_idea (404) OK

            13.
                test a accès a test_guest_project OK
                other_test a accès à test_guest_project OK
                test peut modifier test_guest_project OK
                other_test peut modifier test_guest_project OK

            14. 
                test n'a pas accès a test_guest_project_not_in OK
                other_test a accès à test_guest_project_not_in OK
                other_test peut modifier test_guest_project_not_in OK

            15.
                other_test a accès a test_guest_idea OK
                test n'a pas accès à test_guest_idea OK
        */

        /* ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** **
           **                         FIRST TEST USER : "TEST"                           **
           ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** */
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

        /* ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** **
           **                      SECOND TEST USER : "OTHER_TEST"                       **
           ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** */
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

        // They follow each 
        if (!$user->isFollowing($otherUser)){
            $user->addFollowing($otherUser);
        }
        if (!$otherUser->isFollowing($user)){
            $otherUser->addFollowing($user);
        }
        /* ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** **
           **                         THIRD TEST USER : "MANAGER"                           **
           ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** */
        $managerUser = $this->container->get('doctrine')->getRepository('metaUserBundle:User')->findOneByUsername('test_manager');

        if (!$managerUser){
            // A New test user
            $managerUser = new User();
            $manager->persist($managerUser);
        }

        $managerUser->setUsername("test_manager");
        $managerUser->setFirstname("Patron BigBosss");
        $managerUser->setLastname("Du Test");

        $managerUser->setHeadline("Je suis là pour vous manager les autres et les communautés. Et tester.");
        $managerUser->setCity("Test sur Seine");
        $managerUser->setEmail("test+manager@igneet.com");

        $managerUser->setAbout("<h2>Test!</h2><p>Oui, je manage et je teste.</p>"); // FIXME

        $managerUser->setSalt(md5(uniqid()));

        $encoder = $this->container
            ->get('security.encoder_factory')
            ->getEncoder($managerUser);
        $managerUser->setPassword($encoder->encodePassword('test', $managerUser->getSalt()));
        /* ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** **
           **                         FOURTH TEST USER : "ADMIN"                           **
           ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** */
        $adminUser = $this->container->get('doctrine')->getRepository('metaUserBundle:User')->findOneByUsername('test_admin');

        if (!$adminUser){
            // A New test user
            $adminUser = new User();
            $manager->persist($adminUser);
        }

        $adminUser->setUsername("test_admin");
        $adminUser->setFirstname("God Almighty");
        $adminUser->setLastname("Du Test");

        $adminUser->setHeadline("Je suis là pour vous administrer.");
        $adminUser->setCity("Test sur Seine");
        $adminUser->setEmail("test+admin@igneet.com");
        $adminUser->setRoles(array("ROLE_USER", "ROLE_ADMIN"));

        $adminUser->setAbout("<h2>Test!</h2><p>Oui, j'administre et je teste.</p>"); // FIXME

        $adminUser->setSalt(md5(uniqid()));

        $encoder = $this->container
            ->get('security.encoder_factory')
            ->getEncoder($adminUser);
        $adminUser->setPassword($encoder->encodePassword('test', $adminUser->getSalt()));

        /* ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** **
           **                        FIRST COMMUNITY : "TEST_IN"                         **
           ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** */
        // New communities the user is in / is out
        // Check if test communities are already in
        $community = $this->container->get('doctrine')->getRepository('metaGeneralBundle:Community\Community')->findOneByName('test_in');

        if (!$community){
            // A New test community
            $community = new Community();
            $userCommunity = new UserCommunity();
            $otherUserCommunity = new UserCommunity();
            $managerUserCommunity = new UserCommunity();
            $adminUserCommunity = new UserCommunity();
            $manager->persist($community);
            $manager->persist($userCommunity);
            $manager->persist($otherUserCommunity);
            $manager->persist($managerUserCommunity);
            $manager->persist($adminUserCommunity);
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
            $managerUserCommunity = $this->container->get('doctrine')->getRepository('metaUserBundle:UserCommunity')->findOneBy(array('user' => $managerUser->getId(), 'community' => $community->getId()));
            if (!$managerUserCommunity){
                $managerUserCommunity = new UserCommunity();
                $manager->persist($managerUserCommunity);
            }
            $adminUserCommunity = $this->container->get('doctrine')->getRepository('metaUserBundle:UserCommunity')->findOneBy(array('user' => $adminUser->getId(), 'community' => $community->getId()));
            if (!$adminUserCommunity){
                $adminUserCommunity = new UserCommunity();
                $manager->persist($adminUserCommunity);
            }
        }

        $userCommunity->setUser($user);
        $userCommunity->setCommunity($community);
        $userCommunity->setGuest(false);
        $otherUserCommunity->setUser($otherUser);
        $otherUserCommunity->setCommunity($community);
        $otherUserCommunity->setGuest(false);
        $managerUserCommunity->setUser($managerUser);
        $managerUserCommunity->setCommunity($community);
        $managerUserCommunity->setGuest(false);
        $managerUserCommunity->setManager(true);
        $adminUserCommunity->setUser($adminUser);
        $adminUserCommunity->setCommunity($community);
        $adminUserCommunity->setGuest(false);
        $community->setName('test_in');
        $community->setValidUntil(new \DateTime('now + 10 years'));
        $community->setHeadline('Test users should be here.');

        // Create an invite token for this community
        $token = new UserInviteToken($user, "test+token@igneet.com", $community, 'user', null, null);
        $manager->persist($token);

        /* ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** **
           **                       SECOND COMMUNITY : "TEST_OUT"                        **
           ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** */
        $communityOut = $this->container->get('doctrine')->getRepository('metaGeneralBundle:Community\Community')->findOneByName('test_out');

        if (!$communityOut){
            // A New test community
            $communityOut = new Community();
            $userCommunity = new UserCommunity();
            $otherUserCommunity = new UserCommunity();
            $manager->persist($communityOut);
            $manager->persist($userCommunity);
            $manager->persist($otherUserCommunity);
        } else {
            $userCommunity = $this->container->get('doctrine')->getRepository('metaUserBundle:UserCommunity')->findOneBy(array('user' => $user->getId(), 'community' => $communityOut->getId()));
            if ($userCommunity){
                $manager->remove($userCommunity);
            }
            $otherUserCommunity = $this->container->get('doctrine')->getRepository('metaUserBundle:UserCommunity')->findOneBy(array('user' => $otherUser->getId(), 'community' => $communityOut->getId()));
            if (!$otherUserCommunity){
                $otherUserCommunity = new UserCommunity();
                $manager->persist($otherUserCommunity);
            }
        }

        $communityOut->setName('test_out');
        $communityOut   ->setValidUntil(new \DateTime('now + 10 years'));
        $communityOut->setHeadline('Only OTHER_TEST should be here.');
        $otherUserCommunity->setUser($otherUser);
        $otherUserCommunity->setCommunity($communityOut);
        $otherUserCommunity->setGuest(false);

        /* ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** **
           **                       THIRD COMMUNITY : "TEST_GUEST"                       **
           ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** */
        $communityGuest = $this->container->get('doctrine')->getRepository('metaGeneralBundle:Community\Community')->findOneByName('test_guest');

        if (!$communityGuest){
            // A New test community
            $communityGuest = new Community();
            $userCommunity = new UserCommunity();
            $otherUserCommunity = new UserCommunity();
            $manager->persist($communityGuest);
            $manager->persist($userCommunity);
            $manager->persist($otherUserCommunity);
        } else {
            $userCommunity = $this->container->get('doctrine')->getRepository('metaUserBundle:UserCommunity')->findOneBy(array('user' => $user->getId(), 'community' => $communityGuest->getId()));
            if (!$userCommunity){
                $userCommunity = new UserCommunity();
                $manager->persist($userCommunity);
            }
            $otherUserCommunity = $this->container->get('doctrine')->getRepository('metaUserBundle:UserCommunity')->findOneBy(array('user' => $otherUser->getId(), 'community' => $communityGuest->getId()));
            if (!$otherUserCommunity){
                $otherUserCommunity = new UserCommunity();
                $manager->persist($otherUserCommunity);
            }
        }

        $communityGuest->setName('test_guest');
        $communityGuest->setValidUntil(new \DateTime('now + 10 years'));
        $communityGuest->setHeadline('Only OTHER_TEST should be here, and TEST as guest.');
        $otherUserCommunity->setUser($otherUser);
        $otherUserCommunity->setCommunity($communityGuest);
        $otherUserCommunity->setGuest(false);
        $userCommunity->setUser($user);
        $userCommunity->setCommunity($communityGuest);
        $userCommunity->setGuest(true);

        /* ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** **
           **                           IDEAS IN PRIVATE SPACE                           **
           ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** */
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

        /* ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** **
           **                         PROJECTS IN PRIVATE SPACE                          **
           ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** */
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

        /* ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** **
           **                     PROJECTS/IDEAS IN COMMUNITY TEST_IN                    **
           ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** */

        /* ** ** test_project_community_owner ** ** */
        /* ** ** TEST IS OWNER OF THIS PROJECT ** ** */
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

        /* ** ** test_project_community_participant ** ** */
        /* ** ** TEST IS PARTICIPANT IN THIS PROJECT ** ** */
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

        /* ** ** test_project_community_not_in ** ** */
        /* ** ** TEST IS NOT IN THIS PROJECT ** ** */
        $project = $this->container->get('doctrine')->getRepository('metaProjectBundle:StandardProject')->findOneByName('test_project_community_not_in');

        if (!$project){
            // A New test project
            $project = new StandardProject();
            $manager->persist($project);
        }

        $project->setName('test_project_community_not_in');
        if (!$otherUser->isOwning($project)){
            $otherUser->addProjectsOwned($project);
        }
        if (!$community->getProjects()->contains($project)){
            $community->addProject($project);
        }
        $project->setPrivate(false);

        /* ** ** test_project_community_not_in_private ** ** */
        /* ** ** PRIVATE PROJECT WHERE TEST IS NOT IN ** ** */
        $project = $this->container->get('doctrine')->getRepository('metaProjectBundle:StandardProject')->findOneByName('test_project_community_not_in_private');

        if (!$project){
            // A New test project
            $project = new StandardProject();
            $manager->persist($project);
        }

        $project->setName('test_project_community_not_in_private');
        if (!$otherUser->isOwning($project)){
            $otherUser->addProjectsOwned($project);
        }
        if (!$community->getProjects()->contains($project)){
            $community->addProject($project);
        }
        $project->setPrivate(true);

        /* ** ** test_project_community_owner_private ** ** */
        /* ** ** TEST IS OWNER OF THIS PRIVATE PROJECT ** ** */
        $project = $this->container->get('doctrine')->getRepository('metaProjectBundle:StandardProject')->findOneByName('test_project_community_owner_private');

        if (!$project){
            // A New test project
            $project = new StandardProject();
            $manager->persist($project);
        }

        $project->setName('test_project_community_owner_private');
        if (!$user->isOwning($project)){
            $user->addProjectsOwned($project);
        }
        if (!$community->getProjects()->contains($project)){
            $community->addProject($project);
        }
        $project->setPrivate(true);

        /* ** ** test_idea_community_owner ** ** */
        /* ** ** TEST IS CREATOR OF THIS IDEA ** ** */
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

        /* ** ** test_idea_community_participant ** ** */
        /* ** ** TEST IS PARTICIPANT IN THIS IDEA ** ** */
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
            $user->addIdeaParticipatedIn($idea);
        }
        if (!$community->getIdeas()->contains($idea)){
            $community->addIdea($idea);
        }

        /* ** ** test_idea_community_not_in ** ** */
        /* ** ** TEST IS NOT IN THIS IDEA ** ** */
        $idea = $this->container->get('doctrine')->getRepository('metaIdeaBundle:Idea')->findOneByName('test_idea_community_not_in');

        if (!$idea){
            // A New test idea
            $idea = new Idea();
            $manager->persist($idea);
        }

        $idea->setName('test_idea_community_not_in');
        if (!$idea->getCreators()->contains($otherUser)){
            $idea->addCreator($otherUser);
        }
        if (!$community->getIdeas()->contains($idea)){
            $community->addIdea($idea);
        }

        /* ** ** test_out_idea ** ** */
        /* ** ** OTHER_TEST IS CREATOR OF THIS IDEA ** ** */
        $idea = $this->container->get('doctrine')->getRepository('metaIdeaBundle:Idea')->findOneByName('test_out_idea');

        if (!$idea){
            // A New test idea
            $idea = new Idea();
            $manager->persist($idea);
        }

        $idea->setName('test_out_idea');
        if (!$idea->getCreators()->contains($otherUser)){
            $idea->addCreator($otherUser);
        }
        if (!$communityOut->getIdeas()->contains($idea)){
            $communityOut->addIdea($idea);
        }

        /* ** ** test_out_project ** ** */
        /* ** ** TEST IS OWNER OF THIS PROJECT ** ** */
        $project = $this->container->get('doctrine')->getRepository('metaProjectBundle:StandardProject')->findOneByName('test_out_project');

        if (!$project){
            // A New test project
            $project = new StandardProject();
            $manager->persist($project);
        }

        $project->setName('test_out_project');
        if (!$otherUser->isOwning($project)){
            $otherUser->addProjectsOwned($project);
        }
        if (!$communityOut->getProjects()->contains($project)){
            $communityOut->addProject($project);
        }
        $project->setPrivate(false);


        /* ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** **
           **                   PROJECTS/IDEAS IN COMMUNITY TEST_GUEST                   **
           ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** ** */

        /* ** ** test_guest_project ** ** */
        /* ** ** TEST IS GUEST OWNER OF THIS PROJECT ** ** */
        $project = $this->container->get('doctrine')->getRepository('metaProjectBundle:StandardProject')->findOneByName('test_guest_project');

        if (!$project){
            // A New test project
            $project = new StandardProject();
            $manager->persist($project);
        }

        $project->setName('test_guest_project');
        if (!$user->isOwning($project)){
            $user->addProjectsOwned($project);
        }
        if (!$otherUser->isOwning($project)){
            $otherUser->addProjectsOwned($project);
        }
        if (!$communityGuest->getProjects()->contains($project)){
            $communityGuest->addProject($project);
        }
        $project->setPrivate(false);

        /* ** ** test_guest_project_not_in ** ** */
        /* ** ** TEST IS GUEST OWNER OF THIS PROJECT ** ** */
        $project = $this->container->get('doctrine')->getRepository('metaProjectBundle:StandardProject')->findOneByName('test_guest_project_not_in');

        if (!$project){
            // A New test project
            $project = new StandardProject();
            $manager->persist($project);
        }

        $project->setName('test_guest_project_not_in');
        if (!$otherUser->isOwning($project)){
            $otherUser->addProjectsOwned($project);
        }
        if (!$communityGuest->getProjects()->contains($project)){
            $communityGuest->addProject($project);
        }
        $project->setPrivate(false);

        /* ** ** test_guest_idea ** ** */
        /* ** ** TEST IS NOT IN THIS IDEA ** ** */
        $idea = $this->container->get('doctrine')->getRepository('metaIdeaBundle:Idea')->findOneByName('test_guest_idea');

        if (!$idea){
            // A New test idea
            $idea = new Idea();
            $manager->persist($idea);
        }

        $idea->setName('test_guest_idea');
        if (!$idea->getCreators()->contains($otherUser)){
            $idea->addCreator($otherUser);
        }
        if (!$communityGuest->getIdeas()->contains($idea)){
            $communityGuest->addIdea($idea);
        }



        /* ********************* */
        /* FLUSHES ALL THAT SHIT */
        /* ********************* */
        $manager->flush();
        
    }

}
