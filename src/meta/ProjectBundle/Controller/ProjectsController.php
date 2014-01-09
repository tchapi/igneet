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
    public function listAction(Request $request, $sort, $statuses)
    {

        $page = max(1, $request->request->get('page'));

        $authenticatedUser = $this->getUser();
        $community = $authenticatedUser->getCurrentCommunity();

        $repository = $this->getDoctrine()->getRepository('metaProjectBundle:StandardProject');

        $totalProjects = $repository->countProjectsInCommunityForUser(array( 'community' => $community, 'user' => $authenticatedUser, 'statuses' => $statuses));
        $maxPerPage = $this->container->getParameter('listings.number_of_items_per_page');

        if ( ($page-1) * $maxPerPage > $totalProjects) {
            return $this->redirect($this->generateUrl('p_list_projects', array('sort' => $sort)));
        }
        
        if ($request->request->get('full') == "true"){
            // We need to load all the projects from page 2 to page "$page" (the first page is already outputted in PHP)
            $projects = $repository->findProjectsInCommunityForUser(array( 'community' => $community, 'user' => $authenticatedUser, 'page' => 1, 'maxPerPage' => $maxPerPage*$page, 'sort' => $sort, 'statuses' => $statuses));
            array_splice($projects, 0, $maxPerPage);
        } else {
            // We only load the requested page
            $projects = $repository->findProjectsInCommunityForUser(array( 'community' => $community, 'user' => $authenticatedUser, 'page' => $page, 'maxPerPage' => $maxPerPage, 'sort' => $sort, 'statuses' => $statuses));
        }

        // Let's determine if user is guest in the community
        if (!is_null($community)){
            $userCommunityGuest = $this->getDoctrine()->getRepository('metaUserBundle:UserCommunity')->findBy(array('user' => $authenticatedUser->getId(), 'community' => $community->getId(), 'guest' => true, 'deleted_at' => null));
            $userIsGuest = ($userCommunityGuest != null);
        } else {
            $userCommunityGuest = null; // You're not guest in your private space
            $userIsGuest = false;
        }

        if ($request->isXmlHttpRequest()){
        
            $projectsAsArray = array();
            foreach($projects as $project){
                $projectsAsArray[] = array(
                    'url' => $this->generateUrl('p_show_project', array('uid' => $this->container->get('uid')->toUId($project->getId()))), 
                    'picture' => $project->getPicture(), 
                    'name' => $project->getName(), 
                    'isPrivate' => ($project->isPrivate()?"true":"false"), 
                    'headline' => $project->getHeadline(), 
                    'createdAt' => $project->getCreatedAt()->format($this->get('translator')->trans("date.fullFormat")), 
                    'updatedAt' => $project->getUpdatedAt()->format($this->get('translator')->trans("date.fullFormat")), 
                    'owners' => $this->get('translator')->transchoice('project.owners', $project->countOwners(), array('%count%' => $project->countOwners())), 
                    'participants' => $this->get('translator')->transchoice('project.participants', $project->countParticipants(), array('%count%' => $project->countParticipants()))
                );
            }
            return new Response(json_encode($projectsAsArray), 200, array('Content-Type'=>'application/json'));
        
        } else {
        
            // Get statuses names
            $map_status = $this->container->getParameter('project_statuses');
            $translator = $this->get('translator');
            $statuses_names = array_map( 
                function($status_code) use ($map_status, $translator) { return $translator->trans("project.info.status." . $map_status[$status_code]); }, 
                $statuses
            );

            return $this->render('metaProjectBundle:Projects:list.html.twig', array('projects' => $projects, 'totalProjects' => $totalProjects, 'sort' => $sort, 'userIsGuest' => $userIsGuest, 'statuses' => $statuses_names ));
        
        }
   
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

        return $this->render('metaProjectBundle:Projects:create.html.twig', array('form' => $form->createView()));

    }

}
