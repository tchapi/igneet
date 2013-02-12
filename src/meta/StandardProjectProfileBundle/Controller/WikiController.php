<?php

namespace meta\StandardProjectProfileBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response;

/*
 * Importing Class definitions
 */
use meta\StandardProjectProfileBundle\Entity\Wiki,
    meta\StandardProjectProfileBundle\Entity\WikiPage,
    meta\GeneralBundle\Entity\Behaviour\Tag;

class WikiController extends BaseController
{

    /*  ####################################################
     *                          WIKI
     *  #################################################### */

    public function showWikiHomeAction($slug)
    {
        $this->fetchProjectAndPreComputeRights($slug, false, true);

        if ($this->base == false) 
          return $this->forward('metaStandardProjectProfileBundle:Base:showRestricted', array('slug' => $slug));

        $wiki = $this->base['standardProject']->getWiki();

        if ( !($wiki) ){

          $wiki = new Wiki();

          $this->base['standardProject']->setWiki($wiki);
          
          $em = $this->getDoctrine()->getManager();
          $em->persist($wiki);
          $em->flush();

        }

        $pages = $wiki->getPages();
        $homePage = $wiki->getHomePage();

        if ( count($pages) == 0 ){
          return $this->forward('metaStandardProjectProfileBundle:Wiki:newWikiPage', array('slug' => $slug));
        }

        $repository = $this->getDoctrine()->getRepository('metaStandardProjectProfileBundle:WikiPage');
        $wikiPages = $repository->findAllRootAlphaInWiki($wiki->getId());

        $wikiPage = ($homePage!=null)?$homePage:$repository->findFirstAlphaInWiki($wiki->getId());

        return $this->render('metaStandardProjectProfileBundle:Wiki:showWiki.html.twig', 
            array('base' => $this->base, 
                  'homePage' => $homePage,
                  'wikiPages' => $wikiPages,
                  'wikiPage' => $wikiPage));
    }

    public function showWikiPageAction($slug, $id, $pageSlug)
    {
        $this->fetchProjectAndPreComputeRights($slug, false, true);

        if ($this->base == false) 
          return $this->forward('metaStandardProjectProfileBundle:Base:showRestricted', array('slug' => $slug));

        $wiki = $this->base['standardProject']->getWiki();

        if (!$wiki){
          return $this->forward('metaStandardProjectProfileBundle:Wiki:showWikiHome', array('slug' => $slug));
        }

        $repository = $this->getDoctrine()->getRepository('metaStandardProjectProfileBundle:WikiPage');
        $wikiPage = $repository->findOneByIdInWiki($id, $wiki->getId());

        $wikiPages = $repository->findAllRootAlphaInWiki($wiki->getId());

        // Check if wikiPage belongs to project
        if ( !$wikiPage ){
          throw $this->createNotFoundException('This page does not exist');
        }

        return $this->render('metaStandardProjectProfileBundle:Wiki:showWiki.html.twig', 
            array('base' => $this->base,
                  'homePage' => $wiki->getHomePage(),
                  'wikiPages' => $wikiPages,
                  'wikiPage' => $wikiPage));

    }

    public function newWikiPageAction(Request $request, $slug){

        $this->fetchProjectAndPreComputeRights($slug, false, true);

        if ($this->base == false) 
          return $this->forward('metaStandardProjectProfileBundle:Base:showRestricted', array('slug' => $slug));

        $wiki = $this->base['standardProject']->getWiki();

        if (!$wiki){
          return $this->forward('metaStandardProjectProfileBundle:Wiki:showWikiHome', array('slug' => $slug));
        }

        $wikiPage = new WikiPage();
        $form = $this->createFormBuilder($wikiPage)
            ->add('title', 'text')
            ->getForm();

        if ($request->isMethod('POST')) {

            $form->bind($request);

            $textService = $this->container->get('textService');
            $wikiPage->setSlug($textService->slugify($wikiPage->getTitle()));

            if ($form->isValid()) {

                $this->base['standardProject']->getWiki()->addPage($wikiPage); /* ADD CHILD */

                $em = $this->getDoctrine()->getManager();
                $em->persist($wikiPage);
                $em->flush();

                $logService = $this->container->get('logService');
                $logService->log($this->getUser(), 'user_create_wikipage', $this->base['standardProject'], array( 'wikipage' => array( 'routing' => 'wikipage', 'logName' => $wikiPage->getLogName(), 'args' => $wikiPage->getLogArgs()) ));

                $this->get('session')->setFlash(
                    'success',
                    'Your page "'.$wikiPage->getTitle().'" was successfully created.'
                );

                return $this->redirect($this->generateUrl('sp_show_project_wiki_show_page', array('slug' => $slug, 'id' => $wikiPage->getId(), 'pageSlug' => $wikiPage->getSlug())));
           
            } else {

               $this->get('session')->setFlash(
                    'error',
                    'The information you provided does not seem valid.'
                );
            }

        }

        return $this->render('metaStandardProjectProfileBundle:Wiki:newWikiPage.html.twig', 
            array('base' => $this->base, 'form' => $form->createView()));

    }

