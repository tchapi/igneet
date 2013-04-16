<?php

namespace meta\ProjectBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response;

/*
 * Importing Class definitions
 */
use meta\ProjectBundle\Entity\CommonList,
    meta\ProjectBundle\Entity\CommonListItem,
    meta\GeneralBundle\Entity\Behaviour\Tag;

class ListController extends BaseController
{
    
    /*
     * Show the lists tab
     */
    public function showCommonListHomeAction($slug)
    {
        $menu = $this->container->getParameter('standardproject.menu');
        $this->fetchProjectAndPreComputeRights($slug, false, $menu['lists']['private']);

        if ($this->base == false) 
          return $this->forward('metaProjectBundle:Base:showRestricted', array('slug' => $slug));

        // Now we find the first ranked list
        $repository = $this->getDoctrine()->getRepository('metaProjectBundle:CommonList');
        $commonList = $repository->findFirstInProject($this->base['standardProject']->getId());

        $commonLists = $repository->findAllInProject($this->base['standardProject']->getId());

        if (!$commonList && $this->base['canEdit']){
          return $this->forward('metaProjectBundle:List:newCommonList', array('slug' => $slug));
        }

        return $this->render('metaProjectBundle:List:showCommonList.html.twig', 
            array('base' => $this->base,
                  'commonLists' => $commonLists,
                  'commonList' => $commonList));

    }

    /*
     * Show the lists tab on a specific list
     */
    public function showCommonListAction($slug, $id, $commonListSlug)
    {
        $menu = $this->container->getParameter('standardproject.menu');
        $this->fetchProjectAndPreComputeRights($slug, false, $menu['lists']['private']);

        if ($this->base == false) 
          return $this->forward('metaProjectBundle:Base:showRestricted', array('slug' => $slug));

        $repository = $this->getDoctrine()->getRepository('metaProjectBundle:CommonList');
        $commonList = $repository->findOneByIdInProject($id, $this->base['standardProject']->getId());

        $commonLists = $repository->findAllInProject($this->base['standardProject']->getId());

        // Check if commonList belongs to project
        if ( !$commonList ){
          throw $this->createNotFoundException('This list does not exist');
        }

        return $this->render('metaProjectBundle:List:showCommonList.html.twig', 
            array('base' => $this->base,
                  'commonLists' => $commonLists,
                  'commonList' => $commonList));
    }

    /*
     * Rank the lists (via X-Editable)
     */
    public function rankCommonListsAction(Request $request, $slug)
    {
        $this->fetchProjectAndPreComputeRights($slug, false, true);

        if ($this->base != false) {

            $ranks = explode(',', $request->request->get('ranks'));
            $repository = $this->getDoctrine()->getRepository('metaProjectBundle:CommonList');

            foreach($ranks as $key => $list_id)
            {
                if ($list_id == "") continue;
                $commonList = $repository->findOneByIdInProject(intval($list_id), $this->base['standardProject']->getId());
                if ($commonList) $commonList->setRank(intval($key));
            }

            $em = $this->getDoctrine()->getManager();
            $this->base['standardProject']->setUpdatedAt(new \DateTime('now'));
            $em->flush();
        
            return new Response();

        } else {

            return new Response('Invalid request', 400);
            
        }


    }

