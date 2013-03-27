<?php

namespace meta\StandardProjectProfileBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\File\File,
    Symfony\Component\HttpFoundation\Response;

/*
 * Importing Class definitions
 */
use meta\StandardProjectProfileBundle\Entity\StandardProject,
    meta\StandardProjectProfileBundle\Form\Type\StandardProjectType;

class DefaultController extends BaseController
{

    /*  ####################################################
     *  Lists the projects in the community, available for the user
     *  #################################################### */

    public function listAction($page, $sort)
    {

        $repository = $this->getDoctrine()->getRepository('metaStandardProjectProfileBundle:StandardProject');

        $authenticatedUser = $this->getUser();

        $totalProjects = $repository->countProjectsInCommunityForUser($authenticatedUser->getCurrentCommunity(), $authenticatedUser);
        $maxPerPage = $this->container->getParameter('listings.number_of_items_per_page');

        if ( ($page-1) * $maxPerPage > $totalProjects) {
            return $this->redirect($this->generateUrl('sp_list_projects', array('sort' => $sort)));
        }

        $projects = $repository->findProjectsInCommunityForUser($authenticatedUser->getCurrentCommunity(), $authenticatedUser, $page, $maxPerPage, $sort);

        $pagination = array( 'page' => $page, 'totalProjects' => $totalProjects);
        return $this->render('metaStandardProjectProfileBundle:Default:list.html.twig', array('standardProjects' => $projects, 'pagination' => $pagination, 'sort' => $sort));

    }

    /*  ####################################################
     *  Creates a form for a new project AND process result if POST
     *  #################################################### */

    public function createAction(Request $request)
    {
        
        $authenticatedUser = $this->getUser();

        if ($authenticatedUser->isGuestInCurrentCommunity()){
            $this->get('session')->setFlash(
                'error',
                'You cannot create projects in this community.'
            );
            return $this->redirect($this->generateUrl('sp_list_projects'));
        }

        $standardProject = new StandardProject();
        $form = $this->createForm(new StandardProjectType(), $standardProject);

        if ($request->isMethod('POST')) {

            $form->bind($request);

            if ($form->isValid()) {
                
                $authenticatedUser->addProjectsOwned($standardProject);

                if (!is_null($authenticatedUser->getCurrentCommunity())){
                    $authenticatedUser->getCurrentCommunity()->addProject($standardProject);
                } else {
                    $standardProject->setPrivate(true); // When in private space, we force privacy
                }

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
     *  Edits the project (via POST)
     *  #################################################### */

    public function editAction(Request $request, $slug){

        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('edit', $request->get('token')))
            return new Response('Invalid token', 400);

        $this->fetchProjectAndPreComputeRights($slug, false, true);
        $error = null;
        $response = null;

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
                    $deepLinkingService = $this->container->get('meta.twig.deep_linking_extension');
                    $response = $deepLinkingService->convertDeepLinks(
                      $this->container->get('markdown.parser')->transformMarkdown($request->request->get('value'))
                    );
                    $objectHasBeenModified = true;
                    break;
                case 'community':
                    if ($this->base['standardProject']->getCommunity() === null){ 
                        $repository = $this->getDoctrine()->getRepository('metaGeneralBundle:Community\Community');
                        $community = $repository->findOneById($request->request->get('value'));
                        
                        if ($community && $this->getUser()->belongsTo($community)){
                            $community->addProject($this->base['standardProject']);
                            $this->getUser()->setCurrentCommunity($community);

                            $this->get('session')->setFlash(
                                'success',
                                'This project is now part of the community ' . $community->getName() . '.'
                            );
                            $this->get('session')->setFlash(
                                'info',
                                'You have automatically been switched to the community ' . $community->getName() . '.'
                            );

                            $objectHasBeenModified = true;
                            $needsRedirect = true;
                        }
                    }
                    break;
                case 'picture':
                    $preparedFilename = trim(__DIR__.'/../../../../web'.$request->request->get('value'));
                    
                    $targ_w = $targ_h = 300;
                    $img_r = imagecreatefromstring(file_get_contents($preparedFilename));
                    $dst_r = ImageCreateTrueColor($targ_w, $targ_h);

                    imagecopyresampled($dst_r,$img_r,0,0,
                        intval($request->request->get('x')),
                        intval($request->request->get('y')),
                        $targ_w, $targ_h, 
                        intval($request->request->get('w')),
                        intval($request->request->get('h')));
                    imagepng($dst_r, $preparedFilename.".cropped");

                    /* We need to update the date manually.
                     * Otherwise, as file is not part of the mapping,
                     * @ORM\PreUpdate will not be called and the file will not be persisted
                     */
                    $this->base['standardProject']->setUpdatedAt(new \DateTime('now')); 
                    $this->base['standardProject']->setFile(new File($preparedFilename.".cropped"));

                    $objectHasBeenModified = true;
                    $needsRedirect = true;
                    break;
                case 'skills':
                    $skillSlugsAsArray = $request->request->get('value');
                    
                    $repository = $this->getDoctrine()->getRepository('metaUserProfileBundle:Skill');
                    $skills = $repository->findSkillsByArrayOfSlugs($skillSlugsAsArray);
                    
                    $this->base['standardProject']->clearNeededSkills();
                    foreach($skills as $skill){
                        $this->base['standardProject']->addNeededSkill($skill);
                    }
                    $objectHasBeenModified = true;
                    break;
            }

            $validator = $this->get('validator');
            $errors = $validator->validate($this->base['standardProject']);

            if ($objectHasBeenModified === true && count($errors) == 0){

                $em = $this->getDoctrine()->getManager();
                $em->flush();

                $logService = $this->container->get('logService');
                $logService->log($this->getUser(), 'user_update_project_info', $this->base['standardProject'], array());
            
            } elseif (count($errors) > 0) {

                $error = $errors[0]->getMessage();
            }

        } else {

            $error = 'Invalid request';

        }

        // Wraps up and either return a response or redirect
        if (isset($needsRedirect) && $needsRedirect) {

            if (!is_null($error)) {
                $this->get('session')->setFlash(
                        'error', $error
                    );
            }

            return $this->redirect($this->generateUrl('sp_show_project', array('slug' => $slug)));

        } else {
        
            if (!is_null($error)) {
                return new Response($error, 406);
            }

            return new Response($response);
        }

    }

