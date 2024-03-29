<?php

namespace meta\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\Security\Core\SecurityContext,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\File\File,
    Symfony\Component\HttpFoundation\Response,
    Symfony\Component\Security\Csrf\CsrfToken;

/*
 * Importing Class definitions
 */
use meta\UserBundle\Entity\User,
    meta\UserBundle\Form\Type\UserType,
    meta\UserBundle\Entity\UserCommunity;

class UserController extends Controller
{

    /*
     * Show a user profile
     */
    public function showAction($username)
    {

        $authenticatedUser = $this->getUser();

        $repository = $this->getDoctrine()->getRepository('metaUserBundle:User');
        if ($username !== $authenticatedUser->getUsername()){
            $user = $repository->findOneByUsernameInCommunity(array('username' => $username, 'community' => $authenticatedUser->getCurrentCommunity(), 'includeGuests' => true));
        } else {
            $user = $authenticatedUser;
        }

        // If user is deleted or doesn't exist in this community
        if (!$user || $user->isDeleted()) {

            // It might still exist in another community in which the requesting user is as well ?
            $user = $repository->findOneByUsername($username);
            if (!$user || $user->isDeleted()) {
                throw $this->createNotFoundException($this->get('translator')->trans('user.not.found'));
            } else if ( $commonCommunity = $repository->findCommonCommunity($authenticatedUser, $user) ) {
                // Yes ! Switch this authenticated user to the good community if it is valid

                if ( !($commonCommunity->isValid()) ){

                    $this->get('session')->getFlashBag()->add(
                        'error',
                        $this->get('translator')->trans('community.invalid', array( "%community%" => $commonCommunity->getName()) )
                    );

                    // Back in private space, ahah
                    $authenticatedUser->setCurrentCommunity(null);
                    $em = $this->getDoctrine()->getManager();
                    $em->flush();

                    $this->get('session')->getFlashBag()->add(
                      'info',
                      $this->get('translator')->trans('private.space.back')
                    );

                    return $this->redirect($this->generateUrl('g_switch_private_space', array('token' => $this->get('security.csrf.token_manager')->getToken('switchCommunity')->getValue(), 'redirect' => true)));
                
                } else {

                    $authenticatedUser->setCurrentCommunity($commonCommunity);
                    $em = $this->getDoctrine()->getManager();
                    $em->flush();

                    $this->get('session')->getFlashBag()->add(
                      'info',
                      $this->get('translator')->trans('community.switch', array( '%community%' => $commonCommunity->getName()))
                    );
                }

            } else {
                throw $this->createNotFoundException($this->get('translator')->trans('user.not.found'));
            }
        }

        $alreadyFollowing = $authenticatedUser->isFollowing($user);
        $isMe = ($authenticatedUser->getUsername() == $username);
        $community = $authenticatedUser->getCurrentCommunity();

        // Get projects / ideas lists
        $projectRepository = $this->getDoctrine()->getRepository('metaProjectBundle:StandardProject');
        $projectsOwned = $projectRepository->findAllProjectsInCommunityForUserOwnedBy($community, $authenticatedUser, $user);
        $projectsParticipatedIn = $projectRepository->findAllProjectsInCommunityForUserParticipatedInBy($community, $authenticatedUser, $user);

        $ideaRepository = $this->getDoctrine()->getRepository('metaIdeaBundle:Idea');
        $ideasCreated = $ideaRepository->findAllIdeasInCommunityCreatedBy($community, $user);
        $ideasParticipatedIn = $ideaRepository->findAllIdeasInCommunityParticipatedInBy($community, $user);

        // Followers / Followings
        $followers = $repository->findAllFollowersInCommunityForUser(array( 'community' => $community, 'user' => $user));
        $following = $repository->findAllFollowingInCommunityForUser(array( 'community' => $community, 'user' => $user));

        // Watching projects / ideas
        $ideasWatched = $ideaRepository->findAllIdeasWatchedInCommunityForUser($community, $user);
        $projectsWatched = $projectRepository->findAllProjectsWatchedInCommunityForUser($community, $user);

        $targetAvatarAsBase64 = array ('slug' => 'metaUserBundle:User:edit', 'params' => array('username' => $username ), 'crop' => true, 'filetypes' => array('png', 'jpg', 'jpeg', 'gif'));

        return $this->render('metaUserBundle:User:show.html.twig', 
            array('user' => $user,
                  'alreadyFollowing' => $alreadyFollowing,
                  'canEdit' => $isMe,
                  'targetAvatarAsBase64' => base64_encode(json_encode($targetAvatarAsBase64)),
                  'projectsOwned' => $projectsOwned,
                  'projectsParticipatedIn' => $projectsParticipatedIn,
                  'ideasCreated' => $ideasCreated,
                  'ideasParticipatedIn' => $ideasParticipatedIn,
                  'followers' => $followers,
                  'following' => $following,
                  'projectsWatched' => $projectsWatched,
                  'ideasWatched' => $ideasWatched
                ));
    }

