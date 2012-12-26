<?php

namespace meta\StandardProjectProfileBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response;

class BaseController extends Controller
{
    
    public function fetchProjectAndPreComputeRights($slug, $mustBeOwner = false, $mustParticipate = false)
    {

        $repository = $this->getDoctrine()->getRepository('metaStandardProjectProfileBundle:StandardProject');
        $standardProject = $repository->findOneBySlug($slug);

        if (!$standardProject){
          throw $this->createNotFoundException('This project does not exist');
        }

        $authenticatedUser = $this->getUser();

        $isAlreadyWatching = $authenticatedUser && $authenticatedUser->isWatchingProject($standardProject);
        $isOwning = $authenticatedUser && ($authenticatedUser->isOwning($standardProject));
        $isParticipatingIn = $authenticatedUser && ($authenticatedUser->isParticipatingIn($standardProject));
        
        if ( ($mustBeOwner && !$isOwning) || ($mustParticipate && (!$isParticipatingIn && !$isOwning) )) {
          $this->base = false;
        } else {
          $this->base = array('standardProject' => $standardProject,
                              'isAlreadyWatching' => $isAlreadyWatching,
                              'isParticipatingIn' => $isParticipatingIn,
                              'isOwning' => $isOwning,
                              'canEdit' =>  $isOwning || $isParticipatingIn
                            );
        }

    }

    public function showRestrictedAction($slug)
    {
        $this->fetchProjectAndPreComputeRights($slug, false, false);

        return $this->render('metaStandardProjectProfileBundle:Security:restricted.html.twig', 
            array('base' => $this->base));
    }

    public function navbarAction($activeMenu, $slug)
    {
        $menu = $this->container->getParameter('standardproject.menu');

        return $this->render('metaStandardProjectProfileBundle:Base:navbar.html.twig', array('menu' => $menu, 'activeMenu' => $activeMenu, 'slug' => $slug));
    }
}
