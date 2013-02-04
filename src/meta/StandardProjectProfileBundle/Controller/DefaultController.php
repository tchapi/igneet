<?php

namespace meta\StandardProjectProfileBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response;

/*
 * Importing Class definitions
 */
use meta\StandardProjectProfileBundle\Entity\StandardProject,
    meta\StandardProjectProfileBundle\Form\Type\StandardProjectType;

class DefaultController extends BaseController
{

    /*  ####################################################
     *                    PROJECT LIST
     *  #################################################### */

    public function listAction($max)
    {

        $repository = $this->getDoctrine()->getRepository('metaStandardProjectProfileBundle:StandardProject');

        $standardProjects = $repository->findRecentlyCreatedStandardProjects($max);

        return $this->render('metaStandardProjectProfileBundle:Default:list.html.twig', array('standardProjects' => $standardProjects));

    }


    /*
     * Create a form for a new project AND process result if POST
     */
    public function createAction(Request $request)
    {
        
        $authenticatedUser = $this->getUser();

        $standardProject = new StandardProject();
        $form = $this->createForm(new StandardProjectType(), $standardProject);

        if ($request->isMethod('POST')) {

            $form->bind($request);

            if ($form->isValid()) {
                
                $authenticatedUser->addProjectsOwned($standardProject);

                $em = $this->getDoctrine()->getManager();
                $em->persist($standardProject);
                $em->flush();

                $logService = $this->container->get('logService');
                $logService->log($authenticatedUser, 'user_create_project', $standardProject, array() );

                $this->get('session')->setFlash(
                    'success',
                    'Your new project '.$standardProject->getName().' has successfully been created.'
                );

                return $this->redirect($this->generateUrl('sp_show_project', array('slug' => $standardProject->getSlug())));
           
            } else {
               
               $this->get('session')->setFlash(
                    'error',
                    'The information you provided does not seem valid.'
                );

            }

        }

        return $this->render('metaStandardProjectProfileBundle:Default:create.html.twig', array('form' => $form->createView()));

    }

    /*  ####################################################
     *                       PROJECT EDITION 
     *  #################################################### */

    public function editAction(Request $request, $slug){

        $this->fetchProjectAndPreComputeRights($slug, false, true);
        $response = new Response();

        if ($this->base != false) {
        
            $objectHasBeenModified = false;

            switch ($request->request->get('name')) {
                case 'name':
                    $this->base['standardProject']->setName($request->request->get('value'));
                    $objectHasBeenModified = true;
                    break;
                case 'headline':
                    $this->base['standardProject']->setHeadline($request->request->get('value'));
                    $objectHasBeenModified = true;
                    break;
                case 'about':
                    $this->base['standardProject']->setAbout($request->request->get('value'));
                    $objectHasBeenModified = true;
                    break;
                case 'file':
                    $uploadedFile = $request->files->get('file');
                    $this->base['standardProject']->setFile($uploadedFile);
                    $objectHasBeenModified = true;
                    $needsRedirect = true;
                    break;
                case 'skills':
                    $skillSlugsAsArray = $request->request->get('value');
                    
                    $repository = $this->getDoctrine()->getRepository('metaUserProfileBundle:Skill');
                    $skills = $repository->findSkillsByArrayOfSlugs($skillSlugsAsArray);
                    
                    $this->base['standardProject']->setNeededSkills($skills);
                    $objectHasBeenModified = true;
                    break;
            }

            $validator = $this->get('validator');
            $errors = $validator->validate($this->base['standardProject']);

            if ($objectHasBeenModified === true && count($errors) == 0){
                $this->base['standardProject']->setUpdatedAt(new \DateTime('now'));
                $em = $this->getDoctrine()->getManager();
                $em->flush();

                $logService = $this->container->get('logService');
                $logService->log($this->getUser(), 'user_update_project_info', $this->base['standardProject'], array());

            } elseif (count($errors) > 0) {
                $response->setStatusCode(406);
                $response->setContent($errors[0]->getMessage());
            }


        }

        if (isset($needsRedirect) && $needsRedirect) {

            if (count($errors) > 0) {
                $this->get('session')->setFlash(
                        'error',
                        $errors[0]->getMessage()
                    );
            }

            return $this->redirect($this->generateUrl('sp_show_project', array('slug' => $slug)));

        } else {
        
            return $response;
        }

    }

    public function deleteAction($slug){

        $this->fetchProjectAndPreComputeRights($slug, true, false);

        if ($this->base != false) {
        
            $em = $this->getDoctrine()->getManager();
            $em->remove($this->base['standardProject']);
            $em->flush();

            $this->get('session')->setFlash(
                    'success',
                    'The project '.$this->base['standardProject']->getName().' has been deleted successfully.'
                );
            
            return $this->redirect($this->generateUrl('sp_list_projects'));

        } else {

            $this->get('session')->setFlash(
                    'warning',
                    'You do not have sufficient privileges to delete this project.'
                );

            return $this->redirect($this->generateUrl('sp_show_project', array('slug' => $slug)));
        }

    }

    /*  ####################################################
     *                   WATCH / UNWATCH
     *  #################################################### */

    public function watchAction($slug)
    {

        $authenticatedUser = $this->getUser();

        // The actually authenticated user now watches the project with $slug
        if ($authenticatedUser) {

            $repository = $this->getDoctrine()->getRepository('metaStandardProjectProfileBundle:StandardProject');
            $standardProject = $repository->findOneBySlug($slug);

            if ( !($authenticatedUser->isWatchingProject($standardProject)) ){

                $authenticatedUser->addProjectsWatched($standardProject);

                $em = $this->getDoctrine()->getManager();
                $em->flush();

                $logService = $this->container->get('logService');
                $logService->log($authenticatedUser, 'user_watch_project', $standardProject, array());

                $this->get('session')->setFlash(
                    'success',
                    'You are now watching '.$standardProject->getName().'.'
                );

            } else {

                $this->get('session')->setFlash(
                    'warning',
                    'You are already watching '.$standardProject->getName().'.'
                );

            }

        }

        return $this->redirect($this->generateUrl('sp_show_project', array('slug' => $slug)));
    }

    public function unwatchAction($slug)
    {
        $authenticatedUser = $this->getUser();

        // The actually authenticated user now follows $user if they are not the same
        if ($authenticatedUser) {

            $repository = $this->getDoctrine()->getRepository('metaStandardProjectProfileBundle:StandardProject');
            $standardProject = $repository->findOneBySlug($slug);

            if ( $authenticatedUser->isWatchingProject($standardProject) ){

                $authenticatedUser->removeProjectsWatched($standardProject);

                $em = $this->getDoctrine()->getManager();
                $em->flush();

                $this->get('session')->setFlash(
                    'success',
                    'You are not watching '.$standardProject->getName().' anymore.'
                );

            } else {

                $this->get('session')->setFlash(
                    'warning',
                    'You are not watching '.$standardProject->getName().'.'
                );

            }

        }

        return $this->redirect($this->generateUrl('sp_show_project', array('slug' => $slug)));
    }

}