    /* 
     * Show My own user profile
     */
    public function showMeAction()
    {
        return $this->redirect($this->generateUrl('u_show_user_profile', array('username' => $this->getUser()->getUsername())));
    }

    /*
     * Corresponding actions
     */ 
    public function countNotificationsAction()
    {
        $authenticatedUser = $this->getUser();
        $logService = $this->container->get('logService');

        return new Response($logService->countNotifications($authenticatedUser, null));
    }

    /*
     * Show the notifications for a user
     */
    public function showNotificationsAction($date)
    {
    
        $authenticatedUser = $this->getUser();
        $logService = $this->container->get('logService');

        // In case the last notification to display is before the date we want to display, when we just clicked on notifications
        $lastNotificationDate = $logService->getLastNotificationDate($authenticatedUser, null);
        if ($authenticatedUser->getLastNotifiedAt() > $lastNotificationDate && $date == null && $lastNotificationDate < new \DateTime("now - 1 week") ){
            $date = new \DateTime($lastNotificationDate->format('Y-m-d H:i:s') . " - 1 week");
            $date = $date->format('Y-m-d H:i:s');

            $this->get('session')->getFlashBag()->add(
                'success',
                $this->get('translator')->trans('user.notifications.none.auto')
            );

        }

        $notifications = $logService->getNotifications($authenticatedUser, $date, null, null);
        $count = 0;
        foreach ($notifications['notifications'] as $notification) {
            if ($notification["createdAt"] > $authenticatedUser->getLastNotifiedAt()) {
                $count++;
            }
        }
        $params = array_merge(array('user' => $authenticatedUser, 'date' => $date, 'newNotifications' => $count), $notifications);

        return $this->render('metaUserBundle:User:showNotifications.html.twig', $params);
    }

    /*
     * Mark all notifications as read
     * NEEDS JSON
     */
    public function markNotificationsReadAction(Request $request)
    {
        
        if (!$this->get('security.csrf.token_manager')->isTokenValid(new CsrfToken('markRead', $request->get('token')))) {
            return new Response(
                json_encode(
                    array(
                        'message' => $this->get('translator')->trans('invalid.token', array(), 'errors'))
                    ), 
                400, 
                array('Content-Type'=>'application/json')
            );
        }

        $authenticatedUser = $this->getUser();
        $authenticatedUser->setLastNotifiedAt(new \DateTime('now'));

        $em = $this->getDoctrine()->getManager();
        $em->flush();

        return new Response(json_encode(array('redirect' => $this->generateUrl('u_show_user_notifications'))), 200, array('Content-Type'=>'application/json'));
    }

