<?php

namespace meta\IdeaProfileBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\File\File,
    Symfony\Component\HttpFoundation\Response;

/*
 * Importing Class definitions
 */
use meta\IdeaProfileBundle\Entity\Idea,
    meta\IdeaProfileBundle\Form\Type\IdeaType,
    meta\StandardProjectProfileBundle\Entity\StandardProject,
    meta\StandardProjectProfileBundle\Entity\Wiki,
    meta\StandardProjectProfileBundle\Entity\WikiPage;

class DefaultController extends Controller
{
        
    public function fetchIdeaAndPreComputeRights($id, $mustBeCreator = false, $mustParticipate = false)
    {

        $repository = $this->getDoctrine()->getRepository('metaIdeaProfileBundle:Idea');
        $idea = $repository->findOneById($id);

        if (!$idea){
          throw $this->createNotFoundException('This idea does not exist');
        }

        $authenticatedUser = $this->getUser();

        $isAlreadyWatching = $authenticatedUser && $authenticatedUser->isWatchingIdea($idea);
        $isCreator = $authenticatedUser && ($idea->getCreator() == $authenticatedUser);
        $isParticipatingIn = $authenticatedUser && ($authenticatedUser->isParticipatingInIdea($idea));
        
        $targetPictureAsBase64 = array ('slug' => 'metaIdeaProfileBundle:Default:edit', 'params' => array('id' => $id ), 'crop' => true);

        if ( ($mustBeCreator && !$isCreator) || ($mustParticipate && !$isParticipatingIn && !$isCreator) ) {
          $this->base = false;
        } else {
          $this->base = array('idea' => $idea,
                              'isAlreadyWatching' => $isAlreadyWatching,
                              'isParticipatingIn' => $isParticipatingIn,
                              'isCreator' => $isCreator,
                              'targetPictureAsBase64' => base64_encode(json_encode($targetPictureAsBase64)),
                              'canEdit' =>  $isCreator || $isParticipatingIn
                            );
        }

    }

    /*  ####################################################
     *                    IDEA LIST
     *  #################################################### */

    public function listAction($max)
    {

        $repository = $this->getDoctrine()->getRepository('metaIdeaProfileBundle:Idea');

        $ideas = $repository->findRecentlyCreatedIdeas($max);

        return $this->render('metaIdeaProfileBundle:Default:list.html.twig', array('ideas' => $ideas));

    }

    
    /*  ####################################################
     *                        SHOW
     *  #################################################### */

    public function showAction($id)
    {

        $this->fetchIdeaAndPreComputeRights($id, false, false);

        if ($this->base['idea']->isArchived()){
          throw $this->createNotFoundException('This idea is already archived');
        }
        
        $targetParticipantAsBase64 = array ('slug' => 'i_add_participant_to_idea', 'params' => array('id' => $id, 'owner' => false));
        $targetOwnerAsBase64 = array ('slug' => 'i_transfer_idea', 'params' => array('id' => $id, 'owner' => false));

        return $this->render('metaIdeaProfileBundle:Default:show.html.twig', 
            array('base' => $this->base,
                'targetParticipantAsBase64' => base64_encode(json_encode($targetParticipantAsBase64)),
                'targetOwnerAsBase64' => base64_encode(json_encode($targetOwnerAsBase64)) ));
    }

    /*  ####################################################
     *                       IDEA CREATION 
     *  #################################################### */

    /*
     * Create a form for a new project AND process result if POST
     */
    public function createAction(Request $request)
    {
        
        $authenticatedUser = $this->getUser();

        $idea = new Idea();
        $form = $this->createForm(new IdeaType(), $idea);

        if ($request->isMethod('POST')) {

            $form->bind($request);

            if ($form->isValid()) {

                $idea->setCreator($authenticatedUser);
                $em = $this->getDoctrine()->getManager();
                $em->persist($idea);
                $em->flush();
                
                $logService = $this->container->get('logService');
                $logService->log($authenticatedUser, 'user_create_idea', $idea, array());

                $this->get('session')->setFlash(
                    'success',
                    'The new idea '.$idea->getName().' has successfully been created.'
                );

                return $this->redirect($this->generateUrl('i_show_idea', array('id' => $idea->getId())));
           
            } else {
               
               $this->get('session')->setFlash(
                    'error',
                    'The information you provided does not seem valid.'
                );

            }

        }

        return $this->render('metaIdeaProfileBundle:Default:create.html.twig', array('form' => $form->createView()));

    }

