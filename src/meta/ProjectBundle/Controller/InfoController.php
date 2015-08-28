<?php

namespace meta\ProjectBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response,
    Symfony\Component\Security\Csrf\CsrfToken;

use meta\UserBundle\Entity\UserInviteToken,
    meta\UserBundle\Entity\UserCommunity;

class InfoController extends BaseController
{
    
    /*
     * Show the info tab
     */
    public function showInfoAction($uid)
    {
        $menu = $this->container->getParameter('project.menu');
        $this->preComputeRights(array("mustBeOwner" => false, "mustParticipate" => $menu['info']['private']));

        if ($this->access == false) 
          return $this->forward('metaProjectBundle:Default:showRestricted', array('uid' => $uid));

        $targetOwnerAsBase64 = array('slug' => 'metaProjectBundle:Info:addParticipantOrOwner', 'external' => true, 'params' => array('uid' => $uid,'owner' => true, 'guest' => false));
        $targetParticipantAsBase64 = array('slug' => 'metaProjectBundle:Info:addParticipantOrOwner', 'external' => true, 'params' => array('uid' => $uid,'owner' => false, 'guest' => false));

        return $this->render('metaProjectBundle:Project:showInfo.html.twig', 
            array('base' => $this->base, 
                  'targetOwnerAsBase64' => base64_encode(json_encode($targetOwnerAsBase64)), 
                  'targetParticipantAsBase64' => base64_encode(json_encode($targetParticipantAsBase64)) ));

    }

