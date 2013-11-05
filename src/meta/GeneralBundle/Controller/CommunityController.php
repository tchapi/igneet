<?php

namespace meta\GeneralBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response;

use meta\GeneralBundle\Entity\Community\Community,
    meta\UserBundle\Entity\UserCommunity,
    meta\GeneralBundle\Form\Type\CommunityType;

class CommunityController extends Controller
{
     
    /*
     * Displays a home for the community
     */
    public function homeAction(Request $request)
    {
        $authenticatedUser = $this->getUser();
        $community = $authenticatedUser->GetCurrentCommunity();
        
        // In a real community
        if ( !is_null($community) ){

            // Is community valid ?
            if(!$community->isValid()){

                $this->get('session')->getFlashBag()->add(
                    'error',
                    $this->get('translator')->trans('community.invalid', array( "%community%" => $community->getName()) )
                );

                // Back in private space, ahah
                $authenticatedUser->setCurrentCommunity(null);
                $em = $this->getDoctrine()->getManager();
                $em->flush();

                $this->get('session')->getFlashBag()->add(
                  'info',
                  $this->get('translator')->trans('private.space.back')
                );

                return $this->redirect($this->generateUrl('g_switch_private_space', array('token' => $this->get('form.csrf_provider')->generateCsrfToken('switchCommunity'), 'redirect' => true)));

            }

            // WILL DISAPPEAR WITH THE NEW HOME
            $ideaRepository = $this->getDoctrine()->getRepository('metaIdeaBundle:Idea');
            $totalIdeas = $ideaRepository->countIdeasInCommunityForUser($community, $authenticatedUser, false);

            $projectRepository = $this->getDoctrine()->getRepository('metaProjectBundle:StandardProject');
            $totalProjects = $projectRepository->countProjectsInCommunityForUser($community, $authenticatedUser, null);

            $userRepository = $this->getDoctrine()->getRepository('metaUserBundle:User');
            $totalUsersAndGuests = $userRepository->countUsersInCommunity(array('community' => $community));

            return $this->render('metaGeneralBundle:Community:home.html.twig', array(
                        'totalProjects' => $totalProjects,
                        'totalIdeas' => $totalIdeas,
                        'totalUsersAndGuests' => $totalUsersAndGuests));

        } else {

            // Or in your private space ?
            $projectRepository = $this->getDoctrine()->getRepository('metaProjectBundle:StandardProject');
            $shared_projects = $projectRepository->findById($this->container->getParameter('shared.projects'));
            return $this->render('metaGeneralBundle:Community:privateSpace.html.twig', array( 'shared_projects' => $shared_projects));
        }
       

    }

    public function createAction(Request $request)
    {

        $authenticatedUser = $this->getUser();

        $community = new Community();
        $form = $this->createForm(new CommunityType(), $community);

        if ($request->isMethod('POST')) {

            $form->bind($request);

            if ($form->isValid()) {

                $userCommunity = new UserCommunity();
                $userCommunity->setUser($authenticatedUser);
                $userCommunity->setCommunity($community);
                $userCommunity->setGuest(false);
                $userCommunity->setManager(true); // The one who creates is a manager by default

                $em = $this->getDoctrine()->getManager();
                $em->persist($community);
                $em->persist($userCommunity);
                $em->flush();
                
                $logService = $this->container->get('logService');
                $logService->log($authenticatedUser, 'user_create_community', $community, array());

                $this->get('session')->getFlashBag()->add(
                    'success',
                    $this->get('translator')->trans('community.created', array( '%community%' => $community->getName()))
                );

                return $this->redirect($this->generateUrl('g_switch_community', array('uid' => $this->container->get('uid')->toUId($community->getId()), 'token' => $this->get('form.csrf_provider')->generateCsrfToken('switchCommunity') )));
           
            } else {
               
               $this->get('session')->getFlashBag()->add(
                    'error',
                    $this->get('translator')->trans('information.not.valid', array(), 'errors')
                );

            }

        }

        return $this->render('metaGeneralBundle:Community:create.html.twig', array('form' => $form->createView()));

    }

    public function upgradeAction()
    {

        return $this->render('metaGeneralBundle:Community:upgrade.html.twig');

    }

