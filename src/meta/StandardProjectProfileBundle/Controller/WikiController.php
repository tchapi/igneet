<?php

namespace meta\StandardProjectProfileBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response;

/*
 * Importing Class definitions
 */
use meta\StandardProjectProfileBundle\Entity\Wiki,
    meta\StandardProjectProfileBundle\Entity\WikiPage;

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
        $wikiPages = $repository->findAllAlphaInWiki($wiki->getId());

        $wikiPage = ($homePage!=null)?$homepage:$repository->findFirstAlphaInWiki($wiki->getId());

        return $this->render('metaStandardProjectProfileBundle:Wiki:showWiki.html.twig', 
            array('base' => $this->base, 
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

        $wikiPages = $repository->findAllAlphaInWiki($wiki->getId());

        // Check if wikiPage belongs to project
        if ( !$wikiPage ){
          throw $this->createNotFoundException('This page does not exist');
        }

        return $this->render('metaStandardProjectProfileBundle:Wiki:showWiki.html.twig', 
            array('base' => $this->base,
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

    public function editWikiPageAction(Request $request, $slug, $id)
    {
  
        $this->fetchProjectAndPreComputeRights($slug, false, true);

        if ($this->base != false) {

            $wiki = $this->base['standardProject']->getWiki();

            if ($wiki){

              $repository = $this->getDoctrine()->getRepository('metaStandardProjectProfileBundle:WikiPage');
              $wikiPage = $repository->findOneByIdInWiki($id, $wiki->getId());

              $objectHasBeenModified = false;

              switch ($request->request->get('name')) {
                  case 'title':
                      $wikiPage->setTitle($request->request->get('value'));
                      $objectHasBeenModified = true;
                      break;
                  case 'content':
                      $wikiPage->setContent($request->request->get('value'));
                      $objectHasBeenModified = true;
                      break;
              }

              $validator = $this->get('validator');
              $errors = $validator->validate($wikiPage);
              $error = null;

              if ($objectHasBeenModified === true && count($errors) == 0){
                  $wikiPage->setUpdatedAt(new \DateTime('now'));
                  $em = $this->getDoctrine()->getManager();
                  $return = $em->flush();
              } elseif (count($errors) > 0) {
                  $error = $errors[0]->getMessage(); 
              }

            }

        }

        return new Response($error);
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
                  $wiki->removePage($wikiPage);

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
