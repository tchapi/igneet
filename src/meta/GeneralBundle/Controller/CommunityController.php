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
            $totalUsersAndGuests = $userRepository->countUsersInCommunity($community);

            return $this->render('metaGeneralBundle:Community:home.html.twig', array(
                        'totalProjects' => $totalProjects,
                        'totalIdeas' => $totalIdeas,
                        'totalUsersAndGuests' => $totalUsersAndGuests));

        } else {
            // Or in your private space ?
            return $this->render('metaGeneralBundle:Community:privateSpace.html.twig');
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

                $targetManagerAsBase64 = array('slug' => 'metaGeneralBundle:Community:addManager', 'params' => array('uid' => $this->container->get('uid')->toUId($community->getId()) ));
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

}