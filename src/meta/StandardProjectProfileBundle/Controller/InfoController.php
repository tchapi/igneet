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
        $this->fetchProjectAndPreComputeRights($slug);

        if ($this->base == false) 
          return $this->forward('metaStandardProjectProfileBundle:Default:showRestricted', array('slug' => $slug));

        return $this->render('metaStandardProjectProfileBundle:Info:showInfo.html.twig', 
            array('base' => $this->base));
    }

    /*  ####################################################
     *               PROJECT EDITION / ADD USER
     *  #################################################### */

    public function editAction(Request $request, $slug){

      $authenticatedUser = $this->getUser();

        if ($authenticatedUser) {

            $projectRepository = $this->getDoctrine()->getRepository('metaStandardProjectProfileBundle:StandardProject');
            $standardProject = $projectRepository->findOneBySlug($slug);

            if ( $authenticatedUser->isOwning($standardProject) ){

                $objectHasBeenModified = false;

                switch ($request->request->get('name')) {
                    case 'name':
                        $standardProject->setName($request->request->get('value'));
                        $objectHasBeenModified = true;
                        break;
                    case 'headline':
                        $standardProject->setHeadline($request->request->get('value'));
                        $objectHasBeenModified = true;
                        break;
                    case 'about':
                        $standardProject->setAbout($request->request->get('value'));
                        $objectHasBeenModified = true;
                        break;
                    case 'skills':
                        $skillSlugsAsArray = $request->request->get('value');
                        
                        $repository = $this->getDoctrine()->getRepository('metaUserProfileBundle:Skill');
                        $skills = $repository->findSkillsByArrayOfSlugs($skillSlugsAsArray);
                        
                        $standardProject->setNeededSkills($skills);
                        $objectHasBeenModified = true;
                        break;
                }

                $validator = $this->get('validator');
                $errors = $validator->validate($standardProject);

                if ($objectHasBeenModified === true && count($errors) == 0){
                    $standardProject->setUpdatedAt(new \DateTime('now'));
                    $em = $this->getDoctrine()->getManager();
                    $em->flush();
                    $error = null;
                } else {
                    $error = $errors[0]->getMessage(); 
                }

            }

        }

        return new Response($error);

    }

    public function addParticipantOrOwnerAction($slug, $username, $owner)
    {

        $authenticatedUser = $this->getUser();

        if ($authenticatedUser) {

            $projectRepository = $this->getDoctrine()->getRepository('metaStandardProjectProfileBundle:StandardProject');
            $standardProject = $projectRepository->findOneBySlug($slug);

            if ( $authenticatedUser->isOwning($standardProject) ){

                $userRepository = $this->getDoctrine()->getRepository('metaUserProfileBundle:User');
                $newParticipantOrOwner = $userRepository->findOneByUsername($username);

                if ($newParticipantOrOwner && (( !($newParticipantOrOwner->isOwning($standardProject)) && $owner === true) || ( !($newParticipantOrOwner->isParticipatingIn($standardProject)) && $owner !== true))) {

                    if ($owner === true){

                      $newParticipantOrOwner->addProjectsOwned($standardProject);

                      $this->get('session')->setFlash(
                          'success',
                          'The user '.$newParticipantOrOwner->getFirstName().' is now owner of the project "'.$standardProject->getName().'".'
                      );

                    } else {

                      $newParticipantOrOwner->addProjectsParticipatedIn($standardProject);

                      $this->get('session')->setFlash(
                          'success',
                          'The user '.$newParticipantOrOwner->getFirstName().' now participates in the project "'.$standardProject->getName().'".'
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
                    'You are not an owner of the project "'.$standardProject->getName().'".'
                );

            }

        }

        return $this->redirect($this->generateUrl('sp_show_project', array('slug' => $slug)));
    }

    public function removeParticipantOrOwnerAction($slug, $username, $owner)
    {

        $authenticatedUser = $this->getUser();

        if ($authenticatedUser) {

            $projectRepository = $this->getDoctrine()->getRepository('metaStandardProjectProfileBundle:StandardProject');
            $standardProject = $projectRepository->findOneBySlug($slug);

            if ( $authenticatedUser->isOwning($standardProject) ){

                $userRepository = $this->getDoctrine()->getRepository('metaUserProfileBundle:User');
                $toRemoveParticipantOrOwner = $userRepository->findOneByUsername($username);

                if ($toRemoveParticipantOrOwner && (($toRemoveParticipantOrOwner->isOwning($standardProject) && $owner === true) || ($toRemoveParticipantOrOwner->isParticipatingIn($standardProject) && $owner !== true)) ) {

                    if ($toRemoveParticipantOrOwner != $authenticatedUser){

                        if ($owner === true){

                          $toRemoveParticipantOrOwner->removeProjectsOwned($standardProject);

                          $this->get('session')->setFlash(
                              'success',
                              'The user '.$toRemoveParticipantOrOwner->getFirstName().' is no longer owner of the project "'.$standardProject->getName().'".'
                          );

                        } else {

                          $toRemoveParticipantOrOwner->removeProjectsParticipatedIn($standardProject);

                          $this->get('session')->setFlash(
                              'success',
                              'The user '.$toRemoveParticipantOrOwner->getFirstName().' does not participate in the project "'.$standardProject->getName().'" anymore .'
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
                    'You are not an owner of the project "'.$standardProject->getName().'".'
                );

            }

        }

        return $this->redirect($this->generateUrl('sp_show_project', array('slug' => $slug)));
    }

}
