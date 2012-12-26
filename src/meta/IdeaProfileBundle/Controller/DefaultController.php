<?php

namespace meta\IdeaProfileBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response;

/*
 * Importing Class definitions
 */
use meta\IdeaProfileBundle\Entity\Idea;
use meta\IdeaProfileBundle\Form\Type\IdeaType;

class DefaultController extends Controller
{
    
    /*  ####################################################
     *                    IDEA LIST
     *  #################################################### */

    public function listAction($max)
    {

        $repository = $this->getDoctrine()->getRepository('metaIdeaProfileBundle:Idea');

        $ideas = $repository->findRecentlyCreatedIdeas($max);

        return $this->render('metaIdeaProfileBundle:Default:list.html.twig', array('ideas' => $ideas));

    }

    
    /*  ####################################################
     *                        SHOW
     *  #################################################### */

    public function showAction($id)
    {

        $repository = $this->getDoctrine()->getRepository('metaIdeaProfileBundle:Idea');
        $idea = $repository->findOneById($id);

        if (!$idea){
          throw $this->createNotFoundException('This idea does not exist');
        }

        $authenticatedUser = $this->getUser();
        $isAlreadyWatching = $authenticatedUser->isWatchingIdea($idea);

        $this->base = array('idea' => $idea,
                            'isAlreadyWatching' => $isAlreadyWatching
                          );
        
        return $this->render('metaIdeaProfileBundle:Default:show.html.twig', 
            array('base' => $this->base));
    }

    /*  ####################################################
     *                       IDEA CREATION 
     *  #################################################### */

    /*
     * Create a form for a new project AND process result if POST
     */
    public function createAction(Request $request)
    {
        
        $authenticatedUser = $this->getUser();

        $idea = new Idea();
        $form = $this->createForm(new IdeaType(), $idea);

        if ($request->isMethod('POST')) {

            $form->bind($request);

            if ($form->isValid()) {
                
                $idea->setCreator($authenticatedUser);
                $em = $this->getDoctrine()->getManager();
                $em->persist($idea);
                $em->flush();

                $this->get('session')->setFlash(
                    'success',
                    'The new idea '.$idea->getName().' has successfully been created.'
                );

                return $this->redirect($this->generateUrl('i_show_idea', array('id' => $idea->getId())));
           
            } else {
               
               $this->get('session')->setFlash(
                    'error',
                    'The information you provided does not seem valid.'
                );

            }

        }

        return $this->render('metaIdeaProfileBundle:Default:create.html.twig', array('form' => $form->createView()));

    }

    /*  ####################################################
     *                       IDEA EDITION 
     *  #################################################### */

    public function editAction(Request $request, $id){

        $repository = $this->getDoctrine()->getRepository('metaIdeaProfileBundle:Idea');
        $idea = $repository->findOneById($id);

        if (!$idea){
          throw $this->createNotFoundException('This idea does not exist');
        }

        $authenticatedUser = $this->getUser();

        $response = new Response();
        $objectHasBeenModified = false;

        switch ($request->request->get('name')) {
            case 'name':
                $idea->setName($request->request->get('value'));
                $objectHasBeenModified = true;
                break;
            case 'headline':
                $idea->setHeadline($request->request->get('value'));
                $objectHasBeenModified = true;
                break;
            case 'concept_text':
                $idea->setConceptText($request->request->get('value'));
                $objectHasBeenModified = true;
                break;
            case 'knowledge_text':
                $idea->setKnowledgeText($request->request->get('value'));
                $objectHasBeenModified = true;
                break;
        }

        $validator = $this->get('validator');
        $errors = $validator->validate($idea);

        if ($objectHasBeenModified === true && count($errors) == 0){
            $idea->setUpdatedAt(new \DateTime('now'));
            $em = $this->getDoctrine()->getManager();
            $em->flush();
        } elseif (count($errors) > 0) {
            $response->setStatusCode(406);
            $response->setContent($errors[0]->getMessage());
        }

        return $response;

    }

    /*  ####################################################
     *                   WATCH / UNWATCH
     *  #################################################### */

    public function watchAction($id)
    {

        $authenticatedUser = $this->getUser();

        // The actually authenticated user now watches the idea with $id
        if ($authenticatedUser) {

            $repository = $this->getDoctrine()->getRepository('metaIdeaProfileBundle:Idea');
            $idea = $repository->findOneById($id);

            if ( !($authenticatedUser->isWatchingIdea($idea)) ){

                $authenticatedUser->addIdeasWatched($idea);

                $em = $this->getDoctrine()->getManager();
                $em->flush();

                $this->get('session')->setFlash(
                    'success',
                    'You are now watching '.$idea->getName().'.'
                );

            } else {

                $this->get('session')->setFlash(
                    'warning',
                    'You are already watching '.$idea->getName().'.'
                );

            }

        }

        return $this->redirect($this->generateUrl('i_show_idea', array('id' => $id)));
    }

    public function unwatchAction($id)
    {
        $authenticatedUser = $this->getUser();

        // The actually authenticated user now follows $user if they are not the same
        if ($authenticatedUser) {

            $repository = $this->getDoctrine()->getRepository('metaIdeaProfileBundle:Idea');
            $idea = $repository->findOneById($id);

            if ( $authenticatedUser->isWatchingIdea($idea) ){

                $authenticatedUser->removeIdeasWatched($idea);

                $em = $this->getDoctrine()->getManager();
                $em->flush();

                $this->get('session')->setFlash(
                    'success',
                    'You are not watching '.$idea->getName().' anymore.'
                );

            } else {

                $this->get('session')->setFlash(
                    'warning',
                    'You are not watching '.$idea->getName().'.'
                );

            }

        }

        return $this->redirect($this->generateUrl('i_show_idea', array('id' => $id)));
    }
}
