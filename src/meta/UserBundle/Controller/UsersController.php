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
    public function listAction(Request $request, $sort)
    {

        $page = max(1, $request->request->get('page'));

        $community = $this->getUser()->getCurrentCommunity();

        // In private space : no users
        if (is_null($community)) {
            throw $this->createNotFoundException($this->get('translator')->trans('user.none.inPrivateSpace'));
        }

        $repository = $this->getDoctrine()->getRepository('metaUserBundle:User');

        $totalUsers = $repository->countUsersInCommunity(array('community' => $community, 'includeGuests' => true));
        $maxPerPage = $this->container->getParameter('listings.number_of_items_per_page');

        if ( ($page-1) * $maxPerPage > $totalUsers) {
            return $this->redirect($this->generateUrl('u_list_users', array('sort' => $sort)));
        }

        if ($request->request->get('full') == "true"){
            // We need to load all the uers from page 2 to page "$page" (the first page is already outputted in PHP)
            $usersAndIsGuest = $repository->findAllUsersInCommunity(array( 'community' => $community, 'includeGuests' => true, 'page' => 1, 'maxPerPage' => $maxPerPage*$page, 'sort' => $sort));
            array_splice($usersAndIsGuest, 0, $maxPerPage);
        } else {
            // We only load the requested page
            $usersAndIsGuest = $repository->findAllUsersInCommunity(array( 'community' => $community, 'includeGuests' => true, 'page' => $page, 'maxPerPage' => $maxPerPage, 'sort' => $sort));
        }

        if ($request->isXmlHttpRequest()){
        
            $usersAsArray = array();
            foreach($usersAndIsGuest as $userAndIsGuest){
                $user = $userAndIsGuest['user'];
                $usersAsArray[] = array(
                    'url' => $this->generateUrl('u_show_user_profile', array('username' => $user->getUsername())), 
                    'picture' => $user->getAvatar(), 
                    'name' => $user->getFullName(), 
                    'isGuest' => ($userAndIsGuest['isGuest']?"true":"false"), 
                    'headline' => $user->getHeadline(), 
                    'createdAt' => $user->getCreatedAt()->format($this->get('translator')->trans("date.fullFormat")), 
                    'updatedAt' => $user->getUpdatedAt()->format($this->get('translator')->trans("date.fullFormat")),
                    'updatedAt' => $user->getLastSeenAt()->format($this->get('translator')->trans("date.fullFormat"))
                    );
            }
            return new Response(json_encode($usersAsArray), 200, array('Content-Type'=>'application/json'));
        
        } else {
        
            return $this->render('metaUserBundle:Users:list.html.twig', array('users' => $usersAndIsGuest, 'totalUsers' => $totalUsers, 'sort' => $sort ));
        
        }

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
