<?php

namespace meta\UserProfileBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response,
    Symfony\Component\EventDispatcher\EventDispatcher,
    Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken,
    Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

/*
 * Importing Class definitions
 */
use meta\UserProfileBundle\Entity\User,
    meta\UserProfileBundle\Form\Type\UserType;

class DefaultController extends Controller
{
    /*
     * Show a user profile
     */
    public function showAction($username)
    {

        $repository = $this->getDoctrine()->getRepository('metaUserProfileBundle:User');

        $user = $repository->findOneByUsername($username);

        if (!$user) {
            throw $this->createNotFoundException('This user does not exist');
        }

        $authenticatedUser = $this->getUser();

        $alreadyFollowing = $authenticatedUser && $authenticatedUser->isFollowing($user);
        $isMe = $authenticatedUser && ($authenticatedUser->getUsername() == $username);

        $targetAvatarAsBase64 = array ('slug' => 'metaUserProfileBundle:Default:edit', 'params' => array('username' => $username ));

        return $this->render('metaUserProfileBundle:Default:show.html.twig', 
            array('user' => $user,
                  'alreadyFollowing' => $alreadyFollowing,
                  'isMe' => $isMe,
                  'targetAvatarAsBase64' => base64_encode(json_encode($targetAvatarAsBase64))
                ));
    }

    /*
     * Show My user profile
     */
    public function showMeAction()
    {

        $authenticatedUser = $this->getUser();

        if ($authenticatedUser) {
            return $this->redirect($this->generateUrl('u_show_user_profile', array('username' => $authenticatedUser->getUsername())));
        } 

        $this->get('session')->setFlash(
                'error',
                'Please login to access your account.'
            );

        return $this->redirect($this->generateUrl('login'));

    }

    /*
     * Create a form for a new user to signin AND process result if POST
     */
    public function createAction(Request $request)
    {
        
        $authenticatedUser = $this->getUser();

        if ($authenticatedUser) {

            $this->get('session')->setFlash(
                'warning',
                'You are already logged in as '.$authenticatedUser->getUsername().'. If you wish to create another account, please logout first.'
            );

            return $this->redirect($this->generateUrl('u_show_user_profile', array('username' => $authenticatedUser->getUsername())));
        }

        $user = new User();
        $form = $this->createForm(new UserType(), $user);

        if ($request->isMethod('POST')) {

            $form->bind($request);

            if ($form->isValid()) {
                
                $factory = $this->get('security.encoder_factory');
                $encoder = $factory->getEncoder($user);
                $user->setPassword($encoder->encodePassword($user->getPassword(), $user->getSalt()));

                $em = $this->getDoctrine()->getManager();
                $em->persist($user);
                $em->flush();

                $logService = $this->container->get('logService');
                $logService->log($user, 'user_created', $user, array());

                /* Tries to login the user now */
                // Here, "main" is the name of the firewall in security.yml
                $token = new UsernamePasswordToken($user, $user->getPassword(), "main", $user->getRoles());
                $this->get("security.context")->setToken($token);

                // Fire the login event
                $event = new InteractiveLoginEvent($request, $token);
                $this->get("event_dispatcher")->dispatch("security.interactive_login", $event);

                $this->get('session')->setFlash(
                    'success',
                    'Welcome! This is your profile page, where you can directly edit your information by clicking on the underlined text.'
                );

                return $this->redirect($this->generateUrl('u_show_user_profile', array('username' => $user->getUsername())));
           
            } else {
               
               $this->get('session')->setFlash(
                    'error',
                    'The information you provided does not seem valid.'
                );

            }

        }

        return $this->render('metaUserProfileBundle:Default:create.html.twig', array('form' => $form->createView()));

    }

    /*
     * Authenticated user can edit via X-editable
     */
    public function editAction(Request $request, $username)
    {

        $authenticatedUser = $this->getUser();
        $response = new Response();

        if ($authenticatedUser->getUsername() == $username) {

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
                case 'file':
                    $uploadedFile = $request->files->get('file');
                    $authenticatedUser->setFile($uploadedFile);
                    $objectHasBeenModified = true;
                    $needsRedirect = true;
                    break;
                case 'skills':
                    $skillSlugsAsArray = $request->request->get('value');
                    
                    $repository = $this->getDoctrine()->getRepository('metaUserProfileBundle:Skill');
                    $skills = $repository->findSkillsByArrayOfSlugs($skillSlugsAsArray);
                    
                    $authenticatedUser->setSkills($skills);
                    $objectHasBeenModified = true;
                    break;
            }


            $validator = $this->get('validator');
            $errors = $validator->validate($authenticatedUser);

            if ($objectHasBeenModified === true && count($errors) == 0){
                $authenticatedUser->setUpdatedAt(new \DateTime('now'));

                $logService = $this->container->get('logService');
                $logService->log($authenticatedUser, 'user_update_profile', $authenticatedUser, array());

                $em = $this->getDoctrine()->getManager();
                $em->flush();
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

            return $this->redirect($this->generateUrl('u_show_user_profile', array('username' => $username)));

        } else {
        
            return $response;
        }

    }

