<?php

namespace meta\IdeaBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\File\File,
    Symfony\Component\HttpFoundation\Response,
    Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException,
    Symfony\Component\Security\Csrf\CsrfToken;

/* SYMFONY 2.8 
use Symfony\Component\Form\Extension\Core\Type\TextType,
    Symfony\Component\Form\Extension\Core\Type\TextareaType;
    */
    
/*
 * Importing Class definitions
 */
use meta\IdeaBundle\Entity\Idea,
    meta\IdeaBundle\Entity\Comment\IdeaComment,
    meta\ProjectBundle\Entity\StandardProject,
    meta\ProjectBundle\Entity\Wiki,
    meta\ProjectBundle\Entity\WikiPage;

class IdeaController extends Controller
{
    
    public function preExecute(Request $request)
    {

        $uid = $request->get('uid');

        $repository = $this->getDoctrine()->getRepository('metaIdeaBundle:Idea');
        $idea = $repository->findOneById($this->container->get('uid')->fromUId($uid)); // We do not enforce community here to be able to switch the user later on

        // Unexistant or deleted
        if (!$idea || $idea->isDeleted()){
          throw $this->createNotFoundException($this->get('translator')->trans('idea.not.found'));
        }

        $authenticatedUser = $this->getUser();
        $community = $idea->getCommunity();

        $isAlreadyWatching = $authenticatedUser->isWatchingIdea($idea);
        $isCreator = $idea->getCreators()->contains($authenticatedUser);
        $isParticipatingIn = $authenticatedUser->isParticipatingInIdea($idea);

        // Idea in private space, but not creator nor participant
        if (is_null($community) && !$isCreator && !$isParticipatingIn){
          throw $this->createNotFoundException($this->get('translator')->trans('idea.not.found'));
        }

        // If we're in a community
        if (!is_null($community)){

            // Check we're not guest
            $userCommunity = $this->getDoctrine()->getRepository('metaUserBundle:UserCommunity')->findOneBy(array('user' => $authenticatedUser->getId(), 'community' => $community->getId()));
        
            if (!$userCommunity || $userCommunity->isGuest()){
                // User is guest in community
                throw $this->createNotFoundException($this->get('translator')->trans('idea.not.found'));
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

                return $this->redirect($this->generateUrl('g_switch_private_space', array('token' => $this->get('security.csrf.token_manager')->getToken('switchCommunity')->getValue(), 'redirect' => true)));
            }

        }

        // Idea not in community, we might switch 
        if ($community !== $authenticatedUser->getCurrentCommunity()){

            if (is_null($community) && ($isCreator || $isParticipatingIn) ){

              $authenticatedUser->setCurrentCommunity(null);
              $em = $this->getDoctrine()->getManager();
              $em->flush();

              $this->get('session')->getFlashBag()->add(
                  'info',
                  $this->get('translator')->trans('private.space.back')
              );

            } else {

                // $community is not null here, for sure
                $userCommunity = $this->getDoctrine()->getRepository('metaUserBundle:UserCommunity')->findOneBy(array('user' => $authenticatedUser->getId(), 'community' => $community->getId(), 'guest' => false));

                if ($userCommunity){
                    $this->getUser()->setCurrentCommunity($community);
                    $em = $this->getDoctrine()->getManager();
                    $em->flush();

                    $this->get('session')->getFlashBag()->add(
                        'info',
                        $this->get('translator')->trans('community.switch', array( '%community%' => $community->getName()))
                    );
                } else {
                    // Impossible to reach ?
                    throw $this->createNotFoundException($this->get('translator')->trans('idea.not.found'));
                }
            }
        }

        // Base objects
        $this->base = array('idea' => $idea,
                            'isAlreadyWatching' => $isAlreadyWatching,
                            'isParticipatingIn' => $isParticipatingIn,
                            'isCreator' => $isCreator,
                            'canEdit' =>  $isCreator || $isParticipatingIn
                           );
        // Is access granted ?
        $this->access = false;
    }

