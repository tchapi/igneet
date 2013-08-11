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

class DefaultController extends BaseController
{

    /*
     * List all projects in the given community, for the user
     */
    public function listAction($page, $sort)
    {

        $repository = $this->getDoctrine()->getRepository('metaProjectBundle:StandardProject');

        $authenticatedUser = $this->getUser();
        $community = $authenticatedUser->getCurrentCommunity();

        $totalProjects = $repository->countProjectsInCommunityForUser($community, $authenticatedUser);
        $maxPerPage = $this->container->getParameter('listings.number_of_items_per_page');

        if ( ($page-1) * $maxPerPage > $totalProjects) {
            return $this->redirect($this->generateUrl('p_list_projects', array('sort' => $sort)));
        }
        
        if (is_null($community)){
            $userCommunityGuest = true;
        } else {
            $userCommunityGuest = $this->getDoctrine()->getRepository('metaUserBundle:UserCommunity')->findBy(array('user' => $authenticatedUser->getId(), 'community' => $community->getId(), 'guest' => true));
        }

        $projects = $repository->findProjectsInCommunityForUser($community, $authenticatedUser, $page, $maxPerPage, $sort);

        $pagination = array( 'page' => $page, 'totalProjects' => $totalProjects);
        return $this->render('metaProjectBundle:Default:list.html.twig', array('projects' => $projects, 'pagination' => $pagination, 'sort' => $sort, 'userIsGuest' => ($userCommunityGuest != null)));

    }

    /*
     * Create a project
     */
    public function createAction(Request $request)
    {
        
        $authenticatedUser = $this->getUser();
        $community = $authenticatedUser->getCurrentCommunity();

        $userCommunityGuest = $this->getDoctrine()->getRepository('metaUserBundle:UserCommunity')->findBy(array('user' => $authenticatedUser->getId(), 'community' => $community->getId(), 'guest' => true));

        if ($userCommunityGuest){
            $this->get('session')->getFlashBag()->add(
                'error',
                $this->get('translator')->trans('guest.community.cannot.do')
            );
            return $this->redirect($this->generateUrl('p_list_projects'));
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

    /*
     * Edit a project (via X-Editable)
     */
    public function editAction(Request $request, $uid){

        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('edit', $request->get('token')))
            return new Response($this->get('translator')->trans('invalid.token', array(), 'errors'), 400);

        $this->fetchProjectAndPreComputeRights($uid, false, true);
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
                case 'status':
                    $this->base['standardProject']->setStatus(intval($request->request->get('value')));
                    $objectHasBeenModified = true;
                    break;
                case 'about':
                    $this->base['standardProject']->setAbout($request->request->get('value'));
                    $deepLinkingService = $this->container->get('deep_linking_extension');
                    $response = $deepLinkingService->convertDeepLinks(
                      $this->container->get('markdown.parser')->transformMarkdown($request->request->get('value'))
                    );
                    $objectHasBeenModified = true;
                    break;
                case 'community':
                    if ($this->base['standardProject']->getCommunity() === null){ 
                        $repository = $this->getDoctrine()->getRepository('metaGeneralBundle:Community\Community');
                        $community = $repository->findOneById($request->request->get('value'));
                        
                        $userCommunity = $this->getDoctrine()->getRepository('metaUserBundle:UserCommunity')->findBy(array('user' => $this->getUser()->getId(), 'community' => $community->getId(), 'guest' => false));

                        if ($community && $userCommunity){
                            $community->addProject($this->base['standardProject']);
                            $this->get('session')->getFlashBag()->add(
                                'success',
                                $this->get('translator')->trans('project.in.community', array( '%community%' => $community->getName()))
                            );
                            $logService = $this->container->get('logService');
                            $logService->log($this->getUser(), 'project_enters_community', $this->base['standardProject'], array( 'community' => array( 'logName' => $community->getLogName(), 'identifier' => $community->getId()) ) );
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
                    
                    $repository = $this->getDoctrine()->getRepository('metaUserBundle:Skill');
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
                if ($request->request->get('name') != 'status') {
                    $logService->log($this->getUser(), 'user_update_project_info', $this->base['standardProject'], array());
                } else {
                    $logService->log($this->getUser(), 'user_change_project_status', $this->base['standardProject'], array());
                }
            
            } elseif (count($errors) > 0) {

                $error = $errors[0]->getMessage();
            }

        } else {

            $error = $this->get('translator')->trans('invalid.request', array(), 'errors');

        }

        // Wraps up and either return a response or redirect
        if (isset($needsRedirect) && $needsRedirect) {

            if (!is_null($error)) {
                $this->get('session')->getFlashBag()->add(
                        'error', $error
                    );
            }

            return $this->redirect($this->generateUrl('p_show_project', array('uid' => $uid)));

        } else {
        
            if (!is_null($error)) {
                return new Response($error, 406);
            }

            return new Response($response);
        }

    }

    /*
     * Delete a project
     */
    public function deleteAction(Request $request, $uid){

        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('delete', $request->get('token')))
            return $this->redirect($this->generateUrl('p_show_project', array('uid' => $uid)));

        $this->fetchProjectAndPreComputeRights($uid, true, false);

        if ($this->base != false) {
        
            $em = $this->getDoctrine()->getManager();
            $this->base['standardProject']->delete();
            $em->flush();

            $this->get('session')->getFlashBag()->add(
                    'success',
                    $this->get('translator')->trans('project.deleted', array( '%project%' => $this->base['standardProject']->getName()))
                );
            
            return $this->redirect($this->generateUrl('p_list_projects'));

        } else {

            $this->get('session')->getFlashBag()->add(
                    'warning',
                    $this->get('translator')->trans('project.cannot.delete')
                );

            return $this->redirect($this->generateUrl('p_show_project', array('uid' => $uid)));
        }

    }

    
    /*
     * Reset the picture of a project
     */
    public function resetPictureAction(Request $request, $uid)
    {

        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('resetPicture', $request->get('token')))
            return $this->redirect($this->generateUrl('p_show_project', array('uid' => $uid)));

