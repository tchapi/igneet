<?php

namespace meta\ProjectBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response;

/*
 * Importing Class definitions
 */
use meta\ProjectBundle\Entity\Comment\WikiPageComment,
    meta\ProjectBundle\Entity\Comment\CommonListComment,
    meta\ProjectBundle\Entity\Comment\StandardProjectComment;


class CommentController extends BaseController
{

    /*
     * Output the comment form for an project or add a comment to an project when POST
     */
    public function addStandardProjectCommentAction(Request $request, $slug){

        $menu = $this->container->getParameter('standardproject.menu');
        $this->fetchProjectAndPreComputeRights($slug, false, $menu['timeline']['private']);

        if ($this->base != false) {

            $comment = new StandardProjectComment();
            $form = $this->createFormBuilder($comment)
                ->add('text', 'textarea', array('attr' => array('placeholder' => $this->get('translator')->trans('comment.placeholder') )))
                ->getForm();

            if ($request->isMethod('POST')) {

                $form->bind($request);

                if ($form->isValid()) {

                    $comment->setUser($this->getUser());
                    $this->base['standardProject']->addComment($comment);
                    
                    $em = $this->getDoctrine()->getManager();
                    $em->persist($comment);
                    $em->flush();

                    $logService = $this->container->get('logService');
                    $logService->log($this->getUser(), 'user_comment_project', $this->base['standardProject'], array());

                    $this->get('session')->getFlashBag()->add(
                        'success',
                        'Your comment was successfully added.'
                    );

                } else {

                   $this->get('session')->getFlashBag()->add(
                        'error',
                        $this->get('translator')->trans('information.not.valid', array(), 'errors')
                    );
                }

                return $this->redirect($this->generateUrl('sp_show_project_timeline', array('slug' => $slug)));

            } else {

                $route = $this->get('router')->generate('sp_show_project_comment', array('slug' => $slug));

                return $this->render('metaGeneralBundle:Comment:timelineCommentBox.html.twig', 
                    array('object' => $this->base['standardProject'], 'route' => $route, 'form' => $form->createView()));

            }

        }

        throw $this->createNotFoundException('This project does not exist');

    }

    /*
     * Output the comment form for a wiki page or add a comment to a wiki page when POST
     */
    public function addWikiPageCommentAction(Request $request, $slug, $id){

        $menu = $this->container->getParameter('standardproject.menu');
        $this->fetchProjectAndPreComputeRights($slug, false, $menu['wiki']['private']);

        if ($this->base != false) {

            $wiki = $this->base['standardProject']->getWiki();

            if ($wiki){

                $repository = $this->getDoctrine()->getRepository('metaProjectBundle:WikiPage');
                $wikiPage = $repository->findOneByIdInWiki($id, $wiki->getId());

                if ($wikiPage){
                    $comment = new WikiPageComment();
                    $form = $this->createFormBuilder($comment)
                        ->add('text', 'textarea', array('attr' => array('placeholder' => $this->get('translator')->trans('comment.placeholder') )))
                        ->getForm();

                    if ($request->isMethod('POST')) {

                        $form->bind($request);

                        if ($form->isValid()) {

                            $comment->setUser($this->getUser());
                            $wikiPage->addComment($comment);
                            
                            $em = $this->getDoctrine()->getManager();
                            $em->persist($comment);
                            $em->flush();

                            $logService = $this->container->get('logService');
                            $logService->log($this->getUser(), 'user_comment_wikipage', $this->base['standardProject'], array( 'wikipage' => array( 'routing' => 'wikipage', 'logName' => $wikiPage->getLogName(), 'args' => $wikiPage->getLogArgs()) ));

                            $this->get('session')->getFlashBag()->add(
                                'success',
                                'Your comment was successfully added.'
                            );

                        } else {

                           $this->get('session')->getFlashBag()->add(
                                'error',
                                $this->get('translator')->trans('information.not.valid', array(), 'errors')
                            );
                        }

                        return $this->redirect($this->generateUrl('sp_show_project_wiki_show_page', array('slug' => $slug, 'id' => $wikiPage->getId(), 'pageSlug' => $wikiPage->getSlug())));

                    } else {

                        $route = $this->get('router')->generate('sp_show_project_wikipage_comment', array('slug' => $slug, 'id' => $id));

                        return $this->render('metaGeneralBundle:Comment:commentBox.html.twig', 
                            array('object' => $wikiPage, 'route' => $route, 'form' => $form->createView()));

                    }

                }
            }
        }

        throw $this->createNotFoundException('This page does not exist');

    }

    /*
     * Output the comment form for a list or add a comment to a list when POST
     */
    public function addCommonListCommentAction(Request $request, $slug, $id){

        $menu = $this->container->getParameter('standardproject.menu');
        $this->fetchProjectAndPreComputeRights($slug, false, $menu['lists']['private']);

        if ($this->base != false) {

            $repository = $this->getDoctrine()->getRepository('metaProjectBundle:CommonList');
            $commonList = $repository->findOneByIdInProject($id, $this->base['standardProject']->getId());

            if ($commonList){
                $comment = new CommonListComment();
                $form = $this->createFormBuilder($comment)
                    ->add('text', 'textarea', array('attr' => array('placeholder' => $this->get('translator')->trans('comment.placeholder') )))
                    ->getForm();

                if ($request->isMethod('POST')) {

                    $form->bind($request);

                    if ($form->isValid()) {

                        $comment->setUser($this->getUser());
                        $commonList->addComment($comment);
                        
                        $em = $this->getDoctrine()->getManager();
                        $em->persist($comment);
                        $em->flush();

                        $logService = $this->container->get('logService');
                        $logService->log($this->getUser(), 'user_comment_list', $this->base['standardProject'], array( 'list' => array( 'routing' => 'list', 'logName' => $commonList->getLogName(), 'args' => $commonList->getLogArgs()) ));

                        $this->get('session')->getFlashBag()->add(
                            'success',
                            'Your comment was successfully added.'
                        );

                    } else {

                       $this->get('session')->getFlashBag()->add(
                            'error',
                            $this->get('translator')->trans('information.not.valid', array(), 'errors')
                        );
                    }
                    
                    return $this->redirect($this->generateUrl('sp_show_project_list', array('slug' => $slug, 'id' => $id, 'pageSlug' => $commonList->getSlug())));

                } else {

                    $route = $this->get('router')->generate('sp_show_project_list_comment', array('slug' => $slug, 'id' => $id));

                    return $this->render('metaGeneralBundle:Comment:commentBox.html.twig', 
                        array('object' => $commonList, 'route' => $route, 'form' => $form->createView()));

                }
            }
        
        }

        throw $this->createNotFoundException('This list does not exist');

    }
}