    /*  ####################################################
     *                       IDEA EDITION 
     *  #################################################### */

    public function editAction(Request $request, $id){

        $this->fetchIdeaAndPreComputeRights($id, false, true);
        $response = new Response();

        if ($this->base != false) {
        
            $objectHasBeenModified = false;

            switch ($request->request->get('name')) {
                case 'name':
                    $this->base['idea']->setName($request->request->get('value'));
                    $objectHasBeenModified = true;
                    break;
                case 'headline':
                    $this->base['idea']->setHeadline($request->request->get('value'));
                    $objectHasBeenModified = true;
                    break;
                case 'picture':
                    $preparedFilename = trim(__DIR__.'/../../../../web'.$request->request->get('value'));
                    
                    $targ_w = $targ_h = 300;
                    $img_r = imagecreatefromstring(file_get_contents($preparedFilename));
                    $dst_r = ImageCreateTrueColor($targ_w, $targ_h);

                    imagecopyresampled($dst_r,$img_r,0,0,
                        intval($request->request->get('x')),
                        intval($request->request->get('y')),
                        $targ_w, $targ_h, 
                        intval($request->request->get('w')),
                        intval($request->request->get('h')));
                    imagepng($dst_r, $preparedFilename.".cropped");

                    $this->base['idea']->setFile(new File($preparedFilename.".cropped"));

                    $objectHasBeenModified = true;
                    $needsRedirect = true;
                    break;
                case 'concept_text':
                    $this->base['idea']->setConceptText($request->request->get('value'));
                    $deepLinkingService = $this->container->get('meta.twig.deep_linking_extension');
                        $response->setContent($deepLinkingService->convertDeepLinks(
                          $this->container->get('markdown.parser')->transformMarkdown($request->request->get('value')),
                          $this->get('templating'))
                        );
                    $objectHasBeenModified = true;
                    break;
                case 'knowledge_text':
                    $this->base['idea']->setKnowledgeText($request->request->get('value'));
                    $deepLinkingService = $this->container->get('meta.twig.deep_linking_extension');
                        $response->setContent($deepLinkingService->convertDeepLinks(
                          $this->container->get('markdown.parser')->transformMarkdown($request->request->get('value')),
                          $this->get('templating'))
                        );
                    $objectHasBeenModified = true;
                    break;
            }

            $validator = $this->get('validator');
            $errors = $validator->validate($this->base['idea']);

            if ($objectHasBeenModified === true && count($errors) == 0){
                $this->base['idea']->setUpdatedAt(new \DateTime('now'));

                $logService = $this->container->get('logService');
                $logService->log($this->getUser(), 'user_update_idea_info', $this->base['idea'], array());

                $em = $this->getDoctrine()->getManager();
                $em->flush();
            } elseif (count($errors) > 0) {
                $response->setStatusCode(406);
                $response->setContent($errors[0]->getMessage());
            }
        }

        
        if (isset($needsRedirect) && $needsRedirect) {

            if (count($errors) > 0) {
                $this->get('session')->setFlash(
                        'error',
                        $errors[0]->getMessage()
                    );
            }

            return $this->redirect($this->generateUrl('i_show_idea', array('id' => $id)));

        } else {
        
            return $response;
        }

    }

    public function deleteAction($id){

        $this->fetchIdeaAndPreComputeRights($id, true, false);

        if ($this->base != false) {
        
            $em = $this->getDoctrine()->getManager();
            $em->remove($this->base['idea']);
            $em->flush();

            $this->get('session')->setFlash(
                    'success',
                    'The idea '.$this->base['idea']->getName().' has been deleted successfully.'
                );
            
            return $this->redirect($this->generateUrl('i_list_ideas'));

        } else {

            $this->get('session')->setFlash(
                    'warning',
                    'You do not have sufficient privileges to delete this idea.'
                );

            return $this->redirect($this->generateUrl('i_show_idea', array('id' => $id)));
        }

    }