    public function manageAction()
    {

        $authenticatedUser = $this->getUser();
        $community = $authenticatedUser->getCurrentCommunity();

        if ( !is_null($community) && $community){

            // Is the user manager ?
            $userCommunity = $this->getDoctrine()->getRepository('metaUserBundle:UserCommunity')->findBy(array('user' => $authenticatedUser->getId(), 'community' => $community->getId(), 'deleted_at' => null, 'manager' => true));

            if ($userCommunity){
            
                // Retrieve all the actual managers from the community
                $userCommunityManagers = $this->getDoctrine()->getRepository('metaUserBundle:UserCommunity')->findBy(array('community' => $community->getId(), 'deleted_at' => null, 'manager' => true));

                $targetManagerAsBase64 = array('slug' => 'metaGeneralBundle:Community:addManager', 'external' => false, 'params' => array('guest' => false));
                $targetManagerAsBase64 = base64_encode(json_encode($targetManagerAsBase64));

                return $this->render('metaGeneralBundle:Community:manage.html.twig', array('userCommunityManagers' => $userCommunityManagers, 'targetManagerAsBase64' => $targetManagerAsBase64));

            } else {

                $this->get('session')->getFlashBag()->add(
                  'error',
                  $this->get('translator')->trans('community.not.manager', array( '%community%' => $community->getName()))
                );

                return $this->redirect($this->generateUrl('g_home_community'));
    
            }    

        } else {

                $this->get('session')->getFlashBag()->add(
                  'info',
                  $this->get('translator')->trans('community.not.manageable')
                );

                return $this->redirect($this->generateUrl('g_home_community'));
        }

    }


    /*
     * Add a manager to a community
     */
    public function addManagerAction(Request $request, $mailOrUsername)
    {

        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('addManager', $request->get('token')))
            return $this->redirect($this->generateUrl('g_home_community'));

        $userRepository = $this->getDoctrine()->getRepository('metaUserBundle:User');
        $authenticatedUser = $this->getUser();
        $community = $authenticatedUser->getCurrentCommunity();

        $newManager = $userRepository->findOneByUsernameInCommunity(array('username' => $mailOrUsername, 'community' => $community, 'findGuest' => false));

        // Does the user exist ?
        if ($newManager) {

            // Is he in the community, not as a guest ?
            $userCommunity = $this->getDoctrine()->getRepository('metaUserBundle:UserCommunity')->findOneBy(array('community' => $community->getId(), 'user' => $newManager->getId(), 'deleted_at' => null, 'guest' => false));

            if ($userCommunity && !$userCommunity->isManager()){

                $this->get('session')->getFlashBag()->add(
                    'success',
                    $this->get('translator')->trans('community.manager.added', array( '%user%' => $newManager->getFullName(), '%community%' => $community->getName() ))
                );

                // Sets as Manager
                $userCommunity->setManager(true);

                $em = $this->getDoctrine()->getManager();
                $em->flush();

            } else if ($userCommunity && $userCommunity->isManager()) {

                $this->get('session')->getFlashBag()->add(
                    'warning',
                    $this->get('translator')->trans('community.manager.already', array( '%user%' => $newManager->getFullName(), '%community%' => $community->getName() ))
                );

            } else {

                $this->get('session')->getFlashBag()->add(
                    'error',
                    $this->get('translator')->trans('community.cannot.add')
                );
            }

            
        } else {

            $this->get('session')->getFlashBag()->add(
                'error',
                $this->get('translator')->trans('community.cannot.add')
            );
        }

        return $this->redirect($this->generateUrl('g_manage_community'));

    }

    /*
     * Remove a manager from a community
     */
    public function removeManagerAction(Request $request, $username)
    {

        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('removeManager', $request->get('token')))
            return $this->redirect($this->generateUrl('g_home_community'));

        $userRepository = $this->getDoctrine()->getRepository('metaUserBundle:User');
        $authenticatedUser = $this->getUser();
        $community = $authenticatedUser->getCurrentCommunity();
        $toRemoveManager = $userRepository->findOneByUsernameInCommunity(array('username' => $username, 'community' => $community, 'findGuest' => false));
        $managersCount = $userRepository->countManagersInCommunity(array('community' => $community));

        if ($toRemoveManager && $managersCount > 1 ) {
            
            // Is he manager in the community ?
            $userCommunity = $this->getDoctrine()->getRepository('metaUserBundle:UserCommunity')->findOneBy(array('community' => $community->getId(), 'user' => $toRemoveManager->getId(), 'deleted_at' => null, 'manager' => true));

            if ($userCommunity && $userCommunity->isManager()){

                $this->get('session')->getFlashBag()->add(
                    'success',
                    $this->get('translator')->trans('community.manager.removed', array( '%user%' => $toRemoveManager->getFullName(), '%community%' => $community->getName() ))
                );

                // Sets as Manager
                $userCommunity->setManager(false);

                $em = $this->getDoctrine()->getManager();
                $em->flush();

            } else if ($userCommunity && !$userCommunity->isManager()) {

                $this->get('session')->getFlashBag()->add(
                    'warning',
                    $this->get('translator')->trans('community.manager.not', array( '%user%' => $toRemoveManager->getFullName(), '%community%' => $community->getName() ))
                );

            } else {

                $this->get('session')->getFlashBag()->add(
                    'error',
                    $this->get('translator')->trans('community.cannot.remove')
                );
            }

        } else if ($toRemoveManager){

            $this->get('session')->getFlashBag()->add(
                'error',
                $this->get('translator')->trans('community.manager.atleast', array('%community%' => $community->getName()))
            );

        }

        return $this->redirect($this->generateUrl('g_manage_community'));
    }

}