<?php

namespace meta\IdeaBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\File\File,
    Symfony\Component\HttpFoundation\Response,
    Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/*
 * Importing Class definitions
 */
use meta\IdeaBundle\Entity\Idea,
    meta\IdeaBundle\Form\Type\IdeaType,
    meta\IdeaBundle\Entity\Comment\IdeaComment,
    meta\ProjectBundle\Entity\StandardProject,
    meta\ProjectBundle\Entity\Wiki,
    meta\ProjectBundle\Entity\WikiPage;

class DefaultController extends Controller
{
        
    /*
     * Common helper to fetch idea and rights
     */
    public function fetchIdeaAndPreComputeRights($id, $mustBeCreator = false, $mustParticipate = false)
    {

        $repository = $this->getDoctrine()->getRepository('metaIdeaBundle:Idea');
        $idea = $repository->findOneById($id); // We do not enforce community here to be able to switch the user later on

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

        // User is guest in community
        if ($authenticatedUser->isGuestInCurrentCommunity()){
            throw $this->createNotFoundException($this->get('translator')->trans('idea.not.found'));
        }

        // Idea not in community, we might switch 
        if ($community !== $authenticatedUser->getCurrentCommunity()){

            if ($authenticatedUser->belongsTo($community)){
                $this->getUser()->setCurrentCommunity($community);
                $em = $this->getDoctrine()->getManager();
                $em->flush();

                $this->get('session')->setFlash(
                    'info',
                    $this->get('translator')->trans('community.switch', array( '%community%' => $community->getName()))
                );
            } else {
                throw $this->createNotFoundException($this->get('translator')->trans('idea.not.found'));
            }
        }
        
        $targetPictureAsBase64 = array('slug' => 'metaIdeaBundle:Default:edit', 'params' => array('id' => $id ), 'crop' => true);
        $targetProjectizeAsBase64 = array('slug' => 'metaIdeaBundle:Default:projectize', 'params' => array('id' => $id ));
        $targetProposeToCommunityAsBase64 = array('slug' => 'metaIdeaBundle:Default:edit', 'params' => array('id' => $id ));

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
            throw new AccessDeniedHttpException($this->get('translator')->trans('guest.community.cannot.access'), null);
        }

        $repository = $this->getDoctrine()->getRepository('metaIdeaBundle:Idea');

        $totalIdeas = $repository->countIdeasInCommunityForUser($community, $authenticatedUser, $archived);
        $maxPerPage = $this->container->getParameter('listings.number_of_items_per_page');

        if ( ($page-1) * $maxPerPage > $totalIdeas) {
            return $this->redirect($this->generateUrl('i_list_ideas', array('sort' => $sort)));
        }

        $ideas = $repository->findIdeasInCommunityForUser($community, $authenticatedUser, $page, $maxPerPage, $sort, $archived);

