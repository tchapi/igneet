<?php

namespace meta\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\File\File,
    Symfony\Component\HttpFoundation\Response,
    Symfony\Component\EventDispatcher\EventDispatcher,
    Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken,
    Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

/*
 * Importing Class definitions
 */
use meta\UserBundle\Entity\User,
    meta\UserBundle\Form\Type\UserType;

class DefaultController extends Controller
{

    /*
     * Show a user profile
     */
    public function showAction($username)
    {

        $authenticatedUser = $this->getUser();

        $repository = $this->getDoctrine()->getRepository('metaUserBundle:User');
        if ($username !== $authenticatedUser->getUsername()){
            $user = $repository->findOneByUsernameInCommunity($username, true, $authenticatedUser->getCurrentCommunity());
        } else {
            $user = $authenticatedUser;
        }

        // If user is deleted or doesn't exist in this community
        if (!$user || $user->isDeleted()) {

            // It might still exist in another community in which the requesting user is as well ?
            $user = $repository->findOneByUsername($username);
            if (!$user || $user->isDeleted()) {
                throw $this->createNotFoundException($this->get('translator')->trans('user.not.found'));
            } else if ( $commonCommunity = $user->findCommonCommunity($authenticatedUser) ) {
                // Yes ! Switch this authenticated user to the good community
                $authenticatedUser->setCurrentCommunity($commonCommunity);
                $em = $this->getDoctrine()->getManager();
                $em->flush();

                $this->get('session')->getFlashBag()->add(
                  'info',
                  $this->get('translator')->trans('community.switch', array( '%community%' => $commonCommunity->getName()))
                );
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
        $followers = $repository->findAllFollowersInCommunityForUser($community, $user);
        $following = $repository->findAllFollowingInCommunityForUser($community, $user);

        // Watching projects / ideas
        $ideasWatched = $ideaRepository->findAllIdeasWatchedInCommunityForUser($community, $user);
        $projectsWatched = $projectRepository->findAllProjectsWatchedInCommunityForUser($community, $user);

        $targetAvatarAsBase64 = array ('slug' => 'metaUserBundle:Default:edit', 'params' => array('username' => $username ), 'crop' => true);

        return $this->render('metaUserBundle:Default:show.html.twig', 
            array('user' => $user,
                  'alreadyFollowing' => $alreadyFollowing,
                  'isMe' => $isMe,
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

        $totalUsers = $repository->countUsersInCommunity($community);
        $maxPerPage = $this->container->getParameter('listings.number_of_items_per_page');

        if ( ($page-1) * $maxPerPage > $totalUsers) {
            return $this->redirect($this->generateUrl('u_list_users', array('sort' => $sort)));
        }

        $users = $repository->findAllUsersInCommunity($community, true, $page, $maxPerPage, $sort);

        $pagination = array( 'page' => $page, 'totalUsers' => $totalUsers);
        return $this->render('metaUserBundle:Default:list.html.twig', array('users' => $users, 'pagination' => $pagination, 'sort' => $sort ));

    }

    /*
     * Show a user dashboard
     */
    public function showDashboardAction()
    {

        $authenticatedUser = $this->getUser();

        if (is_null($authenticatedUser->getCurrentCommunity()) || $authenticatedUser->isGuestInCurrentCommunity() ) {

            $this->get('session')->getFlashBag()->add(
                'error',
                $this->get('translator')->trans('user.no.dashboard')
            );

            return $this->redirect($this->generateUrl('u_me'));
        } 

        // So let's get the stuff
        $logRepository = $this->getDoctrine()->getRepository('metaGeneralBundle:Log\BaseLogEntry');
        $userLogRepository = $this->getDoctrine()->getRepository('metaGeneralBundle:Log\UserLogEntry');
        $commentRepository = $this->getDoctrine()->getRepository('metaGeneralBundle:Comment\BaseComment');
        $projectRepository = $this->getDoctrine()->getRepository('metaProjectBundle:StandardProject');
        $ideaRepository = $this->getDoctrine()->getRepository('metaIdeaBundle:Idea');

        // Last recorded activity
        $lastActivity = $logRepository->findLastActivityDateForUser($authenticatedUser);

        // 7 days activity
        $last7daysActivity = $logRepository->computeWeekActivityForUser($authenticatedUser);

        // Recent social activity
        $lastSocial_raw = $userLogRepository->findLastSocialActivityForUser($authenticatedUser, 3);
        $logService = $this->container->get('logService');
        $lastSocial = array();
        foreach ($lastSocial as $entry) {
            $lastSocial[] = $logService->getText($entry);
        }

        $last7daysCommentActivity = $commentRepository->computeWeekCommentActivityForUser($authenticatedUser);
        
        // Top 3 projects
        $top3projects = $projectRepository->findTopProjectsInCommunityForUser($authenticatedUser->getCurrentCommunity(), $authenticatedUser, 3);
        $top3projectsActivity = array();

        if (count($top3projects)){
            $top3projectsActivity_raw = $projectRepository->computeWeekActivityForProjects($top3projects);
            
            foreach ($top3projectsActivity_raw as $key => $value) {
                $top3projectsActivity[$value['id']][] = $value;
            }
        }

        // Last 3 projects created in the community for user (private taken into account)
        $last3projects = $projectRepository->findLastProjectsInCommunityForUser($authenticatedUser->getCurrentCommunity(), $authenticatedUser, 3);
        
        // Top 3 ideas
        $top3ideas = $ideaRepository->findTopIdeasInCommunityForUser($authenticatedUser->getCurrentCommunity(), $authenticatedUser, 3);
        $top3ideasActivity = array();

        if (count($top3ideas)){
            $top3ideasActivity_raw = $ideaRepository->computeWeekActivityForIdeas($top3ideas);
            
            foreach ($top3ideasActivity_raw as $key => $value) {
                $top3ideasActivity[$value['id']][] = $value;
            }
        }

        // Last 3 ideas created in the community
        $last3ideas = $ideaRepository->findLastIdeasInCommunityForUser($authenticatedUser->getCurrentCommunity(), $authenticatedUser, 3);

        return $this->render('metaUserBundle:Dashboard:showDashboard.html.twig', 
            array('user' => $authenticatedUser,
                  'lastActivity' => $lastActivity['date'],
                  'last7daysActivity' => $last7daysActivity,
                  'last7daysCommentActivity' => $last7daysCommentActivity,
                  'lastSocial' => $lastSocial,
                  'top3projects' => $top3projects,
                  'top3projectsActivity' => $top3projectsActivity,
                  'last3projects' => $last3projects,
                  'top3ideas' => $top3ideas,
                  'top3ideasActivity' => $top3ideasActivity,
                  'last3ideas' => $last3ideas
                ));
    }

    /*
     * Helper for count and show Notifications (DRY-style)
     */
    private function getAllObjects()
    {

        $authenticatedUser = $this->getUser();

        // Projects
        $allProjects = array();
        foreach ($authenticatedUser->getProjectsWatched() as $project) { $allProjects[] = $project; }
        foreach ($authenticatedUser->getProjectsOwned() as $project) { $allProjects[] = $project; }
        foreach ($authenticatedUser->getProjectsParticipatedIn() as $project) { $allProjects[] = $project; }

        // Ideas
        $allIdeas = array();
        foreach ($authenticatedUser->getIdeasWatched() as $idea){ $allIdeas[] = $idea; }
        foreach ($authenticatedUser->getIdeasCreated() as $idea){ $allIdeas[] = $idea; }
        foreach ($authenticatedUser->getIdeasParticipatedIn() as $idea){ $allIdeas[] = $idea; }
            
        // Users
        $usersFollowed = $authenticatedUser->getFollowing()->toArray();

        return array( 'projects' => $allProjects,
                      'ideas'   => $allIdeas,
                      'users'   => $usersFollowed);
    }

    /*
     * Count the new notifications for a user
     * No 'backward' style as per the showNotificationsAction (it's just the count of the new stuff)
     */
    public function countNotificationsAction()
    {
        $authenticatedUser = $this->getUser();
        $from = $authenticatedUser->getLastNotifiedAt();
        
        $objects = $this->getAllObjects();

        // Around myself
        $userLogRepository = $this->getDoctrine()->getRepository('metaGeneralBundle:Log\UserLogEntry');
        $selfLogs = $userLogRepository->countLogsForUser($authenticatedUser, $from); // New followers of user

        // Fetch logs related to the projects
        if (count($objects['projects']) > 0){
            $projectLogRepository = $this->getDoctrine()->getRepository('metaGeneralBundle:Log\StandardProjectLogEntry');
            $projectLogs = $projectLogRepository->countLogsForProjects($objects['projects'], $from, $authenticatedUser);
        } else {
            $projectLogs = 0;
        }
        
        // Fetch all logs related to the ideas
        if (count($objects['ideas']) > 0){
            $ideaLogRepository = $this->getDoctrine()->getRepository('metaGeneralBundle:Log\IdeaLogEntry');
            $ideaLogs = $ideaLogRepository->countLogsForIdeas($objects['ideas'], $from, $authenticatedUser);
        } else {
            $ideaLogs = 0;
        }

        // Fetch all logs related to the users followed (their updates, or if they have created new projects or been added into one)
        // In the repository, we make sure we only get logs for the communities the current user can see
        if (count($objects['users']) > 0){
            $baseLogRepository = $this->getDoctrine()->getRepository('metaGeneralBundle:Log\BaseLogEntry');
            $userLogs = $baseLogRepository->countSocialLogsForUsersInCommunities($objects['users'], $authenticatedUser->getCommunities()->toArray(), $from);
        } else {
            $userLogs = 0;
        }

        $total = $selfLogs + $projectLogs + $ideaLogs + $userLogs;

        return new Response($total);
    }

    /*
     * Show the notifications for a user
     */
    public function showNotificationsAction($date)
    {
    
        $authenticatedUser = $this->getUser();

        // So let's get the stuff
        $lastNotified = $authenticatedUser->getLastNotifiedAt();
        $from = is_null($date)?$lastNotified:date_create($date);
        
        $objects = $this->getAllObjects();

        // Now get the logs
        $logService = $this->container->get('logService');
        $notifications = array();

        // Around myself
        $userLogRepository = $this->getDoctrine()->getRepository('metaGeneralBundle:Log\UserLogEntry');
        $selfLogs = $userLogRepository->findLogsForUser($authenticatedUser, $from); // New followers of user
        foreach ($selfLogs as $notification) { $notifications[] = array( 'createdAt' => date_create($notification->getCreatedAt()->format('Y-m-d H:i:s')), 'data' => $logService->getHTML($notification) ); }

        // Fetch logs related to the projects
        if (count($objects['projects']) > 0){
            $projectLogRepository = $this->getDoctrine()->getRepository('metaGeneralBundle:Log\StandardProjectLogEntry');
            $projectLogs = $projectLogRepository->findLogsForProjects($objects['projects'], $from, $authenticatedUser);
            foreach ($projectLogs as $notification) { $notifications[] = array( 'createdAt' => date_create($notification->getCreatedAt()->format('Y-m-d H:i:s')), 'data' => $logService->getHTML($notification) ); }
        }
        
        // Fetch all logs related to the ideas
        if (count($objects['ideas']) > 0){
            $ideaLogRepository = $this->getDoctrine()->getRepository('metaGeneralBundle:Log\IdeaLogEntry');
            $ideaLogs = $ideaLogRepository->findLogsForIdeas($objects['ideas'], $from, $authenticatedUser);
            foreach ($ideaLogs as $notification) { $notifications[] = array( 'createdAt' => date_create($notification->getCreatedAt()->format('Y-m-d H:i:s')), 'data' => $logService->getHTML($notification) ); }
        }

        // Fetch all logs related to the users followed (their updates, or if they have created new projects or been added into one)
        // In the repository, we make sure we only get logs for the communities the current user can see
        if (count($objects['users']) > 0){
            $baseLogRepository = $this->getDoctrine()->getRepository('metaGeneralBundle:Log\BaseLogEntry');
            $userLogs = $baseLogRepository->findSocialLogsForUsersInCommunities($objects['users'], $authenticatedUser->getCommunities()->toArray(), $from);
            foreach ($userLogs as $notification) { $notifications[] = array( 'createdAt' => date_create($notification->getCreatedAt()->format('Y-m-d H:i:s')), 'data' => $logService->getHTML($notification) ); }
        }

        // Sort !
        function build_sorter($key) {
            return function ($a, $b) use ($key) {
                return $a[$key]<$b[$key];
            };
        }
        $notifications = array_unique($notifications, SORT_REGULAR);
        usort($notifications, build_sorter('createdAt'));

        // Lastly, we update the last_notified_at date
        $authenticatedUser->setLastNotifiedAt(new \DateTime('now'));

        return $this->render('metaUserBundle:Dashboard:showNotifications.html.twig', 
            array('user' => $authenticatedUser,
                  'notifications' => $notifications,
                  'lastNotified' => $lastNotified,
                  'from' => $from
                ));
    }

    /*
     * Create a form for a new user to signin AND process the result when POSTed
     */
    public function createAction(Request $request, $inviteToken)
    {
        
        $authenticatedUser = $this->getUser();

        if ($authenticatedUser) {

            $this->get('session')->getFlashBag()->add(
                'warning',
                $this->get('translator')->trans('user.already.logged.long', array( '%user%' => $authenticatedUser->getUsername()))
            );

            return $this->redirect($this->generateUrl('u_show_user_profile', array('username' => $authenticatedUser->getUsername())));
        }

        // Checks the inviteToken
        if ( !is_null($inviteToken) ) {

            $tokenRepository = $this->getDoctrine()->getRepository('metaUserBundle:UserInviteToken');
            $inviteTokenObject = $tokenRepository->findOneByToken($inviteToken);

            if ( $inviteTokenObject && $inviteTokenObject->isUsed() ){

                $this->get('session')->getFlashBag()->add(
                    'error',
                    $this->get('translator')->trans('user.signup.link.already.used')
                );

                $inviteTokenObject = null;
            }

        } else {

            $inviteTokenObject = null;
        
        }

        $user = new User();
        $form = $this->createForm(new UserType(), $user, array( 'translator' => $this->get('translator')));

        if ($request->isMethod('POST')) {

            $form->bind($request);

            if ($form->isValid()) {
                
                $factory = $this->get('security.encoder_factory');
                $encoder = $factory->getEncoder($user);
                $user->setPassword($encoder->encodePassword($user->getPassword(), $user->getSalt()));

                $em = $this->getDoctrine()->getManager();
                $em->persist($user); // doing it now cause log() flushes the $em
                $em->flush(); // We do a first flush here so that next logs will behave correctly

                /* Tries to login the user now */
                // Here, "main" is the name of the firewall in security.yml
                $token = new UsernamePasswordToken($user, $user->getPassword(), "main", $user->getRoles());
                $this->get("security.context")->setToken($token);

                // Fire the login event
                $event = new InteractiveLoginEvent($request, $token);
                $this->get("event_dispatcher")->dispatch("security.interactive_login", $event);

                // Use inviteToken
                if (!is_null($inviteTokenObject)){

                    $inviteTokenObject->setResultingUser($user);

                    if (!is_null($inviteTokenObject->getCommunity())){

                        if ($inviteTokenObject->getCommunityType() === 'user'){
                            $inviteTokenObject->getCommunity()->addUser($user);
                            $logService = $this->container->get('logService');
                            $logService->log($this->getUser(), 'user_enters_community', $user, array( 'community' => array( 'routing' => 'community', 'logName' => $inviteTokenObject->getCommunity()->getLogName(), 'args' => null) ) );
                        } else {
                            $inviteTokenObject->getCommunity()->addGuest($user);
                        }
                        
                        $user->setCurrentCommunity($inviteTokenObject->getCommunity());

                    }

                    if (!is_null($inviteTokenObject->getProject())){

                        if ($inviteTokenObject->getProjectType() === 'owner'){
                            $user->addProjectsOwned($inviteTokenObject->getProject());
                            $logService = $this->container->get('logService');
                            $logService->log($user, 'user_is_made_owner_project', $inviteTokenObject->getProject(), array( 'other_user' => array( 'routing' => 'user', 'logName' => $inviteTokenObject->getReferalUser()->getLogName(), 'args' => $inviteTokenObject->getReferalUser()->getLogArgs()) ));
                        } else {
                            $user->addProjectsParticipatedIn($inviteTokenObject->getProject());
                            $logService = $this->container->get('logService');
                            $logService->log($user, 'user_is_made_participant_project', $inviteTokenObject->getProject(), array( 'other_user' => array( 'routing' => 'user', 'logName' => $inviteTokenObject->getReferalUser()->getLogName(), 'args' => $inviteTokenObject->getReferalUser()->getLogArgs()) ));
                        }

                    }
                }

                $em->flush();

                $logService = $this->container->get('logService');
                $logService->log($user, 'user_created', $user, array());

                $this->get('session')->getFlashBag()->add(
                    'success',
                    $this->get('translator')->trans('user.welcome')
                );

                return $this->redirect($this->generateUrl('u_show_user_profile', array('username' => $user->getUsername())));
           
            } else {
               
               $this->get('session')->getFlashBag()->add(
                    'error',
                    $this->get('translator')->trans('information.not.valid', array(), 'errors')
                );

            }

        }

        return $this->render('metaUserBundle:Default:create.html.twig', array('form' => $form->createView(), 'inviteToken' => $inviteToken));

    }

    /*
     * Edit a user (via X-editable)
     */
    public function editAction(Request $request, $username)
    {

        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('edit', $request->get('token')))
            return new Response($this->get('translator')->trans('invalid.token', array(), 'errors'), 400);

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
                case 'email':
                    $authenticatedUser->setEmail($request->request->get('value'));
                    $objectHasBeenModified = true;
                    break;
                case 'about':
                    $authenticatedUser->setAbout($request->request->get('value'));
                    $deepLinkingService = $this->container->get('meta.twig.deep_linking_extension');
                    $response = $deepLinkingService->convertDeepLinks(
                      $this->container->get('markdown.parser')->transformMarkdown($request->request->get('value'))
                    );
                    $objectHasBeenModified = true;
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
                    $skillSlugsAsArray = $request->request->get('value');
                    
                    $repository = $this->getDoctrine()->getRepository('metaUserBundle:Skill');
                    $skills = $repository->findSkillsByArrayOfSlugs($skillSlugsAsArray);
                    
                    $authenticatedUser->clearSkills();
                    foreach($skills as $skill){
                        $authenticatedUser->addSkill($skill);
                    }
                    $objectHasBeenModified = true;
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

            return $this->redirect($this->generateUrl('u_show_user_profile', array('username' => $username)));

        } else {
            
            if (!is_null($error)) {
                return new Response($error, 406);
            }

            return new Response($response);
        }

    }

    /*
     * Reset the avatar of a user
     */
    public function resetAvatarAction(Request $request, $username)
    {

        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('resetAvatar', $request->get('token')))
            return $this->redirect($this->generateUrl('u_show_user_profile', array('username' => $this->getUser()->getUsername())));

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

        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('delete', $request->get('token')))
            return $this->redirect($this->generateUrl('u_show_user_profile', array('username' => $this->getUser()->getUsername())));

        $authenticatedUser = $this->getUser();

        if ($authenticatedUser->getUsername() === $username) {
        
            $deletable = true;

            // Performs checks for ownerships
            foreach ($authenticatedUser->getProjectsOwned() as $project) {
                if (!$project->isDeleted() && $project->countOwners() == 1){
                    // not good : we're the only owner
                    $deletable = false;
                    break;
                }
            }
            foreach ($authenticatedUser->getIdeasCreated() as $idea) {
                if (!$idea->isDeleted() && $idea->countCreators() == 1){
                    // we're the only creator : we have to delete the idea
                    $idea->delete();
                }
            }

            // Projects must have at least an owner
            if ($deletable === true ) {

                // Delete the user
                $em = $this->getDoctrine()->getManager();
                $authenticatedUser->delete();
                $em->flush();
                
                $this->get('security.context')->setToken(null);
                $this->get('request')->getSession()->invalidate();

                return $this->redirect($this->generateUrl('login'));

            } else {

                $projects = "";
                foreach ($authenticatedUser->getProjectsOwned() as $project) {
                    if (!$project->isDeleted() && $project->countOwners() == 1){
                        $projects .= $project->getName(). ",";
                    }
                }

                // Let's notify the user with the projects he still owns alone
                $this->get('session')->getFlashBag()->add(
                    'error',
                    $this->get('translator')->trans('user.cannot.delete', array( '%projects%' => substr($projects, 0, -1)))
                );

                return $this->redirect($this->generateUrl('u_show_user_profile', array('username' => $username)));
            }

        } else {

            $this->get('session')->getFlashBag()->add(
                'error',
                $this->get('translator')->trans('user.cannot.delete.other')
            );

            return $this->redirect($this->generateUrl('u_show_user_profile', array('username' => $authenticatedUser->getUsername())));
        }

    }

