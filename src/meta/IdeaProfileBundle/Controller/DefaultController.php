<?php

namespace meta\IdeaProfileBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\File\File,
    Symfony\Component\HttpFoundation\Response,
    Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/*
 * Importing Class definitions
 */
use meta\IdeaProfileBundle\Entity\Idea,
    meta\IdeaProfileBundle\Form\Type\IdeaType,
    meta\IdeaProfileBundle\Entity\Comment\IdeaComment,
    meta\StandardProjectProfileBundle\Entity\StandardProject,
    meta\StandardProjectProfileBundle\Entity\Wiki,
    meta\StandardProjectProfileBundle\Entity\WikiPage;

class DefaultController extends Controller
{
        
    /*
     * Common helper to fetch idea and rights
     */
    public function fetchIdeaAndPreComputeRights($id, $mustBeCreator = false, $mustParticipate = false)
    {

        $repository = $this->getDoctrine()->getRepository('metaIdeaProfileBundle:Idea');
        $idea = $repository->findOneById($id); // We do not enforce community here to be able to switch the user later on

        // Unexistant or deleted
        if (!$idea || $idea->isDeleted()){
          throw $this->createNotFoundException('This idea does not exist');
        }

        $authenticatedUser = $this->getUser();
        $community = $idea->getCommunity();

        $isAlreadyWatching = $authenticatedUser->isWatchingIdea($idea);
        $isCreator = $idea->getCreators()->contains($authenticatedUser);
        $isParticipatingIn = $authenticatedUser->isParticipatingInIdea($idea);

        // Idea in private space, but not creator nor participant
        if (is_null($community) && !$isCreator && !$isParticipatingIn){
          throw $this->createNotFoundException('This idea does not exist');
        }

        // User is guest in community
        if ($authenticatedUser->isGuestInCurrentCommunity()){
            throw $this->createNotFoundException('This idea does not exist');
        }

        // Idea not in community, we might switch 
        if ($community !== $authenticatedUser->getCurrentCommunity()){

            if ($authenticatedUser->belongsTo($community)){
                $this->getUser()->setCurrentCommunity($community);
                $em = $this->getDoctrine()->getManager();
                $em->flush();

                $this->get('session')->setFlash(
                    'info',
                    'You have automatically been switched to the community ' . $community->getName() . '.'
                );
            } else {
                throw $this->createNotFoundException('This idea does not exist');
            }
        }
        
        $targetPictureAsBase64 = array('slug' => 'metaIdeaProfileBundle:Default:edit', 'params' => array('id' => $id ), 'crop' => true);
        $targetProjectizeAsBase64 = array('slug' => 'metaIdeaProfileBundle:Default:projectize', 'params' => array('id' => $id ));
        $targetProposeToCommunityAsBase64 = array('slug' => 'metaIdeaProfileBundle:Default:edit', 'params' => array('id' => $id ));

        if ( ($mustBeCreator && !$isCreator) || ($mustParticipate && !$isParticipatingIn && !$isCreator) ) {
          $this->base = false;
        } else {
          $this->base = array('idea' => $idea,
                              'isAlreadyWatching' => $isAlreadyWatching,
                              'isParticipatingIn' => $isParticipatingIn,
                              'isCreator' => $isCreator,
                              'targetPictureAsBase64' => base64_encode(json_encode($targetPictureAsBase64)),
                              'targetProjectizeAsBase64' => base64_encode(json_encode($targetProjectizeAsBase64)),
                              'targetProposeToCommunityAsBase64' => base64_encode(json_encode($targetProposeToCommunityAsBase64)),
                              'canEdit' =>  $isCreator || $isParticipatingIn
                            );
        }

    }


