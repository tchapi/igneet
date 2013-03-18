<?php

namespace meta\StandardProjectProfileBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response;

/*
 * Importing Class definitions
 */
use meta\StandardProjectProfileBundle\Entity\Comment\WikiPageComment,
    meta\StandardProjectProfileBundle\Entity\Comment\CommonListComment,
    meta\StandardProjectProfileBundle\Entity\Comment\StandardProjectComment;


class CommentController extends BaseController
{

    public function addStandardProjectCommentAction(Request $request, $slug){

        $this->fetchProjectAndPreComputeRights($slug, false, false);

        if ($this->base != false) {

            $comment = new StandardProjectComment();
            $form = $this->createFormBuilder($comment)
                ->add('text', 'textarea', array('attr' => array('placeholder' => 'Leave a message ...')))
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

                    $this->get('session')->setFlash(
                        'success',
                        'Your comment was successfully added.'
                    );

                } else {

                   $this->get('session')->setFlash(
                        'error',
                        'The information you provided does not seem valid.'
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

    public function addWikiPageCommentAction(Request $request, $slug, $id){

        $this->fetchProjectAndPreComputeRights($slug, false, false);

        if ($this->base != false) {

            $wiki = $this->base['standardProject']->getWiki();

            if ($wiki){

                $repository = $this->getDoctrine()->getRepository('metaStandardProjectProfileBundle:WikiPage');
                $wikiPage = $repository->findOneByIdInWiki($id, $wiki->getId());

                if ($wikiPage){
                    $comment = new WikiPageComment();
                    $form = $this->createFormBuilder($comment)
                        ->add('text', 'textarea', array('attr' => array('placeholder' => 'Leave a message ...')))
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

                            $this->get('session')->setFlash(
                                'success',
                                'Your comment was successfully added.'
                            );

                        } else {

                           $this->get('session')->setFlash(
                                'error',
                                'The information you provided does not seem valid.'
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

    public function addCommonListCommentAction(Request $request, $slug, $id){

        $this->fetchProjectAndPreComputeRights($slug, false, false);

        if ($this->base != false) {

            $repository = $this->getDoctrine()->getRepository('metaStandardProjectProfileBundle:CommonList');
            $commonList = $repository->findOneByIdInProject($id, $this->base['standardProject']->getId());

            if ($commonList){
                $comment = new CommonListComment();
                $form = $this->createFormBuilder($comment)
                    ->add('text', 'textarea', array('attr' => array('placeholder' => 'Leave a message ...')))
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

                        $this->get('session')->setFlash(
                            'success',
                            'Your comment was successfully added.'
                        );

                    } else {

                       $this->get('session')->setFlash(
                            'error',
                            'The information you provided does not seem valid.'
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