    /*
     * Display the form for a new list and process via POST
     */
    public function newCommonListAction(Request $request, $slug)
    {
        $this->fetchProjectAndPreComputeRights($slug, false, true);

        if ($this->base == false) 
          return $this->forward('metaProjectBundle:Base:showRestricted', array('slug' => $slug));

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
                $this->base['standardProject']->setUpdatedAt(new \DateTime('now'));

                $em = $this->getDoctrine()->getManager();
                $em->persist($commonList);
                $em->flush();

                $logService = $this->container->get('logService');
                $logService->log($this->getUser(), 'user_create_list', $this->base['standardProject'], array( 'list' => array( 'routing' => 'list', 'logName' => $commonList->getLogName(), 'args' => $commonList->getLogArgs()) ));

                $this->get('session')->getFlashBag()->add(
                    'success',
                    'Your list "'.$commonList->getName().'" was successfully created.'
                );

                return $this->redirect($this->generateUrl('sp_show_project_list', array('slug' => $slug, 'id' => $commonList->getId(), 'commonListSlug' => $commonList->getSlug())));
           
            } else {
               
               $this->get('session')->getFlashBag()->add(
                    'error',
                    'The information you provided does not seem valid.'
                );
            }

        }

        return $this->render('metaProjectBundle:List:newCommonList.html.twig', array('base' => $this->base, 'form' => $form->createView()));

    }

    /*
     * Edit a list (via X-Editable)
     */
    public function editCommonListAction(Request $request, $slug, $id)
    {
  
        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('editCommonList', $request->get('token')))
            return new Response('Invalid token', 400);

        $this->fetchProjectAndPreComputeRights($slug, false, true);
        $error = null;

        if ($this->base != false) {

            $repository = $this->getDoctrine()->getRepository('metaProjectBundle:CommonList');
            $commonList = $repository->findOneByIdInProject($id, $this->base['standardProject']->getId());

            if ($commonList) {

                $objectHasBeenModified = false;
                $em = $this->getDoctrine()->getManager();

                switch ($request->request->get('name')) {
                    case 'name':
                        $commonList->setName($request->request->get('value'));
                          $textService = $this->container->get('textService');
                          $commonList->setSlug($textService->slugify($commonList->getName()));
                        $objectHasBeenModified = true;
                        break;
                    case 'description':
                        $commonList->setDescription($request->request->get('value'));
                        $objectHasBeenModified = true;
                        break;
                    case 'tags':
                        $tagsAsArray = $request->request->get('value');

                        $commonList->clearTags();

                        $tagRepository = $this->getDoctrine()->getRepository('metaGeneralBundle:Behaviour\Tag');
                        $existingTags = $tagRepository->findBy(array('name' => $tagsAsArray));
                        $existingTagNames = array();

                        foreach ($existingTags as $tag) {
                          $commonList->addTag($tag);
                          $existingTagNames[] = $tag->getName();
                        }

                        foreach ($tagsAsArray as $name) {
                          if ( in_array($name, $existingTagNames) ){ continue; }
                          $tag = new Tag($name);
                          $em->persist($tag);
                          $commonList->addTag($tag);
                        }

                        $objectHasBeenModified = true;
                        break;
                }

                $errors = $this->get('validator')->validate($commonList);

                if ($objectHasBeenModified === true && count($errors) == 0){
                    
                    $this->base['standardProject']->setUpdatedAt(new \DateTime('now'));
                    $em->flush();

                    $logService = $this->container->get('logService');
                    $logService->log($this->getUser(), 'user_update_list', $this->base['standardProject'], array( 'list' => array( 'routing' => 'list', 'logName' => $commonList->getLogName(), 'args' => $commonList->getLogArgs() ) ));
                
                } elseif (count($errors) > 0) {

                    $error = $errors[0]->getMessage();
                }

            } else {

              $error = 'Invalid request';

            }
            
        } else {

            $error = 'Invalid request';

        }

        // Wraps up and return a response
        if (!is_null($error)) {
            return new Response($error, 406);
        }

        return new Response();
    }

    /*
     * Delete a list
     */
    public function deleteCommonListAction(Request $request, $slug, $id)
    {
  
        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('deleteCommonList', $request->get('token')))
            return $this->redirect($this->generateUrl('sp_show_project_list_home', array('slug' => $slug)));

        $this->fetchProjectAndPreComputeRights($slug, false, true);

        if ($this->base != false) {

            $repository = $this->getDoctrine()->getRepository('metaProjectBundle:CommonList');
            $commonList = $repository->findOneByIdInProject($id, $this->base['standardProject']->getId());

            if ($commonList){

                $this->base['standardProject']->removeCommonList($commonList);
                $this->base['standardProject']->setUpdatedAt(new \DateTime('now'));

                $logService = $this->container->get('logService');
                $logService->log($this->getUser(), 'user_delete_list', $this->base['standardProject'], array( 'list' => array( 'routing' => null, 'logName' => $commonList->getLogName() )) );

                $em = $this->getDoctrine()->getManager();
                $em->remove($commonList);
                $em->flush();

                $this->get('session')->getFlashBag()->add(
                    'success',
                    'Your list "'.$commonList->getName().'" was successfully deleted.'
                );

            } else {

                $this->get('session')->getFlashBag()->add(
                    'warning',
                    'This item does not exist.'
                );

            }
            
        }

        return $this->redirect($this->generateUrl('sp_show_project_list_home', array('slug' => $slug)));

    }

    /*
     * Create a new list item
     */
    public function newCommonListItemAction($slug, $listId, $name)
    {
        $this->fetchProjectAndPreComputeRights($slug, false, true);

        if ($this->base == false) 
          return $this->forward('metaProjectBundle:Base:showRestricted', array('slug' => $slug));

        $commonListItem = new CommonListItem();
        $commonListItem->setText($name);

        $repository = $this->getDoctrine()->getRepository('metaProjectBundle:CommonList');
        $commonList = $repository->findOneByIdInProject($listId, $this->base['standardProject']->getId());

        $commonList->addItem($commonListItem);
        $this->base['standardProject']->setUpdatedAt(new \DateTime('now'));

        $em = $this->getDoctrine()->getManager();
        $em->persist($commonListItem);
        $em->flush();

        $logService = $this->container->get('logService');
        $logService->log($this->getUser(), 'user_create_list_item', $this->base['standardProject'], array( 'list' => array( 'routing' => 'list', 'logName' => $commonList->getLogName(), 'args' => $commonList->getLogArgs() ),
                                                                                                           'list_item' => array( 'routing' => null, 'logName' => $commonListItem->getLogName() )) );

        $this->get('session')->getFlashBag()->add(
            'success',
            'Your new list item was successfully created.'
        );

        return $this->redirect($this->generateUrl('sp_show_project_list', array('slug' => $slug, 'id' => $commonList->getId(), 'commonListSlug' => $commonList->getSlug())));

    }

    /*
     * Edit a list item (via X-Editable)
     */
    public function editCommonListItemAction(Request $request, $slug, $listId, $id)
    {
  
        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('editCommonListItem', $request->get('token')))
            return new Response('Invalid token', 400);

        $this->fetchProjectAndPreComputeRights($slug, false, true);
        $error = null;
        $response = null;

        if ($this->base != false) {

            $repository = $this->getDoctrine()->getRepository('metaProjectBundle:CommonList');
            $commonList = $repository->findOneByIdInProject($listId, $this->base['standardProject']->getId());

            $repository = $this->getDoctrine()->getRepository('metaProjectBundle:CommonListItem');
            $commonListItem = $repository->findOneByIdInProjectAndList($id, $listId, $this->base['standardProject']->getId());

            if ($commonList && $commonListItem) {
                
                $objectHasBeenModified = false;

                switch ($request->request->get('name')) {
                    case 'text':
                        $commonListItem->setText($request->request->get('value'));
                        $deepLinkingService = $this->container->get('meta.twig.deep_linking_extension');
                        $response = $deepLinkingService->convertDeepLinks($request->request->get('value'));
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
                    
                    $this->base['standardProject']->setUpdatedAt(new \DateTime('now'));
                    $em = $this->getDoctrine()->getManager();
                    $em->flush();

                    $logService = $this->container->get('logService');
                    $logService->log($this->getUser(), 'user_update_list_item', $this->base['standardProject'], array( 'list' => array( 'routing' => 'list', 'logName' => $commonList->getLogName(), 'args' => $commonList->getLogArgs()),
                                                                                                                       'list_item' => array( 'routing' => null, 'logName' => $commonListItem->getLogName() )) );
                } elseif (count($errors) > 0) {

                    $error = $errors[0]->getMessage(); 
                }

            } else {

              $error = 'Invalid request';

            }
            
        } else {

            $error = 'Invalid request';

        }

        // Wraps up and return a response
        if (!is_null($error)) {
            return new Response($error, 406);
        }

        return new Response($response);
    }

    /*
     * Delete a list item
     */
    public function deleteCommonListItemAction(Request $request, $slug, $listId, $id)
    {
  
        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('deleteCommonListItem', $request->get('token')))
            return $this->redirect($this->generateUrl('sp_show_project_list', array('slug' => $slug, 'id' => $listId)));

        $this->fetchProjectAndPreComputeRights($slug, false, true);

        if ($this->base != false) {

            $repository = $this->getDoctrine()->getRepository('metaProjectBundle:CommonListItem');
            $commonListItem = $repository->findOneByIdInProjectAndList($id, $listId, $this->base['standardProject']->getId());

            $repository = $this->getDoctrine()->getRepository('metaProjectBundle:CommonList');
            $commonList = $repository->findOneByIdInProject($listId, $this->base['standardProject']->getId());

            if ($commonList && $commonListItem){

                $commonList->removeItem($commonListItem);
                $this->base['standardProject']->setUpdatedAt(new \DateTime('now'));

                $em = $this->getDoctrine()->getManager();
                $em->remove($commonListItem);
                $em->flush();

                $logService = $this->container->get('logService');
                $logService->log($this->getUser(), 'user_delete_list_item', $this->base['standardProject'], array( 'list' => array( 'routing' => 'list', 'logName' => $commonList->getLogName(), 'args' => $commonList->getLogArgs()),
                                                                                                              'list_item' => array( 'routing' => null,   'logName' => $commonListItem->getLogName() )) );


            } else {

                $this->get('session')->getFlashBag()->add(
                    'warning',
                    'This item does not exist.'
                );

            }
            
        }

        return $this->redirect($this->generateUrl('sp_show_project_list', array('slug' => $slug, 'id' => $commonList->getId(), 'commonListSlug' => $commonList->getSlug())));

    }

    /*
     * Toggle a list item
     */
    public function toggleCommonListItemAction(Request $request, $slug, $listId, $id, $do)
    {
  
        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('toggleCommonListItem', $request->get('token')))
            return $this->redirect($this->generateUrl('sp_show_project_list_home', array('slug' => $slug)));

        $this->fetchProjectAndPreComputeRights($slug, false, true);

        if ($this->base != false) {

            $repository = $this->getDoctrine()->getRepository('metaProjectBundle:CommonListItem');
            $commonListItem = $repository->findOneByIdInProjectAndList($id, $listId, $this->base['standardProject']->getId());

            $repository = $this->getDoctrine()->getRepository('metaProjectBundle:CommonList');
            $commonList = $repository->findOneByIdInProject($listId, $this->base['standardProject']->getId());

            if ($commonListItem){

                $commonListItem->setDone($do);
                $this->base['standardProject']->setUpdatedAt(new \DateTime('now'));
                
                $em = $this->getDoctrine()->getManager();
                $em->flush();

                $logService = $this->container->get('logService');
                $action = $do?'do':'undo';
                $logService->log($this->getUser(), 'user_'.$action.'_list_item', $this->base['standardProject'], array( 'list' => array( 'routing' => 'list', 'logName' => $commonList->getLogName(), 'args' => $commonList->getLogArgs()),
                                                                                                                   'list_item' => array( 'routing' => null,   'logName' => $commonListItem->getLogName() )) );

                return $this->redirect($this->generateUrl('sp_show_project_list', array('slug' => $slug, 'id' => $commonList->getId(), 'commonListSlug' => $commonList->getSlug())));

            } else {

                $this->get('session')->getFlashBag()->add(
                    'warning',
                    'This item does not exist.'
                );

            }
            
        }
        
        return $this->redirect($this->generateUrl('sp_show_project_list_home', array('slug' => $slug)));
    
    }

}
