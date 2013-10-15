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
                $logService->log($authenticatedUser, 'user_create_community', $idea, array());

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

}