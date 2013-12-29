<?php

namespace meta\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\Security\Core\SecurityContext,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response;

/*
 * Importing Class definitions
 */
use meta\UserBundle\Entity\User;

class UsersController extends Controller
{
    /*
     * List users
     */
    public function listAction($page, $sort)
    {

        $community = $this->getUser()->getCurrentCommunity();

        // In private space : no users
        if (is_null($community)) {
            throw $this->createNotFoundException($this->get('translator')->trans('user.none.inPrivateSpace'));
        }

        $repository = $this->getDoctrine()->getRepository('metaUserBundle:User');

        $totalUsers = $repository->countUsersInCommunity(array('community' => $community));
        $maxPerPage = $this->container->getParameter('listings.number_of_items_per_page');

        if ( ($page-1) * $maxPerPage > $totalUsers) {
            return $this->redirect($this->generateUrl('u_list_users', array('sort' => $sort)));
        }

        $users = $repository->findAllUsersInCommunity($community, true, $page, $maxPerPage, $sort);

        $pagination = array( 'page' => $page, 'totalUsers' => $totalUsers);
        return $this->render('metaUserBundle:Users:list.html.twig', array('users' => $users, 'pagination' => $pagination, 'sort' => $sort ));

    }

    /*
     * List all skills (for autocompletion in javascript)
     */
    public function listSkillsAction(Request $request)
    {

        if ($request->isXmlHttpRequest()){

            $repository = $this->getDoctrine()->getRepository('metaUserBundle:Skill');
            $skills = $repository->findAll();

            $skillsAsArray = array();

            foreach($skills as $skill){
                $skillsAsArray[] = array('value' => $skill->getSlug(), 'text' => $this->get('translator')->trans($skill->getSlug() . '.name', array(), 'skills'), 'color' => $skill->getColor());
            }

            return new Response(json_encode($skillsAsArray));

        } else {

            throw $this->createNotFoundException($this->get('translator')->trans('invalid.request', array(), 'errors'));

        }

    }

    /*
     * Allow to choose for a user
     */
    public function chooseAction(Request $request, $targetAsBase64)
    {

        $authenticatedUser = $this->getUser();

        // In private space : no users
        if (is_null($authenticatedUser->getCurrentCommunity())) {
            throw $this->createNotFoundException($this->get('translator')->trans('user.none.inPrivateSpace'));
        }

        $target = json_decode(base64_decode($targetAsBase64), true);

        if ($request->isMethod('POST')) {

            // Checking the username is delegated to the resulting action
            if (isset($target['slug']) && isset($target['params']) ){

                $external = trim($request->request->get('mailOrUsername'));

                if ($target['external'] === true){
                    $target['params']['mailOrUsername'] = ($external=="")?trim($request->request->get('username')):$external;
                } else {
                    $target['params']['mailOrUsername'] = trim($request->request->get('username'));
                }

                $target['params']['token'] = $request->get('token'); // For CSRF
                return $this->forward($target['slug'], $target['params']);

            } else {

                throw $this->createNotFoundException($this->get('translator')->trans('invalid.request', array(), 'errors'));

            }

        } else {

            $repository = $this->getDoctrine()->getRepository('metaUserBundle:User');
            $users = $repository->findAllUsersInCommunityExceptMe($authenticatedUser, $authenticatedUser->getCurrentCommunity(), $target['params']['guest']);

            if (count($users) > 0 || $target['external'] == true){

                return $this->render('metaUserBundle:Users:choose.html.twig', array('users' => $users, 'external' => $target['external'], 'targetAsBase64' => $targetAsBase64, 'backLink' => isset($target['backLink'])?$target['backLink']:null, 'token' => $request->get('token')));

            } else {

                $this->get('session')->getFlashBag()->add(
                    'warning',
                    $this->get('translator')->trans('user.none.to.choose')
                );

                return $this->redirect($this->generateUrl('g_home_community'));

            }
            
        }

    }

}