        $pagination = array( 'page' => $page, 'totalIdeas' => $totalIdeas);
        return $this->render('metaIdeaBundle:Default:list.html.twig', array('ideas' => $ideas, 'archived' => $archived, 'pagination' => $pagination, 'sort' => $sort));

    }
    
    /*
     * Show an idea
     */
    public function showAction($id, $slug)
    {

        $this->fetchIdeaAndPreComputeRights($id, false, false);
        
        $targetParticipantAsBase64 = array ('slug' => 'metaIdeaBundle:Default:addParticipant', 'external' => false, 'params' => array('id' => $id, 'owner' => false));

        return $this->render('metaIdeaBundle:Info:showInfo.html.twig', 
            array('base' => $this->base,
                'targetParticipantAsBase64' => base64_encode(json_encode($targetParticipantAsBase64)) ));
    }

    /*
     * Show an idea's timeline
     */
    public function showTimelineAction($id, $slug, $page)
    {
        $this->fetchIdeaAndPreComputeRights($id, false, false);

        return $this->render('metaIdeaBundle:Timeline:showTimeline.html.twig', 
            array('base' => $this->base));
    }


    /*
     * Show an idea's concept or knowledge
     */
    public function showConceptOrKnowledgeAction($id, $slug, $type)
    {

        $this->fetchIdeaAndPreComputeRights($id, false, false);
        
        return $this->render('metaIdeaBundle:Info:show' . ucfirst($type) . '.html.twig', 
            array('base' => $this->base));
    }

    /*
     * Create a form for a new idea AND process result when POSTed
     */
    public function createAction(Request $request)
    {
        
        $authenticatedUser = $this->getUser();
        $community = $authenticatedUser->getCurrentCommunity();

        if ($authenticatedUser->isGuestInCurrentCommunity()){
            $this->get('session')->setFlash(
                'error',
                $this->get('translator')->trans('guest.community.cannot.do')
            );
            return $this->redirect($this->generateUrl('i_list_ideas'));
        }

        $idea = new Idea();
        $form = $this->createForm(new IdeaType(), $idea, array('allowCreators' => !is_null($community), 'community' => $community ));

        if ($request->isMethod('POST')) {

            $form->bind($request);

            $textService = $this->container->get('textService');
            $idea->setSlug($textService->slugify($idea->getName()));

            // Prevents users in the private space from creating ideas with other creators
            if ($form->isValid() && ( !is_null($community) || count($idea->getCreators()) === 0 ) ) {

                if ( !$idea->getCreators()->contains($authenticatedUser) ){
                    $idea->addCreator($authenticatedUser);
                }

                if ( !is_null($community) ){
                    $community->addIdea($idea);
                }

                $em = $this->getDoctrine()->getManager();
                $em->persist($idea);
                $em->flush();
                
                $logService = $this->container->get('logService');
                $logService->log($authenticatedUser, 'user_create_idea', $idea, array());

                $this->get('session')->setFlash(
                    'success',
                    $this->get('translator')->trans('idea.created', array( '%idea%' => $idea->getName()))
                );

                return $this->redirect($this->generateUrl('i_show_idea', array('id' => $idea->getId())));
           
            } else {
               
               $this->get('session')->setFlash(
                    'error',
                    $this->get('translator')->trans('information.not.valid', array(), 'errors')
                );

            }

        }

        return $this->render('metaIdeaBundle:Default:create.html.twig', array('form' => $form->createView()));

    }

    /*
     * Edit an idea (via X-Editable)
     */
    public function editAction(Request $request, $id)
    {

        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('edit', $request->get('token')))
            return new Response($this->get('translator')->trans('invalid.token', array(), 'errors'), 400);

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
                                $this->get('translator')->trans('idea.in.community', array( '%community%' => $community->getName())) 
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

            $error = $this->get('translator')->trans('invalid.request', array(), 'errors');

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
                $this->get('translator')->trans('idea.deleted', array( '%idea%' => $this->base['idea']->getName()) )
            );
            
            return $this->redirect($this->generateUrl('i_list_ideas'));

        } else {

            $this->get('session')->setFlash(
                'warning',
                $this->get('translator')->trans('idea.cannot.delete')
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
            
            $this->get('session')->setFlash(
                'success',
                $this->get('translator')->trans($archive?'idea.archived':'idea.recycled', array( '%idea%' => $this->base['idea']->getName())) 
            );
        
            return $this->redirect($this->generateUrl('i_list_ideas'));

        } else {

            $this->get('session')->setFlash(
                'warning',
                $this->get('translator')->trans('idea.cannot.archiveOrRecycle')
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
                $this->get('translator')->trans('idea.picture.reset')
            );

        } else {
    
            $this->get('session')->setFlash(
                'error',
                $this->get('translator')->trans('idea.picture.cannot.reset')
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
                        $newComment = $comment->createProjectComment();
                        $project->addComment($newComment);
                        $em->persist($newComment);
                    }
                }

            $project->setOriginalIdea($this->base['idea']);
            
            $textService = $this->container->get('textService');

            if ($request->request->get('slug') === ""){
                $project->setSlug($textService->slugify($project->getName()));
            } else {
                $project->setSlug(strtolower(trim($request->request->get('slug'))));
            }

            // Community
            $project->setCommunity($this->base['idea']->getCommunity());

            $em->persist($project);

            // Wiki 
            $wiki = new Wiki();

                $project->setWiki($wiki);
                $wikiPageConcept = new WikiPage();
                    $wikiPageConcept->setTitle($this->get('translator')->trans('idea.concept'));
                    $wikiPageConcept->setContent($this->base['idea']->getConceptText());
                    $wikiPageConcept->setSlug($textService->slugify($wikiPageConcept->getTitle()));

                $wikiPageKnowledge = new WikiPage();
                    $wikiPageKnowledge->setTitle($this->get('translator')->trans('idea.knowledge'));
                    $wikiPageKnowledge->setContent($this->base['idea']->getKnowledgeText());
                    $wikiPageKnowledge->setSlug($textService->slugify($wikiPageKnowledge->getTitle()));

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
                $this->get('translator')->trans('idea.transform.into.project')
            );

            return $this->redirect($this->generateUrl('sp_show_project', array('slug' => $project->getSlug())));

        } else {

            $this->get('session')->setFlash(
                'error',
                $this->get('translator')->trans('idea.cannot.transform.into.project')
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
                ->add('text', 'textarea', array('attr' => array('placeholder' => $this->get('translator')->trans('comment.placeholder') )))
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
                        $this->get('translator')->trans('comment.added')
                    );

                    $logService = $this->container->get('logService');
                    $logService->log($this->getUser(), 'user_comment_idea', $this->base['idea'], array());

                } else {

                   $this->get('session')->setFlash(
                        'error',
                        $this->get('translator')->trans('information.not.valid', array(), 'errors')
                    );
                }

                return $this->redirect($this->generateUrl('i_show_idea_timeline', array('id' => $id)));

            } else {

                $route = $this->get('router')->generate('i_show_idea_comment', array('id' => $id));

                return $this->render('metaGeneralBundle:Comment:timelineCommentBox.html.twig', 
                    array('object' => $this->base['idea'], 'route' => $route, 'form' => $form->createView()));

            }

        }

        throw $this->createNotFoundException($this->get('translator')->trans('idea.not.found'));

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
            $newParticipant = $userRepository->findOneByUsernameInCommunity($mailOrUsername, false, $this->base['idea']->getCommunity());

            if ($newParticipant && 
                !($newParticipant->hasCreatedIdea($this->base['idea'])) &&
                !($newParticipant->isParticipatingInIdea($this->base['idea']))
               ) {

                $newParticipant->addIdeasParticipatedIn($this->base['idea']);

                $this->get('session')->setFlash(
                    'success',
                    $this->get('translator')->trans('idea.add.participant', array( '%user%' => $newParticipant->getFullName(), '%idea%' => $this->base['idea']->getName() ))
                );

                $logService = $this->container->get('logService');
                $logService->log($newParticipant, 'user_is_made_participant_idea', $this->base['idea'], array( 'other_user' => array( 'routing' => 'user', 'logName' => $this->getUser()->getLogName(), 'args' => $this->getUser()->getLogArgs()) ));

                $em = $this->getDoctrine()->getManager();
                $em->flush();
                
            } else {

                $this->get('session')->setFlash(
                    'warning',
                    $this->get('translator')->trans('idea.user.already.participant')
                );
            }

        } else {

            $this->get('session')->setFlash(
                'error',
                $this->get('translator')->trans('idea.cannot.add.participant')
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
            $toRemoveParticipant = $userRepository->findOneByUsernameInCommunity($username, false, $this->base['idea']->getCommunity());

            if ($toRemoveParticipant && $toRemoveParticipant->isParticipatingInIdea($this->base['idea']) ) {
                
                $toRemoveParticipant->removeIdeasParticipatedIn($this->base['idea']);

                $this->get('session')->setFlash(
                    'success',
                    $this->get('translator')->trans('idea.remove.participant', array( '%user%' => $toRemoveParticipant->getFullName(), '%idea%' => $this->base['idea']->getName()))
                );

                $em = $this->getDoctrine()->getManager();
                $em->flush();
                
            } else {

                $this->get('session')->setFlash(
                    'error',
                    $this->get('translator')->trans('idea.user.not.participant')
                );
            }

        } else {

            $this->get('session')->setFlash(
                'error',
                $this->get('translator')->trans('idea.cannot.remove.participant')
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
            throw $this->createNotFoundException($this->get('translator')->trans('idea.not.found'));
        }

        // The actually authenticated user now watches the idea with $id
        $repository = $this->getDoctrine()->getRepository('metaIdeaBundle:Idea');
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
                    $this->get('translator')->trans('idea.watch', array('%idea%' => $idea->getName()))
                );

            } else {

                $this->get('session')->setFlash(
                    'warning',
                    $this->get('translator')->trans('idea.already.watch', array('%idea%' => $idea->getName()))
                );

            }

        } else {

           $this->get('session')->setFlash(
                'error',
                $this->get('translator')->trans('idea.cannot.watch')
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
            throw $this->createNotFoundException($this->get('translator')->trans('idea.not.found'));
        }

        // The actually authenticated user now unwatches idea with $id
        $repository = $this->getDoctrine()->getRepository('metaIdeaBundle:Idea');
        $idea = $repository->findOneByIdInCommunityForUser($id, $authenticatedUser->getCurrentCommunity(), $authenticatedUser, false);

        if ($idea){

            if ( $authenticatedUser->isWatchingIdea($idea) ){

                $authenticatedUser->removeIdeasWatched($idea);

                $em = $this->getDoctrine()->getManager();
                $em->flush();

                $this->get('session')->setFlash(
                    'success',
                    $this->get('translator')->trans('idea.unwatch', array('%idea%' => $idea->getName()))
                );

            } else {

                $this->get('session')->setFlash(
                    'warning',
                    $this->get('translator')->trans('idea.not.watching', array('%idea%' => $idea->getName()))
                );

            }

        } else {

           $this->get('session')->setFlash(
                'error',
                $this->get('translator')->trans('idea.cannot.unwatch')
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

        return $this->render('metaIdeaBundle:Default:navbar.html.twig', array('menu' => $menu, 'activeMenu' => $activeMenu, 'id' => $id, 'slug' => $slug));
    }

    /*
     * Output the timeline history
     */
    public function historyAction($id, $page){

        $this->fetchIdeaAndPreComputeRights($id, false, false);

        $format = $this->get('translator')->trans('date.timeline');
        $this->timeframe = array( 'today' => array( 'name' => $this->get('translator')->trans('date.today'), 'data' => array()),
                            'd-1'   => array( 'name' => date($format, strtotime("-1 day")), 'data' => array() ),
                            'd-2'   => array( 'name' => date($format, strtotime("-2 day")), 'data' => array() ),
                            'd-3'   => array( 'name' => date($format, strtotime("-3 day")), 'data' => array() ),
                            'd-4'   => array( 'name' => date($format, strtotime("-4 day")), 'data' => array() ),
                            'd-5'   => array( 'name' => date($format, strtotime("-5 day")), 'data' => array() ),
                            'd-6'   => array( 'name' => date($format, strtotime("-6 day")), 'data' => array() ),
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

          $text = $logService->getHTML($entry);
          $createdAt = date_create($entry->getCreatedAt()->format('Y-m-d H:i:s')); // Not for display

          $history[] = array( 'createdAt' => $createdAt , 'text' => $text, 'groups' => $log_types[$entry->getType()]['filter_groups']);
        
        }

        // Comments
        foreach ($this->base['idea']->getComments() as $comment) {

          $text = $logService->getHTML($comment);
          $createdAt = date_create($comment->getCreatedAt()->format('Y-m-d H:i:s')); // not for display

          $history[] = array( 'createdAt' => $createdAt , 'text' => $text, 'groups' => array('comments') );

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

        return $this->render('metaIdeaBundle:Timeline:timelineHistory.html.twig', 
            array('base' => $this->base,
                  'timeframe' => $this->timeframe,
                  'filter_groups' => array_unique($filter_groups)));

    }

}
