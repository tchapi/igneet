<?php

namespace meta\ProjectBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response;

/*
 * Importing Class definitions
 */
use meta\ProjectBundle\Entity\Wiki,
    meta\ProjectBundle\Entity\WikiPage,
    meta\GeneralBundle\Entity\Behaviour\Tag;

class WikiController extends BaseController
{

    /*
     * Display the home of the wiki for a project
     */
    public function showWikiHomeAction($uid)
    {
        $menu = $this->container->getParameter('project.menu');
        $this->preComputeRights(array("mustBeOwner" => false, "mustParticipate" => $menu['wiki']['private']));

        if ($this->access == false) 
          return $this->forward('metaProjectBundle:Base:showRestricted', array('uid' => $uid));

        $wiki = $this->base['project']->getWiki();

        // If wiki is not yet created, we create it and create a first page
        if (!$wiki){

          $wiki = new Wiki();

          $this->base['project']->setWiki($wiki);
          $this->base['project']->setUpdatedAt(new \DateTime('now'));

          $em = $this->getDoctrine()->getManager();
          $em->persist($wiki);
          $em->flush();

        }

        if (count($wiki->getPages()) == 0) {

          // Yek, wiki is here but the last page was deleted, recreate one !!
          $wikiPage = new WikiPage();
          $wikiPage->setTitle($this->get('translator')->trans('project.wiki.default'));
          $wikiPage->setParent(null);

          $wiki->addPage($wikiPage);
          $wiki->setHomePage($wikiPage);

          $em = $this->getDoctrine()->getManager();
          $em->persist($wikiPage);
          $em->flush();

        }

        $repository = $this->getDoctrine()->getRepository('metaProjectBundle:WikiPage');
        if ($wiki->getHomePage() === null) {
          $page = $repository->findFirstInWiki($wiki->getId());
        } else {
          $page = $wiki->getHomePage();
        }

        $wikiPages = $repository->findAllRootInWiki($wiki->getId());

        return $this->render('metaProjectBundle:Project:showWiki.html.twig', 
            array('base' => $this->base, 
                  'homePage' => $wiki->getHomePage(),
                  'wikiPages' => $wikiPages,
                  'wikiPage' => $page));
    }

    /*
     * Display a page of the wiki for a project
     */
    public function showWikiPageAction($uid, $page_uid)
    {
        $menu = $this->container->getParameter('project.menu');
        $this->preComputeRights(array("mustBeOwner" => false, "mustParticipate" => $menu['wiki']['private']));

        if ($this->access == false) 
          return $this->forward('metaProjectBundle:Base:showRestricted', array('uid' => $uid));

        $wiki = $this->base['project']->getWiki();

        if (!$wiki){
          return $this->forward('metaProjectBundle:Wiki:showWikiHome', array('uid' => $uid));
        }

        $repository = $this->getDoctrine()->getRepository('metaProjectBundle:WikiPage');
        $wikiPage = $repository->findOneByIdInWiki($this->container->get('uid')->fromUId($page_uid), $wiki->getId());

        $wikiPages = $repository->findAllRootInWiki($wiki->getId());

        // Check if wikiPage belongs to project
        if ( !$wikiPage ){
          throw $this->createNotFoundException($this->get('translator')->trans('project.wiki.not.found'));
        }

        return $this->render('metaProjectBundle:Project:showWiki.html.twig', 
            array('base' => $this->base,
                  'homePage' => $wiki->getHomePage(),
                  'wikiPages' => $wikiPages,
                  'wikiPage' => $wikiPage));

    }

    /*
     * Process via GET
     */
    public function newWikiPageAction(Request $request, $uid)
    {
  
        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('newWikiPage', $request->get('token'))) {
            $this->get('session')->getFlashBag()->add(
                'error',
                $this->get('translator')->trans('invalid.token', array(), 'errors')
            );
            return $this->redirect($this->generateUrl('p_show_project_wiki', array('uid' => $uid )));
        }

        $this->preComputeRights(array("mustBeOwner" => false, "mustParticipate" => true));

