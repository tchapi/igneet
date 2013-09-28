<?php

namespace meta\GeneralBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response;

class HomeController extends Controller
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

        return $this->render('metaGeneralBundle:Home:home.html.twig', array(
          'totalProjects' => $totalProjects,
          'totalIdeas' => $totalIdeas,
          'totalUsersAndGuests' => $totalUsersAndGuests));

    }

}