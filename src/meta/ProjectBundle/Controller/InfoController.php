<?php

namespace meta\ProjectBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response;

use meta\UserBundle\Entity\UserInviteToken;

class InfoController extends BaseController
{
    
    /*
     * Show the info tab
     */
    public function showInfoAction($slug)
    {
        $menu = $this->container->getParameter('standardproject.menu');
        $this->fetchProjectAndPreComputeRights($slug, false, $menu['info']['private']);

        if ($this->base == false) 
          return $this->forward('metaProjectBundle:Default:showRestricted', array('slug' => $slug));

        $targetOwnerAsBase64 = array('slug' => 'metaProjectBundle:Info:addParticipantOrOwner', 'external' => true, 'params' => array('slug' => $slug,'owner' => true));
        $targetParticipantAsBase64 = array('slug' => 'metaProjectBundle:Info:addParticipantOrOwner', 'external' => true, 'params' => array('slug' => $slug,'owner' => false));

        return $this->render('metaProjectBundle:Info:showInfo.html.twig', 
            array('base' => $this->base, 
                  'targetOwnerAsBase64' => base64_encode(json_encode($targetOwnerAsBase64)), 
                  'targetParticipantAsBase64' => base64_encode(json_encode($targetParticipantAsBase64)) ));
    }

    /*
     *
     */
    private function inviteOrPass($mailOrUsername, $project, $owner)
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

      if ($user && !$user->isDeleted()) {

          // If the user is already in the community, might be a guest
          if ($user->belongsTo($community) || $user->isGuestOf($community)){

              return $user;

          // The user has no link with the current community, we must add him as a guest
          } else {

              $community->addGuest($user);
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
              ->setSubject('You\'ve been invited to a project on igneet')
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

    /*
     * Add a participant to a project
     */
    public function addParticipantOrOwnerAction(Request $request, $slug, $mailOrUsername, $owner)
    {

        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('addParticipantOrOwner', $request->get('token')))
            return $this->redirect($this->generateUrl('sp_show_project', array('slug' => $slug)));

        $this->fetchProjectAndPreComputeRights($slug, true, false);

        if ($this->base != false && !is_null($this->base['standardProject']->getCommunity())) {

            // Check legitimity and invite if needed
            $newParticipantOrOwner = $this->inviteOrPass($mailOrUsername, $this->base['standardProject'], $owner);

            if ($newParticipantOrOwner && $newParticipantOrOwner !== 'invited' &&
                !($newParticipantOrOwner->isOwning($this->base['standardProject'])) &&
                ( !($newParticipantOrOwner->isParticipatingIn($this->base['standardProject'])) || $owner === true )
               ) {

                if ($owner === true){

                  $newParticipantOrOwner->addProjectsOwned($this->base['standardProject']);

                  if ($newParticipantOrOwner->isParticipatingIn($this->base['standardProject'])){
                    // We must remove its participation since it is now owner
                    $newParticipantOrOwner->removeProjectsParticipatedIn($this->base['standardProject']);
                  }

                  $logService = $this->container->get('logService');
                  $logService->log($newParticipantOrOwner, 'user_is_made_owner_project', $this->base['standardProject'], array( 'other_user' => array( 'routing' => 'user', 'logName' => $this->getUser()->getLogName(), 'args' => $this->getUser()->getLogArgs()) ));

                  $this->get('session')->setFlash(
                      'success',
                      'The user '.$newParticipantOrOwner->getFullName().' is now owner of the project "'.$this->base['standardProject']->getName().'".'
                  );

                } else {

                  $newParticipantOrOwner->addProjectsParticipatedIn($this->base['standardProject']);

                  $logService = $this->container->get('logService');
                  $logService->log($newParticipantOrOwner, 'user_is_made_participant_project', $this->base['standardProject'], array( 'other_user' => array( 'routing' => 'user', 'logName' => $this->getUser()->getLogName(), 'args' => $this->getUser()->getLogArgs()) ));

                  $this->get('session')->setFlash(
                      'success',
                      'The user '.$newParticipantOrOwner->getFullName().' now participates in the project "'.$this->base['standardProject']->getName().'".'
                  );
                  
                }

                $em = $this->getDoctrine()->getManager();
                $em->flush();
                
            } elseif ( $newParticipantOrOwner === 'invited') {

                $this->get('session')->setFlash(
                    'success',
                    'An invitation was sent to ' . $mailOrUsername . ' on your behalf. He will be added to the project when she/he signs up.'
                );

            } else {

                $this->get('session')->setFlash(
                    'warning',
                    'This user does not exist or is already part of this project.'
                );
            }

        } else {

            $this->get('session')->setFlash(
                'error',
                'You are not allowed to add a participant or owner in this project.'
            );

        }

        return $this->redirect($this->generateUrl('sp_show_project', array('slug' => $slug)));
    }

    /*
     * Remove a participant from a project
     */
    public function removeParticipantOrOwnerAction(Request $request, $slug, $username, $owner)
    {

        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('removeParticipantOrOwner', $request->get('token')))
            return $this->redirect($this->generateUrl('sp_show_project', array('slug' => $slug)));

        $this->fetchProjectAndPreComputeRights($slug, true, false);

        if ($this->base != false && !is_null($this->base['standardProject']->getCommunity())) {

            $userRepository = $this->getDoctrine()->getRepository('metaUserBundle:User');
            $toRemoveParticipantOrOwner = $userRepository->findOneByUsername($username);

            if ($toRemoveParticipantOrOwner && (($toRemoveParticipantOrOwner->isOwning($this->base['standardProject']) && $owner === true) || ($toRemoveParticipantOrOwner->isParticipatingIn($this->base['standardProject']) && $owner !== true)) ) {

                if ($toRemoveParticipantOrOwner != $this->getUser()){

                    if ($owner === true){

                      $toRemoveParticipantOrOwner->removeProjectsOwned($this->base['standardProject']);

                      $this->get('session')->setFlash(
                          'success',
                          'The user '.$toRemoveParticipantOrOwner->getFullName().' is no longer owner of the project "'.$this->base['standardProject']->getName().'".'
                      );

                    } else {

                      $toRemoveParticipantOrOwner->removeProjectsParticipatedIn($this->base['standardProject']);

                      $this->get('session')->setFlash(
                          'success',
                          'The user '.$toRemoveParticipantOrOwner->getFullName().' does not participate in the project "'.$this->base['standardProject']->getName().'" anymore .'
                      );
                      
                    }

                    $em = $this->getDoctrine()->getManager();
                    $em->flush();

                } else {

                    $this->get('session')->setFlash(
                        'error',
                        'You cannot remove yourself from a project.'
                    );

                }
                
            } else {

                $this->get('session')->setFlash(
                    'error',
                    'This user does not exist with this role in the project.'
                );
            }

        } else {

            $this->get('session')->setFlash(
                'error',
                'You are not allowed to remove a participant or owner in this project.'
            );

        }

        return $this->redirect($this->generateUrl('sp_show_project', array('slug' => $slug)));
    }

}