        if ($this->access != false) {

          $wiki = $this->base['project']->getWiki();

          if ($wiki){

            $wikiPage = new WikiPage();
            $wikiPage->setTitle($request->query->get('title'));

            $repository = $this->getDoctrine()->getRepository('metaProjectBundle:WikiPage');
            $parentPage = $repository->findOneByIdInWiki($this->container->get('uid')->fromUId($request->query->get('parent')), $wiki->getId());

            $wikiPage->setParent($parentPage);

            $errors = $this->get('validator')->validate($wikiPage);

            if (count($errors) == 0) {

                $this->base['project']->getWiki()->addPage($wikiPage); /* ADD CHILD */
                $this->base['project']->setUpdatedAt(new \DateTime('now'));

                $em = $this->getDoctrine()->getManager();
                $em->persist($wikiPage);
                $em->flush();

                $logService = $this->container->get('logService');
                $logService->log($this->getUser(), 'user_create_wikipage', $this->base['project'], array( 'wikipage' => array( 'logName' => $wikiPage->getLogName(), 'identifier' => $wikiPage->getId()) ));

                $this->get('session')->getFlashBag()->add(
                    'success',
                    $this->get('translator')->trans('project.wiki.created', array( '%page%' => $wikiPage->getTitle() ))
                );

                return $this->redirect($this->generateUrl('p_show_project_wiki_show_page', array('uid' => $uid, 'page_uid' => $this->container->get('uid')->toUId($wikiPage->getId()))));
           
            }

          }

        }

        $this->get('session')->getFlashBag()->add(
            'error',
            $this->get('translator')->trans('invalid.request', array(), 'errors')
        );

