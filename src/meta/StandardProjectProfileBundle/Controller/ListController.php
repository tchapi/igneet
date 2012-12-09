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

class ListController extends BaseController
{
    
    /*  ####################################################
     *                    Common LISTS
     *  #################################################### */

    public function showCommonListHomeAction($slug)
    {
        $this->fetchProjectAndPreComputeRights($slug, false, true);

        if ($this->base == false) 
          return $this->forward('metaStandardProjectProfileBundle:Base:showRestricted', array('slug' => $slug));

        // Now we find the first alphabetical todo
        $repository = $this->getDoctrine()->getRepository('metaStandardProjectProfileBundle:CommonList');
        $commonList = $repository->findFirstAlphaInProject($this->base['standardProject']->getId());

        $commonLists = $repository->findAllAlphaInProject($this->base['standardProject']->getId());

        if (!$commonList){
          return $this->forward('metaStandardProjectProfileBundle:List:newCommonList', array('slug' => $slug));
        }

        return $this->render('metaStandardProjectProfileBundle:List:showCommonList.html.twig', 
            array('base' => $this->base,
                  'commonLists' => $commonLists,
                  'commonList' => $commonList));

    }

    public function showCommonListAction($slug, $id, $commonListSlug)
    {
        $this->fetchProjectAndPreComputeRights($slug, false, true);

        if ($this->base == false) 
          return $this->forward('metaStandardProjectProfileBundle:Base:showRestricted', array('slug' => $slug));

        $repository = $this->getDoctrine()->getRepository('metaStandardProjectProfileBundle:CommonList');
        $commonList = $repository->findOneByIdInProject($id, $this->base['standardProject']->getId());

        $commonLists = $repository->findAllAlphaInProject($this->base['standardProject']->getId());

        // Check if commonList belongs to project
        if ( !$commonList ){
          throw $this->createNotFoundException('This list does not exist');
        }

        return $this->render('metaStandardProjectProfileBundle:List:showCommonList.html.twig', 
            array('base' => $this->base,
                  'commonLists' => $commonLists,
                  'commonList' => $commonList));
    }

    public function newCommonListAction(Request $request, $slug)
    {
        $this->fetchProjectAndPreComputeRights($slug, false, true);

        if ($this->base == false) 
          return $this->forward('metaStandardProjectProfileBundle:Base:showRestricted', array('slug' => $slug));

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

        return $this->render('metaStandardProjectProfileBundle:List:newCommonList.html.twig', array('base' => $this->base, 'form' => $form->createView()));

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

    public function deleteCommonListAction(Request $request, $slug, $id)
    {
  
        $this->fetchProjectAndPreComputeRights($slug, false, true);

        if ($this->base != false) {

            $repository = $this->getDoctrine()->getRepository('metaStandardProjectProfileBundle:CommonList');
            $commonList = $repository->findOneByIdInProject($id, $this->base['standardProject']->getId());

            if ($commonList){

                $this->base['standardProject']->removeCommonList($commonList);

                $em = $this->getDoctrine()->getManager();
                $em->remove($commonList);
                $em->flush();

                $this->get('session')->setFlash(
                    'success',
                    'Your list "'.$commonList->getName().'" was successfully deleted.'
                );

            } else {

                $this->get('session')->setFlash(
                    'warning',
                    'This item does not exist.'
                );

            }
            
        }

        return $this->redirect($this->generateUrl('sp_show_project_list_home', array('slug' => $slug)));

    }

    public function newCommonListItemAction($slug, $listId, $name)
    {
        $this->fetchProjectAndPreComputeRights($slug, false, true);

        if ($this->base == false) 
          return $this->forward('metaStandardProjectProfileBundle:Base:showRestricted', array('slug' => $slug));

        $commonListItem = new CommonListItem();
        $commonListItem->setText($name);

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

    public function deleteCommonListItemAction(Request $request, $slug, $listId, $id)
    {
  
        $this->fetchProjectAndPreComputeRights($slug, false, true);

        if ($this->base != false) {

            $repository = $this->getDoctrine()->getRepository('metaStandardProjectProfileBundle:CommonListItem');
            $commonListItem = $repository->findOneByIdInProjectAndList($id, $listId, $this->base['standardProject']->getId());

            $repository = $this->getDoctrine()->getRepository('metaStandardProjectProfileBundle:CommonList');
            $commonList = $repository->findOneByIdInProject($listId, $this->base['standardProject']->getId());

            if ($commonList && $commonListItem){

                $commonList->removeItem($commonListItem);

                $em = $this->getDoctrine()->getManager();
                $em->remove($commonListItem);
                $em->flush();

            } else {

                $this->get('session')->setFlash(
                    'warning',
                    'This item does not exist.'
                );

            }
            
        }

        return $this->redirect($this->generateUrl('sp_show_project_list', array('slug' => $slug, 'id' => $commonList->getId(), 'commonListSlug' => $commonList->getSlug())));

    }

    public function toggleCommonListItemAction(Request $request, $slug, $listId, $id, $do)
    {
  
        $this->fetchProjectAndPreComputeRights($slug, false, true);

        if ($this->base != false) {

            $repository = $this->getDoctrine()->getRepository('metaStandardProjectProfileBundle:CommonListItem');
            $commonListItem = $repository->findOneByIdInProjectAndList($id, $listId, $this->base['standardProject']->getId());

            $repository = $this->getDoctrine()->getRepository('metaStandardProjectProfileBundle:CommonList');
            $commonList = $repository->findOneByIdInProject($listId, $this->base['standardProject']->getId());

            if ($commonListItem){

                $commonListItem->setDone($do);

                $commonListItem->setUpdatedAt(new \DateTime('now'));
                $em = $this->getDoctrine()->getManager();
                $return = $em->flush();

            } else {

                $this->get('session')->setFlash(
                    'warning',
                    'This item does not exist.'
                );

            }
            
        }

        return $this->forward('metaStandardProjectProfileBundle:List:showCommonList', array('slug' => $slug, 'id' => $listId, 'commonListSlug' => $commonList->getSlug()));
    }

}