    /*
     * Common helper to compute rights
     */
    public function preComputeRights($options)
    {

        if ( ($options['mustBeCreator'] && !$this->base['isCreator']) || 
             ($options['mustParticipate'] && !$this->base['isParticipatingIn'] && !$this->base['isCreator'])
            ) {
           
            $this->access = false;

        } else {

            $this->access = true;

            $targetPictureAsBase64 = array('slug' => 'metaIdeaBundle:Idea:edit', 'params' => array('uid' => $this->container->get('uid')->toUId($this->base['idea']->getId()) ), 'crop' => true, 'filetypes' => array('png', 'jpg', 'jpeg', 'gif'));
            $targetProposeToCommunityAsBase64 = array('slug' => 'metaIdeaBundle:Idea:edit', 'params' => array('uid' => $this->container->get('uid')->toUId($this->base['idea']->getId()) ));
            $this->base['targetPictureAsBase64'] = base64_encode(json_encode($targetPictureAsBase64));
            $this->base['targetProposeToCommunityAsBase64'] = base64_encode(json_encode($targetProposeToCommunityAsBase64));
        }

    }
    
    /*
     * Show an idea
     */
    public function showAction($uid)
    {

        $this->preComputeRights(array('mustBeCreator' => false, 'mustParticipate' => false));

        if ($this->access != false) {

            $targetParticipantAsBase64 = array('slug' => 'metaIdeaBundle:Idea:addParticipant', 'external' => false, 'params' => array('uid' => $uid, 'owner' => false, 'guest' => false));

            return $this->render('metaIdeaBundle:Idea:showInfo.html.twig', 
                array('base' => $this->base,
                    'targetParticipantAsBase64' => base64_encode(json_encode($targetParticipantAsBase64)) ));

        } else {

            $this->get('session')->getFlashBag()->add(
                'error',
                $this->get('translator')->trans('idea.cannot.access')
            );
            
            return $this->redirect($this->generateUrl('i_list_ideas'));
        }

    }

    /*
     * Show an idea's timeline
     */
    public function showTimelineAction($uid)
    {
        $this->preComputeRights(array('mustBeCreator' => false, 'mustParticipate' => false));

        if ($this->access != false) {

            return $this->render('metaIdeaBundle:Idea:showTimeline.html.twig', 
                array('base' => $this->base));

        } else {

            $this->get('session')->getFlashBag()->add(
                'error',
                $this->get('translator')->trans('idea.cannot.access')
            );
            
            return $this->redirect($this->generateUrl('i_list_ideas'));
        }
    }


    /*
     * Show an idea's content
     */
    public function showContentAction($uid)
    {

        $this->preComputeRights(array('mustBeCreator' => false, 'mustParticipate' => false));
        
        if ($this->access != false) {

            return $this->render('metaIdeaBundle:Idea:showContent.html.twig', 
                array('base' => $this->base));

        } else {

            $this->get('session')->getFlashBag()->add(
                'error',
                $this->get('translator')->trans('idea.cannot.access')
            );
            
            return $this->redirect($this->generateUrl('i_list_ideas'));
        }
    }

    /*
     * Show the settings page
     */
    public function showSettingsAction($uid)
    {

        $this->preComputeRights(array('mustBeCreator' => false, 'mustParticipate' => true));
        
        if ($this->access != false) {

            return $this->render('metaIdeaBundle:Idea:showSettings.html.twig', 
                array('base' => $this->base));

        } else {

            $this->get('session')->getFlashBag()->add(
                'error',
                $this->get('translator')->trans('idea.cannot.access')
            );
            
            return $this->redirect($this->generateUrl('i_list_ideas'));
        }
    }

