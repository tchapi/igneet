<?php

namespace meta\StandardProjectProfileBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response;

class InfoController extends BaseController
{
    
    /*  ####################################################
     *                        INFO
     *  #################################################### */

    public function showInfoAction($slug)
    {
        $this->fetchProjectAndPreComputeRights($slug, false, false);

        if ($this->base == false) 
          return $this->forward('metaStandardProjectProfileBundle:Default:showRestricted', array('slug' => $slug));

        return $this->render('metaStandardProjectProfileBundle:Info:showInfo.html.twig', 
            array('base' => $this->base));
    }

    /*  ####################################################
     *                          ADD USER
     *  #################################################### */

    public function addParticipantOrOwnerAction($slug, $username, $owner)
    {

        $this->fetchProjectAndPreComputeRights($slug, true, false);

        if ($this->base != false) {

            $userRepository = $this->getDoctrine()->getRepository('metaUserProfileBundle:User');
            $newParticipantOrOwner = $userRepository->findOneByUsername($username);

            if ($newParticipantOrOwner && (( !($newParticipantOrOwner->isOwning($this->base['standardProject'])) && $owner === true) || ( !($newParticipantOrOwner->isParticipatingIn($this->base['standardProject'])) && $owner !== true))) {

                if ($owner === true){

                  $newParticipantOrOwner->addProjectsOwned($this->base['standardProject']);

                  $logService = $this->container->get('logService');
                  $logService->log($newParticipantOrOwner, 'user_is_made_owner_project', $this->base['standardProject'], array( 'other_user' => array( 'routing' => 'user', 'logName' => $this->getUser()->getLogName(), 'args' => array( 'username' => $this->getUser()->getUsername())) ));

                  $this->get('session')->setFlash(
                      'success',
                      'The user '.$newParticipantOrOwner->getFirstName().' is now owner of the project "'.$this->base['standardProject']->getName().'".'
                  );

                } else {

                  $newParticipantOrOwner->addProjectsParticipatedIn($this->base['standardProject']);

                  $logService = $this->container->get('logService');
                  $logService->log($newParticipantOrOwner, 'user_is_made_participant_project', $this->base['standardProject'], array( 'other_user' => array( 'routing' => 'user', 'logName' => $this->getUser()->getLogName(), 'args' => array( 'username' => $this->getUser()->getUsername())) ));

                  $this->get('session')->setFlash(
                      'success',
                      'The user '.$newParticipantOrOwner->getFirstName().' now participates in the project "'.$this->base['standardProject']->getName().'".'
                  );
                  
                }

                $em = $this->getDoctrine()->getManager();
                $em->flush();
                
            } else {

                $this->get('session')->setFlash(
                    'error',
                    'This user does not exist or is already part of this project.'
                );
            }

        } else {

            $this->get('session')->setFlash(
                'error',
                'You are not an owner of the project "'.$this->base['standardProject']->getName().'".'
            );

        }


        return $this->redirect($this->generateUrl('sp_show_project', array('slug' => $slug)));
    }

    public function removeParticipantOrOwnerAction($slug, $username, $owner)
    {

        $this->fetchProjectAndPreComputeRights($slug, true, false);

        if ($this->base != false) {

            $userRepository = $this->getDoctrine()->getRepository('metaUserProfileBundle:User');
            $toRemoveParticipantOrOwner = $userRepository->findOneByUsername($username);

            if ($toRemoveParticipantOrOwner && (($toRemoveParticipantOrOwner->isOwning($this->base['standardProject']) && $owner === true) || ($toRemoveParticipantOrOwner->isParticipatingIn($this->base['standardProject']) && $owner !== true)) ) {

                if ($toRemoveParticipantOrOwner != $this->getUser()){

                    if ($owner === true){

                      $toRemoveParticipantOrOwner->removeProjectsOwned($this->base['standardProject']);

                      $this->get('session')->setFlash(
                          'success',
                          'The user '.$toRemoveParticipantOrOwner->getFirstName().' is no longer owner of the project "'.$this->base['standardProject']->getName().'".'
                      );

                    } else {

                      $toRemoveParticipantOrOwner->removeProjectsParticipatedIn($this->base['standardProject']);

                      $this->get('session')->setFlash(
                          'success',
                          'The user '.$toRemoveParticipantOrOwner->getFirstName().' does not participate in the project "'.$this->base['standardProject']->getName().'" anymore .'
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
                'You are not an owner of the project "'.$this->base['standardProject']->getName().'".'
            );

        }

        return $this->redirect($this->generateUrl('sp_show_project', array('slug' => $slug)));
    }

}