    /*
     * Reset picture of idea
     */
    public function resetPictureAction($id)
    {

        $this->fetchIdeaAndPreComputeRights($id, true, false);

        if ($this->base != false) {

            $this->base['idea']->setPicture(null);
            $em = $this->getDoctrine()->getManager();
            $em->flush();

            $this->get('session')->setFlash(
                        'success',
                        'The picture of this idea has successfully been reset.'
                    );

        } else {
    
            $this->get('session')->setFlash(
                    'error',
                    'You cannot reset the picture for this idea.'
                );
        }

        return $this->redirect($this->generateUrl('i_show_idea', array('id' => $id)));

    }

    public function transferAction($id, $username){

        $this->fetchIdeaAndPreComputeRights($id, true, false);

        $repository = $this->getDoctrine()->getRepository('metaUserProfileBundle:User');
        $newCreator = $repository->findOneByUsername($username);

        if ($this->base != false && $newCreator) {

            $this->base['idea']->setCreator($newCreator);

            $logService = $this->container->get('logService');
            $logService->log($newCreator, 'user_is_made_creator_idea', $this->base['idea'], array( 'other_user' => array( 'routing' => 'user', 'logName' => $this->getUser()->getLogName(), 'args' => $this->getUser()->getLogArgs()) ));

            $em = $this->getDoctrine()->getManager();
            $em->flush();

             $this->get('session')->setFlash(
                    'success',
                    'This idea is now owned by ' . $newCreator->getFullName() . '.'
                );
        
        } else {

            $this->get('session')->setFlash(
                    'error',
                    'You cannot transfer ownership for this idea.'
                );

        }

        return $this->redirect($this->generateUrl('i_show_idea', array('id' => $id)));
    
    }

    public function projectizeAction($id)
    {
        $this->fetchIdeaAndPreComputeRights($id, true, false);

        if ($this->base != false){

            $this->base['idea']->setArchived(true);

            $project = new StandardProject();
                $project->setName($this->base['idea']->getName());
                $project->setHeadline($this->base['idea']->getHeadline());
                $project->setAbout("Originated from idea #" . $this->base['idea']->getId());
                $project->setPicture($this->base['idea']->getAbsolutePicturePath());
                $project->setCreatedAt($this->base['idea']->getCreatedAt());

                foreach ($this->base['idea']->getWatchers() as $watcher) {
                    // $watcher->removeIdeasWatched($this->base['idea']); // We keep the history of the idea and its state
                    $watcher->addProjectsWatched($project);
                }

                $project->setOriginalIdea($this->base['idea']);
                
                $textService = $this->container->get('textService');
                $project->setSlug($textService->slugify($project->getName()));

            $wiki = new Wiki();

                $project->setWiki($wiki);
                $wikiPageConcept = new WikiPage();
                    $wikiPageConcept->setTitle("Concept");
                    $wikiPageConcept->setContent($this->base['idea']->getConceptText());
                    $wikiPageConcept->setSlug('concept');

                $wikiPageKnowledge = new WikiPage();
                    $wikiPageKnowledge->setTitle("Knowledge");
                    $wikiPageKnowledge->setContent($this->base['idea']->getKnowledgeText());
                    $wikiPageKnowledge->setSlug('knowledge');

                $wiki->addPage($wikiPageConcept);
                $wiki->addPage($wikiPageKnowledge);

            $this->getUser()->addProjectsOwned($project);

            $em = $this->getDoctrine()->getManager();
            $em->persist($project);
            $em->persist($wiki);
            $em->persist($wikiPageConcept);
            $em->persist($wikiPageKnowledge);
            $em->flush();

            $logService = $this->container->get('logService');
            $logService->log($this->getUser(), 'user_transform_idea_in_project', $this->base['idea'], array( 'project' => array('routing' => 'project', 'logName' => $project->getLogName(), 'args' => $project->getLogArgs() )));


            $this->get('session')->setFlash(
                    'success',
                    'Your idea has successfully been transformed into a project.'
                );

            return $this->redirect($this->generateUrl('sp_show_project', array('slug' => $project->getSlug())));

        } else {

            $this->get('session')->setFlash(
                    'error',
                    'You are not allowed to transform this idea into the project since you have not created it.'
                );

            return $this->redirect($this->generateUrl('i_show_idea', array('id' => $id)));
        }


    }

    /*  ####################################################
     *                   WATCH / UNWATCH
     *  #################################################### */