    /*
     * Edit an idea
     * NEEDS JSON
     */
    public function editAction(Request $request, $uid)
    {

        if (!$this->get('security.csrf.token_manager')->isTokenValid(new CsrfToken('edit', $request->get('token')))) {
            return new Response(
                json_encode(
                    array(
                        'message' => $this->get('translator')->trans('invalid.token', array(), 'errors'))
                    ), 
                400, 
                array('Content-Type'=>'application/json')
            );
        }

        $this->preComputeRights(array('mustBeCreator' => false, 'mustParticipate' => true));

        $error = null;
        $response = null;

        if ($this->access != false) {
        
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
                case 'community':
                    if ($this->base['idea']->getCommunity() === null){ 
                        $repository = $this->getDoctrine()->getRepository('metaGeneralBundle:Community\Community');
                        $community = $repository->findOneById($this->container->get('uid')->fromUId($request->request->get('value')));
                        
                        if (!is_null($community)){
                           
                            $userCommunity = $this->getDoctrine()->getRepository('metaUserBundle:UserCommunity')->findOneBy(array('user' => $this->getUser()->getId(), 'community' => $community->getId(), 'guest' => false));

                            if ($userCommunity){
                                $community->addIdea($this->base['idea']);
                                $this->get('session')->getFlashBag()->add(
                                    'success',
                                    $this->get('translator')->trans('idea.in.community', array( '%community%' => $community->getName())) 
                                );
                                $logService = $this->container->get('logService');
                                $logService->log($this->getUser(), 'idea_enters_community', $this->base['idea'], array( 'community' => array( 'logName' => $community->getLogName(), 'identifier' => $community->getId() ) ) );
                                $objectHasBeenModified = true;
                                $needsRedirect = true;
                            }
                        }
                    }
                    break;
                case 'about':
                    $this->base['idea']->setAbout($request->request->get('value'));
                    $objectHasBeenModified = true;
                    break;
                case 'file': // In this case, no file was passed to upload, so we just pass our way
                    $needsRedirect = true;
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

                    /* We need to update the date manually.
                     * Otherwise, as file is not part of the mapping,
                     * @ORM\PreUpdate will not be called and the file will not be persisted
                     */
                    $this->base['idea']->update();
                    $this->base['idea']->setFile(new File($preparedFilename.".cropped"));

                    $objectHasBeenModified = true;
                    $needsRedirect = true;
                    break;
                case 'content':
                    $this->base['idea']->setContent($request->request->get('value'));
                    $objectHasBeenModified = true;
                    break;
            }

            $validator = $this->get('validator');
            $errors = $validator->validate($this->base['idea']);

            if ($objectHasBeenModified === true && count($errors) == 0){

                $logService = $this->container->get('logService');
                $logService->log($this->getUser(), 'user_update_idea_info', $this->base['idea'], array());

                $em = $this->getDoctrine()->getManager();
                $em->flush();

            } elseif (count($errors) > 0) {

                $error = $this->get('translator')->trans($errors[0]->getMessage());
            }

        } else {

            $error = $this->get('translator')->trans('invalid.request', array(), 'errors');

        }

