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
    public function showWikiHomeAction($slug)
    {
        $menu = $this->container->getParameter('standardproject.menu');
        $this->fetchProjectAndPreComputeRights($slug, false, $menu['wiki']['private']);

        if ($this->base == false) 
          return $this->forward('metaProjectBundle:Base:showRestricted', array('slug' => $slug));

        $wiki = $this->base['standardProject']->getWiki();

        if ( !($wiki) ){

          $wiki = new Wiki();

          $this->base['standardProject']->setWiki($wiki);
          $this->base['standardProject']->setUpdatedAt(new \DateTime('now'));

          $em = $this->getDoctrine()->getManager();
          $em->persist($wiki);
          $em->flush();

        }

        $pages = $wiki->getPages();
        $homePage = $wiki->getHomePage();

        if ( count($pages) == 0 && $this->base['canEdit']){
          return $this->forward('metaProjectBundle:Wiki:newWikiPage', array('slug' => $slug));
        }

        $repository = $this->getDoctrine()->getRepository('metaProjectBundle:WikiPage');
        $wikiPages = $repository->findAllRootInWiki($wiki->getId());

        $wikiPage = ($homePage!=null)?$homePage:$repository->findFirstInWiki($wiki->getId());

        return $this->render('metaProjectBundle:Wiki:showWiki.html.twig', 
            array('base' => $this->base, 
                  'homePage' => $homePage,
                  'wikiPages' => $wikiPages,
                  'wikiPage' => $wikiPage));
    }

    /*
     * Display a page of the wiki for a project
     */
    public function showWikiPageAction($slug, $id, $pageSlug)
    {
        $menu = $this->container->getParameter('standardproject.menu');
        $this->fetchProjectAndPreComputeRights($slug, false, $menu['wiki']['private']);

        if ($this->base == false) 
          return $this->forward('metaProjectBundle:Base:showRestricted', array('slug' => $slug));

        $wiki = $this->base['standardProject']->getWiki();

        if (!$wiki){
          return $this->forward('metaProjectBundle:Wiki:showWikiHome', array('slug' => $slug));
        }

        $repository = $this->getDoctrine()->getRepository('metaProjectBundle:WikiPage');
        $wikiPage = $repository->findOneByIdInWiki($id, $wiki->getId());

        $wikiPages = $repository->findAllRootInWiki($wiki->getId());

        // Check if wikiPage belongs to project
        if ( !$wikiPage ){
          throw $this->createNotFoundException($this->get('translator')->trans('project.wiki.not.found'));
        }

        return $this->render('metaProjectBundle:Wiki:showWiki.html.twig', 
            array('base' => $this->base,
                  'homePage' => $wiki->getHomePage(),
                  'wikiPages' => $wikiPages,
                  'wikiPage' => $wikiPage));

    }

    /*
     * Output the form to create a new page and process the POST
     */
    public function newWikiPageAction(Request $request, $slug)
    {

        $this->fetchProjectAndPreComputeRights($slug, false, true);

        if ($this->base == false) 
          return $this->forward('metaProjectBundle:Base:showRestricted', array('slug' => $slug));

        $wiki = $this->base['standardProject']->getWiki();

        if (!$wiki){
          return $this->forward('metaProjectBundle:Wiki:showWikiHome', array('slug' => $slug));
        }

        $wikiPage = new WikiPage();
        $form = $this->createFormBuilder($wikiPage)
            ->add('title', 'text', array( 'label' => "project.wiki.titleField"))
            ->getForm();

        if ($request->isMethod('POST')) {

            $form->bind($request);

            $textService = $this->container->get('textService');
            $wikiPage->setSlug($textService->slugify($wikiPage->getTitle()));

            if ($form->isValid()) {

                $this->base['standardProject']->getWiki()->addPage($wikiPage); /* ADD CHILD */
                $this->base['standardProject']->setUpdatedAt(new \DateTime('now'));

                $em = $this->getDoctrine()->getManager();
                $em->persist($wikiPage);
                $em->flush();

                $logService = $this->container->get('logService');
                $logService->log($this->getUser(), 'user_create_wikipage', $this->base['standardProject'], array( 'wikipage' => array( 'routing' => 'wikipage', 'logName' => $wikiPage->getLogName(), 'args' => $wikiPage->getLogArgs()) ));

                $this->get('session')->getFlashBag()->add(
                    'success',
                    $this->get('translator')->trans('project.wiki.created', array( '%page%' => $wikiPage->getTitle() ))
                );

                return $this->redirect($this->generateUrl('p_show_project_wiki_show_page', array('slug' => $slug, 'id' => $wikiPage->getId(), 'pageSlug' => $wikiPage->getSlug())));
           
            } else {

               $this->get('session')->getFlashBag()->add(
                    'error',
                    $this->get('translator')->trans('information.not.valid', array(), 'errors')
                );
            }

        }

        return $this->render('metaProjectBundle:Wiki:newWikiPage.html.twig', 
            array('base' => $this->base, 'form' => $form->createView()));

    }

    /*
     * Make a wiki page the home page of the wiki
     */
    public function makeHomeWikiPageAction(Request $request, $slug, $id)
    {
        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('makeHomeWikiPage', $request->get('token')))
            return $this->redirect($this->generateUrl('p_show_project_wiki', array('slug' => $slug)));

        $this->fetchProjectAndPreComputeRights($slug, false, true);

        if ($this->base == false) 
          return $this->forward('metaProjectBundle:Base:showRestricted', array('slug' => $slug));

        $wiki = $this->base['standardProject']->getWiki();

        if (!$wiki){
          return $this->forward('metaProjectBundle:Wiki:showWikiHome', array('slug' => $slug));
        }

        $repository = $this->getDoctrine()->getRepository('metaProjectBundle:WikiPage');
        $wikiPage = $repository->findOneByIdInWiki($id, $wiki->getId());

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
          $this->base['standardProject']->setUpdatedAt(new \DateTime('now'));
          $em->flush();

        }

        return $this->redirect($this->generateUrl('p_show_project_wiki', array('slug' => $slug)));
           
    }

    /*
     * Rank wiki pages
     */
    public function rankWikiPagesAction(Request $request, $slug)
    {
        $this->fetchProjectAndPreComputeRights($slug, false, true);

        if ($this->base != false) {

            $wiki = $this->base['standardProject']->getWiki();

            if ($wiki) {

                $ranks = explode(',', $request->request->get('ranks'));
                $repository = $this->getDoctrine()->getRepository('metaProjectBundle:WikiPage');

                foreach($ranks as $key => $page_id)
                {
                    if ($page_id == "") continue;
                    $wikiPage = $repository->findOneByIdInWiki(intval($page_id), $wiki->getId());
                    if ($wikiPage) $wikiPage->setRank(intval($key));
                }

                $this->base['standardProject']->setUpdatedAt(new \DateTime('now'));
                $em = $this->getDoctrine()->getManager();
                $em->flush();
        
                return new Response();
              
            } else {

                return new Response($this->get('translator')->trans('invalid.request', array(), 'errors'), 400);

            }

        } else {

            return new Response($this->get('translator')->trans('invalid.request', array(), 'errors'), 400);

        }

    }

    /*
     * Edit a wiki page (via X-Editable)
     */
    public function editWikiPageAction(Request $request, $slug, $id)
    {
  
        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('editWikiPage', $request->get('token')))
            return new Response($this->get('translator')->trans('invalid.token', array(), 'errors'), 400);

        $this->fetchProjectAndPreComputeRights($slug, false, true);
        $error = null;
        $response = null;

        if ($this->base != false) {

            $wiki = $this->base['standardProject']->getWiki();

            if ($wiki){

              $repository = $this->getDoctrine()->getRepository('metaProjectBundle:WikiPage');
              $wikiPage = $repository->findOneByIdInWiki($id, $wiki->getId());
              
              if ($wikiPage){

                $objectHasBeenModified = false;
                $em = $this->getDoctrine()->getManager();

                switch ($request->request->get('name')) {
                    case 'title':
                        $wikiPage->setTitle($request->request->get('value'));
                          $textService = $this->container->get('textService');
                          $wikiPage->setSlug($textService->slugify($wikiPage->getTitle()));
                        $objectHasBeenModified = true;
                        break;
                    case 'parent':
                        $parent = $repository->findOneByIdInWiki(intval($request->request->get('value')), $wiki->getId());
                        $wikiPage->setParent($parent);
                        $objectHasBeenModified = true;
                        break;
                    case 'content':
                        $wikiPage->setContent($request->request->get('value'));
                        $objectHasBeenModified = true;
                        $deepLinkingService = $this->container->get('deep_linking_extension');
                        $response = $deepLinkingService->convertDeepLinks(
                          $this->container->get('markdown.parser')->transformMarkdown($request->request->get('value'))
                          );
                        break;
                    case 'tags':
                        $tagsAsArray = $request->request->get('value');

                        $wikiPage->clearTags();

                        $tagRepository = $this->getDoctrine()->getRepository('metaGeneralBundle:Behaviour\Tag');
                        $existingTags = $tagRepository->findBy(array('name' => $tagsAsArray));
                        $existingTagNames = array();

                        foreach ($existingTags as $tag) {
                          $wikiPage->addTag($tag);
                          $existingTagNames[] = $tag->getName();
                        }

                        foreach ($tagsAsArray as $name) {
                          if ( in_array($name, $existingTagNames) ){ continue; }
                          $tag = new Tag($name);
                          $em->persist($tag);
                          $wikiPage->addTag($tag);
                        }

                        $objectHasBeenModified = true;
                        break;
                }

                $errors = $this->get('validator')->validate($wikiPage);

                if ($objectHasBeenModified === true && count($errors) == 0){
                    
                    $this->base['standardProject']->setUpdatedAt(new \DateTime('now'));
                    $em->flush();

                    $logService = $this->container->get('logService');
                    $logService->log($this->getUser(), 'user_update_wikipage', $this->base['standardProject'], array( 'wikipage' => array( 'routing' => 'wikipage', 'logName' => $wikiPage->getLogName(), 'args' => $wikiPage->getLogArgs() ) ));
                
                } elseif (count($errors) > 0) {

                    $error = $errors[0]->getMessage();
                }
                
              } else {

                $error = $this->get('translator')->trans('invalid.request', array(), 'errors');

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
     * Delete a wiki page
     */
    public function deleteWikiPageAction(Request $request, $slug, $id)
    {
  
        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('deleteWikiPage', $request->get('token')))
            return $this->redirect($this->generateUrl('p_show_project_wiki', array('slug' => $slug)));

        $this->fetchProjectAndPreComputeRights($slug, false, true);

        if ($this->base != false) {

            $wiki = $this->base['standardProject']->getWiki();

            if ($wiki){

                $repository = $this->getDoctrine()->getRepository('metaProjectBundle:WikiPage');
                $wikiPage = $repository->findOneByIdInWiki($id, $wiki->getId());

                if ($wikiPage){
                  
                  // What if this is the homepage of the wiki ?
                  if ($wikiPage == $wiki->getHomePage()) $wiki->setHomePage(null);
                  $wiki->removePage($wikiPage);

                  // What if the page has children ?
                  foreach($wikiPage->getChildren() as $child){
                    $child->setParent(null);
                  }

                  $logService = $this->container->get('logService');
                  $logService->log($this->getUser(), 'user_delete_wikipage', $this->base['standardProject'], array( 'wikipage' => array( 'routing' => null, 'logName' => $wikiPage->getLogName() )) );

                  $em = $this->getDoctrine()->getManager();
                  $em->remove($wikiPage);
                  $this->base['standardProject']->setUpdatedAt(new \DateTime('now'));

                  $em->flush();

                  $this->get('session')->getFlashBag()->add(
                      'success',
                      $this->get('translator')->trans('project.wiki.deleted', array( '%page%' => $wikiPage->getTitle() ))
                  );

                } else {

                    $this->get('session')->getFlashBag()->add(
                        'warning',
                        $this->get('translator')->trans('project.wiki.not.found')
                    );

                }

            } else {

                $this->get('session')->getFlashBag()->add(
                    'warning',
                    $this->get('translator')->trans('project.wiki.not.found')
                );

            }
            
        }

        return $this->redirect($this->generateUrl('p_show_project_wiki', array('slug' => $slug)));

    }
}