    /*
     * Edit a user
     * NEEDS JSON
     */
    public function editAction(Request $request, $username)
    {

        if (!$this->get('security.csrf.token_manager')->isTokenValid(new CsrfToken('edit', $request->get('token')))) {
            return new Response(
                json_encode(
                    array(
                        'message' => $this->get('translator')->trans('invalid.token', array(), 'errors'))
                    ), 
                400, 
                array('Content-Type'=>'application/json')
            );
        }

        $authenticatedUser = $this->getUser();

        $error = null;
        $response = null;

        if ($authenticatedUser->getUsername() === $username) {

            $objectHasBeenModified = false;

            switch ($request->request->get('name')) {
                case 'first_name':
                    $authenticatedUser->setFirstName($request->request->get('value'));
                    $objectHasBeenModified = true;
                    break;
                case 'last_name':
                    $authenticatedUser->setLastName($request->request->get('value'));
                    $objectHasBeenModified = true;
                    break;
                case 'city':
                    $authenticatedUser->setCity($request->request->get('value'));
                    $objectHasBeenModified = true;
                    break;
                case 'headline':
                    $authenticatedUser->setHeadline($request->request->get('value'));
                    $objectHasBeenModified = true;
                    break;
                case 'about':
                    $authenticatedUser->setAbout($request->request->get('value'));
                    $objectHasBeenModified = true;
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
                        intval($request->request->get('h'))
                    );
                    imagepng($dst_r, $preparedFilename.'.cropped');

                    /* We need to update the date manually : it's done later in the action.
                     * Otherwise, as file is not part of the mapping,
                     * @ORM\PreUpdate will not be called and the file will not be persisted
                     */
                    $authenticatedUser->setFile(new File($preparedFilename.'.cropped'));

                    $objectHasBeenModified = true;
                    $needsRedirect = true;
                    break;
                case 'skills':
                    $repository = $this->getDoctrine()->getRepository('metaUserBundle:Skill');
                    $skill = $repository->findOneBySlug($request->request->get('key'));
                    
                    if ($request->request->get('value') == 'remove' && $authenticatedUser->hasSkill($skill)) {
                        $authenticatedUser->removeSkill($skill);
                        $objectHasBeenModified = true;
                    } else if ($request->request->get('value') == 'add' && !$authenticatedUser->hasSkill($skill)) {
                        $authenticatedUser->addSkill($skill);
                        $response = array('skill' => $this->renderView('metaUserBundle:Skills:skill.html.twig', array( 'skill' => $skill, 'canEdit' => true)));
                        $objectHasBeenModified = true;
                    }

                    break;
            }

            $errors = $this->get('validator')->validate($authenticatedUser);

            if ($objectHasBeenModified === true && count($errors) == 0){

                /* We have no PreUpdate trigger on User to keep the last_seen_at behaviour */
                $authenticatedUser->setUpdatedAt(new \DateTime('now'));
                $logService = $this->container->get('logService');
                $logService->log($authenticatedUser, 'user_update_profile', $authenticatedUser, array());

                $em = $this->getDoctrine()->getManager();
                $em->flush();

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

            return $this->redirect($this->generateUrl('u_show_user_profile', array('username' => $username)));

        } else {
            
            if (!is_null($error)) {
                return new Response(json_encode(array('message' => $error)), 406, array('Content-Type'=>'application/json'));
            }

            return new Response(json_encode($response), 200, array('Content-Type'=>'application/json'));
        }

    }

    /*
     * Reset the avatar of a user
     */
    public function resetAvatarAction(Request $request, $username)
    {

        if (!$this->get('security.csrf.token_manager')->isTokenValid(new CsrfToken('resetAvatar', $request->get('token')))) {
            $this->get('session')->getFlashBag()->add(
                'error',
                $this->get('translator')->trans('invalid.token', array(), 'errors')
            );
            return $this->redirect($this->generateUrl('u_show_user_profile', array('username' => $this->getUser()->getUsername())));
        }

        $authenticatedUser = $this->getUser();

        if ($authenticatedUser->getUsername() !== $username) {

            $this->get('session')->getFlashBag()->add(
                'error',
                $this->get('translator')->trans('user.picture.cannot.reset')
            );

        } else {

            $this->getUser()->setAvatar(null);
            $this->getUser()->setUpdatedAt(new \DateTime('now'));
            $em = $this->getDoctrine()->getManager();
            $em->flush();

            $this->get('session')->getFlashBag()->add(
                'success',
                $this->get('translator')->trans('user.picture.reset')
            );
    
        }

        return $this->redirect($this->generateUrl('u_show_user_profile', array('username' => $this->getUser()->getUsername())));

    }

