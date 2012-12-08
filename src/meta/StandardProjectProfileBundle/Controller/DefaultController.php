<?php

namespace meta\StandardProjectProfileBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response;

/*
 * Importing Class definitions
 */
use meta\StandardProjectProfileBundle\Entity\CommonList,
    meta\StandardProjectProfileBundle\Entity\CommonListItem;

class DefaultController extends Controller
{
    
    public function fetchProjectAndPreComputeRights($slug, $mustBeOwner = false, $mustParticipate = false)
    {

        $repository = $this->getDoctrine()->getRepository('metaStandardProjectProfileBundle:StandardProject');
        $standardProject = $repository->findOneBySlug($slug);

        if (!$standardProject){
          throw $this->createNotFoundException('This project does not exist');
        }

        $authenticatedUser = $this->getUser();

        $isAlreadyWatching = $authenticatedUser && $authenticatedUser->isWatching($standardProject);
        $isOwning = $authenticatedUser && ($authenticatedUser->isOwning($standardProject));
        $isParticipatingIn = $isOwning || ($authenticatedUser && ($authenticatedUser->isParticipatingIn($standardProject)));
        
        if ( ($mustBeOwner && !$isOwning) || ($mustParticipate && !$isParticipatingIn)) {
          $this->base = false;
        } else {
          $this->base = array('standardProject' => $standardProject,
                              'isAlreadyWatching' => $isAlreadyWatching,
                              'isParticipatingIn' => $isParticipatingIn,
                              'isOwning' => $isOwning
                            );
        }

    }

    public function showRestrictedAction($slug)
    {
        $this->fetchProjectAndPreComputeRights($slug, false, false);

        return $this->render('metaStandardProjectProfileBundle:Security:restricted.html.twig', 
            array('base' => $this->base));
    }

    public function navbarAction($activeMenu, $slug)
    {
        $menu = $this->container->getParameter('standardproject.menu');

        return $this->render('metaStandardProjectProfileBundle:Default:navbar.html.twig', array('menu' => $menu, 'activeMenu' => $activeMenu, 'slug' => $slug));
    }

    /*  ####################################################
     *                        TIMELINE
     *  #################################################### */

    public function showTimelineAction($slug, $page)
    {
        $this->fetchProjectAndPreComputeRights($slug, false, true);

        if ($this->base == false) 
          return $this->forward('metaStandardProjectProfileBundle:Default:showRestricted', array('slug' => $slug));

        return $this->render('metaStandardProjectProfileBundle:Default:Timeline/showTimeline.html.twig', 
            array('base' => $this->base));
    }

    /*  ####################################################
     *                        INFO
     *  #################################################### */

    public function showInfoAction($slug)
    {
        $this->fetchProjectAndPreComputeRights($slug);

        if ($this->base == false) 
          return $this->forward('metaStandardProjectProfileBundle:Default:showRestricted', array('slug' => $slug));

        return $this->render('metaStandardProjectProfileBundle:Default:Info/showInfo.html.twig', 
            array('base' => $this->base));
    }


    /*  ####################################################
     *                    Common LISTS
     *  #################################################### */

    public function showCommonListHomeAction($slug)
    {
        $this->fetchProjectAndPreComputeRights($slug, false, true);

        if ($this->base == false) 
          return $this->forward('metaStandardProjectProfileBundle:Default:showRestricted', array('slug' => $slug));

        // Now we find the first alphabetical todo
        $repository = $this->getDoctrine()->getRepository('metaStandardProjectProfileBundle:CommonList');
        $commonList = $repository->findFirstAlphaInProject($this->base['standardProject']->getId());

        $commonLists = $repository->findAllAlphaInProject($this->base['standardProject']->getId());

        if (!$commonList){
          return $this->forward('metaStandardProjectProfileBundle:Default:newCommonList', array('slug' => $slug));
        }

        return $this->render('metaStandardProjectProfileBundle:Default:Lists/showCommonList.html.twig', 
            array('base' => $this->base,
                  'commonLists' => $commonLists,
                  'commonList' => $commonList));

    }

