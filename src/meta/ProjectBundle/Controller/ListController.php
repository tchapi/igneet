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
    public function showCommonListHomeAction($uid)
    {
        $menu = $this->container->getParameter('standardproject.menu');
        $this->fetchProjectAndPreComputeRights($uid, false, $menu['lists']['private']);

        if ($this->base == false) 
          return $this->forward('metaProjectBundle:Base:showRestricted', array('uid' => $uid));

        // Now we find the first ranked list
        $repository = $this->getDoctrine()->getRepository('metaProjectBundle:CommonList');
        $commonList = $repository->findFirstInProject($this->base['standardProject']->getId());

        $commonLists = $repository->findAllInProject($this->base['standardProject']->getId());

        if (!$commonList && $this->base['canEdit']){
          return $this->forward('metaProjectBundle:List:newCommonList', array('uid' => $uid));
        }

        return $this->render('metaProjectBundle:List:showCommonList.html.twig', 
            array('base' => $this->base,
                  'commonLists' => $commonLists,
                  'commonList' => $commonList));

    }

    /*
     * Show the lists tab on a specific list
     */
    public function showCommonListAction($uid, $list_uid)
    {
        $menu = $this->container->getParameter('standardproject.menu');
        $this->fetchProjectAndPreComputeRights($uid, false, $menu['lists']['private']);

        if ($this->base == false) 
          return $this->forward('metaProjectBundle:Base:showRestricted', array('uid' => $uid));

        $repository = $this->getDoctrine()->getRepository('metaProjectBundle:CommonList');
        $commonList = $repository->findOneByIdInProject($this->container->get('uid')->fromUId($list_uid), $this->base['standardProject']->getId());

        $commonLists = $repository->findAllInProject($this->base['standardProject']->getId());

        // Check if commonList belongs to project
        if ( !$commonList ){
          throw $this->createNotFoundException($this->get('translator')->trans('project.lists.not.found'));
        }

        return $this->render('metaProjectBundle:List:showCommonList.html.twig', 
            array('base' => $this->base,
                  'commonLists' => $commonLists,
                  'commonList' => $commonList));
    }

    /*
     * Rank the lists (via X-Editable)
     */
    public function rankCommonListsAction(Request $request, $uid)
    {
        $this->fetchProjectAndPreComputeRights($uid, false, true);

        if ($this->base != false) {

            $ranks = explode(',', $request->request->get('ranks'));
            $repository = $this->getDoctrine()->getRepository('metaProjectBundle:CommonList');

            foreach($ranks as $key => $list_uid)
            {
                if ($list_uid == "") continue;
                $commonList = $repository->findOneByIdInProject($this->container->get('uid')->fromUId($list_uid), $this->base['standardProject']->getId());
                if ($commonList) $commonList->setRank(intval($key));
            }

            $em = $this->getDoctrine()->getManager();
            $this->base['standardProject']->setUpdatedAt(new \DateTime('now'));
            $em->flush();
        
            return new Response();

        } else {

            return new Response($this->get('translator')->trans('invalid.request', array(), 'errors'), 400);
            
        }


    }

    /*
     * Display the form for a new list and process via POST
     */
    public function newCommonListAction(Request $request, $uid)
    {
        $this->fetchProjectAndPreComputeRights($uid, false, true);

        if ($this->base == false) 
          return $this->forward('metaProjectBundle:Base:showRestricted', array('uid' => $uid));

        $commonList = new CommonList();
        $form = $this->createFormBuilder($commonList)
            ->add('name', 'text', array('label' => "project.lists.name"))
            ->add('description', 'text', array('required' => false, 'label' => "project.lists.description"))
            ->getForm();

        if ($request->isMethod('POST')) {

            $form->bind($request);

            if ($form->isValid()) {

                $this->base['standardProject']->addCommonList($commonList);
                $this->base['standardProject']->setUpdatedAt(new \DateTime('now'));

                $em = $this->getDoctrine()->getManager();
                $em->persist($commonList);
                $em->flush();

                $logService = $this->container->get('logService');
                $logService->log($this->getUser(), 'user_create_list', $this->base['standardProject'], array( 'list' => array( 'logName' => $commonList->getLogName(), 'identifier' => $commonList->getId()) ));

                $this->get('session')->getFlashBag()->add(
                    'success',
                    $this->get('translator')->trans('project.lists.created', array('%list%' => $commonList->getName()))
                );

                return $this->redirect($this->generateUrl('p_show_project_list', array('uid' => $uid, 'list_uid' => $this->container->get('uid')->toUId($commonList->getId()) )));
           
            } else {
               
               $this->get('session')->getFlashBag()->add(
                    'error',
                    $this->get('translator')->trans('information.not.valid', array(), 'errors')
                );
            }

        }

        return $this->render('metaProjectBundle:List:newCommonList.html.twig', array('base' => $this->base, 'form' => $form->createView()));

    }

    /*
     * Edit a list (via X-Editable)
     */
    public function editCommonListAction(Request $request, $uid, $list_uid)
    {
  
        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('editCommonList', $request->get('token')))
            return new Response($this->get('translator')->trans('invalid.token', array(), 'errors'), 400);

        $this->fetchProjectAndPreComputeRights($uid, false, true);
        $error = null;

        if ($this->base != false) {

            $repository = $this->getDoctrine()->getRepository('metaProjectBundle:CommonList');
            $commonList = $repository->findOneByIdInProject($this->container->get('uid')->fromUId($list_uid), $this->base['standardProject']->getId());

            if ($commonList) {

                $objectHasBeenModified = false;
                $em = $this->getDoctrine()->getManager();

                switch ($request->request->get('name')) {
                    case 'name':
                        $commonList->setName($request->request->get('value'));
                        $objectHasBeenModified = true;
                        break;
                    case 'description':
                        $commonList->setDescription($request->request->get('value'));
                        $objectHasBeenModified = true;
                        break;
                    case 'tags':
                        $tagsAsArray = array_map('strtolower', $request->request->get('value'));

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
                    $logService->log($this->getUser(), 'user_update_list', $this->base['standardProject'], array( 'list' => array( 'logName' => $commonList->getLogName(), 'identifier' => $commonList->getId() ) ));
                
                } elseif (count($errors) > 0) {

                    $error = $this->get('translator')->trans($errors[0]->getMessage());
                }

            } else {

              $error = $this->get('translator')->trans('invalid.request', array(), 'errors');

            }
            
        } else {

            $error = $this->get('translator')->trans('invalid.request', array(), 'errors');

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
    public function deleteCommonListAction(Request $request, $uid, $list_uid)
    {
  
        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('deleteCommonList', $request->get('token')))
            return $this->redirect($this->generateUrl('p_show_project_list_home', array('uid' => $uid)));

        $this->fetchProjectAndPreComputeRights($uid, false, true);

        if ($this->base != false) {

            $repository = $this->getDoctrine()->getRepository('metaProjectBundle:CommonList');
            $commonList = $repository->findOneByIdInProject($this->container->get('uid')->fromUId($list_uid), $this->base['standardProject']->getId());

            if ($commonList){

                $this->base['standardProject']->removeCommonList($commonList);
                $this->base['standardProject']->setUpdatedAt(new \DateTime('now'));

                $logService = $this->container->get('logService');
                $logService->log($this->getUser(), 'user_delete_list', $this->base['standardProject'], array( 'list' => array( 'logName' => $commonList->getLogName() )) );

                $em = $this->getDoctrine()->getManager();
                $em->remove($commonList);
                $em->flush();

                $this->get('session')->getFlashBag()->add(
                    'success',
                    $this->get('translator')->trans('project.lists.deleted', array( '%list%' => $commonList->getName()))
                );

            } else {

                $this->get('session')->getFlashBag()->add(
                    'warning',
                    $this->get('translator')->trans('project.lists.not.found')
                );

            }
            
        }

        return $this->redirect($this->generateUrl('p_show_project_list_home', array('uid' => $uid)));

    }

    /*
     * Create a new list item
     */
    public function newCommonListItemAction($uid, $list_uid, $name)
    {
        $this->fetchProjectAndPreComputeRights($uid, false, true);

        if ($this->base == false) 
          return $this->forward('metaProjectBundle:Base:showRestricted', array('uid' => $uid));

        $commonListItem = new CommonListItem();
        $commonListItem->setText($name);

        $repository = $this->getDoctrine()->getRepository('metaProjectBundle:CommonList');
        $commonList = $repository->findOneByIdInProject($this->container->get('uid')->fromUId($list_uid), $this->base['standardProject']->getId());

        $commonList->addItem($commonListItem);
        $this->base['standardProject']->setUpdatedAt(new \DateTime('now'));

        $em = $this->getDoctrine()->getManager();
        $em->persist($commonListItem);
        $em->flush();

        $logService = $this->container->get('logService');
        $logService->log($this->getUser(), 'user_create_list_item', $this->base['standardProject'], array( 'list' => array( 'logName' => $commonList->getLogName(), 'identifier' => $commonList->getId() ),
                                                                                                           'list_item' => array( 'logName' => $commonListItem->getLogName() )) );

        $this->get('session')->getFlashBag()->add(
            'success',
            $this->get('translator')->trans('project.lists.items.created')
        );

        return $this->redirect($this->generateUrl('p_show_project_list', array('uid' => $uid, 'list_uid' => $this->container->get('uid')->toUId($commonList->getId()) )));

    }

    /*
     * Edit a list item (via X-Editable)
     */
    public function editCommonListItemAction(Request $request, $uid, $list_uid, $item_uid)
    {
  
        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('editCommonListItem', $request->get('token')))
            return new Response($this->get('translator')->trans('invalid.token', array(), 'errors'), 400);

        $this->fetchProjectAndPreComputeRights($uid, false, true);
        $error = null;
        $response = null;

        if ($this->base != false) {

            $repository = $this->getDoctrine()->getRepository('metaProjectBundle:CommonList');
            $commonList = $repository->findOneByIdInProject($this->container->get('uid')->fromUId($list_uid), $this->base['standardProject']->getId());

            $repository = $this->getDoctrine()->getRepository('metaProjectBundle:CommonListItem');
            $commonListItem = $repository->findOneByIdInProjectAndList($this->container->get('uid')->fromUId($item_uid), $this->container->get('uid')->fromUId($list_uid), $this->base['standardProject']->getId());

            if ($commonList && $commonListItem) {
                
                $objectHasBeenModified = false;

                switch ($request->request->get('name')) {
                    case 'text':
                        $commonListItem->setText($request->request->get('value'));
                        $deepLinkingService = $this->container->get('deep_linking_extension');
                        $response = $deepLinkingService->convertDeepLinks($request->request->get('value'));
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
                    $logService->log($this->getUser(), 'user_update_list_item', $this->base['standardProject'], array( 'list' => array( 'logName' => $commonList->getLogName(), 'identifier' => $commonList->getId()),
                                                                                                                       'list_item' => array( 'logName' => $commonListItem->getLogName() )) );
                } elseif (count($errors) > 0) {

                    $error = $this->get('translator')->trans($errors[0]->getMessage()); 
                }

            } else {

              $error = $this->get('translator')->trans('invalid.request', array(), 'errors');

            }
            
        } else {

            $error = $this->get('translator')->trans('invalid.request', array(), 'errors');

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
    public function deleteCommonListItemAction(Request $request, $uid, $list_uid, $item_uid)
    {
  
        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('deleteCommonListItem', $request->get('token')))
            return $this->redirect($this->generateUrl('p_show_project_list', array('uid' => $uid, 'list_uid' => $this->container->get('uid')->fromUId($list_uid))));

        $this->fetchProjectAndPreComputeRights($uid, false, true);

        if ($this->base != false) {

            $repository = $this->getDoctrine()->getRepository('metaProjectBundle:CommonListItem');
            $commonListItem = $repository->findOneByIdInProjectAndList($this->container->get('uid')->fromUId($item_uid), $this->container->get('uid')->fromUId($list_uid), $this->base['standardProject']->getId());

            $repository = $this->getDoctrine()->getRepository('metaProjectBundle:CommonList');
            $commonList = $repository->findOneByIdInProject($this->container->get('uid')->fromUId($list_uid), $this->base['standardProject']->getId());

            if ($commonList && $commonListItem){

                $commonList->removeItem($commonListItem);
                $this->base['standardProject']->setUpdatedAt(new \DateTime('now'));

                $em = $this->getDoctrine()->getManager();
                $em->remove($commonListItem);
                $em->flush();

                $logService = $this->container->get('logService');
                $logService->log($this->getUser(), 'user_delete_list_item', $this->base['standardProject'], array( 'list' => array( 'logName' => $commonList->getLogName(), 'identifier' => $commonList->getId()),
                                                                                                              'list_item' => array( 'logName' => $commonListItem->getLogName() )) );

                $this->get('session')->getFlashBag()->add(
                    'success',
                    $this->get('translator')->trans('project.lists.items.deleted')
                );

            } else {

                $this->get('session')->getFlashBag()->add(
                    'warning',
                    $this->get('translator')->trans('project.lists.items.not.found')
                );

            }
            
        }

        return $this->redirect($this->generateUrl('p_show_project_list', array('uid' => $uid, 'list_uid' => $this->container->get('uid')->toUId($commonList->getId()) )));

    }

    /*
     * Toggle a list item
     */
    public function toggleCommonListItemAction(Request $request, $uid, $list_uid, $item_uid, $do)
    {
  
        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('toggleCommonListItem', $request->get('token')))
            return $this->redirect($this->generateUrl('p_show_project_list_home', array('uid' => $uid)));

        $this->fetchProjectAndPreComputeRights($uid, false, true);

        if ($this->base != false) {

            $repository = $this->getDoctrine()->getRepository('metaProjectBundle:CommonListItem');
            $commonListItem = $repository->findOneByIdInProjectAndList($this->container->get('uid')->fromUId($item_uid), $this->container->get('uid')->fromUId($list_uid), $this->base['standardProject']->getId());

            $repository = $this->getDoctrine()->getRepository('metaProjectBundle:CommonList');
            $commonList = $repository->findOneByIdInProject($this->container->get('uid')->fromUId($list_uid), $this->base['standardProject']->getId());

            if ($commonListItem){

                $commonListItem->setDone($do);
                $this->base['standardProject']->setUpdatedAt(new \DateTime('now'));
                
                $em = $this->getDoctrine()->getManager();
                $em->flush();

                $logService = $this->container->get('logService');
                $action = $do?'do':'undo';
                $logService->log($this->getUser(), 'user_'.$action.'_list_item', $this->base['standardProject'], array( 'list' => array( 'logName' => $commonList->getLogName(), 'identifier' => $commonList->getId()),
                                                                                                                   'list_item' => array( 'logName' => $commonListItem->getLogName() )) );

                return $this->redirect($this->generateUrl('p_show_project_list', array('uid' => $uid, 'list_uid' => $this->container->get('uid')->toUId($commonList->getId()) )));

            } else {

                $this->get('session')->getFlashBag()->add(
                    'warning',
                    $this->get('translator')->trans('project.lists.items.not.found')
                );

            }
            
        }
        
        return $this->redirect($this->generateUrl('p_show_project_list_home', array('uid' => $uid)));
    
    }

    public function addLaunchingListsAction(Request $request, $uid)
    {

        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('launch', $request->get('token')))
            return $this->redirect($this->generateUrl('p_show_project', array('uid' => $uid)));

        $this->fetchProjectAndPreComputeRights($uid, false, true);

        if ($this->base != false) {
        
            $lists = $this->container->getParameter("launch");

            $em = $this->getDoctrine()->getManager();

            foreach ($lists as $list) {
                
                $autoList = new CommonList();
                $em->persist($autoList);
                $autoList->setName($list['name']);
                $autoList->setDescription($list['description']);
                $autoList->setAutogenerated(true);

                foreach ($list['items'] as $item) {
                    $autoListItem = new CommonListItem();
                    $autoListItem->setText($item);
                    $em->persist($autoListItem);
                    $autoList->addItem($autoListItem);
                }

                $this->base['standardProject']->addCommonList($autoList);
            }

            $this->base['standardProject']->setUpdatedAt(new \DateTime('now'));
            
            $em->flush();

        }

         return $this->redirect($this->generateUrl('p_show_project_list_home', array('uid' => $uid)));
    
    }

}