    /*
     * List all the ideas in the community
     */
    public function listAction($page, $archived, $sort)
    {

        $authenticatedUser = $this->getUser();
        $community = $authenticatedUser->GetCurrentCommunity();

        // User is guest in community
        if ($authenticatedUser->isGuestInCurrentCommunity()){
            throw new AccessDeniedHttpException('You do not have access to this page as a guest in the community.', null);
        }

        $repository = $this->getDoctrine()->getRepository('metaIdeaProfileBundle:Idea');

        $totalIdeas = $repository->countIdeasInCommunityForUser($community, $authenticatedUser, $archived);
        $maxPerPage = $this->container->getParameter('listings.number_of_items_per_page');

        if ( ($page-1) * $maxPerPage > $totalIdeas) {
            return $this->redirect($this->generateUrl('i_list_ideas', array('sort' => $sort)));
        }

        $ideas = $repository->findIdeasInCommunityForUser($community, $authenticatedUser, $page, $maxPerPage, $sort, $archived);

        $pagination = array( 'page' => $page, 'totalIdeas' => $totalIdeas);
        return $this->render('metaIdeaProfileBundle:Default:list.html.twig', array('ideas' => $ideas, 'archived' => $archived, 'pagination' => $pagination, 'sort' => $sort));

    }
    
    /*
     * Show an idea
     */
    public function showAction($id, $slug)
    {

        $this->fetchIdeaAndPreComputeRights($id, false, false);
        
        $targetParticipantAsBase64 = array ('slug' => 'metaIdeaProfileBundle:Default:addParticipant', 'external' => false, 'params' => array('id' => $id, 'owner' => false));

        return $this->render('metaIdeaProfileBundle:Info:showInfo.html.twig', 
            array('base' => $this->base,
                'targetParticipantAsBase64' => base64_encode(json_encode($targetParticipantAsBase64)) ));
    }

    /*
     * Show an idea's timeline
     */
    public function showTimelineAction($id, $slug, $page)
    {
        $this->fetchIdeaAndPreComputeRights($id, false, false);

        return $this->render('metaIdeaProfileBundle:Timeline:showTimeline.html.twig', 
            array('base' => $this->base));
    }


    /*
     * Show an idea's concept or knowledge
     */
    public function showConceptOrKnowledgeAction($id, $slug, $type)
    {

        $this->fetchIdeaAndPreComputeRights($id, false, false);
        
        return $this->render('metaIdeaProfileBundle:Info:show' . ucfirst($type) . '.html.twig', 
            array('base' => $this->base));
    }

