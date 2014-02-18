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
    public function showListHomeAction($uid)
    {
        $menu = $this->container->getParameter('project.menu');

        $this->preComputeRights(array("mustBeOwner" => false, "mustParticipate" => $menu['lists']['private']));

        if ($this->access == false) 
          return $this->forward('metaProjectBundle:Base:showRestricted', array('uid' => $uid));

        // Now we find the first ranked list
        $repository = $this->getDoctrine()->getRepository('metaProjectBundle:CommonList');
        $list = $repository->findFirstInProject($this->base['project']->getId());

        $items = null;
        $lists = null;

        if ($list != null ){
            $itemsRepository = $this->getDoctrine()->getRepository('metaProjectBundle:CommonListItem');
            $items = $itemsRepository->findAllInProjectAndList($list->getId(), $this->base['project']->getId());
            $lists = $repository->findAllInProject($this->base['project']->getId());
        }

        return $this->render('metaProjectBundle:Project:showLists.html.twig', 
            array('base' => $this->base,
                  'lists' => $lists,
                  'list' => $list,
                  'items' => $items));

    }

    /*
     * Show the lists tab on a specific list
     */
    public function showListAction($uid, $list_uid)
    {
        $menu = $this->container->getParameter('project.menu');
        $this->preComputeRights(array("mustBeOwner" =>false, "mustParticipate" => $menu['lists']['private']));

        if ($this->access == false) 
          return $this->forward('metaProjectBundle:Base:showRestricted', array('uid' => $uid));

        $repository = $this->getDoctrine()->getRepository('metaProjectBundle:CommonList');
        $list = $repository->findOneByIdInProject($this->container->get('uid')->fromUId($list_uid), $this->base['project']->getId());

        $itemsRepository = $this->getDoctrine()->getRepository('metaProjectBundle:CommonListItem');
        $items = $itemsRepository->findAllInProjectAndList($this->container->get('uid')->fromUId($list_uid), $this->base['project']->getId());

        $lists = $repository->findAllInProject($this->base['project']->getId());

        // Check if list belongs to project
        if ( !$list ){
          throw $this->createNotFoundException($this->get('translator')->trans('project.lists.not.found'));
        }

        return $this->render('metaProjectBundle:Project:showLists.html.twig', 
            array('base' => $this->base,
                  'lists' => $lists,
                  'list' => $list,
                  'items' => $items));
    }

    /*
     * Rank the lists (POST)
     * NEEDS JSON
     */
    public function rankListsAction(Request $request, $uid)
    {
        
        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('rankLists', $request->get('token'))) {
            return new Response(
                json_encode(
                    array(
                        'message' => $this->get('translator')->trans('invalid.token', array(), 'errors'))
                    ), 
                400, 
                array('Content-Type'=>'application/json')
            );
        }

        $this->preComputeRights(array("mustBeOwner" => false, "mustParticipate" => true));

        if ($this->access != false) {

            $ranks = explode(',', $request->request->get('ranks'));
            $repository = $this->getDoctrine()->getRepository('metaProjectBundle:CommonList');

            foreach($ranks as $key => $list_uid)
            {
                if ($list_uid == "") continue;
                $list = $repository->findOneByIdInProject($this->container->get('uid')->fromUId($list_uid), $this->base['project']->getId());
                if ($list) $list->setRank(intval($key));
            }

            $em = $this->getDoctrine()->getManager();
            $this->base['project']->setUpdatedAt(new \DateTime('now'));
            $em->flush();
        
             return new Response();

        } else {

            return new Response(json_encode(array(
                'message' => $this->get('translator')->trans('invalid.request', array(), 'errors')
                )), 400, array('Content-Type'=>'application/json'));
            
        }


    }

    /*
     * Process via GET
     */
    public function newListAction(Request $request, $uid)
    {

        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('newList', $request->get('token'))) {
            $this->get('session')->getFlashBag()->add(
                'error',
                $this->get('translator')->trans('invalid.token', array(), 'errors')
            );
            return $this->redirect($this->generateUrl('p_show_project_list_home', array('uid' => $uid )));
        }

        $this->preComputeRights(array("mustBeOwner" => false, "mustParticipate" => true));

        if ($this->access != false) {

            $list = new CommonList();
            $list->setName($request->query->get('title'));

            $errors = $this->get('validator')->validate($list);

            if (count($errors) == 0) {

                $this->base['project']->addCommonList($list);
                $this->base['project']->setUpdatedAt(new \DateTime('now'));

                $em = $this->getDoctrine()->getManager();
                $em->persist($list);
                $em->flush();

                $logService = $this->container->get('logService');
                $logService->log($this->getUser(), 'user_create_list', $this->base['project'], array( 'list' => array( 'logName' => $list->getLogName(), 'identifier' => $list->getId()) ));

                $this->get('session')->getFlashBag()->add(
                    'success',
                    $this->get('translator')->trans('project.lists.created', array('%list%' => $list->getName()))
                );

                return $this->redirect($this->generateUrl('p_show_project_list', array('uid' => $uid, 'list_uid' => $this->container->get('uid')->toUId($list->getId()) )));
           
            } 

        }

        $this->get('session')->getFlashBag()->add(
            'error',
            $this->get('translator')->trans('invalid.request', array(), 'errors')
        );

        return $this->redirect($this->generateUrl('p_show_project_list_home', array('uid' => $uid )));

    }

    /*
     * Edit a list
     * NEEDS JSON
     */
    public function editListAction(Request $request, $uid, $list_uid)
    {
  
        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('editList', $request->get('token'))) {
            return new Response(
                json_encode(
                    array(
                        'message' => $this->get('translator')->trans('invalid.token', array(), 'errors'))
                    ), 
                400, 
                array('Content-Type'=>'application/json')
            );
        }

        $this->preComputeRights(array("mustBeOwner" => false, "mustParticipate" => true));

        $response = null;

        if ($this->access != false) {

            $repository = $this->getDoctrine()->getRepository('metaProjectBundle:CommonList');
            $list = $repository->findOneByIdInProject($this->container->get('uid')->fromUId($list_uid), $this->base['project']->getId());

            if ($list) {

                $objectHasBeenModified = false;
                $em = $this->getDoctrine()->getManager();

                switch ($request->request->get('name')) {
                    case 'name':
                        $list->setName($request->request->get('value'));
                        $objectHasBeenModified = true;
                        break;
                    case 'description':
                        $list->setDescription($request->request->get('value'));
                        $objectHasBeenModified = true;
                        break;
                    case 'tags':
                        $tag = strtolower($request->request->get('key'));

                        $tagRepository = $this->getDoctrine()->getRepository('metaGeneralBundle:Behaviour\Tag');
                        $existingTag = $tagRepository->findOneBy(array('name' => $tag));

                        if ($request->request->get('value') == 'remove' && $existingTag && $list->hasTag($existingTag)) {
                            $list->removeTag($existingTag);
                            $objectHasBeenModified = true;
                        } else if ($request->request->get('value') == 'add' && $existingTag && !$list->hasTag($existingTag)) {
                            $list->addTag($existingTag);
                            $objectHasBeenModified = true;
                            $response = array('tag' => $this->renderView('metaGeneralBundle:Tags:tag.html.twig', array( 'tag' => $existingTag, 'canEdit' => true)));
                        } else if ($request->request->get('value') == 'add' && !$existingTag ){
                            $newTag = new Tag($tag);
                            $em->persist($newTag);
                            $list->addTag($newTag);
                            $response = array('tag' => $this->renderView('metaGeneralBundle:Tags:tag.html.twig', array( 'tag' => $newTag, 'canEdit' => true)));
                            $objectHasBeenModified = true;
                        } else {
                            $response = array('message' => $this->get('translator')->trans('project.lists.tag.already')); // tag already in the page
                        }

                        break;
            
                }

                $errors = $this->get('validator')->validate($list);

                if ($objectHasBeenModified === true && count($errors) == 0){
                    
                    $this->base['project']->setUpdatedAt(new \DateTime('now'));
                    $em->flush();

                    $logService = $this->container->get('logService');
                    $logService->log($this->getUser(), 'user_update_list', $this->base['project'], array( 'list' => array( 'logName' => $list->getLogName(), 'identifier' => $list->getId() ) ));
                
                    return new Response(json_encode($response), 200, array('Content-Type'=>'application/json'));

                } elseif (count($errors) > 0) {

                    $response = array('message' => $this->get('translator')->trans($errors[0]->getMessage()));

                } else {
                    
                    if ($response == null) {
                        $response = array('message' => $this->get('translator')->trans('unnecessary.request', array(), 'errors'));
                    }
                    
                }

            }
            
        }

        return new Response(json_encode($response?$response:array('message' => $this->get('translator')->trans('invalid.request', array(), 'errors'))), 406, array('Content-Type'=>'application/json'));

    }

    /*
     * Delete a list
     * NEEDS JSON (not implemented yet but we will)
     */
    public function deleteListAction(Request $request, $uid, $list_uid)
    {
  
        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('deleteList', $request->get('token'))) {
            return new Response(
                json_encode(
                    array(
                        'message' => $this->get('translator')->trans('invalid.token', array(), 'errors'))
                    ), 
                400, 
                array('Content-Type'=>'application/json')
            );
        }

        $this->preComputeRights(array("mustBeOwner" => false, "mustParticipate" => true));

        if ($this->access != false) {

            $repository = $this->getDoctrine()->getRepository('metaProjectBundle:CommonList');
            $list = $repository->findOneByIdInProject($this->container->get('uid')->fromUId($list_uid), $this->base['project']->getId());

            if ($list){

                $this->base['project']->removeCommonList($list);
                $this->base['project']->setUpdatedAt(new \DateTime('now'));

                $logService = $this->container->get('logService');
                $logService->log($this->getUser(), 'user_delete_list', $this->base['project'], array( 'list' => array( 'logName' => $list->getLogName() )) );

                $em = $this->getDoctrine()->getManager();
                $em->remove($list);
                $em->flush();

                $this->get('session')->getFlashBag()->add(
                    'success',
                    $this->get('translator')->trans('project.lists.deleted', array('%list%' => $list->getName()))
                );

                return new Response(json_encode(array('redirect' => $this->generateUrl('p_show_project_list_home', array('uid' => $uid)))), 200, array('Content-Type'=>'application/json'));

            } 
            
        }

        return new Response(json_encode(array('message' =>  $this->get('translator')->trans('invalid.request', array(), 'errors'))), 406, array('Content-Type'=>'application/json'));

    }

    /*
     * Create a new list item
     * NEEDS JSON
     */
    public function newListItemAction(Request $request, $uid, $list_uid)
    {
        
        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('newListItem', $request->get('token'))) {
            return new Response(
                json_encode(
                    array(
                        'message' => $this->get('translator')->trans('invalid.token', array(), 'errors'))
                    ), 
                400, 
                array('Content-Type'=>'application/json')
            );
        }

        $this->preComputeRights(array("mustBeOwner" => false, "mustParticipate" => true));

        if ($this->access != false) {

            $listItem = new CommonListItem();
            $listItem->setText($request->get('text'));

            $repository = $this->getDoctrine()->getRepository('metaProjectBundle:CommonList');
            $list = $repository->findOneByIdInProject($this->container->get('uid')->fromUId($list_uid), $this->base['project']->getId());

            $list->addItem($listItem);
            $this->base['project']->setUpdatedAt(new \DateTime('now'));

            $em = $this->getDoctrine()->getManager();
            $em->persist($listItem);
            $em->flush();

            $logService = $this->container->get('logService');
            $logService->log($this->getUser(), 'user_create_list_item', $this->base['project'], array( 'list' => array( 'logName' => $list->getLogName(), 'identifier' => $list->getId() ),
                                                                                                               'list_item' => array( 'logName' => $listItem->getLogName() )) );

            return new Response(json_encode(array(
                // no message here, we don't want UI to be crowded
                'item' => $this->renderView('metaProjectBundle:Project:showLists.item.html.twig', 
                        array('project' => $this->base['project'],
                              'item' => $listItem,
                              'list' => $list,
                              'canEdit' => $this->base['canEdit']))
                )), 200, array('Content-Type'=>'application/json'));
        }

        return new Response(json_encode(array('message' =>  $this->get('translator')->trans('invalid.request', array(), 'errors'))), 406, array('Content-Type'=>'application/json'));

    }

    /*
     * Edit a list item
     * NEEDS JSON
     */
    public function editListItemAction(Request $request, $uid, $list_uid, $item_uid)
    {
  
        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('editListItem', $request->get('token'))) {
            return new Response(
                json_encode(
                    array(
                        'message' => $this->get('translator')->trans('invalid.token', array(), 'errors'))
                    ), 
                400, 
                array('Content-Type'=>'application/json')
            );
        }

        $this->preComputeRights(array("mustBeOwner" => false, "mustParticipate" => true));

        $response = null;

        if ($this->access != false) {

            $repository = $this->getDoctrine()->getRepository('metaProjectBundle:CommonList');
            $list = $repository->findOneByIdInProject($this->container->get('uid')->fromUId($list_uid), $this->base['project']->getId());

            $repository = $this->getDoctrine()->getRepository('metaProjectBundle:CommonListItem');
            $listItem = $repository->findOneByIdInProjectAndList($this->container->get('uid')->fromUId($item_uid), $this->container->get('uid')->fromUId($list_uid), $this->base['project']->getId());

            if ($list && $listItem) {
                
                $objectHasBeenModified = false;

                switch ($request->request->get('name')) {
                    case 'text':
                        $listItem->setText($request->request->get('value'));
                        // This needs to stay here for the list items, we need to send the value back
                        $deepLinkingService = $this->container->get('deep_linking_extension');
                        $response = array('text' => $deepLinkingService->convertDeepLinks($request->request->get('value')));
                        $objectHasBeenModified = true;
                        break;
                }

                $validator = $this->get('validator');
                $errors = $validator->validate($listItem);

                if ($objectHasBeenModified === true && count($errors) == 0){
                    
                    $this->base['project']->setUpdatedAt(new \DateTime('now'));
                    $em = $this->getDoctrine()->getManager();
                    $em->flush();

                    $logService = $this->container->get('logService');
                    $logService->log($this->getUser(), 'user_update_list_item', $this->base['project'], array( 'list' => array( 'logName' => $list->getLogName(), 'identifier' => $list->getId()),
                                                                                                                       'list_item' => array( 'logName' => $listItem->getLogName() )) );
                
                    return new Response(json_encode($response), 200, array('Content-Type'=>'application/json'));

                } elseif (count($errors) > 0) {

                    $message = array('message' => $this->get('translator')->trans($errors[0]->getMessage())); 

                } else {
                    
                    if ($response == null) {
                        $response = array('message' => $this->get('translator')->trans('unnecessary.request', array(), 'errors'));
                    }
                    
                }

            } 

        }

        return new Response(json_encode($response?$response:array('message' => $this->get('translator')->trans('invalid.request', array(), 'errors'))), 406, array('Content-Type'=>'application/json'));

    }

    /*
     * Delete a list item
     * NEEDS JSON
     */
    public function deleteListItemAction(Request $request, $uid, $list_uid, $item_uid)
    {
  
        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('deleteListItem', $request->get('token'))) {
            return new Response(
                json_encode(
                    array(
                        'message' => $this->get('translator')->trans('invalid.token', array(), 'errors'))
                    ), 
                400, 
                array('Content-Type'=>'application/json')
            );
        }

        $this->preComputeRights(array("mustBeOwner" => false, "mustParticipate" => true));

        if ($this->access != false) {

            $repository = $this->getDoctrine()->getRepository('metaProjectBundle:CommonListItem');
            $listItem = $repository->findOneByIdInProjectAndList($this->container->get('uid')->fromUId($item_uid), $this->container->get('uid')->fromUId($list_uid), $this->base['project']->getId());

            $repository = $this->getDoctrine()->getRepository('metaProjectBundle:CommonList');
            $list = $repository->findOneByIdInProject($this->container->get('uid')->fromUId($list_uid), $this->base['project']->getId());

            if ($list && $listItem){

                $list->removeItem($listItem);
                $this->base['project']->setUpdatedAt(new \DateTime('now'));

                $em = $this->getDoctrine()->getManager();
                $em->remove($listItem);
                $em->flush();

                $logService = $this->container->get('logService');
                $logService->log($this->getUser(), 'user_delete_list_item', $this->base['project'], array( 'list' => array( 'logName' => $list->getLogName(), 'identifier' => $list->getId()),
                                                                                                              'list_item' => array( 'logName' => $listItem->getLogName() )) );

                return new Response();
            }
            
        }

        return new Response(json_encode(array('message' => $this->get('translator')->trans('invalid.request', array(), 'errors'))), 406, array('Content-Type'=>'application/json'));

    }

    /*
     * Rank listItems
     * NEEDS JSON
     */
    public function rankListItemsAction(Request $request, $uid)
    {
        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('rankListItems', $request->get('token'))) {
            return new Response(
                json_encode(
                    array(
                        'message' => $this->get('translator')->trans('invalid.token', array(), 'errors'))
                    ), 
                400, 
                array('Content-Type'=>'application/json')
            );
        }

        $this->preComputeRights(array("mustBeOwner" => false, "mustParticipate" => true));

        if ($this->access != false) {

            $ranks = explode(',', $request->request->get('ranks'));
            $repository = $this->getDoctrine()->getRepository('metaProjectBundle:CommonListItem');

            foreach($ranks as $key => $item_uid)
            {
                if ($item_uid == "") continue;
                $listItem = $repository->findOneById($this->container->get('uid')->fromUId($item_uid));
                if ($listItem) $listItem->setRank(intval($key));
            }

            $this->base['project']->setUpdatedAt(new \DateTime('now'));
            $em = $this->getDoctrine()->getManager();
            $em->flush();
    
            return new Response();

        }

        return new Response(json_encode(array('message' => $this->get('translator')->trans('invalid.request', array(), 'errors'))), 406, array('Content-Type'=>'application/json'));

    }

    /*
     * Toggle a list item
     * NEEDS JSON
     */
    public function toggleListItemAction(Request $request, $uid, $list_uid, $item_uid, $do)
    {
  
        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('toggleListItem', $request->get('token'))) {
            return new Response(
                json_encode(
                    array(
                        'message' => $this->get('translator')->trans('invalid.token', array(), 'errors'))
                    ), 
                400, 
                array('Content-Type'=>'application/json')
            );
        }

        $this->preComputeRights(array("mustBeOwner" => false, "mustParticipate" => true));

        if ($this->access != false) {

            $repository = $this->getDoctrine()->getRepository('metaProjectBundle:CommonListItem');
            $listItem = $repository->findOneByIdInProjectAndList($this->container->get('uid')->fromUId($item_uid), $this->container->get('uid')->fromUId($list_uid), $this->base['project']->getId());

            $repository = $this->getDoctrine()->getRepository('metaProjectBundle:CommonList');
            $list = $repository->findOneByIdInProject($this->container->get('uid')->fromUId($list_uid), $this->base['project']->getId());

            if ($listItem){

                $listItem->setDone($do);
                $this->base['project']->setUpdatedAt(new \DateTime('now'));
                
                $em = $this->getDoctrine()->getManager();
                $em->flush();

                $logService = $this->container->get('logService');
                $action = $do?'do':'undo';
                $logService->log($this->getUser(), 'user_'.$action.'_list_item', $this->base['project'], array( 'list' => array( 'logName' => $list->getLogName(), 'identifier' => $list->getId()),
                                                                                                                   'list_item' => array( 'logName' => $listItem->getLogName() )) );

                return new Response(json_encode(array(
                    // no message here, we don't want UI to be crowded
                    'item' => $this->renderView('metaProjectBundle:Project:showLists.item.html.twig', 
                                array('project' => $this->base['project'],
                                      'item' => $listItem,
                                      'list' => $list,
                                      'canEdit' => $this->base['canEdit']))
                    )), 200, array('Content-Type'=>'application/json'));

            } 
            
        }

        return new Response(json_encode(array('message' => $this->get('translator')->trans('invalid.request', array(), 'errors'))), 406, array('Content-Type'=>'application/json'));
        
    }

}