    public function showCommonListAction($slug, $id, $commonListSlug)
    {
        $this->fetchProjectAndPreComputeRights($slug, false, true);

        if ($this->base == false) 
          return $this->forward('metaStandardProjectProfileBundle:Default:showRestricted', array('slug' => $slug));

        $repository = $this->getDoctrine()->getRepository('metaStandardProjectProfileBundle:CommonList');
        $commonList = $repository->findOneByIdInProject($id, $this->base['standardProject']->getId());

        $commonLists = $repository->findAllAlphaInProject($this->base['standardProject']->getId());

        // Check if commonList belongs to project
        if ( !$commonList ){
          throw $this->createNotFoundException('This list does not exist');
        }

        return $this->render('metaStandardProjectProfileBundle:Default:Lists/showCommonList.html.twig', 
            array('base' => $this->base,
                  'commonLists' => $commonLists,
                  'commonList' => $commonList));
    }

    public function newCommonListAction(Request $request, $slug)
    {
        $this->fetchProjectAndPreComputeRights($slug, false, true);

        if ($this->base == false) 
          return $this->forward('metaStandardProjectProfileBundle:Default:showRestricted', array('slug' => $slug));

        $commonList = new CommonList();
        $form = $this->createFormBuilder($commonList)
            ->add('name', 'text')
            ->add('description', 'text', array('required' => false))
            ->getForm();

        if ($request->isMethod('POST')) {

            $form->bind($request);

            $textService = $this->container->get('textService');
            $commonList->setSlug($textService->slugify($commonList->getName()));

            if ($form->isValid()) {

                $this->base['standardProject']->addCommonList($commonList);

                $em = $this->getDoctrine()->getManager();
                $em->persist($commonList);
                $em->flush();

                $this->get('session')->setFlash(
                    'success',
                    'Your list "'.$commonList->getName().'" was successfully created.'
                );

                return $this->redirect($this->generateUrl('sp_show_project_list', array('slug' => $slug, 'id' => $commonList->getId(), 'commonListSlug' => $commonList->getSlug())));
           
            } else {
               
               $this->get('session')->setFlash(
                    'error',
                    'The information you provided does not seem valid.'
                );
            }

        }

        return $this->render('metaStandardProjectProfileBundle:Default:Lists/newCommonList.html.twig', array('base' => $this->base, 'form' => $form->createView()));

    }

    public function editCommonListAction(Request $request, $slug, $id)
    {
  
        $this->fetchProjectAndPreComputeRights($slug, false, true);

        if ($this->base != false) {

            $repository = $this->getDoctrine()->getRepository('metaStandardProjectProfileBundle:CommonList');
            $commonList = $repository->findOneByIdInProject($id, $this->base['standardProject']->getId());

            $objectHasBeenModified = false;

            switch ($request->request->get('name')) {
                case 'name':
                    $commonList->setName($request->request->get('value'));
                    $objectHasBeenModified = true;
                    break;
                case 'description':
                    $commonList->setDescription($request->request->get('value'));
                    $objectHasBeenModified = true;
                    break;
            }

            $validator = $this->get('validator');
            $errors = $validator->validate($commonList);

            if ($objectHasBeenModified === true && count($errors) == 0){
                $commonList->setUpdatedAt(new \DateTime('now'));
                $em = $this->getDoctrine()->getManager();
                $return = $em->flush();
                $error = null;
            } else {
                $error = $errors[0]->getMessage(); 
            }
            
        }

        return new Response($error);
    }

    public function newCommonListItemAction($slug, $listId)
    {
        $this->fetchProjectAndPreComputeRights($slug, false, true);

        if ($this->base == false) 
          return $this->forward('metaStandardProjectProfileBundle:Default:showRestricted', array('slug' => $slug));

        $commonListItem = new CommonListItem();
        $commonListItem->setDefaultText();

        $repository = $this->getDoctrine()->getRepository('metaStandardProjectProfileBundle:CommonList');
        $commonList = $repository->findOneByIdInProject($listId, $this->base['standardProject']->getId());

        $commonList->addItem($commonListItem);

        $em = $this->getDoctrine()->getManager();
        $em->persist($commonListItem);
        $em->flush();

        $this->get('session')->setFlash(
            'success',
            'Your new item was successfully created.'
        );

        return $this->redirect($this->generateUrl('sp_show_project_list', array('slug' => $slug, 'id' => $commonList->getId(), 'commonListSlug' => $commonList->getSlug())));

    }