    /*
     * List all skills (for X-editable)
     */
    public function listSkillsAction(Request $request)
    {

        if ($request->isXmlHttpRequest()){

            $repository = $this->getDoctrine()->getRepository('metaUserBundle:Skill');
            $skills = $repository->findAll();

            $skillsAsArray = array();

            foreach($skills as $skill){
                $skillsAsArray[] = array('value' => $skill->getSlug(), 'text' => $this->get('translator')->trans($skill->getSlug() . '.name', array(), 'skills'));
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
            $users = $repository->findAllUsersInCommunityExceptMe($authenticatedUser, $authenticatedUser->getCurrentCommunity());

            return $this->render('metaUserBundle:Default:choose.html.twig', array('users' => $users, 'external' => $target['external'], 'targetAsBase64' => $targetAsBase64, 'token' => $request->get('token')));

        }

    }

    /*
     * Authenticated user follows the request user
     */
    public function followUserAction(Request $request, $username)
    {
        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('followUser', $request->get('token')))
            return $this->redirect($this->generateUrl('u_show_user_profile', array('username' => $username)));

        $authenticatedUser = $this->getUser();

        // The actually authenticated user now follows $user if they are not the same
        if ($username !== $authenticatedUser->getUsername()){

            $repository = $this->getDoctrine()->getRepository('metaUserBundle:User');
            $user = $repository->findOneByUsernameInCommunity($username, true, $authenticatedUser->getCurrentCommunity());

            if ($user && !$user->isDeleted()){

                if (!($this->getUser()->isFollowing($user)) ){

                    $authenticatedUser->addFollowing($user);

                    $logService = $this->container->get('logService');
                    $logService->log($authenticatedUser, 'user_follow_user', $user, array());

                    $em = $this->getDoctrine()->getManager();
                    $em->flush();

                    $this->get('session')->getFlashBag()->add(
                        'success',
                        $this->get('translator')->trans('user.following', array( '%user%' => $user->getFullName() ))
                    );

                } else {

                    $this->get('session')->getFlashBag()->add(
                        'warning',
                        $this->get('translator')->trans('user.already.following', array( '%user%' => $user->getFullName() ))
                    );

                }

            } else {

               $this->get('session')->getFlashBag()->add(
                    'error',
                    $this->get('translator')->trans('user.cannot.follow')
                ); 

            }

        } else {
            $this->get('session')->getFlashBag()->add(
                'warning',
                $this->get('translator')->trans('user.cannot.followSelf')
            );
        }

        return $this->redirect($this->generateUrl('u_show_user_profile', array('username' => $username)));
    }

