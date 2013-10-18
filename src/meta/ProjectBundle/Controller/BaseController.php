<?php

namespace meta\ProjectBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response;

use meta\UserBundle\Entity\UserInviteToken,
    meta\UserBundle\Entity\UserCommunity;
    
class BaseController extends Controller
{
    
    public function preExecute(Request $request)
    {

        $uid = $request->get('uid');

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

        if (!is_null($community)){
          
          $userCommunityGuest = $this->getDoctrine()->getRepository('metaUserBundle:UserCommunity')->findBy(array('user' => $authenticatedUser->getId(), 'community' => $community->getId(), 'guest' => true, 'deleted_at' => null));
        
          // User is guest in community
          if ($userCommunityGuest && !$isOwning && !$isParticipatingIn){
              throw $this->createNotFoundException($this->get('translator')->trans('project.not.found'));
          }

          // And that community is valid ?
          if ( !($community->isValid()) ){

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

              $userCommunity = $this->getDoctrine()->getRepository('metaUserBundle:UserCommunity')->findBy(array('user' => $authenticatedUser->getId(), 'community' => $community->getId(), 'guest' => false, 'deleted_at' => null));
              $userCommunityGuest = $this->getDoctrine()->getRepository('metaUserBundle:UserCommunity')->findBy(array('user' => $authenticatedUser->getId(), 'community' => $community->getId(), 'guest' => true, 'deleted_at' => null));

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

        // Base objects
        $this->base = array('project' => $project,
                            'isAlreadyWatching' => $isAlreadyWatching,
                            'isParticipatingIn' => $isParticipatingIn,
                            'isOwning' => $isOwning,
                            'canEdit' =>  $isOwning || $isParticipatingIn,
                            'vacantSkills' => $vacantSkills
                           );

        // Is access granted ?
        $this->access = false;

    }

    /*
     * Common helper for fetching project and computing rights
     */
    public function preComputeRights($options) // $uid, $mustBeOwner = false, $mustParticipate = false)
    {

        if ( ($options['mustBeOwner'] && !$this->base['isOwning']) || 
             ($options['mustParticipate'] && !$this->base['isParticipatingIn'] && !$this->base['isOwning'])
            ) {

          $this->access = false;

        } else {

          $this->access = true;

          $targetPictureAsBase64 = array ('slug' => 'metaProjectBundle:Project:edit', 'params' => array('uid' => $this->container->get('uid')->toUId($this->base['project']->getId()) ), 'crop' => true);
          $targetProposeToCommunityAsBase64 = array('slug' => 'metaProjectBundle:Project:edit', 'params' => array('uid' => $this->container->get('uid')->toUId($this->base['project']->getId())));
          $this->base['targetPictureAsBase64'] = base64_encode(json_encode($targetPictureAsBase64));
          $this->base['targetProposeToCommunityAsBase64'] = base64_encode(json_encode($targetProposeToCommunityAsBase64));

        }

    }

    /* ********************************************************************* */
    /*                           Non-routed actions                          */
    /*                     are NOT subject to Pre-execute                    */
    /* ********************************************************************* */

    /*
     * Output the navbar for the idea
     */
    public function navbarAction($activeMenu, $uid)
    {
        $menu = $this->container->getParameter('project.menu');

        return $this->render('metaProjectBundle:Base:navbar.html.twig', array('menu' => $menu, 'activeMenu' => $activeMenu, 'uid' => $uid));
    }

    /*
     * Output a standard restricted partial
     */ 
    public function showRestrictedAction($uid)
    {
        return $this->render('metaProjectBundle:Security:restricted.html.twig', 
            array('base' => $this->base)); // We need to pass $base because the restricted template is a child of a template that needs it
    }


    /*
     * Invites a user in a projet
     */
    protected function inviteOrPass($mailOrUsername, $project, $owner)
    {

      $authenticatedUser = $this->getUser();
      $isEmail = filter_var($mailOrUsername, FILTER_VALIDATE_EMAIL);

      // It might be a user already
      $repository = $this->getDoctrine()->getRepository('metaUserBundle:User');
      $em = $this->getDoctrine()->getManager();

      if($isEmail){
          $user = $repository->findOneByEmail($mailOrUsername);
      } else {
          $user = $repository->findOneByUsername($mailOrUsername);
      }
      
      $community = $project->getCommunity();

      // No rationale for that in private space
      if (is_null($community)) return null;

      if ($user && !$user->isDeleted()) {

          // If the user is already in the community, might be a guest

          $userCommunity = $this->getDoctrine()->getRepository('metaUserBundle:UserCommunity')->findBy(array('user' => $user->getId(), 'community' => $community->getId(), 'deleted_at' => null));

          if ($userCommunity){

              return $user;

          // The user has no link with the current community, we must add him as a guest
          } else {

              $userCommunity = new UserCommunity();
              $userCommunity->setUser($user);
              $userCommunity->setCommunity($community);

              $userCommunity->setGuest(true);
              $em->persist($userCommunity);

              $em->flush();

              return $user;

          }

      } elseif ($isEmail) {

          // Create token linked to email
          $token = new UserInviteToken($authenticatedUser, $mailOrUsername, $community, 'guest', $project, $owner?'owner':'participant');
          $em->persist($token);
          $em->flush();

          // Sends mail to invitee
          $message = \Swift_Message::newInstance()
              ->setSubject($this->get('translator')->trans('project.invitation.mail.subject'))
              ->setFrom($this->container->getParameter('mailer_from'))
              ->setReplyTo($authenticatedUser->getEmail())
              ->setTo($mailOrUsername)
              ->setBody(
                  $this->renderView(
                      'metaUserBundle:Mail:invite.mail.html.twig',
                      array('user' => $authenticatedUser, 'inviteToken' => $token->getToken(), 'invitee' => null, 'community' => null, 'project' => $project )
                  ), 'text/html'
              )
          ;
          $this->get('mailer')->send($message);

          return 'invited';

      } else {

          return null;
      }

    }

}
