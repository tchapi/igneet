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

        $lists = $repository->findAllInProject($this->base['project']->getId());

        if (!$list && $this->base['canEdit']){
          return $this->forward('metaProjectBundle:List:newList', array('uid' => $uid));
        }

        return $this->render('metaProjectBundle:Project:showList.html.twig', 
            array('base' => $this->base,
                  'lists' => $lists,
                  'list' => $list));

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

        $lists = $repository->findAllInProject($this->base['project']->getId());

        // Check if list belongs to project
        if ( !$list ){
          throw $this->createNotFoundException($this->get('translator')->trans('project.lists.not.found'));
        }

        return $this->render('metaProjectBundle:Project:showList.html.twig', 
            array('base' => $this->base,
                  'lists' => $lists,
                  'list' => $list));
    }

    /*
     * Rank the lists (via X-Editable)
     */
    public function rankListsAction(Request $request, $uid)
    {
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

            return new Response($this->get('translator')->trans('invalid.request', array(), 'errors'), 400);
            
        }


    }

    /*
     * Display the form for a new list and process via POST
     */
    public function newListAction(Request $request, $uid)
    {

        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('newList', $request->get('token')))
            return new Response($this->get('translator')->trans('invalid.token', array(), 'errors'), 400);

        $this->preComputeRights(array("mustBeOwner" => false, "mustParticipate" => true));

        if ($this->access == false) 
          return $this->forward('metaProjectBundle:Base:showRestricted', array('uid' => $uid));

        $list = new CommonList();
        $form = $this->createFormBuilder($list)
            ->add('name', 'text', array('label' => "project.lists.name"))
            ->add('description', 'text', array('required' => false, 'label' => "project.lists.description"))
            ->getForm();

        if ($request->isMethod('POST')) {

            $form->bind($request);

            if ($form->isValid()) {

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
    public function editListAction(Request $request, $uid, $list_uid)
    {
  
        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('editList', $request->get('token')))
            return new Response($this->get('translator')->trans('invalid.token', array(), 'errors'), 400);

        $this->preComputeRights(array("mustBeOwner" => false, "mustParticipate" => true));
        $error = null;

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
                        $tagsAsArray = array_map('strtolower', $request->request->get('value'));

                        $list->clearTags();

                        $tagRepository = $this->getDoctrine()->getRepository('metaGeneralBundle:Behaviour\Tag');
                        $existingTags = $tagRepository->findBy(array('name' => $tagsAsArray));
                        $existingTagNames = array();

                        foreach ($existingTags as $tag) {
                          $list->addTag($tag);
                          $existingTagNames[] = $tag->getName();
                        }

                        foreach ($tagsAsArray as $name) {
                          if ( in_array($name, $existingTagNames) ){ continue; }
                          $tag = new Tag($name);
                          $em->persist($tag);
                          $list->addTag($tag);
                        }

                        $objectHasBeenModified = true;
                        break;
                }

                $errors = $this->get('validator')->validate($list);

                if ($objectHasBeenModified === true && count($errors) == 0){
                    
                    $this->base['project']->setUpdatedAt(new \DateTime('now'));
                    $em->flush();

                    $logService = $this->container->get('logService');
                    $logService->log($this->getUser(), 'user_update_list', $this->base['project'], array( 'list' => array( 'logName' => $list->getLogName(), 'identifier' => $commonList->getId() ) ));
                
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
    public function deleteListAction(Request $request, $uid, $list_uid)
    {
  
        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('deleteList', $request->get('token')))
            return $this->redirect($this->generateUrl('p_show_project_list_home', array('uid' => $uid)));

        $this->preComputeRights(array("mustBeOwner" => false, "mutParticipate" => true));

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
                    $this->get('translator')->trans('project.lists.deleted', array( '%list%' => $list->getName()))
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
    public function newListItemAction($uid, $list_uid, $name)
    {
        $this->preComputeRights(array("mustBeOwner" => false, "mustParticipate" => true));

        if ($this->access == false) 
          return $this->forward('metaProjectBundle:Base:showRestricted', array('uid' => $uid));

        $listItem = new CommonListItem();
        $listItem->setText($name);

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

        $this->get('session')->getFlashBag()->add(
            'success',
            $this->get('translator')->trans('project.lists.items.created')
        );

        return $this->redirect($this->generateUrl('p_show_project_list', array('uid' => $uid, 'list_uid' => $this->container->get('uid')->toUId($list->getId()) )));

    }

    /*
     * Edit a list item (via X-Editable)
     */
    public function editListItemAction(Request $request, $uid, $list_uid, $item_uid)
    {
  
        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('editListItem', $request->get('token')))
            return new Response($this->get('translator')->trans('invalid.token', array(), 'errors'), 400);

        $this->preComputeRights(array("mustBeOwner" => false, "mustParticipate" => true));
        $error = null;
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
                        $deepLinkingService = $this->container->get('deep_linking_extension');
                        $response = $deepLinkingService->convertDeepLinks($request->request->get('value'));
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
    public function deleteListItemAction(Request $request, $uid, $list_uid, $item_uid)
    {
  
        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('deleteListItem', $request->get('token')))
            return $this->redirect($this->generateUrl('p_show_project_list', array('uid' => $uid, 'list_uid' => $this->container->get('uid')->fromUId($list_uid))));

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

        return $this->redirect($this->generateUrl('p_show_project_list', array('uid' => $uid, 'list_uid' => $this->container->get('uid')->toUId($list->getId()) )));

    }

    /*
     * Toggle a list item
     */
    public function toggleListItemAction(Request $request, $uid, $list_uid, $item_uid, $do)
    {
  
        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('toggleListItem', $request->get('token')))
            return $this->redirect($this->generateUrl('p_show_project_list_home', array('uid' => $uid)));

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

                return $this->redirect($this->generateUrl('p_show_project_list', array('uid' => $uid, 'list_uid' => $this->container->get('uid')->toUId($list->getId()) )));

            } else {

                $this->get('session')->getFlashBag()->add(
                    'warning',
                    $this->get('translator')->trans('project.lists.items.not.found')
                );

            }
            
        }
        
        return $this->redirect($this->generateUrl('p_show_project_list_home', array('uid' => $uid)));
    
    }

}