        return $this->redirect($this->generateUrl('p_show_project_wiki', array('uid' => $uid )));
           
    }

    /*
     * Make a wiki page the home page of the wiki
     */
    public function makeHomeWikiPageAction(Request $request, $uid, $page_uid)
    {
        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('makeHomeWikiPage', $request->get('token'))) {
            $this->get('session')->getFlashBag()->add(
                'error',
                $this->get('translator')->trans('invalid.token', array(), 'errors')
            );
            return $this->redirect($this->generateUrl('p_show_project_wiki', array('uid' => $uid )));
        }

        $this->preComputeRights(array("mustBeOwner" => false, "mustParticipate" => true));

        if ($this->access == false) 
          return $this->forward('metaProjectBundle:Base:showRestricted', array('uid' => $uid));

        $wiki = $this->base['project']->getWiki();

        $repository = $this->getDoctrine()->getRepository('metaProjectBundle:WikiPage');
        $wikiPage = $repository->findOneByIdInWiki($this->container->get('uid')->fromUId($page_uid), $wiki->getId());

        // Check if wikiPage belongs to project
        if ( !$wikiPage ){
          throw $this->createNotFoundException($this->get('translator')->trans('project.wiki.not.found'));
        }

        if ($wiki->getHomePage() == $wikiPage){

          $this->get('session')->getFlashBag()->add(
              'error',
              $this->get('translator')->trans('project.wiki.home.already', array( '%page%' => $wikiPage->getTitle() ))
          );

        } else {

          $this->get('session')->getFlashBag()->add(
              'success',
              $this->get('translator')->trans('project.wiki.homed', array( '%page%' => $wikiPage->getTitle() ))
          );

          $em = $this->getDoctrine()->getManager();
          $wiki->setHomePage($wikiPage);
          $this->base['project']->setUpdatedAt(new \DateTime('now'));
          $em->flush();

        }

        return $this->redirect($this->generateUrl('p_show_project_wiki', array('uid' => $uid)));
           
    }

    /*
     * Rank wiki pages
     * NEEDS JSON
     */
    public function rankWikiPagesAction(Request $request, $uid)
    {
        $this->preComputeRights(array("mustBeOwner" => false, "mustParticipate" => true));

        if ($this->access != false) {

            $wiki = $this->base['project']->getWiki();

            if ($wiki) {

                $ranks = explode(',', $request->request->get('ranks'));
                $repository = $this->getDoctrine()->getRepository('metaProjectBundle:WikiPage');

                foreach($ranks as $key => $page_uid)
                {
                    if ($page_uid == "") continue;
                    $wikiPage = $repository->findOneByIdInWiki($this->container->get('uid')->fromUId($page_uid), $wiki->getId());
                    if ($wikiPage) $wikiPage->setRank(intval($key));
                }

                $this->base['project']->setUpdatedAt(new \DateTime('now'));
                $em = $this->getDoctrine()->getManager();
                $em->flush();
        
                return new Response();
              
            }

        } 

        return new Response(json_encode(array(
                'message' => $this->get('translator')->trans('invalid.request', array(), 'errors')
                )), 400, array('Content-Type'=>'application/json'));

    }

    /*
     * Edit a wiki page
     * NEEDS JSON
     */
    public function editWikiPageAction(Request $request, $uid, $page_uid)
    {
  
        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('editWikiPage', $request->get('token'))) {
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

            $wiki = $this->base['project']->getWiki();

            if ($wiki){

              $repository = $this->getDoctrine()->getRepository('metaProjectBundle:WikiPage');
              $wikiPage = $repository->findOneByIdInWiki($this->container->get('uid')->fromUId($page_uid), $wiki->getId());
              
              if ($wikiPage){

                $objectHasBeenModified = false;
                $em = $this->getDoctrine()->getManager();

                switch ($request->request->get('name')) {
                    case 'title':
                        $wikiPage->setTitle($request->request->get('value'));
                        $objectHasBeenModified = true;
                        break;
                    case 'parent':
                        $parent = $repository->findOneByIdInWiki( $this->container->get('uid')->fromUId($request->request->get('value')), $wiki->getId());
                        $wikiPage->setParent($parent);
                        $objectHasBeenModified = true;
                        break;
                    case 'content':
                        $wikiPage->setContent($request->request->get('value'));
                        $objectHasBeenModified = true;
                        break;
                    case 'tags':
                        $tag = strtolower($request->request->get('key'));

                        $tagRepository = $this->getDoctrine()->getRepository('metaGeneralBundle:Behaviour\Tag');
                        $existingTag = $tagRepository->findOneBy(array('name' => $tag));

                        if ($request->request->get('value') == 'remove' && $existingTag && $wikiPage->hasTag($existingTag)) {
                            $wikiPage->removeTag($existingTag);
                            $objectHasBeenModified = true;
                        } else if ($request->request->get('value') == 'add' && $existingTag && !$wikiPage->hasTag($existingTag)) {
                            $wikiPage->addTag($existingTag);
                            $objectHasBeenModified = true;
                            $response = array('tag' => $this->renderView('metaGeneralBundle:Tags:tag.html.twig', array( 'tag' => $existingTag, 'canEdit' => true)));
                        } else if ($request->request->get('value') == 'add' && !$existingTag ){
                            $newTag = new Tag($tag);
                            $em->persist($newTag);
                            $wikiPage->addTag($newTag);
                            $response = array('tag' => $this->renderView('metaGeneralBundle:Tags:tag.html.twig', array( 'tag' => $newTag, 'canEdit' => true)));
                            $objectHasBeenModified = true;
                        } else {
                            $response = array('message' => $this->get('translator')->trans('project.wiki.tag.already')); // tag already in the page
                        }

                        break;
                }

                $errors = $this->get('validator')->validate($wikiPage);

                if ($objectHasBeenModified === true && count($errors) == 0){
                    
                    $this->base['project']->setUpdatedAt(new \DateTime('now'));
                    $em->flush();

                    $logService = $this->container->get('logService');
                    $logService->log($this->getUser(), 'user_update_wikipage', $this->base['project'], array( 'wikipage' => array( 'logName' => $wikiPage->getLogName(), 'identifier' => $wikiPage->getId() ) ));
                
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

        }

        return new Response(json_encode($response?$response:array('message' => $this->get('translator')->trans('invalid.request', array(), 'errors'))), 406, array('Content-Type'=>'application/json'));

    }

    /*
     * Delete a wiki page
     * NEEDS JSON (not implemented yet but we will)
     */
    public function deleteWikiPageAction(Request $request, $uid, $page_uid)
    {
  
        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('deleteWikiPage', $request->get('token'))) {
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

            $wiki = $this->base['project']->getWiki();

            if ($wiki){

                $repository = $this->getDoctrine()->getRepository('metaProjectBundle:WikiPage');
                $wikiPage = $repository->findOneByIdInWiki($this->container->get('uid')->fromUId($page_uid), $wiki->getId());

                if ($wikiPage){
                  
                  $em = $this->getDoctrine()->getManager();
                  $wiki->removePage($wikiPage);
                  $wiki->setHomePage(null);
                  $em->flush();

                  // What if the page has children ?
                  foreach($wikiPage->getChildren() as $child){
                    $child->setParent(null);
                  }

                  $logService = $this->container->get('logService');
                  $logService->log($this->getUser(), 'user_delete_wikipage', $this->base['project'], array( 'wikipage' => array( 'logName' => $wikiPage->getLogName() )) );

                  $em = $this->getDoctrine()->getManager();
                  $em->remove($wikiPage);
                  $this->base['project']->setUpdatedAt(new \DateTime('now'));

                  $em->flush();

                  $this->get('session')->getFlashBag()->add(
                      'success',
                      $this->get('translator')->trans('project.wiki.deleted', array( '%page%' => $wikiPage->getTitle() ))
                  );

                  return new Response(json_encode(array("redirect" => $this->generateUrl('p_show_project_wiki', array('uid' => $uid)))), 200, array('Content-Type'=>'application/json'));

                }

            }
            
        }

        return new Response(json_encode(array("message" => ($message?$message:$this->get('translator')->trans('project.wiki.not.found')))), 406, array('Content-Type'=>'application/json'));

    }
}