    /*
     * Delete a user
     */
    public function deleteAction(Request $request, $username)
    {

        if (!$this->get('security.csrf.token_manager')->isTokenValid(new CsrfToken('delete', $request->get('token')))) {
            $this->get('session')->getFlashBag()->add(
                'error',
                $this->get('translator')->trans('invalid.token', array(), 'errors')
            );
            return $this->redirect($this->generateUrl('u_show_user_profile', array('username' => $this->getUser()->getUsername())));
        }

        $authenticatedUser = $this->getUser();
        $em = $this->getDoctrine()->getManager();

        if ($authenticatedUser->getUsername() === $username) {
        
            $deletable = true;

            // Performs checks for ownerships
            $projects = "";
            foreach ($authenticatedUser->getProjectsOwned() as $project) {

                // If we are the only owner, we might have a problem.
                if (!$project->isDeleted() && $project->countOwners() == 1){

                    if ($project->getCommunity() == null){
                        // It's a private space project : kill it !
                        $project->delete();

                    } else {

                        // Not good : we're the only owner, and the project is in a community
                        $deletable = false;
                        $projects .= $project->getName(). ",";

                    }
                }
            }

            // Performs checks for community management
            $communities = "";
            $userRepository = $this->getDoctrine()->getRepository('metaUserBundle:User');

            // Yes, we have to check for each community
            foreach ($authenticatedUser->getUserCommunities() as $userCommunity) {
                
                $community = $userCommunity->getCommunity();
                $nbManagersInCommunity = $userRepository->countManagersInCommunity(array('community' => $community));
                $nbUsersInCommunity = $userRepository->countUsersInCommunity(array('community' => $community, 'includeGuests' => false));

                // Do we have a single manager ? Then we might have a problem. Else, no.
                if ($nbManagersInCommunity == 1 && $userCommunity->isManager()){
                
                    // Two cases : either it's a community the user created and he is the single user/manager in it,
                    // or he is the only manager of a community of others, in which case he is not deletable
                    if ($nbUsersInCommunity == 1) {

                        // If we remove a community, we need to remove the userInviteToken for this community
                        $userInviteTokenRepository = $this->getDoctrine()->getRepository('metaUserBundle:UserInviteToken');
                        $userInviteTokens = $userInviteTokenRepository->findByCommunity($community);
                        foreach ($userInviteTokens as $userInviteToken) {
                            $em->remove($userInviteToken);
                        }

                        // And the logs as well
                        $logsRepository = $this->getDoctrine()->getRepository('metaGeneralBundle:Log\BaseLogEntry');
                        $logs = $logsRepository->findByCommunity($community);
                        foreach ($logs as $log) {
                            $em->remove($log);
                        }

                        // And the projects & users owns / participating
                        $ideasRepository = $this->getDoctrine()->getRepository('metaIdeaBundle:Idea');
                        $ideas = $ideasRepository->findByCommunity($community);
                        foreach ($ideas as $idea) {
                            $em->remove($idea);
                        }

                        // And the ideas & user created / participating
                        $projectRepository = $this->getDoctrine()->getRepository('metaStandardProjectBundle:StandardProject');
                        $projects = $projectRepository->findByCommunity($community);
                        foreach ($projects as $project) {
                            $em->remove($project);
                        }

                        // Comments
                        $commentsRepository = $this->getDoctrine()->getRepository('metaGeneralProjectBundle:Comment\CommunityComment');
                        $comments = $commentsRepository->findByCommunity($community);
                        foreach ($comments as $comment) {
                            $em->remove($comment);
                        }

                        // Good to go : it's his own community and he's alone in it
                        // But we have to delete the community as well (the following is safe since it will not be flushed until $em->flush() though)
                        $em->remove($community);

                    } else {
                        
                        // Not good : we're the only manager of the community
                        $deletable = false;
                        $communities .= $userCommunity->getCommunity()->getName() . ",";
                    
                    }

                }

            }

            // Projects must have at least an owner
            if ($deletable === true ) {

                foreach ($authenticatedUser->getIdeasCreated() as $idea) {
                    if (!$idea->isDeleted() && $idea->countCreators() == 1){
                        // we're the only creator : we have to delete the idea
                        $idea->delete();
                    }
                }
                
                foreach ($authenticatedUser->getUserCommunities() as $userCommunity) {
                    // We delete the userCommunity object
                    $em->remove($userCommunity);
                }

                // Removes any previous OpenIdEntities linked to that user
                $openIdIdentityRepository = $this->getDoctrine()->getRepository('metaUserBundle:OpenIdIdentity');
                $openIdIdentities = $openIdIdentityRepository->findByUser($authenticatedUser);

                foreach ($openIdIdentities as $openIdIdentity) {
                    $em->remove($openIdIdentity);
                }

                // Removes any userInviteToken
                $userInviteTokenRepository = $this->getDoctrine()->getRepository('metaUserBundle:UserInviteToken');
                $referedUserInviteTokens = $userInviteTokenRepository->findByReferalUser($authenticatedUser);
                foreach ($referedUserInviteTokens as $referedUserInviteToken) {
                    $em->remove($referedUserInviteToken);
                }
                $resultingUserInviteTokens = $userInviteTokenRepository->findByResultingUser($authenticatedUser);
                foreach ($resultingUserInviteTokens as $resultingUserInviteToken) {
                    $em->remove($resultingUserInviteToken);
                }

                // Delete the user and the associated elements FOR REAL MOTHER FUCKERZ
                $authenticatedUser->setCurrentCommunity(null); // Just in case
                $authenticatedUser->delete();
                $em->flush();

                $this->get('security.context')->setToken(null);
                $this->get('request')->getSession()->invalidate();

                return $this->redirect($this->generateUrl('login'));

            } else {

                // Let's notify the user with the projects he still owns alone
                $this->get('session')->getFlashBag()->add(
                    'error',
                    $this->get('translator')->trans('user.cannot.delete', array( '%projects%' => substr($projects, 0, -1), '%communities%' => substr($communities, 0, -1)))
                );

                return $this->redirect($this->generateUrl('u_show_user_settings'));
            }

        } else {

            $this->get('session')->getFlashBag()->add(
                'error',
                $this->get('translator')->trans('user.cannot.delete.other')
            );

            return $this->redirect($this->generateUrl('u_show_user_settings'));
        }

    }