        // Wraps up and either return a response or redirect
        if (isset($needsRedirect) && $needsRedirect) {

            if (!is_null($error)) {
                $this->get('session')->getFlashBag()->add(
                    'error', $error
                );
            }

            return $this->redirect($this->generateUrl('i_show_idea_info', array('uid' => $uid)));

        } else {
        
            if (!is_null($error)) {
                return new Response(json_encode(array('message' => $error)), 406, array('Content-Type'=>'application/json'));
            }

            return new Response(json_encode($response), 200, array('Content-Type'=>'application/json'));
        }

    }

    /*
     * Delete an idea
     */
    public function deleteAction(Request $request, $uid)
    {

        if (!$this->get('security.csrf.token_manager')->isTokenValid(new CsrfToken('delete', $request->get('token')))) {
            $this->get('session')->getFlashBag()->add(
                'error',
                $this->get('translator')->trans('invalid.token', array(), 'errors')
            );
            return $this->redirect($this->generateUrl('i_show_idea_settings', array('uid' => $uid)));
        }

        $this->preComputeRights(array('mustBeCreator' => true, 'mustParticipate' => false));

        if ($this->access != false) {
        
            $em = $this->getDoctrine()->getManager();
            $this->base['idea']->delete();
            $em->flush();

            $this->get('session')->getFlashBag()->add(
                'success',
                $this->get('translator')->trans('idea.deleted', array( '%idea%' => $this->base['idea']->getName()) )
            );
            
            return $this->redirect($this->generateUrl('i_list_ideas'));

        } else {

            $this->get('session')->getFlashBag()->add(
                'warning',
                $this->get('translator')->trans('idea.cannot.delete')
            );

            return $this->redirect($this->generateUrl('i_show_idea', array('uid' => $uid)));
        }

    }

    /*
     * Archive or recycle (unarchive) an idea
     */
    public function archiveOrRecycleAction(Request $request, $uid, $archive)
    {

        if (!$this->get('security.csrf.token_manager')->isTokenValid(new CsrfToken('archiveOrRecycle', $request->get('token')))) {
            $this->get('session')->getFlashBag()->add(
                'error',
                $this->get('translator')->trans('invalid.token', array(), 'errors')
            );
            return $this->redirect($this->generateUrl('i_show_idea_settings', array('uid' => $uid)));
        }

        $this->preComputeRights(array('mustBeCreator' => false, 'mustParticipate' => false));

        if ($this->access != false) {

            $em = $this->getDoctrine()->getManager();
            
            if ($archive === true){
                $this->base['idea']->archive();
            } else {
                $this->base['idea']->recycle();
            }

            $em->flush();
            
            $this->get('session')->getFlashBag()->add(
                'success',
                $this->get('translator')->trans($archive?'idea.archived':'idea.recycled', array( '%idea%' => $this->base['idea']->getName())) 
            );
        
            return $this->redirect($this->generateUrl('i_list_ideas'));

        } else {

            $this->get('session')->getFlashBag()->add(
                'warning',
                $this->get('translator')->trans('idea.cannot.archiveOrRecycle')
            );

            return $this->redirect($this->generateUrl('i_show_idea_settings', array('uid' => $uid)));
        }

    }

    /*
     * Reset picture of idea
     */
    public function resetPictureAction(Request $request, $uid)
    {

        if (!$this->get('security.csrf.token_manager')->isTokenValid(new CsrfToken('resetPicture', $request->get('token')))) {
            $this->get('session')->getFlashBag()->add(
                'error',
                $this->get('translator')->trans('invalid.token', array(), 'errors')
            );
            return $this->redirect($this->generateUrl('i_show_idea_info', array('uid' => $uid)));
        }

        $this->preComputeRights(array('mustBeCreator' => false, 'mustParticipate' => true));

        if ($this->access != false) {

            $this->base['idea']->setPicture(null);
            $em = $this->getDoctrine()->getManager();
            $em->flush();

            $this->get('session')->getFlashBag()->add(
                'success',
                $this->get('translator')->trans('idea.picture.reset')
            );

        } else {
    
            $this->get('session')->getFlashBag()->add(
                'error',
                $this->get('translator')->trans('idea.picture.cannot.reset')
            );
        }

        return $this->redirect($this->generateUrl('i_show_idea_info', array('uid' => $uid)));

    }

    /*
     * Transform an idea into a project
     */
    public function projectizeAction(Request $request, $uid)
    {

        if (!$this->get('security.csrf.token_manager')->isTokenValid(new CsrfToken('projectize', $request->get('token')))) {
            $this->get('session')->getFlashBag()->add(
                'error',
                $this->get('translator')->trans('invalid.token', array(), 'errors')
            );
            return $this->redirect($this->generateUrl('i_show_idea_settings', array('uid' => $uid)));
        }

        $this->preComputeRights(array('mustBeCreator' => false, 'mustParticipate' => false));

        if ($this->access != false && $this->base['idea']->isArchived() === false){

            $em = $this->getDoctrine()->getManager();

            $project = new StandardProject();
                $project->setName($this->base['idea']->getName());
                $project->setHeadline($this->base['idea']->getHeadline());
                $project->setPicture($this->base['idea']->getRawPicture());
                $project->setCreatedAt($this->base['idea']->getCreatedAt());
                $project->setAbout($this->base['idea']->getAbout()); 

                foreach ($this->base['idea']->getWatchers() as $watcher) {
                    $watcher->addProjectsWatched($project);
                }

                foreach ($this->base['idea']->getParticipants() as $participant) {
                    $participant->addProjectsParticipatedIn($project);
                }

                foreach ($this->base['idea']->getCreators() as $creator) {
                    $creator->addProjectsOwned($project);
                }

                foreach ($this->base['idea']->getComments() as $comment) {
                    if (!$comment->isDeleted()) {
                        $newComment = $comment->createProjectComment();
                        $project->addComment($newComment);
                        $em->persist($newComment);
                    }
                }

            $project->setOriginalIdea($this->base['idea']);
            
            // Community
            $project->setCommunity($this->base['idea']->getCommunity());

            $em->persist($project);

            $textService = $this->container->get('textService');

            // Wiki 
            $wiki = new Wiki();

                $project->setWiki($wiki);
                $wikiPage = new WikiPage();
                    $wikiPage->setTitle($this->get('translator')->trans('idea.content.title'));
                    $wikiPage->setContent($this->base['idea']->getContent());

                $wiki->addPage($wikiPage);

            $em->persist($wiki);
            $em->persist($wikiPage);

            $em->flush();

            // We archive the idea
            $this->base['idea']->archive();

            $logService = $this->container->get('logService');
            $logService->log($this->getUser(), 'user_transform_idea_in_project', $this->base['idea'], array( 'project' => array( 'logName' => $project->getLogName(), 'identifier' => $project->getId() )));
            $logService->log($this->getUser(), 'user_create_project_from_idea', $project, array( 'idea' => array('logName' => $this->base['idea']->getLogName(), 'identifier' => $this->base['idea']->getId() )));

            $this->get('session')->getFlashBag()->add(
                'success',
                $this->get('translator')->trans('idea.projectized')
            );

            return $this->redirect($this->generateUrl('p_show_project', array('uid' => $this->container->get('uid')->toUId($project->getId()))));

        } else {

            $this->get('session')->getFlashBag()->add(
                'error',
                $this->get('translator')->trans('idea.cannot.projectize')
            );

            return $this->redirect($this->generateUrl('i_show_idea_settings', array('uid' => $uid)));
        }


    }

    /*
     * Output the comment form for an idea or add a comment to an idea when POST
     */
    public function addIdeaCommentAction(Request $request, $uid){

        if ($request->isMethod('POST') && !$this->get('security.csrf.token_manager')->isTokenValid(new CsrfToken('comment', $request->get('token')))) {
            return new Response(
                json_encode(
                    array(
                        'message' => $this->get('translator')->trans('invalid.token', array(), 'errors'))
                    ), 
                400, 
                array('Content-Type'=>'application/json')
            );
        }
        
        $comment = new IdeaComment();
        $form = $this->createFormBuilder($comment)
            ->add('text', 'textarea' /* SYMFONY 2.8 TextareaType::class*/, array('required' => false, 'attr' => array('placeholder' => $this->get('translator')->trans('comment.placeholder') )))
            ->getForm();

        // Routed
        if ($request->isMethod('POST')) {

            $this->preComputeRights(array('mustBeCreator' => false, 'mustParticipate' => false));

            if ($this->access != false) {
                
                $comment->setText($request->get('comment'));
                $comment->setUser($this->getUser());

                $errors = $this->get('validator')->validate($comment);

                if (count($errors) == 0) {
                    
                    $comment->linkify();
                    $this->base['idea']->addComment($comment);
                    
                    $em = $this->getDoctrine()->getManager();
                    $em->persist($comment);
                    $em->flush();

                    $logService = $this->container->get('logService');
                    $logService->log($this->getUser(), 'user_comment_idea', $this->base['idea'], array());

                    $renderedComment = $this->renderView('metaGeneralBundle:Log:logItemComment.html.twig', array('comment' => $comment));

                    return new Response(json_encode(array( 'comment' => $renderedComment, 'message' => $this->get('translator')->trans('comment.added'))), 200, array('Content-Type'=>'application/json'));

                } else {

                   return new Response(json_encode(array('message' => $this->get('translator')->trans('information.not.valid', array(), 'errors'))), 400, array('Content-Type'=>'application/json'));
                }
              
            }

        } else { // Non-routed

            $route = $this->get('router')->generate('i_show_idea_comment', array('uid' => $uid, 'token' => $this->get('security.csrf.token_manager')->getToken('comment')->getValue()));

            // We can fetch directly here since it is a non routed action
            $ideaRepository = $this->getDoctrine()->getRepository('metaIdeaBundle:Idea');
            $idea = $ideaRepository->findOneById($this->container->get('uid')->fromUId($uid));

            return $this->render('metaGeneralBundle:Comment:commentBox.html.twig', 
                array('object' => $idea, 'route' => $route, 'form' => $form->createView()));

        }

        throw $this->createNotFoundException($this->get('translator')->trans('idea.not.found'));

    }

    /*
     * Add a participant
     */
    public function addParticipantAction(Request $request, $uid, $mailOrUsername)
    {

        if (!$this->get('security.csrf.token_manager')->isTokenValid(new CsrfToken('addParticipant', $request->get('token')))) {
            $this->get('session')->getFlashBag()->add(
                'error',
                $this->get('translator')->trans('invalid.token', array(), 'errors')
            );
            return $this->redirect($this->generateUrl('i_show_idea_info', array('uid' => $uid)));
        }

        $this->preComputeRights(array('mustBeCreator' => true, 'mustParticipate' => false));

        if ($this->access != false && !is_null($this->base['idea']->getCommunity()) ) {

            $userRepository = $this->getDoctrine()->getRepository('metaUserBundle:User');
            $newParticipant = $userRepository->findOneByUsernameInCommunity(array('username' => $mailOrUsername, 'community' => $this->base['idea']->getCommunity(), 'includeGuests' => false));

            if ($newParticipant && 
                !($newParticipant->hasCreatedIdea($this->base['idea'])) &&
                !($newParticipant->isParticipatingInIdea($this->base['idea']))
               ) {

                $newParticipant->addIdeaParticipatedIn($this->base['idea']);

                $this->get('session')->getFlashBag()->add(
                    'success',
                    $this->get('translator')->trans('idea.add.participant', array( '%user%' => $newParticipant->getFullName(), '%idea%' => $this->base['idea']->getName() ))
                );

                $logService = $this->container->get('logService');
                $logService->log($this->getUser(), 'user_made_user_participant_idea', $this->base['idea'], array( 'other_user' => array('logName' => $newParticipant->getLogName(), 'identifier' => $newParticipant->getUsername()) ));

                $em = $this->getDoctrine()->getManager();
                $em->flush();
                
            } else {

                $this->get('session')->getFlashBag()->add(
                    'warning',
                    $this->get('translator')->trans('idea.user.already.participant')
                );
            }

        } else {

            $this->get('session')->getFlashBag()->add(
                'error',
                $this->get('translator')->trans('idea.cannot.add.participant')
            );

        }

        return $this->redirect($this->generateUrl('i_show_idea_info', array('uid' => $uid)));
    }

    /*
     * Remove a participant
     */ 
    public function removeParticipantAction(Request $request, $uid, $username)
    {

        if (!$this->get('security.csrf.token_manager')->isTokenValid(new CsrfToken('removeParticipant', $request->get('token')))) {
            $this->get('session')->getFlashBag()->add(
                'error',
                $this->get('translator')->trans('invalid.token', array(), 'errors')
            );
            return $this->redirect($this->generateUrl('i_show_idea_info', array('uid' => $uid)));
        }

        $this->preComputeRights(array('mustBeCreator' => true, 'mustParticipate' => false));

        if ($this->access != false && !is_null($this->base['idea']->getCommunity())) {

            $userRepository = $this->getDoctrine()->getRepository('metaUserBundle:User');
            $toRemoveParticipant = $userRepository->findOneByUsernameInCommunity(array('username' => $username, 'community' => $this->base['idea']->getCommunity(), 'includeGuests' => false));

            if ($toRemoveParticipant && $toRemoveParticipant->isParticipatingInIdea($this->base['idea']) ) {
                
                $toRemoveParticipant->removeIdeaParticipatedIn($this->base['idea']);

                $this->get('session')->getFlashBag()->add(
                    'success',
                    $this->get('translator')->trans('idea.remove.participant', array( '%user%' => $toRemoveParticipant->getFullName(), '%idea%' => $this->base['idea']->getName()))
                );

                $em = $this->getDoctrine()->getManager();
                $em->flush();
                
            } else {

                $this->get('session')->getFlashBag()->add(
                    'error',
                    $this->get('translator')->trans('idea.user.not.participant')
                );
            }

        } else {

            $this->get('session')->getFlashBag()->add(
                'error',
                $this->get('translator')->trans('idea.cannot.remove.participant')
            );

        }

        return $this->redirect($this->generateUrl('i_show_idea_info', array('uid' => $uid)));
    }

    /*
     * Authenticated user now watches the idea
     * NEEDS JSON
     */
    public function watchAction(Request $request, $uid)
    {

        if (!$this->get('security.csrf.token_manager')->isTokenValid(new CsrfToken('watch', $request->get('token')))) {
            return new Response(
                json_encode(
                    array(
                        'message' => $this->get('translator')->trans('invalid.token', array(), 'errors'))
                    ), 
                400, 
                array('Content-Type'=>'application/json')
            );
        }

        $authenticatedUser = $this->getUser();
        $community = $authenticatedUser->getCurrentCommunity();

        $this->preComputeRights(array('mustBeCreator' => false, 'mustParticipate' => false));

        if ($this->access != false) {

            if ( !($authenticatedUser->isWatchingIdea($this->base['idea'])) ){

                $authenticatedUser->addIdeaWatched($this->base['idea']);

                $logService = $this->container->get('logService');
                $logService->log($authenticatedUser, 'user_watch_idea', $this->base['idea'], array());

                $em = $this->getDoctrine()->getManager();
                $em->flush();
                
                $rendered = $this->renderView('metaIdeaBundle:Partials:watchers.html.twig', array('idea' => $this->base['idea'], 'isAlreadyWatching' => true));

                $response = array( 'div' => $rendered, 'message' => $this->get('translator')->trans('idea.now.watching', array('%idea%' => $this->base['idea']->getName())));

                return new Response(json_encode($response), 200, array('Content-Type'=>'application/json'));
                
            } else {

                $error = $this->get('translator')->trans('idea.already.watching', array('%idea%' => $this->base['idea']->getName()));

            }

        } else {

           $error = $this->get('translator')->trans('idea.cannot.watch');
        }

        return new Response(json_encode(array('message' => $error)), 406, array('Content-Type'=>'application/json'));
    }

    /*
     * Authenticated user now unwatches the idea
     * NEEDS JSON
     */
    public function unwatchAction(Request $request, $uid)
    {

        if (!$this->get('security.csrf.token_manager')->isTokenValid(new CsrfToken('unwatch', $request->get('token')))) {
            return new Response(
                json_encode(
                    array(
                        'message' => $this->get('translator')->trans('invalid.token', array(), 'errors'))
                    ), 
                400, 
                array('Content-Type'=>'application/json')
            );
        }

        $authenticatedUser = $this->getUser();
        $community = $authenticatedUser->getCurrentCommunity();

        $this->preComputeRights(array('mustBeCreator' => false, 'mustParticipate' => false));

        if ($this->access != false) {

            // The actually authenticated user now unwatches $idea
            if ( $authenticatedUser->isWatchingIdea($this->base['idea']) ){

                $authenticatedUser->removeIdeaWatched($this->base['idea']);

                $em = $this->getDoctrine()->getManager();
                $em->flush();

                $rendered = $this->renderView('metaIdeaBundle:Partials:watchers.html.twig', array('idea' => $this->base['idea'], 'isAlreadyWatching' => false));

                $response = array( 'div' => $rendered, 'message' => $this->get('translator')->trans('idea.unwatching', array('%idea%' => $this->base['idea']->getName())));

                return new Response(json_encode($response), 200, array('Content-Type'=>'application/json'));
                
            } else {

                $error = $this->get('translator')->trans('idea.not.watching', array('%idea%' => $this->base['idea']->getName()));
            }

        } else {

           $error = $this->get('translator')->trans('idea.cannot.watch');

        }

        return new Response(json_encode(array('message' => $error)), 406, array('Content-Type'=>'application/json'));

    }

    /* ********************************************************************* */
    /*                           Non-routed actions                          */
    /*                     are NOT subject to Pre-execute                    */
    /* ********************************************************************* */

    /*
     * Output the navbar for the idea
     */
    public function navbarAction($activeMenu, $uid, $canEdit)
    {
        $menu = $this->container->getParameter('idea.menu');

        return $this->render('metaIdeaBundle:Partials:navbar.html.twig', array('menu' => $menu, 'activeMenu' => $activeMenu, 'uid' => $uid, 'canEdit' => $canEdit));
    }

    /*
     * Output the timeline history
     */
    public function historyAction(Request $request, $uid)
    {

        $lastNotified = $this->getUser()->getLastNotifiedAt();

        $this->timeframe = array( 'today' => array( 'current' => true, 'name' => $this->get('translator')->trans('date.today'), 'data' => array()),
                            'd-1'   => array( 'name' => $this->get('translator')->trans('date.yesterday'), 'data' => array() ),
                            'd-2'   => array( 'name' => $this->get('translator')->trans('date.timeline', array( "%days%" => 2)), 'data' => array() ),
                            'd-3'   => array( 'name' => $this->get('translator')->trans('date.timeline', array( "%days%" => 3)), 'data' => array() ),
                            'd-4'   => array( 'name' => $this->get('translator')->trans('date.timeline', array( "%days%" => 4)), 'data' => array() ),
                            'd-5'   => array( 'name' => $this->get('translator')->trans('date.timeline', array( "%days%" => 5)), 'data' => array() ),
                            'd-6'   => array( 'name' => $this->get('translator')->trans('date.timeline', array( "%days%" => 6)), 'data' => array() ),
                            'before'=> array( 'name' => $this->get('translator')->trans('date.past.week'), 'data' => array() )
                            );

        $repository = $this->getDoctrine()->getRepository('metaGeneralBundle:Log\IdeaLogEntry');
        $entries = $repository->findByIdea($this->base['idea']);

        $history = array();

        // Logs
        $log_types = $this->container->getParameter('general.log_types');
        $logService = $this->container->get('logService');

        foreach ($entries as $entry) {
          
          if ($log_types[$entry->getType()]['displayable'] === false ) continue;

          $text = $logService->getHTML($entry, $lastNotified);
          $createdAt = date_create($entry->getCreatedAt()->format('Y-m-d H:i:s')); // Not for display

          $history[] = array( 'createdAt' => $createdAt , 'text' => $text);
        
        }

        // Comments
        foreach ($this->base['idea']->getComments() as $comment) {

          $text = $logService->getHTML($comment, $lastNotified);
          $createdAt = date_create($comment->getCreatedAt()->format('Y-m-d H:i:s')); // not for display

          $history[] = array( 'createdAt' => $createdAt , 'text' => $text);

        }

        // Sort !
        if (!function_exists('meta\IdeaBundle\Controller\build_sorter')) {
            function build_sorter($key) {
                return function ($a, $b) use ($key) {
                    return $a[$key]>$b[$key];
                };
            }
        }
        usort($history, build_sorter('createdAt'));
        
        // Now put the entries in the correct timeframes
        $startOfToday = date_create('midnight');
        $before = date_create('midnight 6 days ago');
        $filter_groups = array();

        foreach ($history as $historyEntry) {
          
          if ( $historyEntry['createdAt'] > $startOfToday ) {
            
            // Today
            array_unshift($this->timeframe['today']['data'], $historyEntry['text']);

          } else if ( $historyEntry['createdAt'] < $before ) {

            // Before
            array_unshift($this->timeframe['before']['data'], $historyEntry['text']);

          } else {

            // Last seven days, by day
            $days = date_diff($historyEntry['createdAt'], $startOfToday)->days + 1;

            array_unshift($this->timeframe['d-'.$days]['data'], $historyEntry['text']);

          }

        }

        return $this->render('metaGeneralBundle:Timeline:timelineHistory.html.twig', 
            array('base' => $this->base,
                  'timeframe' => $this->timeframe,
                  'filter_groups' => array_unique($filter_groups)));

    }

}