    /*
     * Authenticated user unfollows the request user
     */
    public function unfollowUserAction(Request $request, $username)
    {

        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('unfollowUser', $request->get('token')))
            return $this->redirect($this->generateUrl('u_show_user_profile', array('username' => $username)));

        $authenticatedUser = $this->getUser();

        // The actually authenticated user now follows $user if they are not the same
        if ($username !== $authenticatedUser->getUsername()){

            $repository = $this->getDoctrine()->getRepository('metaUserBundle:User');
            $user = $repository->findOneByUsernameInCommunity($username, true, $authenticatedUser->getCurrentCommunity());

            if ($user && !$user->isDeleted()){

                if ($this->getUser()->isFollowing($user) ){

                    $authenticatedUser->removeFollowing($user);

                    $em = $this->getDoctrine()->getManager();
                    $em->flush();

                    $this->get('session')->getFlashBag()->add(
                        'success',
                        $this->get('translator')->trans('user.unfollowing', array('%user%' => $user->getFullName()))
                    );

                } else {

                    $this->get('session')->getFlashBag()->add(
                        'warning',
                        $this->get('translator')->trans('user.not.following', array('%user%' => $user->getFullName()))
                    );

                }

            } else {

               $this->get('session')->getFlashBag()->add(
                    'error',
                    $this->get('translator')->trans('user.cannot.unfollow')
                ); 

            }

        } else {
            $this->get('session')->getFlashBag()->add(
                'warning',
                $this->get('translator')->trans('user.cannot.unfollowSelf')
            );
        }
            
        return $this->redirect($this->generateUrl('u_show_user_profile', array('username' => $username)));
    }

}