    /*
     * Add a participant to a project
     */
    public function addParticipantOrOwnerAction(Request $request, $uid, $mailOrUsername, $owner)
    {

        if (!$this->get('security.csrf.token_manager')->isTokenValid(new CsrfToken('addParticipantOrOwner', $request->get('token')))) {
            $this->get('session')->getFlashBag()->add(
                'error',
                $this->get('translator')->trans('invalid.token', array(), 'errors')
            );
            return $this->redirect($this->generateUrl('p_show_project_info', array('uid' => $uid)));
          }

        $this->preComputeRights(array( "mustBeOwner" => true, "mustParticipate" => false));

        if ($this->access != false && !is_null($this->base['project']->getCommunity())) {

            // Check legitimity and invite if needed
            $newParticipantOrOwner = $this->inviteOrPass($mailOrUsername, $this->base['project'], $owner);

            if ($newParticipantOrOwner && $newParticipantOrOwner !== 'invited' &&
                !($newParticipantOrOwner->isOwning($this->base['project'])) &&
                ( !($newParticipantOrOwner->isParticipatingIn($this->base['project'])) || $owner === true )
               ) {

                if ($owner === true){

                  $newParticipantOrOwner->addProjectsOwned($this->base['project']);

                  if ($newParticipantOrOwner->isParticipatingIn($this->base['project'])){
                    // We must remove its participation since it is now owner
                    $newParticipantOrOwner->removeProjectsParticipatedIn($this->base['project']);
                  }

                  $logService = $this->container->get('logService');
                  $logService->log($this->getUser(), 'user_made_user_owner_project', $this->base['project'], array( 'other_user' => array( 'logName' => $newParticipantOrOwner->getLogName(), 'identifier' => $newParticipantOrOwner->getUsername()) ));

                  $this->get('session')->getFlashBag()->add(
                      'success',
                      $this->get('translator')->trans('project.add.owner', array('%user%' =>$newParticipantOrOwner->getFullName(), '%project%' =>$this->base['project']->getName()))
                  );

                } else {

                  $newParticipantOrOwner->addProjectsParticipatedIn($this->base['project']);

                  $logService = $this->container->get('logService');
                  $logService->log($this->getUser(), 'user_made_user_participant_project', $this->base['project'], array( 'other_user' => array( 'logName' => $newParticipantOrOwner->getLogName(), 'identifier' => $newParticipantOrOwner->getUsername()) ));

                  $this->get('session')->getFlashBag()->add(
                      'success',
                      $this->get('translator')->trans('project.add.participant', array('%user%' =>$newParticipantOrOwner->getFullName(), '%project%' =>$this->base['project']->getName()))
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

        return $this->redirect($this->generateUrl('p_show_project_info', array('uid' => $uid)));
    }

    /*
     * Remove a participant from a project
     */
    public function removeParticipantOrOwnerAction(Request $request, $uid, $username, $owner)
    {

        if (!$this->get('security.csrf.token_manager')->isTokenValid(new CsrfToken('removeParticipantOrOwner', $request->get('token')))) {
            $this->get('session')->getFlashBag()->add(
                'error',
                $this->get('translator')->trans('invalid.token', array(), 'errors')
            );
            return $this->redirect($this->generateUrl('p_show_project_info', array('uid' => $uid)));
          }

        $this->preComputeRights(array( "mustBeOwner" => true, "mustParticipate" => false));

        if ($this->access != false && !is_null($this->base['project']->getCommunity())) {

            $userRepository = $this->getDoctrine()->getRepository('metaUserBundle:User');
            $toRemoveParticipantOrOwner = $userRepository->findOneByUsername($username);

            if ($toRemoveParticipantOrOwner && (($toRemoveParticipantOrOwner->isOwning($this->base['project']) && $owner === true) || ($toRemoveParticipantOrOwner->isParticipatingIn($this->base['project']) && $owner !== true)) ) {

                if ($toRemoveParticipantOrOwner != $this->getUser() || 
                    !$this->getUser()->isOwning($this->base['project']) ||
                    $this->getUser()->isOwning($this->base['project']) && $this->base['project']->countOwners() > 1 ){

                    if ($owner === true){

                      $toRemoveParticipantOrOwner->removeProjectsOwned($this->base['project']);

                      $this->get('session')->getFlashBag()->add(
                          'success',
                          $this->get('translator')->trans('project.remove.owner', array('%user%' =>$toRemoveParticipantOrOwner->getFullName(), '%project%' =>$this->base['project']->getName()))
                      );

                    } else {

                      $toRemoveParticipantOrOwner->removeProjectsParticipatedIn($this->base['project']);

                      $this->get('session')->getFlashBag()->add(
                          'success',
                          $this->get('translator')->trans('project.remove.participant', array('%user%' =>$toRemoveParticipantOrOwner->getFullName(), '%project%' =>$this->base['project']->getName()))
                      );
                      
                    }

                    $em = $this->getDoctrine()->getManager();
                    // We need to flush now to be able to count the remaining projects afterwards
                    $em->flush();

                    $userCommunityRepository = $this->getDoctrine()->getRepository('metaUserBundle:UserCommunity');
                    $userCommunity = $userCommunityRepository->findOneBy(array('user' => $toRemoveParticipantOrOwner, 'community' => $this->base['project']->getCommunity()));

                    // If he is guest in the community, we might need to get him out of the community if he isn't in any 
                    // projects anymore
                    if ($userCommunity->isGuest()) {
                      // How many projects has this user in the community ?
                      $projectRepository = $this->getDoctrine()->getRepository('metaProjectBundle:StandardProject');
                      $count = $projectRepository->countProjectsInCommunityForUser(array('user' => $toRemoveParticipantOrOwner, 'community' => $this->base['project']->getCommunity()));
                      
                      if ($count == 0) {
                        // Bye bye 
                        $em->remove($userCommunity);
                        $toRemoveParticipantOrOwner->setCurrentCommunity(null);
                      }
                    }
                    
                    // Flush again
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

        return $this->redirect($this->generateUrl('p_show_project_info', array('uid' => $uid)));
    }

    /*
     * Remove myself as a participant of a project
     */
    public function removeMySelfParticipantAction(Request $request, $uid, $username)
    {

        if (!$this->get('security.csrf.token_manager')->isTokenValid(new CsrfToken('removeMySelfParticipant', $request->get('token')))) {
            $this->get('session')->getFlashBag()->add(
                'error',
                $this->get('translator')->trans('invalid.token', array(), 'errors')
            );
            return $this->redirect($this->generateUrl('p_show_project_info', array('uid' => $uid)));
          }

        $this->preComputeRights(array( "mustBeOwner" => false, "mustParticipate" => true));

        if ($this->access != false && !is_null($this->base['project']->getCommunity())) {

            $userRepository = $this->getDoctrine()->getRepository('metaUserBundle:User');
            $toRemoveParticipantOrOwner = $userRepository->findOneByUsername($username);

            if ($toRemoveParticipantOrOwner && $toRemoveParticipantOrOwner->isParticipatingIn($this->base['project']) ) {

                $toRemoveParticipantOrOwner->removeProjectsParticipatedIn($this->base['project']);

                $this->get('session')->getFlashBag()->add(
                    'success',
                    $this->get('translator')->trans('project.remove.participant', array('%user%' =>$toRemoveParticipantOrOwner->getFullName(), '%project%' =>$this->base['project']->getName()))
                );

                $em = $this->getDoctrine()->getManager();
                // We need to flush now to be able to count the remaining projects afterwards
                $em->flush();

                $userCommunityRepository = $this->getDoctrine()->getRepository('metaUserBundle:UserCommunity');
                $userCommunity = $userCommunityRepository->findOneBy(array('user' => $toRemoveParticipantOrOwner, 'community' => $this->base['project']->getCommunity()));

                // If he is guest in the community, we might need to get him out of the community if he isn't in any 
                // projects anymore
                if ($userCommunity->isGuest()) {
                  // How many projects has this user in the community ?
                  $projectRepository = $this->getDoctrine()->getRepository('metaProjectBundle:StandardProject');
                  $count = $projectRepository->countProjectsInCommunityForUser(array('user' => $toRemoveParticipantOrOwner, 'community' => $this->base['project']->getCommunity()));
                  
                  if ($count == 0) {
                    // Bye bye 
                    $em->remove($userCommunity);
                    $toRemoveParticipantOrOwner->setCurrentCommunity(null);
                  }
                }
                
                // Flush again
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