    public function editCommonListItemAction(Request $request, $slug, $listId, $id)
    {
  
        $this->fetchProjectAndPreComputeRights($slug, false, true);

        if ($this->base != false) {

            $repository = $this->getDoctrine()->getRepository('metaStandardProjectProfileBundle:CommonListItem');
            $commonListItem = $repository->findOneByIdInProjectAndList($id, $listId, $this->base['standardProject']->getId());

            $objectHasBeenModified = false;

            switch ($request->request->get('name')) {
                case 'text':
                    $commonListItem->setText($request->request->get('value'));
                    $objectHasBeenModified = true;
                    break;
                case 'done':
                    $commonListItem->setDone($request->request->get('value'));
                    $objectHasBeenModified = true;
                    break;
            }

            $validator = $this->get('validator');
            $errors = $validator->validate($commonListItem);

            if ($objectHasBeenModified === true && count($errors) == 0){
                $commonListItem->setUpdatedAt(new \DateTime('now'));
                $em = $this->getDoctrine()->getManager();
                $return = $em->flush();
                $error = null;
            } else {
                $error = $errors[0]->getMessage(); 
            }
            
        }

        return new Response($error);
    }

    public function toggleCommonListItemAction(Request $request, $slug, $listId, $id, $do)
    {
  
        $this->fetchProjectAndPreComputeRights($slug, false, true);

        if ($this->base != false) {

            $repository = $this->getDoctrine()->getRepository('metaStandardProjectProfileBundle:CommonListItem');
            $commonListItem = $repository->findOneByIdInProjectAndList($id, $listId, $this->base['standardProject']->getId());

            $repository = $this->getDoctrine()->getRepository('metaStandardProjectProfileBundle:CommonList');
            $commonList = $repository->findOneByIdInProject($listId, $this->base['standardProject']->getId());

            $commonListItem->setDone($do);

            $commonListItem->setUpdatedAt(new \DateTime('now'));
            $em = $this->getDoctrine()->getManager();
            $return = $em->flush();
            
        }

        return $this->forward('metaStandardProjectProfileBundle:Default:showCommonList', array('slug' => $slug, 'id' => $listId, 'commonListSlug' => $commonList->getSlug()));
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

                if ($newParticipantOrOwner) {

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
                        'This user does not exist.'
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


    /*  ####################################################
     *                    PROJECT LIST
     *  #################################################### */

    public function listAction($max)
    {

        $repository = $this->getDoctrine()->getRepository('metaStandardProjectProfileBundle:StandardProject');

        $standardProjects = $repository->findRecentlyCreatedStandardProjects($max);

        return $this->render('metaStandardProjectProfileBundle:Default:list.html.twig', array('standardProjects' => $standardProjects));

    }


    /*  ####################################################
     *                   WATCH / UNWATCH
     *  #################################################### */

    public function watchAction($slug)
    {

        $authenticatedUser = $this->getUser();

        // The actually authenticated user now watches the project with $slug
        if ($authenticatedUser) {

            $repository = $this->getDoctrine()->getRepository('metaStandardProjectProfileBundle:StandardProject');
            $standardProject = $repository->findOneBySlug($slug);

            if ( !($authenticatedUser->isWatching($standardProject)) ){

                $authenticatedUser->addProjectsWatched($standardProject);

                $em = $this->getDoctrine()->getManager();
                $em->flush();

                $this->get('session')->setFlash(
                    'success',
                    'You are now watching '.$standardProject->getName().'.'
                );

            } else {

                $this->get('session')->setFlash(
                    'warning',
                    'You are already watching '.$standardProject->getName().'.'
                );

            }

        }

        return $this->redirect($this->generateUrl('sp_show_project', array('slug' => $slug)));
    }

    public function unwatchAction($slug)
    {
        $authenticatedUser = $this->getUser();

        // The actually authenticated user now follows $user if they are not the same
        if ($authenticatedUser) {

            $repository = $this->getDoctrine()->getRepository('metaStandardProjectProfileBundle:StandardProject');
            $standardProject = $repository->findOneBySlug($slug);

            if ( $authenticatedUser->isWatching($standardProject) ){

                $authenticatedUser->removeProjectsWatched($standardProject);

                $em = $this->getDoctrine()->getManager();
                $em->flush();

                $this->get('session')->setFlash(
                    'success',
                    'You are not watching '.$standardProject->getName().' anymore.'
                );

            } else {

                $this->get('session')->setFlash(
                    'warning',
                    'You are not watching '.$standardProject->getName().'.'
                );

            }

        }

        return $this->redirect($this->generateUrl('sp_show_project', array('slug' => $slug)));
    }

}