    /*
     * Create a form for a new idea AND process result when POSTed
     */
    public function createAction(Request $request)
    {
        
        $authenticatedUser = $this->getUser();

        if ($authenticatedUser->isGuestInCurrentCommunity()){
            $this->get('session')->setFlash(
                'error',
                'You cannot create ideas in a community in which you are a guest.'
            );
            return $this->redirect($this->generateUrl('i_list_ideas'));
        }

        $idea = new Idea();
        $form = $this->createForm(new IdeaType(), $idea, array('allowCreators' => !is_null($authenticatedUser->getCurrentCommunity()), 'community' => $authenticatedUser->getCurrentCommunity() ));

        if ($request->isMethod('POST')) {

            $form->bind($request);

            $textService = $this->container->get('textService');
            $idea->setSlug($textService->slugify($idea->getName()));

            // Prevents users in the private space from creating ideas with other creators
            if ($form->isValid() && ( !is_null($authenticatedUser->getCurrentCommunity()) || count($idea->getCreators()) === 0 ) ) {

                if ( !$idea->getCreators()->contains($authenticatedUser) ){
                    $idea->addCreator($authenticatedUser);
                }

                if ( !is_null($authenticatedUser->getCurrentCommunity()) ){
                    $authenticatedUser->getCurrentCommunity()->addIdea($idea);
                }

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

    /*
     * Edit an idea (via X-Editable)
     */
    public function editAction(Request $request, $id)
    {

        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('edit', $request->get('token')))
            return new Response('Invalid token', 400);

        $this->fetchIdeaAndPreComputeRights($id, false, true);
        $error = null;
        $response = null;

        if ($this->base != false) {
        
            $objectHasBeenModified = false;

            switch ($request->request->get('name')) {
                case 'name':
                    $this->base['idea']->setName($request->request->get('value'));
                      $textService = $this->container->get('textService');
                      $this->base['idea']->setSlug($textService->slugify($this->base['idea']->getName()));
                    $objectHasBeenModified = true;
                    break;
                case 'headline':
                    $this->base['idea']->setHeadline($request->request->get('value'));
                    $objectHasBeenModified = true;
                    break;
                case 'community':
                    if ($this->base['idea']->getCommunity() === null){ 
                        $repository = $this->getDoctrine()->getRepository('metaGeneralBundle:Community\Community');
                        $community = $repository->findOneById($request->request->get('value'));
                        
                        if ($community && $this->getUser()->belongsTo($community)){
                            $community->addIdea($this->base['idea']);
                            $this->get('session')->setFlash(
                                'success',
                                'This idea is now part of the community ' . $community->getName() . '.'
                            );
                            $logService = $this->container->get('logService');
                            $logService->log($this->getUser(), 'idea_enters_community', $this->base['idea'], array( 'community' => array( 'routing' => 'community', 'logName' => $community->getLogName(), 'args' => null) ) );
                            $objectHasBeenModified = true;
                            $needsRedirect = true;
                        }
                    }
                    break;
                case 'about':
                    $this->base['idea']->setAbout($request->request->get('value'));
                    $deepLinkingService = $this->container->get('meta.twig.deep_linking_extension');
                    $response = $deepLinkingService->convertDeepLinks(
                      $this->container->get('markdown.parser')->transformMarkdown($request->request->get('value'))
                    );
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

                    /* We need to update the date manually.
                     * Otherwise, as file is not part of the mapping,
                     * @ORM\PreUpdate will not be called and the file will not be persisted
                     */
                    $this->base['idea']->setUpdatedAt(new \DateTime('now'));
                    $this->base['idea']->setFile(new File($preparedFilename.".cropped"));

                    $objectHasBeenModified = true;
                    $needsRedirect = true;
                    break;
                case 'concept_text':
                    $this->base['idea']->setConceptText($request->request->get('value'));
                    $deepLinkingService = $this->container->get('meta.twig.deep_linking_extension');
                    $response = $deepLinkingService->convertDeepLinks(
                      $this->container->get('markdown.parser')->transformMarkdown($request->request->get('value'))
                    );
                    $objectHasBeenModified = true;
                    break;
                case 'knowledge_text':
                    $this->base['idea']->setKnowledgeText($request->request->get('value'));
                    $deepLinkingService = $this->container->get('meta.twig.deep_linking_extension');
                    $response = $deepLinkingService->convertDeepLinks(
                      $this->container->get('markdown.parser')->transformMarkdown($request->request->get('value'))
                    );
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

                $error = $errors[0]->getMessage();
            }

        } else {

            $error = 'Invalid request';

        }

        // Wraps up and either return a response or redirect
        if (isset($needsRedirect) && $needsRedirect) {

            if (!is_null($error)) {
                $this->get('session')->setFlash(
                    'error',
                    $error
                );
            }

            return $this->redirect($this->generateUrl('i_show_idea', array('id' => $id)));

        } else {
        
            if (!is_null($error)) {
                return new Response($error, 406);
            }

            return new Response($response);
        }

    }

    /*
     * Delete an idea
     */
    public function deleteAction(Request $request, $id)
    {

        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('delete', $request->get('token')))
            return $this->redirect($this->generateUrl('i_show_idea', array('id' => $id)));

        $this->fetchIdeaAndPreComputeRights($id, true, false);

        if ($this->base != false) {
        
            $em = $this->getDoctrine()->getManager();
            $this->base['idea']->delete();
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
     * Archive or recycle (unarchive) an idea
     */
    public function archiveOrRecycleAction(Request $request, $id, $archive)
    {

        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('archiveOrRecycle', $request->get('token')))
            return $this->redirect($this->generateUrl('i_show_idea', array('id' => $id)));

        $this->fetchIdeaAndPreComputeRights($id, true, false);

        if ($this->base != false) {

            $em = $this->getDoctrine()->getManager();
            
            if ($archive === true){
                $this->base['idea']->archive();
            } else {
                $this->base['idea']->recycle();
            }

            $em->flush();
            
            $action = $archive?'archive':'recycle';
            $this->get('session')->setFlash(
                'success',
                'The idea '.$this->base['idea']->getName().' has been ' . $action . 'd successfully.'
            );
        
            return $this->redirect($this->generateUrl('i_list_ideas'));

        } else {

            $this->get('session')->setFlash(
                'warning',
                'You do not have sufficient privileges to archive or recycle this idea.'
            );

            return $this->redirect($this->generateUrl('i_show_idea', array('id' => $id)));
        }

    }

    /*
     * Reset picture of idea
     */
    public function resetPictureAction(Request $request, $id)
    {

        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('resetPicture', $request->get('token')))
            return $this->redirect($this->generateUrl('i_show_idea', array('id' => $id)));

        $this->fetchIdeaAndPreComputeRights($id, false, true);

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

    /*
     * Transform an idea into a project
     */
    public function projectizeAction(Request $request, $id)
    {

        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('projectize', $request->get('token')))
            return $this->redirect($this->generateUrl('i_show_idea', array('id' => $id)));

        $this->fetchIdeaAndPreComputeRights($id, true, false);

        if ($this->base != false && $this->base['idea']->isArchived() === false){

            $em = $this->getDoctrine()->getManager();

            $project = new StandardProject();
                $project->setName($this->base['idea']->getName());
                $project->setHeadline($this->base['idea']->getHeadline());
                $project->setAbout("Originated from idea #" . $this->base['idea']->getId() . " : " . $this->base['idea']->getName() );
                $project->setPicture($this->base['idea']->getRawPicture());
                $project->setCreatedAt($this->base['idea']->getCreatedAt());

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
                        $newComment = $comment->createStandardProjectComment();
                        $project->addComment($newComment);
                        $em->persist($newComment);
                    }
                }

            $project->setOriginalIdea($this->base['idea']);

            if ($request->request->get('slug') === ""){
                $textService = $this->container->get('textService');
                $project->setSlug($textService->slugify($project->getName()));
            } else {
                $project->setSlug(trim($request->request->get('slug')));
            }

            // Community
            $project->setCommunity($this->base['idea']->getCommunity());

            $em->persist($project);

            // Wiki 
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

            $em->persist($wiki);
            $em->persist($wikiPageConcept);
            $em->persist($wikiPageKnowledge);

            $em->flush();

            // We archive the idea
            $this->base['idea']->archive();

            $logService = $this->container->get('logService');
            $logService->log($this->getUser(), 'user_transform_idea_in_project', $this->base['idea'], array( 'project' => array('routing' => 'project', 'logName' => $project->getLogName(), 'args' => $project->getLogArgs() )));
            $logService->log($this->getUser(), 'user_create_project_from_idea', $project, array( 'idea' => array('routing' => 'idea', 'logName' => $this->base['idea']->getLogName(), 'args' => $this->base['idea']->getLogArgs() )));

            $this->get('session')->setFlash(
                'success',
                'Your idea has successfully been transformed into a project.'
            );

            return $this->redirect($this->generateUrl('sp_show_project', array('slug' => $project->getSlug())));

        } else {

            $this->get('session')->setFlash(
                'error',
                'You are not allowed to transform this idea into the project since you have not created it or it is already archived.'
            );

            return $this->redirect($this->generateUrl('i_show_idea', array('id' => $id)));
        }


    }

    /*
     * Output the comment form for an idea or add a comment to an idea when POST
     */
    public function addIdeaCommentAction(Request $request, $id){

        $this->fetchIdeaAndPreComputeRights($id, false, false);

        if ($this->base != false) {

            $comment = new IdeaComment();
            $form = $this->createFormBuilder($comment)
                ->add('text', 'textarea', array('attr' => array('placeholder' => 'Leave a message ...')))
                ->getForm();

            if ($request->isMethod('POST')) {

                $form->bind($request);

                if ($form->isValid()) {

                    $comment->setUser($this->getUser());
                    $this->base['idea']->addComment($comment);
                    
                    $em = $this->getDoctrine()->getManager();
                    $em->persist($comment);
                    $em->flush();

                    $this->get('session')->setFlash(
                        'success',
                        'Your comment was successfully added.'
                    );

                    $logService = $this->container->get('logService');
                    $logService->log($this->getUser(), 'user_comment_idea', $this->base['idea'], array());

                } else {

                   $this->get('session')->setFlash(
                        'error',
                        'The information you provided does not seem valid.'
                    );
                }

                return $this->redirect($this->generateUrl('i_show_idea_timeline', array('id' => $id)));

            } else {

                $route = $this->get('router')->generate('i_show_idea_comment', array('id' => $id));

                return $this->render('metaGeneralBundle:Comment:timelineCommentBox.html.twig', 
                    array('object' => $this->base['idea'], 'route' => $route, 'form' => $form->createView()));

            }

        }

        throw $this->createNotFoundException('This idea does not exist');

    }

    /*
     * Add a participant
     */
    public function addParticipantAction(Request $request, $id, $mailOrUsername)
    {

        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('addParticipant', $request->get('token')))
            return $this->redirect($this->generateUrl('i_show_idea', array('id' => $id)));

        $this->fetchIdeaAndPreComputeRights($id, false, true);

        if ($this->base != false && !is_null($this->base['idea']->getCommunity()) ) {

            $userRepository = $this->getDoctrine()->getRepository('metaUserBundle:User');
            $newParticipant = $userRepository->findOneByUsernameInCommunity($mailOrUsername, $this->base['idea']->getCommunity());

            if ($newParticipant && 
                !($newParticipant->hasCreatedIdea($this->base['idea'])) &&
                !($newParticipant->isParticipatingInIdea($this->base['idea']))
               ) {

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
                    'warning',
                    'This user does not exist or is already part of this idea.'
                );
            }

        } else {

            $this->get('session')->setFlash(
                'error',
                'You are not allowed to add a participant to this idea.'
            );

        }

        return $this->redirect($this->generateUrl('i_show_idea', array('id' => $id)));
    }

    /*
     * Remove a participant
     */ 
    public function removeParticipantAction(Request $request, $id, $username)
    {

        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('removeParticipant', $request->get('token')))
            return $this->redirect($this->generateUrl('i_show_idea', array('id' => $id)));

        $this->fetchIdeaAndPreComputeRights($id, true, false);

        if ($this->base != false && !is_null($this->base['idea']->getCommunity())) {

            $userRepository = $this->getDoctrine()->getRepository('metaUserBundle:User');
            $toRemoveParticipant = $userRepository->findOneByUsernameInCommunity($username, $this->base['idea']->getCommunity());

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
                'You are not allowed to remove a participant of this idea.'
            );

        }

        return $this->redirect($this->generateUrl('i_show_idea', array('id' => $id)));
    }

    /*
     * Authenticated user now watches the idea
     */
    public function watchAction(Request $request, $id)
    {

        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('watch', $request->get('token')))
            return $this->redirect($this->generateUrl('i_show_idea', array('id' => $id)));

        $authenticatedUser = $this->getUser();

        // User is guest in community
        if ($authenticatedUser->isGuestInCurrentCommunity()){
            throw $this->createNotFoundException('This idea does not exist');
        }

        // The actually authenticated user now watches the idea with $id
        $repository = $this->getDoctrine()->getRepository('metaIdeaProfileBundle:Idea');
        $idea = $repository->findOneByIdInCommunityForUser($id, $authenticatedUser->getCurrentCommunity(), $authenticatedUser, false);

        if ($idea){

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

        } else {

           $this->get('session')->setFlash(
                'error',
                'You cannot watch this idea.'
            ); 

        }

        return $this->redirect($this->generateUrl('i_show_idea', array('id' => $id)));
    }

    /*
     * Authenticated user now unwatches the idea
     */
    public function unwatchAction(Request $request, $id)
    {
        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('unwatch', $request->get('token')))
            return $this->redirect($this->generateUrl('i_show_idea', array('id' => $id)));

        $authenticatedUser = $this->getUser();

        // User is guest in community
        if ($authenticatedUser->isGuestInCurrentCommunity()){
            throw $this->createNotFoundException('This idea does not exist');
        }

        // The actually authenticated user now unwatches idea with $id
        $repository = $this->getDoctrine()->getRepository('metaIdeaProfileBundle:Idea');
        $idea = $repository->findOneByIdInCommunityForUser($id, $authenticatedUser->getCurrentCommunity(), $authenticatedUser, false);

        if ($idea){

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

        } else {

           $this->get('session')->setFlash(
                'error',
                'You cannot unwatch this idea.'
            ); 

        }

        return $this->redirect($this->generateUrl('i_show_idea', array('id' => $id)));
    }

    /* ********************************************************************* */
    /*                           Non-routed actions                          */
    /* ********************************************************************* */

    /*
     * Output the navbar for the idea
     */
    public function navbarAction($activeMenu, $id, $slug)
    {
        $menu = $this->container->getParameter('idea.menu');

        return $this->render('metaIdeaProfileBundle:Default:navbar.html.twig', array('menu' => $menu, 'activeMenu' => $activeMenu, 'id' => $id, 'slug' => $slug));
    }

    /*
     * Output the timeline history
     */
    public function historyAction($id, $page){

        $this->fetchIdeaAndPreComputeRights($id, false, false);

        $this->timeframe = array( 'today' => array( 'name' => 'today', 'data' => array()),
                            'd-1'   => array( 'name' => date("M j", strtotime("-1 day")), 'data' => array() ),
                            'd-2'   => array( 'name' => date("M j", strtotime("-2 day")), 'data' => array() ),
                            'd-3'   => array( 'name' => date("M j", strtotime("-3 day")), 'data' => array() ),
                            'd-4'   => array( 'name' => date("M j", strtotime("-4 day")), 'data' => array() ),
                            'd-5'   => array( 'name' => date("M j", strtotime("-5 day")), 'data' => array() ),
                            'd-6'   => array( 'name' => date("M j", strtotime("-6 day")), 'data' => array() ),
                            'before'=> array( 'name' => 'a week ago', 'data' => array() )
                            );

        $repository = $this->getDoctrine()->getRepository('metaGeneralBundle:Log\IdeaLogEntry');
        $entries = $repository->findByIdea($this->base['idea']);

        $history = array();

        // Logs
        $log_types = $this->container->getParameter('general.log_types');
        $logService = $this->container->get('logService');

        foreach ($entries as $entry) {
          
          if ($log_types[$entry->getType()]['displayable'] === false ) continue;

          $text = $logService->getHTML($entry);
          $createdAt = date_create($entry->getCreatedAt()->format('Y-m-d H:i:s'));

          $history[] = array( 'createdAt' => $createdAt , 'text' => $text, 'groups' => $log_types[$entry->getType()]['filter_groups']);
        
        }

        // Comments
        foreach ($this->base['idea']->getComments() as $comment) {

          $text = $logService->getHTML($comment);
          $createdAt = date_create($comment->getCreatedAt()->format('Y-m-d H:i:s'));

          $history[] = array( 'createdAt' => $createdAt , 'text' => $text, 'groups' => 'comments' );

        }

        // Sort !
        function build_sorter($key) {
            return function ($a, $b) use ($key) {
                return $a[$key]>$b[$key];
            };
        }
        usort($history, build_sorter('createdAt'));
        
        // Now put the entries in the correct timeframes
        $startOfToday = date_create('midnight');
        $before = date_create('midnight 6 days ago');
        $filter_groups = array();

        foreach ($history as $historyEntry) {
          
          if ( $historyEntry['createdAt'] > $startOfToday ) {
            
            // Today
            array_unshift($this->timeframe['today']['data'], array( 'text' => $historyEntry['text'], 'groups' => $historyEntry['groups']) );

          } else if ( $historyEntry['createdAt'] < $before ) {

            // Before
            array_unshift($this->timeframe['before']['data'], array( 'text' => $historyEntry['text'], 'groups' => $historyEntry['groups']) );

          } else {

            // Last seven days, by day
            $days = date_diff($historyEntry['createdAt'], $startOfToday)->days + 1;

            array_unshift($this->timeframe['d-'.$days]['data'], array( 'text' => $historyEntry['text'], 'groups' => $historyEntry['groups']) );

          }

          $filter_groups = array_merge_recursive($filter_groups,$historyEntry['groups']);

        }

        // Filters
        $filter_groups_names = $this->container->getParameter('general.log_filter_groups');
        
        return $this->render('metaIdeaProfileBundle:Timeline:timelineHistory.html.twig', 
            array('base' => $this->base,
                  'timeframe' => $this->timeframe,
                  'filter_groups_names' => $filter_groups_names,
                  'filter_groups' => array_unique($filter_groups)));

    }

}