    public function watchAction($id)
    {

        $authenticatedUser = $this->getUser();

        // The actually authenticated user now watches the idea with $id
        if ($authenticatedUser) {

            $repository = $this->getDoctrine()->getRepository('metaIdeaProfileBundle:Idea');
            $idea = $repository->findOneById($id);

            if ( !($authenticatedUser->isWatchingIdea($idea)) ){

                $authenticatedUser->addIdeasWatched($idea);

                $logService = $this->container->get('logService');
                $logService->log($authenticatedUser, 'user_watch_idea', $idea, array());


                $em = $this->getDoctrine()->getManager();
                $em->flush();

                $this->get('session')->setFlash(
                    'success',
                    'You are now watching '.$idea->getName().'.'
                );

            } else {

                $this->get('session')->setFlash(
                    'warning',
                    'You are already watching '.$idea->getName().'.'
                );

            }

        }

        return $this->redirect($this->generateUrl('i_show_idea', array('id' => $id)));
    }

    public function unwatchAction($id)
    {
        $authenticatedUser = $this->getUser();

        // The actually authenticated user now follows $user if they are not the same
        if ($authenticatedUser) {

            $repository = $this->getDoctrine()->getRepository('metaIdeaProfileBundle:Idea');
            $idea = $repository->findOneById($id);

            if ( $authenticatedUser->isWatchingIdea($idea) ){

                $authenticatedUser->removeIdeasWatched($idea);

                $em = $this->getDoctrine()->getManager();
                $em->flush();

                $this->get('session')->setFlash(
                    'success',
                    'You are not watching '.$idea->getName().' anymore.'
                );

            } else {

                $this->get('session')->setFlash(
                    'warning',
                    'You are not watching '.$idea->getName().'.'
                );

            }

        }

        return $this->redirect($this->generateUrl('i_show_idea', array('id' => $id)));
    }

    /*  ####################################################
     *                          ADD USER
     *  #################################################### */

    public function addParticipantAction($id, $username)
    {

        $this->fetchIdeaAndPreComputeRights($id, true, false);

        if ($this->base != false) {

            $userRepository = $this->getDoctrine()->getRepository('metaUserProfileBundle:User');
            $newParticipant = $userRepository->findOneByUsername($username);

            if ($newParticipant && ($newParticipant != $this->base['idea']->getCreator()) && !($newParticipant->isParticipatingInIdea($this->base['idea'])) ) {

                $newParticipant->addIdeasParticipatedIn($this->base['idea']);

                $this->get('session')->setFlash(
                  'success',
                  'The user '.$newParticipant->getFullName().' now participates in the idea "'.$this->base['idea']->getName().'".'
                );

                $logService = $this->container->get('logService');
                $logService->log($newParticipant, 'user_is_made_participant_idea', $this->base['idea'], array( 'other_user' => array( 'routing' => 'user', 'logName' => $this->getUser()->getLogName(), 'args' => $this->getUser()->getLogArgs()) ));


                $em = $this->getDoctrine()->getManager();
                $em->flush();
                
            } else {

                $this->get('session')->setFlash(
                    'error',
                    'This user does not exist or is already part of this idea.'
                );
            }

        } else {

            $this->get('session')->setFlash(
                'error',
                'You are not allowed to add a participant to an idea you have not initiated.'
            );

        }


        return $this->redirect($this->generateUrl('i_show_idea', array('id' => $id)));
    }

    public function removeParticipantAction($id, $username)
    {

        $this->fetchIdeaAndPreComputeRights($id, true, false);

        if ($this->base != false) {

            $userRepository = $this->getDoctrine()->getRepository('metaUserProfileBundle:User');
            $toRemoveParticipant = $userRepository->findOneByUsername($username);

            if ($toRemoveParticipant && $toRemoveParticipant->isParticipatingInIdea($this->base['idea']) ) {
                
                $toRemoveParticipant->removeIdeasParticipatedIn($this->base['idea']);

                $this->get('session')->setFlash(
                  'success',
                  'The user '.$toRemoveParticipant->getFullName().' does not participate in the idea "'.$this->base['idea']->getName().'" anymore .'
                );

                $em = $this->getDoctrine()->getManager();
                $em->flush();
                
            } else {

                $this->get('session')->setFlash(
                    'error',
                    'This user does not exist with this role in the idea.'
                );
            }

        } else {

            $this->get('session')->setFlash(
                'error',
                'You are not the creator of the idea "'.$this->base['idea']->getName().'".'
            );

        }

        return $this->redirect($this->generateUrl('i_show_idea', array('id' => $id)));
    }
}
