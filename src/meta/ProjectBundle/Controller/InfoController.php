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
    public function showInfoAction($uid)
    {
        $menu = $this->container->getParameter('standardproject.menu');
        $this->fetchProjectAndPreComputeRights($uid, false, $menu['info']['private']);

        if ($this->base == false) 
          return $this->forward('metaProjectBundle:Default:showRestricted', array('uid' => $uid));

        $targetOwnerAsBase64 = array('slug' => 'metaProjectBundle:Info:addParticipantOrOwner', 'external' => true, 'params' => array('uid' => $uid,'owner' => true));
        $targetParticipantAsBase64 = array('slug' => 'metaProjectBundle:Info:addParticipantOrOwner', 'external' => true, 'params' => array('uid' => $uid,'owner' => false));

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

      // No sense in private space
      if (is_null($community)) return null;

      if ($user && !$user->isDeleted()) {

          // If the user is already in the community, might be a guest

          $userCommunity = $this->getDoctrine()->getRepository('metaUserBundle:UserCommunity')->findBy(array('user' => $user->getId(), 'community' => $community->getId()));

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

    /*
     * Add a participant to a project
     */
    public function addParticipantOrOwnerAction(Request $request, $uid, $mailOrUsername, $owner)
    {

        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('addParticipantOrOwner', $request->get('token')))
            return $this->redirect($this->generateUrl('p_show_project', array('uid' => $uid)));

        $this->fetchProjectAndPreComputeRights($uid, true, false);

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
                  $logService->log($newParticipantOrOwner, 'user_is_made_owner_project', $this->base['standardProject'], array( 'other_user' => array( 'logName' => $this->getUser()->getLogName(), 'identifier' => $this->getUser()->getUsername()) ));

                  $this->get('session')->getFlashBag()->add(
                      'success',
                      $this->get('translator')->trans('project.add.owner', array('%user%' =>$newParticipantOrOwner->getFullName(), '%project%' =>$this->base['standardProject']->getName()))
                  );

                } else {

                  $newParticipantOrOwner->addProjectsParticipatedIn($this->base['standardProject']);

                  $logService = $this->container->get('logService');
                  $logService->log($newParticipantOrOwner, 'user_is_made_participant_project', $this->base['standardProject'], array( 'other_user' => array( 'logName' => $this->getUser()->getLogName(), 'identifier' => $this->getUser()->getUsername()) ));

                  $this->get('session')->getFlashBag()->add(
                      'success',
                      $this->get('translator')->trans('project.add.participant', array('%user%' =>$newParticipantOrOwner->getFullName(), '%project%' =>$this->base['standardProject']->getName()))
                  );
                  
                }

                $em = $this->getDoctrine()->getManager();
                $em->flush();
                
            } elseif ( $newParticipantOrOwner === 'invited') {

                $this->get('session')->getFlashBag()->add(
                    'success',
                    $this->get('translator')->trans('project.invitation.mail.sent', array( '%mail%' => $mailOrUsername))
                );

            } else {

                $this->get('session')->getFlashBag()->add(
                    'warning',
                    $this->get('translator')->trans('project.user.already.participant')
                );
            }

        } else {

            $this->get('session')->getFlashBag()->add(
                'error',
                $this->get('translator')->trans('project.cannot.add.participant')
            );

        }

        return $this->redirect($this->generateUrl('p_show_project', array('uid' => $uid)));
    }

    /*
     * Remove a participant from a project
     */
    public function removeParticipantOrOwnerAction(Request $request, $uid, $username, $owner)
    {

        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('removeParticipantOrOwner', $request->get('token')))
            return $this->redirect($this->generateUrl('p_show_project', array('uid' => $uid)));

        $this->fetchProjectAndPreComputeRights($uid, true, false);

        if ($this->base != false && !is_null($this->base['standardProject']->getCommunity())) {

            $userRepository = $this->getDoctrine()->getRepository('metaUserBundle:User');
            $toRemoveParticipantOrOwner = $userRepository->findOneByUsername($username);

            if ($toRemoveParticipantOrOwner && (($toRemoveParticipantOrOwner->isOwning($this->base['standardProject']) && $owner === true) || ($toRemoveParticipantOrOwner->isParticipatingIn($this->base['standardProject']) && $owner !== true)) ) {

                if ($toRemoveParticipantOrOwner != $this->getUser() || 
                    !$this->getUser()->isOwning($this->base['standardProject']) ||
                    $this->getUser()->isOwning($this->base['standardProject']) && $this->base['standardProject']->countOwners() > 1 ){

                    if ($owner === true){

                      $toRemoveParticipantOrOwner->removeProjectsOwned($this->base['standardProject']);

                      $this->get('session')->getFlashBag()->add(
                          'success',
                          $this->get('translator')->trans('project.remove.owner', array('%user%' =>$toRemoveParticipantOrOwner->getFullName(), '%project%' =>$this->base['standardProject']->getName()))
                      );

                    } else {

                      $toRemoveParticipantOrOwner->removeProjectsParticipatedIn($this->base['standardProject']);

                      $this->get('session')->getFlashBag()->add(
                          'success',
                          $this->get('translator')->trans('project.remove.participant', array('%user%' =>$toRemoveParticipantOrOwner->getFullName(), '%project%' =>$this->base['standardProject']->getName()))
                      );
                      
                    }

                    $em = $this->getDoctrine()->getManager();
                    $em->flush();

                } else {

                    $this->get('session')->getFlashBag()->add(
                        'error',
                        $this->get('translator')->trans('project.cannot.remove.self')
                    );

                }
                
            } else {

                $this->get('session')->getFlashBag()->add(
                    'error',
                    $this->get('translator')->trans('project.user.not.participant')
                );
            }

        } else {

            $this->get('session')->getFlashBag()->add(
                'error',
                $this->get('translator')->trans('project.cannot.remove.participant')
            );

        }

        return $this->redirect($this->generateUrl('p_show_project', array('uid' => $uid)));
    }

    /*
     * Remove myself as a participant of a project
     */
    public function removeMySelfParticipantAction(Request $request, $uid, $username)
    {

        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('removeMySelfParticipant', $request->get('token')))
            return $this->redirect($this->generateUrl('p_show_project', array('uid' => $uid)));

        $this->fetchProjectAndPreComputeRights($uid, false, true);

        if ($this->base != false && !is_null($this->base['standardProject']->getCommunity())) {

            $userRepository = $this->getDoctrine()->getRepository('metaUserBundle:User');
            $toRemoveParticipantOrOwner = $userRepository->findOneByUsername($username);

            if ($toRemoveParticipantOrOwner && $toRemoveParticipantOrOwner->isParticipatingIn($this->base['standardProject']) ) {

                $toRemoveParticipantOrOwner->removeProjectsParticipatedIn($this->base['standardProject']);

                $this->get('session')->getFlashBag()->add(
                    'success',
                    $this->get('translator')->trans('project.remove.participant', array('%user%' =>$toRemoveParticipantOrOwner->getFullName(), '%project%' =>$this->base['standardProject']->getName()))
                );

                $em = $this->getDoctrine()->getManager();
                $em->flush();

            } else {

                $this->get('session')->getFlashBag()->add(
                    'error',
                    $this->get('translator')->trans('project.user.not.participant')
                );
            }

        } else {

            $this->get('session')->getFlashBag()->add(
                'error',
                $this->get('translator')->trans('project.cannot.remove.participant')
            );

        }
        
        // Redirect to list
        return $this->redirect($this->generateUrl('p_list_projects'));

    }
}
