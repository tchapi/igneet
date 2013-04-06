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

        $ideaRepository = $this->getDoctrine()->getRepository('metaIdeaProfileBundle:Idea');
        $totalIdeas = $ideaRepository->countIdeasInCommunityForUser($community, $authenticatedUser, false);
        
        $projectRepository = $this->getDoctrine()->getRepository('metaStandardProjectProfileBundle:StandardProject');
        $totalProjects = $projectRepository->countProjectsInCommunityForUser($community, $authenticatedUser);
        
        return $this->render('metaGeneralBundle:Home:home.html.twig', array(
          'totalProjects' => $totalProjects,
          'totalIdeas' => $totalIdeas));

    }

}