    /*
     * Authenticated user follows the request user
     * NEEDS JSON
     */
    public function followUserAction(Request $request, $username)
    {
        if (!$this->get('security.csrf.token_manager')->isTokenValid(new CsrfToken('followUser', $request->get('token')))) {
            return new Response(
                json_encode(
                    array(
                        'message' => $this->get('translator')->trans('invalid.token', array(), 'errors'))
                    ), 
                400, 
                array('Content-Type'=>'application/json')
            );
        }

        $authenticatedUser = $this->getUser();

        // The actually authenticated user now follows $user if they are not the same
        if ($username !== $authenticatedUser->getUsername()){

            $repository = $this->getDoctrine()->getRepository('metaUserBundle:User');
            $user = $repository->findOneByUsernameInCommunity(array('username' => $username, 'community' => $authenticatedUser->getCurrentCommunity(), 'includeGuests' => true));

            if ($user && !$user->isDeleted()){

                if (!($this->getUser()->isFollowing($user)) ){

                    $authenticatedUser->addFollowing($user);

                    $logService = $this->container->get('logService');
                    $logService->log($authenticatedUser, 'user_follow_user', $user, array());

                    $em = $this->getDoctrine()->getManager();
                    $em->flush();

                    $rendered = $this->renderView('metaUserBundle:Partials:followers.html.twig', array('user' => $user, 'alreadyFollowing' => true, 'canEdit' => false));

                    $response = array( 'div' => $rendered, 'message' => $this->get('translator')->trans('user.now.following', array('%user%' => $user->getFullName())));

                    return new Response(json_encode($response), 200, array('Content-Type'=>'application/json'));

                } else {

                    $error = $this->get('translator')->trans('user.already.following', array( '%user%' => $user->getFullName() ));

                }

            } else {

               $error = $this->get('translator')->trans('user.cannot.follow');

            }

        } else {

            $error = $this->get('translator')->trans('user.cannot.followSelf');

        }

        return new Response(json_encode(array('message' => $error)), 406, array('Content-Type'=>'application/json'));
    }

    /*
     * Authenticated user unfollows the request user
     * NEEDS JSON
     */
    public function unfollowUserAction(Request $request, $username)
    {

        if (!$this->get('security.csrf.token_manager')->isTokenValid(new CsrfToken('unfollowUser', $request->get('token')))) {
            return new Response(
                json_encode(
                    array(
                        'message' => $this->get('translator')->trans('invalid.token', array(), 'errors'))
                    ), 
                400, 
                array('Content-Type'=>'application/json')
            );
        }

        $authenticatedUser = $this->getUser();

        // The actually authenticated user now follows $user if they are not the same
        if ($username !== $authenticatedUser->getUsername()){

            $repository = $this->getDoctrine()->getRepository('metaUserBundle:User');
            $user = $repository->findOneByUsernameInCommunity(array('username' => $username, 'community' => $authenticatedUser->getCurrentCommunity(), 'includeGuests' => true));

            if ($user && !$user->isDeleted()){

                if ($this->getUser()->isFollowing($user) ){

                    $authenticatedUser->removeFollowing($user);

                    $em = $this->getDoctrine()->getManager();
                    $em->flush();

                    $rendered = $this->renderView('metaUserBundle:Partials:followers.html.twig', array('user' => $user, 'alreadyFollowing' => false, 'canEdit' => false));

                    $response = array( 'div' => $rendered, 'message' => $this->get('translator')->trans('user.unfollowing', array('%user%' => $user->getFullName())));

                    return new Response(json_encode($response), 200, array('Content-Type'=>'application/json'));

                } else {

                    $error = $this->get('translator')->trans('user.not.following', array('%user%' => $user->getFullName()));

                }

            } else {

               $error = $this->get('translator')->trans('user.cannot.unfollow');

            }

        } else {

            $error = $this->get('translator')->trans('user.cannot.unfollowSelf');
        }
            
        return new Response(json_encode(array('message' => $error)), 406, array('Content-Type'=>'application/json'));
    }

}
