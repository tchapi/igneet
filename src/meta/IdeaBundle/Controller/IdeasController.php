<?php

namespace meta\IdeaBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\File\File,
    Symfony\Component\HttpFoundation\Response,
    Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/*
 * Importing Class definitions
 */
use meta\IdeaBundle\Entity\Idea,
    meta\IdeaBundle\Form\Type\IdeaType;

class IdeasController extends Controller
{

    public function preExecute(Request $request)
    {

        $authenticatedUser = $this->getUser();
        $community = $authenticatedUser->getCurrentCommunity();

        if (!is_null($community)){

            $userCommunityGuest = $this->getDoctrine()->getRepository('metaUserBundle:UserCommunity')->findBy(array('user' => $authenticatedUser->getId(), 'community' => $community->getId(), 'guest' => true));
        
            // User is guest in community
            if ($userCommunityGuest){
                throw new AccessDeniedHttpException($this->get('translator')->trans('guest.community.cannot.access'), null);
            }
 
            // And that community is valid ?
            if ( !($community->isValid()) ){

                $this->get('session')->getFlashBag()->add(
                    'error',
                    $this->get('translator')->trans('community.invalid', array( "%community%" => $community->getName()) )
                );

                // Back in private space, ahah
                $authenticatedUser->setCurrentCommunity(null);
                $em = $this->getDoctrine()->getManager();
                $em->flush();

                $this->get('session')->getFlashBag()->add(
                  'info',
                  $this->get('translator')->trans('private.space.back')
                );

                return $this->redirect($this->generateUrl('g_switch_private_space', array('token' => $this->get('form.csrf_provider')->generateCsrfToken('switchCommunity'), 'redirect' => true)));
            }

        }

    }

    /*
     * List all the ideas in the community
     */
    public function listAction(Request $request, $archived, $sort)
    {

        $page = max(1, $request->request->get('page'));

        $authenticatedUser = $this->getUser();
        $community = $authenticatedUser->getCurrentCommunity();

        $repository = $this->getDoctrine()->getRepository('metaIdeaBundle:Idea');

        $totalIdeas = $repository->countIdeasInCommunityForUser(array( 'community' => $community, 'user' => $authenticatedUser, 'archived' => $archived));
        $maxPerPage = $this->container->getParameter('listings.number_of_items_per_page');

        if ( ($page-1) * $maxPerPage > $totalIdeas) {
            if ($request->isXmlHttpRequest()){
                // No content
                return new Response(null, 204, array('Content-Type'=>'application/json'));
            } else {
                return $this->redirect($this->generateUrl('i_list_ideas', array('sort' => $sort)));
            }
        }

        if ($request->request->get('full') == "true"){
            // We need to load all the ideas from page 2 to page "$page" (the first page is already outputted in PHP)
            $ideas = $repository->findIdeasInCommunityForUser(array( 'community' => $community, 'user' => $authenticatedUser, 'archived' => $archived, 'page' => 1, 'maxPerPage' => $maxPerPage*$page, 'sort' => $sort));
            array_splice($ideas, 0, $maxPerPage);
        } else {
            // We only load the requested page
            $ideas = $repository->findIdeasInCommunityForUser(array( 'community' => $community, 'user' => $authenticatedUser, 'archived' => $archived, 'page' => $page, 'maxPerPage' => $maxPerPage, 'sort' => $sort));
        }

        if ($request->isXmlHttpRequest()){

            $response = array('objects' => $this->renderView('metaIdeaBundle:Ideas:list.ideas.html.twig', array('ideas' => $ideas)));
            return new Response(json_encode($response), 200, array('Content-Type'=>'application/json'));
        
        } else {
        
            return $this->render('metaIdeaBundle:Ideas:list.html.twig', array('ideas' => $ideas, 'archived' => $archived, 'totalIdeas' => $totalIdeas, 'sort' => $sort));
        
        }
        
    }

    /*
     * Create a form for a new idea AND process result when POSTed
     */
    public function createAction(Request $request)
    {
        
        $authenticatedUser = $this->getUser();
        $community = $authenticatedUser->getCurrentCommunity();
       
        $idea = new Idea();
        $form = $this->createForm(new IdeaType(), $idea, array('allowCreators' => !is_null($community), 'community' => $community, 'translator' => $this->get('translator') ));

        if ($request->isMethod('POST')) {

            $form->bind($request);

            // Prevents users in the private space from creating ideas with other creators
            if ($form->isValid() && ( !is_null($community) || count($idea->getCreators()) === 0 ) ) {

                if ( !$idea->getCreators()->contains($authenticatedUser) ){
                    $idea->addCreator($authenticatedUser);
                }

                if ( !is_null($community) ){
                    $community->addIdea($idea);
                }

                $em = $this->getDoctrine()->getManager();
                $em->persist($idea);
                $em->flush();
                
                $logService = $this->container->get('logService');
                $logService->log($authenticatedUser, 'user_create_idea', $idea, array());

                $this->get('session')->getFlashBag()->add(
                    'success',
                    $this->get('translator')->trans('idea.created', array( '%idea%' => $idea->getName()))
                );

                return $this->redirect($this->generateUrl('i_show_idea', array('uid' => $this->container->get('uid')->toUId($idea->getId()))));
           
            } else {
               
               $this->get('session')->getFlashBag()->add(
                    'error',
                    $this->get('translator')->trans('information.not.valid', array(), 'errors')
                );

            }

        }

        return $this->render('metaIdeaBundle:Ideas:create.html.twig', array('form' => $form->createView()));

    }

}
