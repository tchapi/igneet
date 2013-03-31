<?php

namespace meta\StandardProjectProfileBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response;

class BaseController extends Controller
{
    
    /*
     * Common helper for fetching project and computing rights
     */
    public function fetchProjectAndPreComputeRights($slug, $mustBeOwner = false, $mustParticipate = false)
    {

        $repository = $this->getDoctrine()->getRepository('metaStandardProjectProfileBundle:StandardProject');
        $project = $repository->findOneBySlug($slug); // We do not enforce community here to be able to switch the user later on

        // Unexistant or deleted project
        if (!$project || $project->isDeleted()){
          throw $this->createNotFoundException('This project does not exist');
        }

        $authenticatedUser = $this->getUser();
        $community = $project->getCommunity();

        $isAlreadyWatching = $authenticatedUser && $authenticatedUser->isWatchingProject($project);
        $isOwning = $authenticatedUser && ($authenticatedUser->isOwning($project));
        $isParticipatingIn = $authenticatedUser && ($authenticatedUser->isParticipatingIn($project));
        
        // Project in private space, but not owner nor participant
        if (is_null($community) && !$isOwning && !$isParticipatingIn){
          throw $this->createNotFoundException('This project does not exist');
        }

        // Private project of which I'm not owner nor participant
        if ($project->isPrivate() && !$isOwning && !$isParticipatingIn){
          throw $this->createNotFoundException('This project does not exist');
        }

        // User is guest in community
        if ($authenticatedUser->isGuestInCurrentCommunity() && !$isOwning && !$isParticipatingIn){
            throw $this->createNotFoundException('This project does not exist');
        }

        // Project not in community, we might switch 
        if ($community !== $authenticatedUser->getCurrentCommunity()){

            if ($authenticatedUser->belongsTo($community) || ($authenticatedUser->isGuestOf($community) && ($isOwning || $isParticipatingIn)) ){
                $authenticatedUser->setCurrentCommunity($community);
                $em = $this->getDoctrine()->getManager();
                $em->flush();

                $this->get('session')->setFlash(
                    'info',
                    'You have automatically been switched to the community ' . $community->getName() . '.'
                );
            } else {
                throw $this->createNotFoundException('This project does not exist');
            }
        }

        $targetPictureAsBase64 = array ('slug' => 'metaStandardProjectProfileBundle:Default:edit', 'params' => array('slug' => $slug ), 'crop' => true);
        $targetProposeToCommunityAsBase64 = array('slug' => 'metaStandardProjectProfileBundle:Default:edit', 'params' => array('slug' => $slug));

        // Compute endowed/vacant skills
        $vacantSkills = array();
        foreach ($project->getNeededSkills() as $skill) {
          $found = false;
          foreach ($project->getOwners() as $owner) {
            if ($owner->getSkills()->contains($skill)) {
              $found = true;
              break;
            }
          }
          if (!$found){
            foreach ($project->getParticipants() as $participant) {
              if ($participant->getSkills()->contains($skill)) {
                $found = true;
                break;
              }
            }
          }
          if (!$found) $vacantSkills[] = $skill;
        }

        if ( ($mustBeOwner && !$isOwning) || ($mustParticipate && (!$isParticipatingIn && !$isOwning) )) {
          $this->base = false;
        } else {
          $this->base = array('standardProject' => $project,
                              'isAlreadyWatching' => $isAlreadyWatching,
                              'isParticipatingIn' => $isParticipatingIn,
                              'isOwning' => $isOwning,
                              'targetPictureAsBase64' => base64_encode(json_encode($targetPictureAsBase64)),
                              'targetProposeToCommunityAsBase64' => base64_encode(json_encode($targetProposeToCommunityAsBase64)),
                              'canEdit' =>  $isOwning || $isParticipatingIn,
                              'vacantSkills' => $vacantSkills
                            );
        }

    }

    /* ********************************************************************* */
    /*                           Non-routed actions                          */
    /* ********************************************************************* */

    /*
     * Output a standard restricted partial
     */ 
    public function showRestrictedAction($slug)
    {
        $this->fetchProjectAndPreComputeRights($slug, false, false);

        return $this->render('metaStandardProjectProfileBundle:Security:restricted.html.twig', 
            array('base' => $this->base));
    }

    /*
     * Output the navbar for the idea
     */
    public function navbarAction($activeMenu, $slug)
    {
        $menu = $this->container->getParameter('standardproject.menu');

        return $this->render('metaStandardProjectProfileBundle:Base:navbar.html.twig', array('menu' => $menu, 'activeMenu' => $activeMenu, 'slug' => $slug));
    }
}
