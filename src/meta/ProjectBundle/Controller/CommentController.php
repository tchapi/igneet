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
    public function addProjectCommentAction(Request $request, $uid)
    {

        $menu = $this->container->getParameter('project.menu');
        $this->preComputeRights(array("mustBeOwner" => false, "mustParticipate" => $menu['timeline']['private']));

        if ($this->access != false) {

            $comment = new StandardProjectComment();
            $form = $this->createFormBuilder($comment)
                ->add('text', 'textarea', array('required' => false, 'attr' => array('placeholder' => $this->get('translator')->trans('comment.placeholder') )))
                ->getForm();

            if ($request->isMethod('POST')) {

                $comment->setText($request->get('comment'));
                $comment->setUser($this->getUser());

                $errors = $this->get('validator')->validate($comment);

                if (count($errors) == 0) {
                    
                    $comment->linkify();
                    $this->base['project']->addComment($comment);
                    
                    $em = $this->getDoctrine()->getManager();
                    $em->persist($comment);
                    $em->flush();

                    $logService = $this->container->get('logService');
                    $logService->log($this->getUser(), 'user_comment_project', $this->base['project'], array());

                    $renderedComment = $this->renderView('metaGeneralBundle:Log:logItemComment.html.twig', array('comment' => $comment));

                    return new Response(json_encode(array( 'comment' => $renderedComment, 'message' => $this->get('translator')->trans('comment.added'))), 200, array('Content-Type'=>'application/json'));

                } else {

                   return new Response(json_encode(array('message' => $this->get('translator')->trans('information.not.valid', array(), 'errors'))), 400, array('Content-Type'=>'application/json'));
                }

            } else {

                $route = $this->get('router')->generate('p_show_project_comment', array('uid' => $uid));

                return $this->render('metaGeneralBundle:Comment:commentBox.html.twig', 
                    array('object' => $this->base['project'], 'route' => $route, 'form' => $form->createView()));

            }

        }

        throw $this->createNotFoundException($this->get('translator')->trans('project.not.found'));

    }

    /*
     * Output the comment form for a wiki page or add a comment to a wiki page when POST
     */
    public function addWikiPageCommentAction(Request $request, $uid, $page_uid){

        $menu = $this->container->getParameter('project.menu');
        $this->preComputeRights(array("mustBeOwner" => false, "mustParticipate" => $menu['wiki']['private']));

        if ($this->access != false) {

            $wiki = $this->base['project']->getWiki();

            if ($wiki){

                $repository = $this->getDoctrine()->getRepository('metaProjectBundle:WikiPage');
                $wikiPage = $repository->findOneByIdInWiki($this->container->get('uid')->fromUId($page_uid), $wiki->getId());

                if ($wikiPage){

                    $comment = new WikiPageComment();
                    $form = $this->createFormBuilder($comment)
                        ->add('text', 'textarea', array('required' => false, 'attr' => array('placeholder' => $this->get('translator')->trans('comment.placeholder') )))
                        ->getForm();

                    if ($request->isMethod('POST')) {

                        $comment->setText($request->get('comment'));
                        $comment->setUser($this->getUser());

                        $errors = $this->get('validator')->validate($comment);

                        if (count($errors) == 0) {
                            
                            $comment->linkify();
                            $wikiPage->addComment($comment);
                            
                            $em = $this->getDoctrine()->getManager();
                            $em->persist($comment);
                            $em->flush();

                            $logService = $this->container->get('logService');
                            $logService->log($this->getUser(), 'user_comment_wikipage', $this->base['project'], array( 'wikipage' => array( 'logName' => $wikiPage->getLogName(), 'identifier' => $wikiPage->getId()) ));

                            $renderedComment = $this->renderView('metaGeneralBundle:Log:logItemComment.html.twig', array('comment' => $comment));

                            return new Response(json_encode(array( 'comment' => $renderedComment, 'message' => $this->get('translator')->trans('comment.added'))), 200, array('Content-Type'=>'application/json'));

                        } else {

                           return new Response(json_encode(array('message' => $this->get('translator')->trans('information.not.valid', array(), 'errors'))), 400, array('Content-Type'=>'application/json'));
                        }
           
                    } else {

                        $route = $this->get('router')->generate('p_show_project_wikipage_comment', array('uid' => $uid, 'page_uid' => $page_uid ));

                        return $this->render('metaGeneralBundle:Comment:commentThread.html.twig', 
                            array('object' => $wikiPage, 'route' => $route, 'form' => $form->createView()));

                    }

                }
            }
        }

        throw $this->createNotFoundException($this->get('translator')->trans('project.wiki.not.found'));

    }

    /*
     * Output the comment form for a list or add a comment to a list when POST
     */
    public function addListCommentAction(Request $request, $uid, $list_uid){

        $menu = $this->container->getParameter('project.menu');
        $this->preComputeRights(array("mustBeOwner" => false, "mustParticipate" => $menu['lists']['private']));

        if ($this->access != false) {

            $repository = $this->getDoctrine()->getRepository('metaProjectBundle:CommonList');
            $commonList = $repository->findOneByIdInProject($this->container->get('uid')->fromUId($list_uid), $this->base['project']->getId());

            if ($commonList){
                $comment = new CommonListComment();
                $form = $this->createFormBuilder($comment)
                    ->add('text', 'textarea', array('required' => false, 'attr' => array('placeholder' => $this->get('translator')->trans('comment.placeholder') )))
                    ->getForm();

                if ($request->isMethod('POST')) {

                    $comment->setText($request->get('comment'));
                    $comment->setUser($this->getUser());

                    $errors = $this->get('validator')->validate($comment);

                    if (count($errors) == 0) {
                        
                        $comment->linkify();
                        $commonList->addComment($comment);
                        
                        $em = $this->getDoctrine()->getManager();
                        $em->persist($comment);
                        $em->flush();

                        $logService = $this->container->get('logService');
                        $logService->log($this->getUser(), 'user_comment_list', $this->base['project'], array( 'list' => array( 'logName' => $commonList->getLogName(), 'identifier' => $commonList->getId()) ));

                        $renderedComment = $this->renderView('metaGeneralBundle:Log:logItemComment.html.twig', array('comment' => $comment));

                        return new Response(json_encode(array( 'comment' => $renderedComment, 'message' => $this->get('translator')->trans('comment.added'))), 200, array('Content-Type'=>'application/json'));

                    } else {

                       return new Response(json_encode(array('message' => $this->get('translator')->trans('information.not.valid', array(), 'errors'))), 400, array('Content-Type'=>'application/json'));
                    }

                } else {

                    $route = $this->get('router')->generate('p_show_project_list_comment', array('uid' => $uid, 'list_uid' => $list_uid ));

                    return $this->render('metaGeneralBundle:Comment:commentThread.html.twig', 
                        array('object' => $commonList, 'route' => $route, 'form' => $form->createView()));

                }
            }
        
        }

        throw $this->createNotFoundException($this->get('translator')->trans('project.lists.not.found'));

    }
}