    public function deleteAction($username){

        $authenticatedUser = $this->getUser();

        if ($authenticatedUser->getUsername() === $username) {
        
            // Performs checks for ownerships

            // ideas must have a creator and projects must have at least an owner
            if ($authenticatedUser->countNotArchivedIdeasCreated() === 0 &&
                $authenticatedUser->getProjectsOwned()->count() === 0 ) {

                // Then reassign the comments to NULL
                foreach ($authenticatedUser->getComments() as $comment) {
                    $comment->setUser(null);
                }

                // Delete the user for real
                $em = $this->getDoctrine()->getManager();
                $em->remove($authenticatedUser);
                $em->flush();

                $this->get('session')->setFlash(
                        'success',
                        'The user '.$username.' has been deleted successfully.'
                    );
                
                return $this->redirect($this->generateUrl('login'));

            } else {

                $this->get('session')->setFlash(
                    'error',
                    'You cannot delete your account; you still own projects or unarchived ideas. Transfer idea ownership, make sure your projects have another owner and try again.'
                );

                return $this->redirect($this->generateUrl('u_show_user_profile', array('username' => $username)));
            }

        } else {

            $this->get('session')->setFlash(
                    'error',
                    'You cannot delete someone else\'s account.'
                );

            return $this->redirect($this->generateUrl('u_show_user_profile', array('username' => $username)));
        }

    }

    /*
     * Authenticated user follows the request user
     */
    public function followUserAction($username)
    {
        $authenticatedUser = $this->getUser();

        // The actually authenticated user now follows $user if they are not the same
        if ($authenticatedUser) {

            if ($username != $authenticatedUser->getUsername()){

                $repository = $this->getDoctrine()->getRepository('metaUserProfileBundle:User');
                $user = $repository->findOneByUsername($username);

                if ( !($this->getUser()->isFollowing($user)) ){

                    $authenticatedUser->addFollowing($user);

                    $logService = $this->container->get('logService');
                    $logService->log($authenticatedUser, 'user_follow_user', $user, array());

                    $em = $this->getDoctrine()->getManager();
                    $em->flush();

                    $this->get('session')->setFlash(
                        'success',
                        'You are now following '.$user->getFullName().'.'
                    );

                } else {

                    $this->get('session')->setFlash(
                        'warning',
                        'You are already following '.$user->getFullName().'.'
                    );

                }

            } else {
                $this->get('session')->setFlash(
                    'warning',
                    'You cannot follow yourself.'
                );
            }
            
        }

        return $this->redirect($this->generateUrl('u_show_user_profile', array('username' => $username)));
    }

    /*
     * Authenticated user unfollows the request user
     */
    public function unfollowUserAction($username)
    {

        $authenticatedUser = $this->getUser();

        // The actually authenticated user now follows $user if they are not the same
        if ($authenticatedUser) {

            if ($username != $authenticatedUser->getUsername()){

                $repository = $this->getDoctrine()->getRepository('metaUserProfileBundle:User');
                $user = $repository->findOneByUsername($username);

                if ( $this->getUser()->isFollowing($user) ){

                    $authenticatedUser->removeFollowing($user);

                    $em = $this->getDoctrine()->getManager();
                    $em->flush();

                    $this->get('session')->setFlash(
                        'success',
                        'You are not following '.$user->getFullName().' anymore.'
                    );

                } else {

                    $this->get('session')->setFlash(
                        'warning',
                        'You are not following '.$user->getFullName().'.'
                    );

                }

            } else {
                $this->get('session')->setFlash(
                    'warning',
                    'You cannot unfollow yourself.'
                );
            }
            
        }

        return $this->redirect($this->generateUrl('u_show_user_profile', array('username' => $username)));
    }

    /*
     * List recently created users
     */
    public function listRecentlyCreatedAction($max = 3)
    {

        $repository = $this->getDoctrine()->getRepository('metaUserProfileBundle:User');
        $users = $repository->findRecentlyCreatedUsers($max);

        return $this->render('metaUserProfileBundle:Default:list.html.twig', array('users' => $users));

    }

    /*
     * List all skills for X-editable
     */
    public function listSkillsAction(Request $request)
    {

        if ($request->isXmlHttpRequest()){

            $repository = $this->getDoctrine()->getRepository('metaUserProfileBundle:Skill');
            $skills = $repository->findAll();

            $skillsAsArray = array();

            foreach($skills as $skill){
                $skillsAsArray[] = array('value' => $skill->getSlug(), 'text' => $skill->getName());
            }

            return new Response(json_encode($skillsAsArray));

        } else {

            throw $this->createNotFoundException();

        }

    }

    /*
     * Allow to choose for a user
     */
    public function chooseAction(Request $request, $targetAsBase64)
    {

        $target = json_decode(base64_decode($targetAsBase64), true);

        if ($request->isMethod('POST')) {

            $username = $request->request->get('username');

            $repository = $this->getDoctrine()->getRepository('metaUserProfileBundle:User');
            $user = $repository->findOneByUsername($username);

            if ($user && isset($target['slug']) && isset($target['params']) ){

                $target['params']['username'] = $username;
                return $this->redirect($this->generateUrl($target['slug'], $target['params']));

            } else {

                throw $this->createNotFoundException();

            }

        } else {

            $repository = $this->getDoctrine()->getRepository('metaUserProfileBundle:User');
            $users = $repository->findAllUsersExceptMe($this->getUser()->getId());

            if (count($users) == 0 ){

                $this->get('session')->setFlash(
                        'warning',
                        'You\'re alone, mate.'
                    );

                return $this->redirect($this->generateUrl('u_show_user_profile', array('username' => $this->getUser()->getUsername())));
            }

            return $this->render('metaUserProfileBundle:Default:choose.html.twig', array('users' => $users, 'targetAsBase64' => $targetAsBase64));

        }

    }
}
