<?php

namespace meta\ProjectBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response;

class BaseController extends Controller
{
    
    /*
     * Common helper for fetching project and computing rights
     */
    public function fetchProjectAndPreComputeRights($uid, $mustBeOwner = false, $mustParticipate = false)
    {

        $repository = $this->getDoctrine()->getRepository('metaProjectBundle:StandardProject');
        $project = $repository->findOneById($this->container->get('uid')->fromUId($uid)); // We do not enforce community here to be able to switch the user later on

        // Unexistant or deleted project
        if (!$project || $project->isDeleted()){
          throw $this->createNotFoundException($this->get('translator')->trans('project.not.found'));
        }

        $authenticatedUser = $this->getUser();
        $community = $project->getCommunity();

        $isAlreadyWatching = $authenticatedUser && $authenticatedUser->isWatchingProject($project);
        $isOwning = $authenticatedUser && ($authenticatedUser->isOwning($project));
        $isParticipatingIn = $authenticatedUser && ($authenticatedUser->isParticipatingIn($project));
        
        // Project in private space, but not owner nor participant
        if (is_null($community) && !$isOwning && !$isParticipatingIn){
          throw $this->createNotFoundException($this->get('translator')->trans('project.not.found'));
        }

        // Private project of which I'm not owner nor participant
        if ($project->isPrivate() && !$isOwning && !$isParticipatingIn){
          throw $this->createNotFoundException($this->get('translator')->trans('project.not.found'));
        }

        $userCommunityGuest = $this->getDoctrine()->getRepository('metaUserBundle:UserCommunity')->findBy(array('user' => $authenticatedUser->getId(), 'community' => $authenticatedUser->getCurrentCommunity()->getId(), 'guest' => true));

        // User is guest in community
        if ($userCommunityGuest && !$isOwning && !$isParticipatingIn){
            throw $this->createNotFoundException($this->get('translator')->trans('project.not.found'));
        }

        // Project not in community, we might switch 
        if ($community !== $authenticatedUser->getCurrentCommunity()){

            if (is_null($community) && ($isOwning || $isParticipatingIn) ){

              $authenticatedUser->setCurrentCommunity(null);
              $em = $this->getDoctrine()->getManager();
              $em->flush();

              $this->get('session')->getFlashBag()->add(
                  'info',
                  $this->get('translator')->trans('private.space.back')
              );

            } else {

              $userCommunity = $this->getDoctrine()->getRepository('metaUserBundle:UserCommunity')->findBy(array('user' => $authenticatedUser->getId(), 'community' => $community->getId(), 'guest' => false));
              $userCommunityGuest = $this->getDoctrine()->getRepository('metaUserBundle:UserCommunity')->findBy(array('user' => $authenticatedUser->getId(), 'community' => $community->getId(), 'guest' => true));

              if ($userCommunity || ($userCommunityGuest && ($isOwning || $isParticipatingIn)) ){
                  $authenticatedUser->setCurrentCommunity($community);
                  $em = $this->getDoctrine()->getManager();
                  $em->flush();

                  $this->get('session')->getFlashBag()->add(
                      'info',
                      $this->get('translator')->trans('community.switch', array( '%community%' => $community->getName()))
                  );
              } else {
                  throw $this->createNotFoundException($this->get('translator')->trans('project.not.found'));
              }

            }
        }

        $targetPictureAsBase64 = array ('slug' => 'metaProjectBundle:Default:edit', 'params' => array('uid' => $uid ), 'crop' => true);
        $targetProposeToCommunityAsBase64 = array('slug' => 'metaProjectBundle:Default:edit', 'params' => array('uid' => $uid));

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
    public function showRestrictedAction($uid)
    {
        $this->fetchProjectAndPreComputeRights($uid, false, false);

        return $this->render('metaProjectBundle:Security:restricted.html.twig', 
            array('base' => $this->base));
    }

    /*
     * Output the navbar for the idea
     */
    public function navbarAction($activeMenu, $uid)
    {
        $menu = $this->container->getParameter('standardproject.menu');

        return $this->render('metaProjectBundle:Base:navbar.html.twig', array('menu' => $menu, 'activeMenu' => $activeMenu, 'uid' => $uid));
    }
}
