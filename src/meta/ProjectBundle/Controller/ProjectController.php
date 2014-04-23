<?php

namespace meta\ProjectBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\File\File,
    Symfony\Component\HttpFoundation\Response;

class ProjectController extends BaseController
{
    
    /*
     * Edit a project (via X-Editable)
     */
    public function editAction(Request $request, $uid)
    {

        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('edit', $request->get('token'))) {
            return new Response(
                json_encode(
                    array(
                        'message' => $this->get('translator')->trans('invalid.token', array(), 'errors'))
                    ), 
                400, 
                array('Content-Type'=>'application/json')
            );
        }

        $this->preComputeRights(array('mustBeOwner' => false, 'mustParticipate' => true));

        $error = null;
        $response = null;

        if ($this->access != false) {
        
            $objectHasBeenModified = false;

            switch ($request->request->get('name')) {
                case 'name':
                    $this->base['project']->setName($request->request->get('value'));
                    $objectHasBeenModified = true;
                    break;
                case 'headline':
                    $this->base['project']->setHeadline($request->request->get('value'));
                    $objectHasBeenModified = true;
                    break;
                case 'status':
                    $this->base['project']->setStatus(intval($request->request->get('value')));
                    $objectHasBeenModified = true;
                    break;
                case 'about':
                    $this->base['project']->setAbout($request->request->get('value'));
                    $objectHasBeenModified = true;
                    break;
                case 'community':
                    if ($this->base['project']->getCommunity() === null){ 
                        $repository = $this->getDoctrine()->getRepository('metaGeneralBundle:Community\Community');
                        $community = $repository->findOneById($this->container->get('uid')->fromUId($request->request->get('value')));
                        
                        if (!is_null($community)){
                            
                            $userCommunity = $this->getDoctrine()->getRepository('metaUserBundle:UserCommunity')->findOneBy(array('user' => $this->getUser()->getId(), 'community' => $community->getId(), 'guest' => false));

                            if ($userCommunity){
                                $community->addProject($this->base['project']);
                                $this->get('session')->getFlashBag()->add(
                                    'success',
                                    $this->get('translator')->trans('project.in.community', array( '%community%' => $community->getName()))
                                );
                                $logService = $this->container->get('logService');
                                $logService->log($this->getUser(), 'project_enters_community', $this->base['project'], array( 'community' => array( 'logName' => $community->getLogName(), 'identifier' => $community->getId()) ) );
                                $objectHasBeenModified = true;
                            }
                        }
                        
                        $needsRedirect = true;

                    }
                    break;
                case 'file': // In this case, no file was passed to upload, so we just pass our way
                    $needsRedirect = true;
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
                    $this->base['project']->update(); 
                    $this->base['project']->setFile(new File($preparedFilename.".cropped"));

                    $objectHasBeenModified = true;
                    $needsRedirect = true;
                    break;
                case 'skills':
                    $repository = $this->getDoctrine()->getRepository('metaUserBundle:Skill');
                    $skill = $repository->findOneBySlug($request->request->get('key'));
                    
                    if ($request->request->get('value') == 'remove' && $this->base['project']->hasNeededSkill($skill)) {
                        $this->base['project']->removeNeededSkill($skill);
                        $objectHasBeenModified = true;
                    } else if ($request->request->get('value') == 'add' && !$this->base['project']->hasNeededSkill($skill)) {
                        $this->base['project']->addNeededSkill($skill);
                        $response = array('skill' => $this->renderView('metaUserBundle:Skills:skill.html.twig', array( 'skill' => $skill, 'canEdit' => true)));
                        $objectHasBeenModified = true;
                    }

                    break;
            }

            $validator = $this->get('validator');
            $errors = $validator->validate($this->base['project']);

            if ($objectHasBeenModified === true && count($errors) == 0){

                $em = $this->getDoctrine()->getManager();
                $em->flush();

                $logService = $this->container->get('logService');
                if ($request->request->get('name') != 'status') {
                    $logService->log($this->getUser(), 'user_update_project_info', $this->base['project'], array());
                } else {
                    $logService->log($this->getUser(), 'user_change_project_status', $this->base['project'], array());
                }
            
            } elseif (count($errors) > 0) {

                $error = $this->get('translator')->trans($errors[0]->getMessage());
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

            return $this->redirect($this->generateUrl('p_show_project_info', array('uid' => $uid)));

        } else {
        
            if (!is_null($error)) {
                return new Response(json_encode(array('message' => $error)), 406, array('Content-Type'=>'application/json'));
            }

            return new Response(json_encode($response), 200, array('Content-Type'=>'application/json'));
        }

    }

