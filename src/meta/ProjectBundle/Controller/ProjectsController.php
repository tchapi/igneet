<?php

namespace meta\ProjectBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\File\File,
    Symfony\Component\HttpFoundation\Response;

/*
 * Importing Class definitions
 */
use meta\ProjectBundle\Entity\StandardProject,
    meta\ProjectBundle\Form\Type\StandardProjectType;

class ProjectsController extends Controller
{

    public function preExecute(Request $request)
    {

        $authenticatedUser = $this->getUser();
        $community = $authenticatedUser->getCurrentCommunity();

        if (!is_null($community)){

            // That community is valid ?
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
     * List all projects in the given community, for the user
     */
    public function listAction($page, $sort, $statuses)
    {

        $repository = $this->getDoctrine()->getRepository('metaProjectBundle:StandardProject');

        $authenticatedUser = $this->getUser();
        $community = $authenticatedUser->getCurrentCommunity();

        $totalProjects = $repository->countProjectsInCommunityForUser($community, $authenticatedUser, $statuses);
        $maxPerPage = $this->container->getParameter('listings.number_of_items_per_page');

        if ( ($page-1) * $maxPerPage > $totalProjects) {
            return $this->redirect($this->generateUrl('p_list_projects', array('sort' => $sort)));
        }
        
        if (!is_null($community)){
            
            $userCommunityGuest = $this->getDoctrine()->getRepository('metaUserBundle:UserCommunity')->findBy(array('user' => $authenticatedUser->getId(), 'community' => $community->getId(), 'guest' => true, 'deleted_at' => null));
        
        } else {
            
            $userCommunityGuest = null; // You're not guest in your private space
        }

        $projects = $repository->findProjectsInCommunityForUser($community, $authenticatedUser, $page, $maxPerPage, $sort, $statuses);

        $map_status = $this->container->getParameter('project_statuses');
        $translator = $this->get('translator');
        $statuses_names = array_map( 
                function($status_code) use ($map_status, $translator) { return $translator->trans("project.info.status." . $map_status[$status_code]); }, 
                $statuses
        ); 

        $pagination = array( 'page' => $page, 'totalProjects' => $totalProjects);
        return $this->render('metaProjectBundle:Default:list.html.twig', array('projects' => $projects, 'pagination' => $pagination, 'sort' => $sort, 'userIsGuest' => ($userCommunityGuest != null), 'statuses' => $statuses_names ));

    }

    /*
     * Create a project
     */
    public function createAction(Request $request)
    {
        
        $authenticatedUser = $this->getUser();
        $community = $authenticatedUser->getCurrentCommunity();

        if (!is_null($community)){
            
            $userCommunityGuest = $this->getDoctrine()->getRepository('metaUserBundle:UserCommunity')->findBy(array('user' => $authenticatedUser->getId(), 'community' => $community->getId(), 'guest' => true, 'deleted_at' => null));
        
            if ($userCommunityGuest){
                $this->get('session')->getFlashBag()->add(
                    'error',
                    $this->get('translator')->trans('guest.community.cannot.do')
                );
                return $this->redirect($this->generateUrl('p_list_projects'));
            }

        }

        $project = new StandardProject();
        $form = $this->createForm(new StandardProjectType(), $project, array( 'translator' => $this->get('translator'), 'isPrivate' => is_null($community)));

        if ($request->isMethod('POST')) {

            $form->bind($request);

            if ($form->isValid()) {
                
                $authenticatedUser->addProjectsOwned($project);

                if (!is_null($community)){
                    $community->addProject($project);
                } else {
                    $project->setPrivate(true); // When in private space, we force privacy
                }

                $em = $this->getDoctrine()->getManager();
                $em->persist($project);
                $em->flush();

                $logService = $this->container->get('logService');
                $logService->log($authenticatedUser, 'user_create_project', $project, array() );

                $this->get('session')->getFlashBag()->add(
                    'success',
                    $this->get('translator')->trans('project.created', array( '%project%' => $project->getName()))
                );

                return $this->redirect($this->generateUrl('p_show_project', array('uid' => $this->container->get('uid')->toUId($project->getId()) )));
           
            } else {
               
               $this->get('session')->getFlashBag()->add(
                    'error',
                    $this->get('translator')->trans('information.not.valid', array(), 'errors')
                );

            }

        }

        return $this->render('metaProjectBundle:Default:create.html.twig', array('form' => $form->createView()));

    }

}