    public function makeHomeWikiPageAction($slug, $id)
    {
        $this->fetchProjectAndPreComputeRights($slug, false, true);

        if ($this->base == false) 
          return $this->forward('metaStandardProjectProfileBundle:Base:showRestricted', array('slug' => $slug));

        $wiki = $this->base['standardProject']->getWiki();

        if (!$wiki){
          return $this->forward('metaStandardProjectProfileBundle:Wiki:showWikiHome', array('slug' => $slug));
        }

        $repository = $this->getDoctrine()->getRepository('metaStandardProjectProfileBundle:WikiPage');
        $wikiPage = $repository->findOneByIdInWiki($id, $wiki->getId());

        // Check if wikiPage belongs to project
        if ( !$wikiPage ){
          throw $this->createNotFoundException('This page does not exist');
        }

        if ($wiki->getHomePage() == $wikiPage){

          $this->get('session')->setFlash(
                    'error',
                    'This page is already the home of this wiki.'
                );

        } else {

          $this->get('session')->setFlash(
                    'success',
                    'Your page "'.$wikiPage->getTitle().'" was successfully promoted to home page for this wiki.'
                );

          $em = $this->getDoctrine()->getManager();
          $wiki->setHomePage($wikiPage);
          $em->flush();

        }

        return $this->redirect($this->generateUrl('sp_show_project_wiki', array('slug' => $slug)));
           

    }

    public function editWikiPageAction(Request $request, $slug, $id)
    {
  
        $this->fetchProjectAndPreComputeRights($slug, false, true);
        $response = new Response();

        if ($this->base != false) {

            $wiki = $this->base['standardProject']->getWiki();

            if ($wiki){

              $repository = $this->getDoctrine()->getRepository('metaStandardProjectProfileBundle:WikiPage');
              $wikiPage = $repository->findOneByIdInWiki($id, $wiki->getId());
              
              if ($wikiPage){

                $objectHasBeenModified = false;
                $em = $this->getDoctrine()->getManager();

                switch ($request->request->get('name')) {
                    case 'title':
                        $wikiPage->setTitle($request->request->get('value'));
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
                        $deepLinkingService = $this->container->get('meta.twig.deep_linking_extension');
                        $response->setContent($deepLinkingService->convertDeepLinks(
                          $this->container->get('markdown.parser')->transformMarkdown($request->request->get('value')),
                          $this->get('templating'))
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

                $validator = $this->get('validator');
                $errors = $validator->validate($wikiPage);

                if ($objectHasBeenModified === true && count($errors) == 0){
                    $wikiPage->setUpdatedAt(new \DateTime('now'));
                    $em->flush();

                    $logService = $this->container->get('logService');
                    $logService->log($this->getUser(), 'user_update_wikipage', $this->base['standardProject'], array( 'wikipage' => array( 'routing' => 'wikipage', 'logName' => $wikiPage->getLogName(), 'args' => $wikiPage->getLogArgs() ) ));

                } elseif (count($errors) > 0) {
                    $response->setStatusCode(406);
                    $response->setContent($errors[0]->getMessage());
                }
                
              }

            }

        }

        return $response;
    }

    public function deleteWikiPageAction(Request $request, $slug, $id)
    {
  
        $this->fetchProjectAndPreComputeRights($slug, false, true);

        if ($this->base != false) {

            $wiki = $this->base['standardProject']->getWiki();

            if ($wiki){

                $repository = $this->getDoctrine()->getRepository('metaStandardProjectProfileBundle:WikiPage');
                $wikiPage = $repository->findOneByIdInWiki($id, $wiki->getId());

                if ($wikiPage){
                  
                  if ($wikiPage == $wiki->getHomePage()) $wiki->setHomePage(null);
                  $wiki->removePage($wikiPage);

                  $logService = $this->container->get('logService');
                  $logService->log($this->getUser(), 'user_delete_wikipage', $this->base['standardProject'], array( 'wikipage' => array( 'routing' => null, 'logName' => $wikiPage->getLogName() )) );

                  $em = $this->getDoctrine()->getManager();
                  $em->remove($wikiPage);
                  $em->flush();

                  $this->get('session')->setFlash(
                      'success',
                      'Your page "'.$wikiPage->getTitle().'" was successfully deleted.'
                  );

                } else {

                    $this->get('session')->setFlash(
                        'warning',
                        'This item does not exist.'
                    );

                }

            } else {

                $this->get('session')->setFlash(
                    'warning',
                    'This item does not exist.'
                );

            }
            
        }

        return $this->redirect($this->generateUrl('sp_show_project_wiki', array('slug' => $slug)));

    }
}