    /*
     * Delete a project
     */
    public function deleteAction(Request $request, $uid){

        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('delete', $request->get('token'))) {
            $this->get('session')->getFlashBag()->add(
                'error',
                $this->get('translator')->trans('invalid.token', array(), 'errors')
            );
            return $this->redirect($this->generateUrl('p_show_project_settings', array('uid' => $uid)));
        }

        $this->preComputeRights(array('mustBeOwner' => true, 'mustParticipate' => false));

        if ($this->access != false) {

            $em = $this->getDoctrine()->getManager();
            $this->base['project']->delete();
            $em->flush();

            $community = $this->base['project']->getCommunity();

            // If we had owners or participants that were only in this project AND guest in the community, we should get them out
            $communityRepository = $this->getDoctrine()->getRepository('metaGeneralBundle:Community\Community');
            $userCommunityRepository = $this->getDoctrine()->getRepository('metaUserBundle:UserCommunity');
            $guests = $communityRepository->findAllPrunableGuestsInCommunity($community);

            foreach ($guests as $guest) {
                $userCommunityToRemove = $userCommunityRepository->findOneById($guest['userCommunityId']);
                if ($userCommunityToRemove) {
                    $em->remove($userCommunityToRemove);
                    $guest['user']->setCurrentCommunity(null);
                }
            }

            $em->flush(); // Flush again for deleted guests

            $this->get('session')->getFlashBag()->add(
                    'success',
                    $this->get('translator')->trans('project.deleted', array( '%project%' => $this->base['project']->getName()))
                );
            
            return $this->redirect($this->generateUrl('p_list_projects'));

        } else {

            $this->get('session')->getFlashBag()->add(
                    'warning',
                    $this->get('translator')->trans('project.cannot.delete')
                );

            return $this->redirect($this->generateUrl('p_show_project_settings', array('uid' => $uid)));
        }

    }

    
    /*
     * Reset the picture of a project
     */
    public function resetPictureAction(Request $request, $uid)
    {

        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('resetPicture', $request->get('token'))) {
            $this->get('session')->getFlashBag()->add(
                'error',
                $this->get('translator')->trans('invalid.token', array(), 'errors')
            );
            return $this->redirect($this->generateUrl('p_show_project_info', array('uid' => $uid)));
        }

        $this->preComputeRights(array('mustBeOwner' => false, 'mustParticipate' => true));

        if ($this->access != false) {

            $this->base['project']->setPicture(null);
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

        return $this->redirect($this->generateUrl('p_show_project_info', array('uid' => $uid)));

    }

    
    /*
     * Make a project public (it must be in a community before hand)
     */
    public function makePublicAction(Request $request, $uid)
    {
        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('makePublic', $request->get('token'))) {
            $this->get('session')->getFlashBag()->add(
                'error',
                $this->get('translator')->trans('invalid.token', array(), 'errors')
            );
            return $this->redirect($this->generateUrl('p_show_project_settings', array('uid' => $uid)));
        }

        $this->preComputeRights(array('mustBeOwner' => true, 'mustParticipate' => false));

        if ($this->access != false && !is_null($this->base['project']->getCommunity())) {
            // A project in the private space cannot be public

            $this->base['project']->setPrivate(false);
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

        return $this->redirect($this->generateUrl('p_show_project_settings', array('uid' => $uid)));

    }

    
    /*
     * Make a project private (it is already in a community, obviously, since it's public)
     */
    public function makePrivateAction(Request $request, $uid)
    {
        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('makePrivate', $request->get('token'))) {
            $this->get('session')->getFlashBag()->add(
                'error',
                $this->get('translator')->trans('invalid.token', array(), 'errors')
            );
            return $this->redirect($this->generateUrl('p_show_project_settings', array('uid' => $uid)));
        }

        $this->preComputeRights(array('mustBeOwner' => true, 'mustParticipate' => false));

        if ($this->access != false) {

            $this->base['project']->setPrivate(true);
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

        return $this->redirect($this->generateUrl('p_show_project_settings', array('uid' => $uid)));

    }  

    /*
     * Authenticated user now watches the project
     * NEEDS JSON
     */
    public function watchAction(Request $request, $uid)
    {

        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('watch', $request->get('token'))) {
            return new Response(
                json_encode(
                    array(
                        'message' => $this->get('translator')->trans('invalid.token', array(), 'errors'))
                    ), 
                400, 
                array('Content-Type'=>'application/json')
            );
        }

        $menu = $this->container->getParameter('project.menu');
        $this->preComputeRights(array('mustBeOwner' => false, 'mustParticipate' => $menu['info']['private']));

        if ($this->access != false) {

            $authenticatedUser = $this->getUser();

            if ( !($authenticatedUser->isWatchingProject($this->base['project'])) ){

                $authenticatedUser->addProjectsWatched($this->base['project']);

                $em = $this->getDoctrine()->getManager();
                $em->flush();

                $logService = $this->container->get('logService');
                $logService->log($authenticatedUser, 'user_watch_project', $this->base['project'], array());

                $rendered = $this->renderView('metaProjectBundle:Partials:watchers.html.twig', array('project' => $this->base['project'], 'isAlreadyWatching' => true));

                $response = array( 'div' => $rendered, 'message' => $this->get('translator')->trans('project.now.watching', array('%project%' => $this->base['project']->getName())));

                return new Response(json_encode($response), 200, array('Content-Type'=>'application/json'));

            } else {

                $error = $this->get('translator')->trans('project.already.watching', array('%project%' => $this->base['project']->getName() ));

            }

        } else {

            $error = $this->get('translator')->trans('project.cannot.watch');

        }

        return new Response(json_encode(array('message' => $error)), 406, array('Content-Type'=>'application/json'));
    }

    /*
     * Authenticated user now unwatches the project
     * NEEDS JSON
     */
    public function unwatchAction(Request $request, $uid)
    {

        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('unwatch', $request->get('token'))) {
            return new Response(
                json_encode(
                    array(
                        'message' => $this->get('translator')->trans('invalid.token', array(), 'errors'))
                    ), 
                400, 
                array('Content-Type'=>'application/json')
            );
        }

        $menu = $this->container->getParameter('project.menu');
        $this->preComputeRights(array('mustBeOwner' => false, 'mustParticipate' => $menu['info']['private']));

        if ($this->access != false) {

            $authenticatedUser = $this->getUser();

            if ( $authenticatedUser->isWatchingProject($this->base['project']) ){

                $authenticatedUser->removeProjectsWatched($this->base['project']);

                $em = $this->getDoctrine()->getManager();
                $em->flush();

                $rendered = $this->renderView('metaProjectBundle:Partials:watchers.html.twig', array('project' => $this->base['project'], 'isAlreadyWatching' => false));

                $response = array( 'div' => $rendered, 'message' => $this->get('translator')->trans('project.unwatching', array('%project%' => $this->base['project']->getName())));

                return new Response(json_encode($response), 200, array('Content-Type'=>'application/json'));

            } else {

                $error = $this->get('translator')->trans('project.not.watching', array('%project%' => $this->base['project']->getName() ));

            }

        } else {

            $error = $this->get('translator')->trans('project.cannot.unwatch');

        }

        return new Response(json_encode(array('message' => $error)), 406, array('Content-Type'=>'application/json'));
    }

}