        $this->fetchProjectAndPreComputeRights($uid, false, true);

        if ($this->base != false) {

            $this->base['standardProject']->setPicture(null);
            $em = $this->getDoctrine()->getManager();
            $em->flush();

            $this->get('session')->getFlashBag()->add(
                        'success',
                        $this->get('translator')->trans('idea.picture.reset')
                    );

        } else {
    
            $this->get('session')->getFlashBag()->add(
                    'error',
                    $this->get('translator')->trans('project.picture.cannot.reset')
                );
        }

        return $this->redirect($this->generateUrl('p_show_project', array('uid' => $uid)));

    }

    
    /*
     * Make a project public (it must be in a community before hand)
     */
    public function makePublicAction(Request $request, $uid)
    {
        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('makePublic', $request->get('token')))
            return $this->redirect($this->generateUrl('p_show_project', array('uid' => $uid)));

        $this->fetchProjectAndPreComputeRights($uid, true, false);

        if ($this->base != false && !is_null($this->base['standardProject']->getCommunity())) {
            // A project in the private space cannot be public

            $this->base['standardProject']->setPrivate(false);
            $em = $this->getDoctrine()->getManager();
            $em->flush();

            $this->get('session')->getFlashBag()->add(
                'success',
                $this->get('translator')->trans('project.public')
            );

        } else {
    
            $this->get('session')->getFlashBag()->add(
                'error',
                $this->get('translator')->trans('project.cannot.public')
            );
        }

        return $this->redirect($this->generateUrl('p_show_project', array('uid' => $uid)));

    }

    
    /*
     * Make a project private (it is already in a community, obviously, since it's public)
     */
    public function makePrivateAction(Request $request, $uid)
    {
        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('makePrivate', $request->get('token')))
            return $this->redirect($this->generateUrl('p_show_project', array('uid' => $uid)));

        $this->fetchProjectAndPreComputeRights($uid, true, false);

        if ($this->base != false) {

            $this->base['standardProject']->setPrivate(true);
            $em = $this->getDoctrine()->getManager();
            $em->flush();

            $this->get('session')->getFlashBag()->add(
                'success',
                $this->get('translator')->trans('project.private')
            );

        } else {
    
            $this->get('session')->getFlashBag()->add(
                'error',
                $this->get('translator')->trans('project.cannot.private')
            );
        }

        return $this->redirect($this->generateUrl('p_show_project', array('uid' => $uid)));

    }  

    /*
     * Authenticated user now watches the project
     */
    public function watchAction(Request $request, $uid)
    {

        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('watch', $request->get('token')))
            return $this->redirect($this->generateUrl('p_show_project', array('uid' => $uid)));

        $menu = $this->container->getParameter('standardproject.menu');
        $this->fetchProjectAndPreComputeRights($uid, false, $menu['info']['private']);

        if ($this->base != false) {

            $authenticatedUser = $this->getUser();

            if ( !($authenticatedUser->isWatchingProject($this->base['standardProject'])) ){

                $authenticatedUser->addProjectsWatched($this->base['standardProject']);

                $em = $this->getDoctrine()->getManager();
                $em->flush();

                $logService = $this->container->get('logService');
                $logService->log($authenticatedUser, 'user_watch_project', $this->base['standardProject'], array());

                $this->get('session')->getFlashBag()->add(
                    'success',
                    $this->get('translator')->trans('project.watching', array('%project%' => $this->base['standardProject']->getName() ))
                );

            } else {

                $this->get('session')->getFlashBag()->add(
                    'warning',
                    $this->get('translator')->trans('project.already.watching', array('%project%' => $this->base['standardProject']->getName() ))
                );

            }

        } else {

            $this->get('session')->getFlashBag()->add(
                'warning',
                $this->get('translator')->trans('project.cannot.watch')
            );
        }

        return $this->redirect($this->generateUrl('p_show_project', array('uid' => $uid)));
    }

    /*
     * Authenticated user now unwatches the project
     */
    public function unwatchAction(Request $request, $uid)
    {
        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('unwatch', $request->get('token')))
            return $this->redirect($this->generateUrl('p_show_project', array('uid' => $uid)));

        $menu = $this->container->getParameter('standardproject.menu');
        $this->fetchProjectAndPreComputeRights($uid, false, $menu['info']['private']);

        if ($this->base != false) {

            $authenticatedUser = $this->getUser();

            if ( $authenticatedUser->isWatchingProject($this->base['standardProject']) ){

                $authenticatedUser->removeProjectsWatched($this->base['standardProject']);

                $em = $this->getDoctrine()->getManager();
                $em->flush();

                $this->get('session')->getFlashBag()->add(
                    'success',
                    $this->get('translator')->trans('project.unwatching', array('%project%' => $this->base['standardProject']->getName() ))
                );

            } else {

                $this->get('session')->getFlashBag()->add(
                    'warning',
                    $this->get('translator')->trans('project.not.watching', array('%project%' => $this->base['standardProject']->getName() ))
                );

            }

        } else {

            $this->get('session')->getFlashBag()->add(
                'warning',
                $this->get('translator')->trans('project.cannot.unwatch')
            );
        }

        return $this->redirect($this->generateUrl('p_show_project', array('uid' => $uid)));
    }

}