    /*  ####################################################
     *  Deletes a project
     *  #################################################### */

    public function deleteAction(Request $request, $slug){

        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('delete', $request->get('token')))
            return $this->redirect($this->generateUrl('sp_show_project', array('slug' => $slug)));

        $this->fetchProjectAndPreComputeRights($slug, true, false);

        if ($this->base != false) {
        
            $em = $this->getDoctrine()->getManager();
            $this->base['standardProject']->delete();
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
     *  Resets the picture for a project
     *  #################################################### */

    public function resetPictureAction(Request $request, $slug)
    {

        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('resetPicture', $request->get('token')))
            return $this->redirect($this->generateUrl('sp_show_project', array('slug' => $slug)));

        $this->fetchProjectAndPreComputeRights($slug, false, true);

        if ($this->base != false) {

            $this->base['standardProject']->setPicture(null);
            $em = $this->getDoctrine()->getManager();
            $em->flush();

            $this->get('session')->setFlash(
                        'success',
                        'The picture of the project has successfully been reset.'
                    );

        } else {
    
            $this->get('session')->setFlash(
                    'error',
                    'You cannot reset the picture for this project.'
                );
        }

        return $this->redirect($this->generateUrl('sp_show_project', array('slug' => $slug)));

    }

    
    /*  ####################################################
     *  Make a project public (only if this project is in a community)
     *  #################################################### */

    public function makePublicAction(Request $request, $slug)
    {
        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('makePublic', $request->get('token')))
            return $this->redirect($this->generateUrl('sp_show_project', array('slug' => $slug)));

        $this->fetchProjectAndPreComputeRights($slug, true, false);

        if ($this->base != false && $this->base['standardProject']->getCommunity() !== null) {
            // A project in the private space cannot be public

            $this->base['standardProject']->setPrivate(false);
            $em = $this->getDoctrine()->getManager();
            $em->flush();

            $this->get('session')->setFlash(
                        'success',
                        'This project is now public.'
                    );

        } else {
    
            $this->get('session')->setFlash(
                    'error',
                    'You cannot make this project public.'
                );
        }

        return $this->redirect($this->generateUrl('sp_show_project', array('slug' => $slug)));

    }

    
    /*  ####################################################
     *  Allows to choose a slug and validates it is not already existing. 
     *  Generally forwards to editAction
     *  #################################################### */

    public function chooseSlugAction(Request $request, $targetAsBase64)
    {

        $target = json_decode(base64_decode($targetAsBase64), true);

        if ($request->isMethod('POST')) {

            $newSlug = $request->request->get('slug');

            if ("" !== $newSlug) {

                $repository = $this->getDoctrine()->getRepository('metaStandardProjectProfileBundle:StandardProject');
                $slugExists = $repository->findOneBySlug($newSlug);

                if ($slugExists !== null){

                    $this->get('session')->setFlash(
                        'error',
                        'This slug cannot be chosen at this time. Try to choose a different slug.'
                    );

                } else {
                
                    $target['params']['token'] = $request->get('token');
                    return $this->forward($target['slug'], $target['params']);
                }
            }

        } 

        return $this->render('metaStandardProjectProfileBundle:Default:chooseSlug.html.twig', array('targetAsBase64' => $targetAsBase64, 'token' => $request->get('token')));

    }

    /*  ####################################################
     *                   WATCH / UNWATCH
     *  #################################################### */

    public function watchAction(Request $request, $slug)
    {

        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('watch', $request->get('token')))
            return $this->redirect($this->generateUrl('sp_show_project', array('slug' => $slug)));

        $menu = $this->container->getParameter('standardproject.menu');
        $this->fetchProjectAndPreComputeRights($slug, false, $menu['info']['private']);

        if ($this->base != false) {

            $authenticatedUser = $this->getUser();

            // The actually authenticated user now watches the project with $slug
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

        } else {

            $this->get('session')->setFlash(
                'warning',
                'You cannot watch this project.'
            );
        }

        return $this->redirect($this->generateUrl('sp_show_project', array('slug' => $slug)));
    }

    public function unwatchAction(Request $request, $slug)
    {
        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('unwatch', $request->get('token')))
            return $this->redirect($this->generateUrl('sp_show_project', array('slug' => $slug)));

        $menu = $this->container->getParameter('standardproject.menu');
        $this->fetchProjectAndPreComputeRights($slug, false, $menu['info']['private']);

        if ($this->base != false) {

            $authenticatedUser = $this->getUser();

            // The actually authenticated user now follows $user if they are not the same
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

        } else {

            $this->get('session')->setFlash(
                'warning',
                'You cannot watch this project.'
            );
        }

        return $this->redirect($this->generateUrl('sp_show_project', array('slug' => $slug)));
    }

}
