<?php

namespace meta\GeneralBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\File\File,
    Symfony\Component\HttpFoundation\Response,
    Symfony\Component\Security\Csrf\CsrfToken;

/* SYMFONY 2.8
use Symfony\Component\Form\Extension\Core\Type\TextType,
    Symfony\Component\Form\Extension\Core\Type\TextareaType;
*/
use meta\GeneralBundle\Entity\Community\Community,
    meta\GeneralBundle\Entity\Comment\CommunityComment,
    meta\UserBundle\Entity\UserCommunity,
    meta\UserBundle\Entity\UserInviteToken,
    meta\GeneralBundle\Form\Type\CommunityType;

class CommunityController extends Controller
{
     
    /*
     * Displays a home for the community
     */
    public function homeAction(Request $request)
    {
        $authenticatedUser = $this->getUser();
        $community = $authenticatedUser->GetCurrentCommunity();
        
        // In a real community
        if ( !is_null($community) ){

            $userCommunity = $this->getDoctrine()->getRepository('metaUserBundle:UserCommunity')->findOneBy(array('user' => $authenticatedUser->getId(), 'community' => $community->getId()));

            // User in community or That community is valid ?
            if ( !$userCommunity || !($community->isValid()) ){

                if (!($community->isValid())) {
                    $this->get('session')->getFlashBag()->add(
                        'error',
                        $this->get('translator')->trans('community.invalid', array( "%community%" => $community->getName()) )
                    );
                }

                // Back in private space, ahah
                $authenticatedUser->setCurrentCommunity(null);
                $em = $this->getDoctrine()->getManager();
                $em->flush();

                $this->get('session')->getFlashBag()->add(
                  'info',
                  $this->get('translator')->trans('private.space.back')
                );

                return $this->redirect($this->generateUrl('g_switch_private_space', array('token' => $this->get('security.csrf.token_manager')->getToken('switchCommunity')->getValue(), 'redirect' => true)));

            }

            // Is the user manager or guest ?
            $userCommunity = $this->getDoctrine()->getRepository('metaUserBundle:UserCommunity')->findOneBy(array('user' => $authenticatedUser->getId(), 'community' => $community->getId()));
            
            // ALL USERS as a mosaik with dot indicating number of recent actions (> 9  : show a '+') 
            $userRepository = $this->getDoctrine()->getRepository('metaUserBundle:User');
            $allUsers = $userRepository->findAllUsersInCommunityExceptMe(array( 'user' => $authenticatedUser, 'community' => $community, 'includeGuests' => false)); // Without guests (false)

            // Last ideas
            $ideaRepository = $this->getDoctrine()->getRepository('metaIdeaBundle:Idea');
            $lastIdeas = $ideaRepository->findLastIdeasInCommunityForUser(array('community' => $community, 'user' => $authenticatedUser, 'max' => 3));

            // Last projects
            $projectRepository = $this->getDoctrine()->getRepository('metaProjectBundle:StandardProject');
            $lastProjects = $projectRepository->findLastProjectsInCommunityForUser(array('community' => $community, 'user' => $authenticatedUser, 'max' => 3));

            $targetPictureAsBase64 = array('slug' => 'metaGeneralBundle:Community:edit', 'params' => array(), 'crop' => true, 'filetypes' => array('png', 'jpg', 'jpeg', 'gif'));

            return $this->render('metaGeneralBundle:Community:home.html.twig', array(
                        'isManager' => ($userCommunity && $userCommunity->isManager()),
                        'isGuest' => ($userCommunity && $userCommunity->isGuest()),
                        'targetPictureAsBase64' => base64_encode(json_encode($targetPictureAsBase64)),
                        'lastProjects' => $lastProjects,
                        'lastIdeas' => $lastIdeas,
                        'users' => $allUsers));

        } else { // Or in your private space ?

            $cookies = $request->cookies;
            $shared_projects = true;

            if ($cookies->has('igneet_dismiss'))
            {   
                $cookie = $cookies->get('igneet_dismiss');
                $shared_projects = !($cookie['shared_projects'] == "true");
            }

            if ($shared_projects == true){
                $projectRepository = $this->getDoctrine()->getRepository('metaProjectBundle:StandardProject');
                $shared_projects = $projectRepository->findById($this->container->getParameter('shared.projects'));
            }

            return $this->render('metaGeneralBundle:Community:privateSpace.html.twig', array( 'shared_projects' => $shared_projects));
        }
       

    }

    public function createAction(Request $request)
    {

        $authenticatedUser = $this->getUser();

        $community = new Community($this->container->getParameter('community.demo_validity'));
        $form = $this->createForm(new CommunityType(), $community);

        if ($request->isMethod('POST')) {

            $form->bind($request);

            if ($form->isValid()) {

                $userCommunity = new UserCommunity();
                $userCommunity->setUser($authenticatedUser);
                $userCommunity->setCommunity($community);
                $userCommunity->setGuest(false);
                $userCommunity->setManager(true); // The one who creates is a manager by default

                // We set the current community of the user
                $authenticatedUser->setCurrentCommunity($community);

                $em = $this->getDoctrine()->getManager();
                $em->persist($community);
                $em->persist($userCommunity);
                $em->flush();
                
                $logService = $this->container->get('logService');
                $logService->log($authenticatedUser, 'user_create_community', $community, array());

                $this->get('session')->getFlashBag()->add(
                    'success',
                    $this->get('translator')->trans('community.created', array( '%community%' => $community->getName()))
                );

                return $this->redirect($this->generateUrl('g_manage_community'));
           
            } else {
               
               $this->get('session')->getFlashBag()->add(
                    'error',
                    $this->get('translator')->trans('information.not.valid', array(), 'errors')
                );

            }

        }

        return $this->render('metaGeneralBundle:Community:create.html.twig', array('form' => $form->createView()));

    }

    public function upgradeAction()
    {

        // TODO !!
        return $this->render('metaGeneralBundle:Community:upgrade.html.twig');

    }

    public function editAction(Request $request)
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

        $error = null;
        $response = null;

        $authenticatedUser = $this->getUser();
        $community = $authenticatedUser->getCurrentCommunity();

        if ( !is_null($community) && $community){

            // Is the user manager ?
            $userCommunity = $this->getDoctrine()->getRepository('metaUserBundle:UserCommunity')->findOneBy(array('user' => $authenticatedUser->getId(), 'community' => $community->getId(), 'manager' => true));

            if ($userCommunity){
        
                $objectHasBeenModified = false;

                switch ($request->request->get('name')) {
                    case 'name':
                        $community->setName($request->request->get('value'));
                        $objectHasBeenModified = true;
                        break;
                    case 'headline':
                        $community->setHeadline($request->request->get('value'));
                        $objectHasBeenModified = true;
                        break;
                        /*
                    case 'about':
                        $this->base['idea']->setAbout($request->request->get('value'));
                        $deepLinkingService = $this->container->get('deep_linking_extension');
                        $response = $deepLinkingService->convertDeepLinks($request->request->get('value'));
                        $objectHasBeenModified = true;
                        break;
                        */
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
                        $community->update();
                        $community->setFile(new File($preparedFilename.".cropped"));

                        $objectHasBeenModified = true;
                        $needsRedirect = true;
                        break;
                }

                $validator = $this->get('validator');
                $errors = $validator->validate($community);

                if ($objectHasBeenModified === true && count($errors) == 0){

                    $logService = $this->container->get('logService');
                    $logService->log($this->getUser(), 'user_update_community_info', $community, array());

                    $em = $this->getDoctrine()->getManager();
                    $em->flush();

                } elseif (count($errors) > 0) {

                    $error = $this->get('translator')->trans($errors[0]->getMessage());
                }

            }

        }

        // Wraps up and either return a response or redirect
        if (isset($needsRedirect) && $needsRedirect) {

            if (!is_null($error)) {
                $this->get('session')->getFlashBag()->add(
                    'error',
                    $error
                );
            }

            return $this->redirect($this->generateUrl('g_home_community'));

        } else {
        
            if (!is_null($error)) {
                return new Response(json_encode(array('message' => $error)), 406, array('Content-Type'=>'application/json'));
            }

            return new Response(json_encode($response), 200, array('Content-Type'=>'application/json'));
        }

    }

    /*
     * Reset picture of community
     */
    public function resetPictureAction(Request $request)
    {

        if (!$this->get('security.csrf.token_manager')->isTokenValid(new CsrfToken('resetPicture', $request->get('token')))) {
            $this->get('session')->getFlashBag()->add(
                'error',
                $this->get('translator')->trans('invalid.token', array(), 'errors')
            );
            return $this->redirect($this->generateUrl('g_home_community'));
        }

        $authenticatedUser = $this->getUser();
        $community = $authenticatedUser->getCurrentCommunity();

        if ( !is_null($community) && $community){

            // Is the user manager ?
            $userCommunity = $this->getDoctrine()->getRepository('metaUserBundle:UserCommunity')->findOneBy(array('user' => $authenticatedUser->getId(), 'community' => $community->getId(), 'manager' => true));

            if ($userCommunity){

                $community->setPicture(null);
                $em = $this->getDoctrine()->getManager();
                $em->flush();

                $this->get('session')->getFlashBag()->add(
                    'success',
                    $this->get('translator')->trans('community.picture.reset')
                );

            } else {
        
                $this->get('session')->getFlashBag()->add(
                    'error',
                    $this->get('translator')->trans('community.picture.cannot.reset')
                );
            }

        }

        return $this->redirect($this->generateUrl('g_home_community'));

    }

    /*
     * Manages the community
     */
    public function manageAction()
    {

        $authenticatedUser = $this->getUser();
        $community = $authenticatedUser->getCurrentCommunity();

        if ( !is_null($community) && $community){

            // Is the user manager ?
            $userCommunity = $this->getDoctrine()->getRepository('metaUserBundle:UserCommunity')->findOneBy(array('user' => $authenticatedUser->getId(), 'community' => $community->getId(), 'manager' => true));

            if ($userCommunity){
            
                // Retrieve all the actual managers from the community
                $userCommunityManagers = $this->getDoctrine()->getRepository('metaUserBundle:UserCommunity')->findBy(array('community' => $community->getId(), 'manager' => true));

                // Counts the actual users in the community
                $userCommunityUsers = $this->getDoctrine()->getRepository('metaUserBundle:UserCommunity')->findBy(array('community' => $community->getId()));
                $userCommunityGuests = $this->getDoctrine()->getRepository('metaUserBundle:UserCommunity')->findBy(array('community' => $community->getId(), 'guest' => true));

                $targetManagerAsBase64 = array('slug' => 'metaGeneralBundle:Community:addManager', 'external' => false, 'params' => array('guest' => false));
                $targetManagerAsBase64 = base64_encode(json_encode($targetManagerAsBase64));

                return $this->render('metaGeneralBundle:Community:manage.html.twig', array(
                    'userCommunityManagers' => $userCommunityManagers, 
                    'usersCount' => count($userCommunityUsers), 
                    'guestsCount' => count($userCommunityGuests), 
                    'targetManagerAsBase64' => $targetManagerAsBase64)
                );

            } else {

                $this->get('session')->getFlashBag()->add(
                  'error',
                  $this->get('translator')->trans('community.not.manager', array( '%community%' => $community->getName()))
                );

                return $this->redirect($this->generateUrl('g_home_community'));
    
            }    

        } else {

                $this->get('session')->getFlashBag()->add(
                  'info',
                  $this->get('translator')->trans('community.not.manageable')
                );

                return $this->redirect($this->generateUrl('g_home_community'));
        }

    }
 
    /*
     * Output the comment form for a community or add a comment to a community when POST
     */
    public function addCommunityCommentAction(Request $request)
    {

        if ($request->isMethod('POST') && !$this->get('security.csrf.token_manager')->isTokenValid(new CsrfToken('comment', $request->get('token')))) {
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
        $community = $authenticatedUser->getCurrentCommunity();

        if ( !is_null($community) && $community){

            $comment = new CommunityComment();
            $form = $this->createFormBuilder($comment)
                ->add('text', 'textarea' /* SYMFONY 2.8 TextareaType::class*/, array('required' => false, 'attr' => array('placeholder' => $this->get('translator')->trans('comment.placeholder') )))
                ->getForm();

            if ($request->isMethod('POST')) {

                $comment->setText($request->get('comment'));
                $comment->setUser($this->getUser());

                $errors = $this->get('validator')->validate($comment);

                if (count($errors) == 0) {
                    
                    $comment->linkify();
                    $community->addComment($comment);
                    
                    $em = $this->getDoctrine()->getManager();
                    $em->persist($comment);
                    $em->flush();

                    // We indicate if the current user can add a note to the comment or not 
                    // In strict mode, PHP will complain but well ...
                    $userCommunity = $this->getDoctrine()->getRepository('metaUserBundle:UserCommunity')->findOneBy(array('user' => $authenticatedUser->getId(), 'community' => $community->getId(), 'manager' => true));
                    $comment->contextable = ($userCommunity !== null);

                    $logService = $this->container->get('logService');
                    $logService->log($this->getUser(), 'user_comment_community', $community, array());

                    $renderedComment = $this->renderView('metaGeneralBundle:Log:logItemComment.html.twig', array('comment' => $comment));

                    return new Response(json_encode(array( 'comment' => $renderedComment, 'message' => $this->get('translator')->trans('comment.added'))), 200, array('Content-Type'=>'application/json'));

                } else {

                   return new Response(json_encode(array('message' => $this->get('translator')->trans('information.not.valid', array(), 'errors'))), 400, array('Content-Type'=>'application/json'));
                }

            } else {

                $route = $this->get('router')->generate('g_community_comment', array('token' => $this->get('security.csrf.token_manager')->getToken('comment')->getValue()));

                return $this->render('metaGeneralBundle:Comment:commentBox.html.twig', 
                    array('object' => $community, 'route' => $route, 'form' => $form->createView()));

            }

        }

        throw $this->createNotFoundException($this->get('translator')->trans('community.not.found'));

    }

    /*
     * Display invite page or invite a user in a community by username or email
     */
    public function inviteAction(Request $request)
    {

        if (!$this->get('security.csrf.token_manager')->isTokenValid(new CsrfToken('invite', $request->get('token'))) && $request->get('ref') !== "mail") {
            $this->get('session')->getFlashBag()->add(
                'error',
                $this->get('translator')->trans('invalid.token', array(), 'errors')
            );
            return $this->redirect($this->generateUrl('g_home_community'));
        }

        $authenticatedUser = $this->getUser();
        $community = $authenticatedUser->getCurrentCommunity();

        if (!is_null($community)){
            $userCommunityManager = $this->getDoctrine()->getRepository('metaUserBundle:UserCommunity')->findOneBy(array('user' => $authenticatedUser->getId(), 'community' => $community->getId(), 'manager' => true));
        } else {

            $this->get('session')->getFlashBag()->add(
                'error',
                $this->get('translator')->trans('user.invitation.privatespace')
            );

            return $this->redirect($this->generateUrl('g_home_community'));
        
        }
        
        if ( !is_null($community) && $userCommunityManager ) {

            if ($request->isMethod('POST')) {
            
                // Gets mail or username
                $mailOrUsername = $request->request->get('mailOrUsername');
                $isEmail = filter_var($mailOrUsername, FILTER_VALIDATE_EMAIL);

                // It might be a user already
                $repository = $this->getDoctrine()->getRepository('metaUserBundle:User');
                $em = $this->getDoctrine()->getManager();

                if($isEmail){
                    $user = $repository->findOneByEmail($mailOrUsername);
                } else {
                    $user = $repository->findOneByUsername($mailOrUsername);
                }

                if ($user && !$user->isDeleted()) {

                    // Confirm .. ?
                    if ($request->get('confirm') == true) {
                        return $this->render('metaGeneralBundle:Community:invite.confirm.html.twig', array('community' => $community, 'user' => $user) );
                    }

                    $mailOrUsername = $user->getEmail();
                    $token = null;

                    $userCommunity = $this->getDoctrine()->getRepository('metaUserBundle:UserCommunity')->findOneBy(array('user' => $user->getId(), 'community' => $community->getId(), 'guest' => false));

                    $userCommunityGuest = $this->getDoctrine()->getRepository('metaUserBundle:UserCommunity')->findOneBy(array('user' => $user->getId(), 'community' => $community->getId(), 'guest' => true));

                    // If the user is already in the community
                    if ($userCommunity){

                        $this->get('session')->getFlashBag()->add(
                            'warning',
                            $this->get('translator')->trans('user.already.in.community', array( '%user%' => $user->getFullName(), '%community%' => $community->getName() ))
                        );

                        return $this->redirect($this->generateUrl('g_invite', array('token' => $this->get('security.csrf.token_manager')->getToken('invite')->getValue())));

                    // If the user is already a guest in the community
                    } elseif ($userCommunityGuest) {

                        // User is not guest anymore, we already have a userCommunity object
                        $userCommunityGuest->setGuest(false);
                        $logService = $this->container->get('logService');
                        $logService->log($user, 'user_enters_community', $community, array());
                            
                        $this->get('session')->getFlashBag()->add(
                            'success',
                            $this->get('translator')->trans('user.belonging.community', array( '%user%' => $user->getFullName(), '%community%' => $community->getName() ))
                        );

                        $community->extendValidityBy($this->container->getParameter('community.viral_extension'));

                    // The user has no link with the current community
                    } else {

                        // Creates the userCommunity
                        $userCommunity = new UserCommunity();
                        $userCommunity->setUser($user);
                        $userCommunity->setCommunity($community);
                        $userCommunity->setGuest(false);

                        $em->persist($userCommunity);

                        $logService = $this->container->get('logService');
                        $logService->log($user, 'user_enters_community', $community, array());
                            
                        $this->get('session')->getFlashBag()->add(
                            'success',
                            $this->get('translator')->trans('user.belonging.community', array( '%user%' => $user->getFullName(), '%community%' => $community->getName() ))
                        );

                        $community->extendValidityBy($this->container->getParameter('community.viral_extension'));
                        
                    }

                } elseif ($isEmail) {

                    // Confirm .. ?
                    if ($request->get('confirm') == true) {
                        return $this->render('metaGeneralBundle:Community:invite.confirm.html.twig', array('community' => $community, 'user' => null, 'email' => $mailOrUsername, 'md5' => md5(strtolower(trim($mailOrUsername)))) );
                    }

                    // Create token linked to email
                    $token = new UserInviteToken($authenticatedUser, $mailOrUsername, $community, 'user', null, null);
                    $em->persist($token);
                
                    $this->get('session')->getFlashBag()->add(
                        'success',
                        $this->get('translator')->trans('user.invitation.sent', array('%mail%' => $mailOrUsername))
                    );

                } else {

                    $this->get('session')->getFlashBag()->add(
                        'error',
                        $this->get('translator')->trans('user.email.invalid')
                    );

                    return $this->redirect($this->generateUrl('g_invite', array('token' => $this->get('security.csrf.token_manager')->getToken('invite')->getValue())));
                }

                $em->flush();

                // Sends mail to invitee
                $message = \Swift_Message::newInstance()
                    ->setSubject($this->get('translator')->trans('user.invitation.mail.subject'))
                    ->setFrom(array($this->container->getParameter('mailer_from') => $this->container->getParameter('mailer_from_name')))
                    ->setReplyTo($authenticatedUser->getEmail())
                    ->setTo($mailOrUsername)
                    ->setBody(
                        $this->renderView(
                            'metaUserBundle:Mail:invite.mail.html.twig',
                            array('user' => $authenticatedUser, 'inviteToken' => $token?$token->getToken():null, 'invitee' => ($user && !$user->isDeleted()), 'community' => $community, 'project' => null )
                        ), 'text/html'
                    );
                $this->get('mailer')->send($message);

                return $this->redirect($this->generateUrl('g_home_community'));

            } else {

                return $this->render('metaGeneralBundle:Community:invite.html.twig', array('community' => $community, 'predefined_user' => $request->get('user')) );

            }

        } else {

            $this->get('session')->getFlashBag()->add(
                'error',
                $this->get('translator')->trans('user.invitation.impossible')
            );

            return $this->redirect($this->generateUrl('g_home_community'));

        }

    }


    /*
     * Display propose page or propose a user in a community to a manager
     */
    public function proposeAction(Request $request)
    {

        if (!$this->get('security.csrf.token_manager')->isTokenValid(new CsrfToken('propose', $request->get('token')))) {
            $this->get('session')->getFlashBag()->add(
                'error',
                $this->get('translator')->trans('invalid.token', array(), 'errors')
            );
            return $this->redirect($this->generateUrl('g_home_community'));
        }

        $authenticatedUser = $this->getUser();
        $community = $authenticatedUser->getCurrentCommunity();

        // Redirect to invite if manager
        $selfManager = $this->getDoctrine()->getRepository('metaUserBundle:UserCommunity')->findBy(array('community' => $community->getId(), 'user' => $authenticatedUser, 'manager' => true));
        if ($selfManager){
            return $this->redirect($this->generateUrl('g_invite', array('token' => $this->get('security.csrf.token_manager')->getToken('invite')->getValue())));
        }

        if (!is_null($community)){
            $userCommunityManager = $this->getDoctrine()->getRepository('metaUserBundle:UserCommunity')->findBy(array('community' => $community->getId(), 'manager' => true));
        } else {

            $this->get('session')->getFlashBag()->add(
                'error',
                $this->get('translator')->trans('user.invitation.privatespace')
            );

            return $this->redirect($this->generateUrl('g_home_community'));
        
        }
        
        if ( !is_null($community) && count($userCommunityManager) > 0 ) {

            if ($request->isMethod('POST')) {
            
                // Gets mail or username
                $mailOrUsername = $request->request->get('mailOrUsername');
                $isEmail = filter_var($mailOrUsername, FILTER_VALIDATE_EMAIL);

                // It might be a user already
                $repository = $this->getDoctrine()->getRepository('metaUserBundle:User');

                if($isEmail){
                    $user = $repository->findOneByEmail($mailOrUsername);
                } else {
                    $user = $repository->findOneByUsername($mailOrUsername);
                }

                if ($user && !$user->isDeleted()) {

                    // Confirm .. ?
                    if ($request->get('confirm') == true) {
                        return $this->render('metaGeneralBundle:Community:propose.confirm.html.twig', array('community' => $community, 'user' => $user) );
                    }

                    $userCommunity = $this->getDoctrine()->getRepository('metaUserBundle:UserCommunity')->findOneBy(array('user' => $user->getId(), 'community' => $community->getId()));

                    // If the user is already in the community
                    // or If the user is already a guest in the community
                    if ($userCommunity){

                        $this->get('session')->getFlashBag()->add(
                            'warning',
                            $this->get('translator')->trans('user.already.in.community', array( '%user%' => $user->getFullName(), '%community%' => $community->getName() ))
                        );

                        return $this->redirect($this->generateUrl('g_propose', array('token' => $this->get('security.csrf.token_manager')->getToken('propose')->getValue())));
                    
                    }

                    $mailOrUsername = $user->getEmail();

                } elseif ($isEmail) {

                    // Confirm .. ?
                    if ($request->get('confirm') == true) {
                        return $this->render('metaGeneralBundle:Community:propose.confirm.html.twig', array('community' => $community, 'user' => null, 'email' => $mailOrUsername, 'md5' => md5(strtolower(trim($mailOrUsername)))) );
                    }
                
                } else {

                    $this->get('session')->getFlashBag()->add(
                        'error',
                        $this->get('translator')->trans('user.email.invalid')
                    );

                    return $this->redirect($this->generateUrl('g_propose', array('token' => $this->get('security.csrf.token_manager')->getToken('propose')->getValue())));
                }

                // admin mails
                $managers = array();
                foreach ($userCommunityManager as $ucmanager) {
                    $managers[] = $ucmanager->getUser()->getEmail();
                }

                // OK, send mail to admins
                $message = \Swift_Message::newInstance()
                    ->setSubject($this->get('translator')->trans('user.proposition.mail.subject'))
                    ->setFrom(array($this->container->getParameter('mailer_from') => $this->container->getParameter('mailer_from_name')))
                    ->setReplyTo($authenticatedUser->getEmail())
                    ->setTo($managers)
                    ->setBody(
                        $this->renderView(
                            'metaUserBundle:Mail:propose.mail.html.twig',
                            array('user' => $authenticatedUser, 'link' => $this->generateUrl('g_invite', array(), true)."?ref=mail&user=".urlencode($mailOrUsername), 'proposee' => $mailOrUsername, 'community' => $community, 'project' => null )
                        ), 'text/html'
                    );
                $this->get('mailer')->send($message);

                $this->get('session')->getFlashBag()->add(
                    'success',
                    $this->get('translator')->trans('user.proposition.sent', array('%mail%' => $mailOrUsername))
                );

                return $this->redirect($this->generateUrl('g_home_community'));

            } else {

                return $this->render('metaGeneralBundle:Community:propose.html.twig', array('community' => $community) );

            }

        } else {

            $this->get('session')->getFlashBag()->add(
                'error',
                $this->get('translator')->trans('user.proposition.impossible')
            );

            return $this->redirect($this->generateUrl('g_home_community'));

        }

    }

    /*
     * Display remove page or remove a user in a community by username or email
     */
    public function removeAction(Request $request)
    {

        if (!$this->get('security.csrf.token_manager')->isTokenValid(new CsrfToken('remove', $request->get('token')))) {
            $this->get('session')->getFlashBag()->add(
                'error',
                $this->get('translator')->trans('invalid.token', array(), 'errors')
            );
            return $this->redirect($this->generateUrl('g_manage_community'));
        }

        $authenticatedUser = $this->getUser();
        $community = $authenticatedUser->getCurrentCommunity();

        if (!is_null($community)){
            $userCommunityManager = $this->getDoctrine()->getRepository('metaUserBundle:UserCommunity')->findOneBy(array('user' => $authenticatedUser->getId(), 'community' => $community->getId(), 'manager' => true));
        } else {

            $this->get('session')->getFlashBag()->add(
                'error',
                $this->get('translator')->trans('user.removal.privatespace')
            );

            return $this->redirect($this->generateUrl('g_home_community'));
        
        }
        
        if ( !is_null($community) && $userCommunityManager ) {

            if ($request->isMethod('POST')) {
            
                // Gets mail or username
                $mailOrUsername = $request->request->get('mailOrUsername');
                $isEmail = filter_var($mailOrUsername, FILTER_VALIDATE_EMAIL);

                // It must be a user already
                $repository = $this->getDoctrine()->getRepository('metaUserBundle:User');
                $em = $this->getDoctrine()->getManager();

                if($isEmail){
                    $user = $repository->findOneByEmail($mailOrUsername);
                } else {
                    $user = $repository->findOneByUsername($mailOrUsername);
                }

                if ($user && !$user->isDeleted()) {

                    $mailOrUsername = $user->getEmail();

                    $userCommunity = $this->getDoctrine()->getRepository('metaUserBundle:UserCommunity')->findOneBy(array('user' => $user->getId(), 'community' => $community->getId(), 'guest' => false));
                    $userCommunityGuest = $this->getDoctrine()->getRepository('metaUserBundle:UserCommunity')->findOneBy(array('user' => $user->getId(), 'community' => $community->getId(), 'guest' => true));

                    // If the user is in the community
                    if ($userCommunity && $userCommunity->isManager() === false){ 

                        $mustStayGuest = false;

                        // IDEAS : Remove the ones where $user is the only creator, get him out of the others
                        foreach ($user->getIdeasCreated() as $idea) {
                            if ($idea->isDeleted() == false && $idea->getCommunity() == $community) {
                                if ($idea->countCreators() == 1) {
                                    $idea->delete();
                                } else {
                                    $idea->removeCreator($user);
                                }
                            }
                        }
                        // Get him out of the ideas he participates in
                        foreach ($user->getIdeasParticipatedIn() as $idea) {
                            if ($idea->isDeleted() == false && $idea->getCommunity() == $community) {
                                $idea->removeParticipant($user);
                            }
                        }

                        // PROJECTS : If he participates or owns a projet : keep him as guest
                        foreach ($user->getProjectsParticipatedIn() as $project) {
                            if ($project->isDeleted() == false && $project->getCommunity() == $community) {
                                // No need to go further : user is in one project of the community, we shall keep him as a guest
                                $mustStayGuest = true;
                                break;
                            }
                        }
                        foreach ($user->getProjectsOwned() as $project) {
                            if ($project->isDeleted() == false && $project->getCommunity() == $community) {
                                // No need to go further : user is in one project of the community, we shall keep him as a guest
                                $mustStayGuest = true;
                                break;
                            }
                        }

                        // Is he (not the only) manager in the community ? We have to destituate him hahah
                        $userCommunity->setManager(false);

                        // We keep the user as a guest :
                        if ($mustStayGuest) {
                            $userCommunity->setGuest(true);
                            $this->get('session')->getFlashBag()->add(
                                'success',
                                $this->get('translator')->trans('user.removal.stays.guest.in.community', array( '%user%' => $user->getFullName(), '%community%' => $community->getName() ))
                            );
                        } else {

                            $em->remove($userCommunity);
                            $this->get('session')->getFlashBag()->add(
                                'success',
                                $this->get('translator')->trans('user.removal.no.guest.in.community', array( '%user%' => $user->getFullName(), '%community%' => $community->getName() ))
                            );

                        }

                        $em->flush();

                        // Sends mail to removee
                        $message = \Swift_Message::newInstance()
                            ->setSubject($this->get('translator')->trans('user.removal.mail.subject'))
                            ->setFrom(array($this->container->getParameter('mailer_from') => $this->container->getParameter('mailer_from_name')))
                            ->setReplyTo($authenticatedUser->getEmail())
                            ->setTo($mailOrUsername)
                            ->setBody(
                                $this->renderView(
                                    'metaUserBundle:Mail:remove.mail.html.twig',
                                    array('user' => $authenticatedUser, 'removee' => ($user && !$user->isDeleted()), 'community' => $community)
                                ), 'text/html'
                            );
                        $this->get('mailer')->send($message);


                    } elseif ($userCommunity && $userCommunity->isManager() === true){ 

                        $this->get('session')->getFlashBag()->add(
                            'error',
                            $this->get('translator')->trans('user.removal.manager', array( '%user%' => $user->getFullName(), '%community%' => $community->getName() ))
                        );

                    // If the user is only a guest in the community
                    } elseif ($userCommunityGuest) {
    
                        $this->get('session')->getFlashBag()->add(
                            'warning',
                            $this->get('translator')->trans('user.guest.in.community', array( '%user%' => $user->getFullName(), '%community%' => $community->getName() ))
                        );

                    // The user has no link with the current community
                    } else {

                        $this->get('session')->getFlashBag()->add(
                            'error',
                            $this->get('translator')->trans('user.not.in.community', array( '%user%' => $user->getFullName(), '%community%' => $community->getName() ))
                        );

                    }

                } else {

                    $this->get('session')->getFlashBag()->add(
                        'error',
                        $this->get('translator')->trans('user.email.invalid')
                    );

                    return $this->redirect($this->generateUrl('g_remove'));
                }

                return $this->redirect($this->generateUrl('g_manage_community'));

            } else {

                return $this->render('metaGeneralBundle:Community:remove.html.twig', array('community' => $community) );

            }

        } else {

            $this->get('session')->getFlashBag()->add(
                'error',
                $this->get('translator')->trans('user.removal.impossible')
            );

            return $this->redirect($this->generateUrl('g_manage_community'));

        }

    }

    /*
     * Add a manager to a community
     */
    public function addManagerAction(Request $request, $mailOrUsername)
    {

        if (!$this->get('security.csrf.token_manager')->isTokenValid(new CsrfToken('addManager', $request->get('token')))) {
            $this->get('session')->getFlashBag()->add(
                'error',
                $this->get('translator')->trans('invalid.token', array(), 'errors')
            );
            return $this->redirect($this->generateUrl('g_manage_community'));
        }

        $userRepository = $this->getDoctrine()->getRepository('metaUserBundle:User');
        $authenticatedUser = $this->getUser();
        $community = $authenticatedUser->getCurrentCommunity();

        $newManager = $userRepository->findOneByUsernameInCommunity(array('username' => $mailOrUsername, 'community' => $community, 'includeGuests' => false));

        // Does the user exist ?
        if ($newManager) {

            // Is he in the community, not as a guest ?
            $userCommunity = $this->getDoctrine()->getRepository('metaUserBundle:UserCommunity')->findOneBy(array('community' => $community->getId(), 'user' => $newManager->getId(), 'guest' => false));

            if ($userCommunity && !$userCommunity->isManager()){

                $this->get('session')->getFlashBag()->add(
                    'success',
                    $this->get('translator')->trans('community.manager.added', array( '%user%' => $newManager->getFullName(), '%community%' => $community->getName() ))
                );

                // Sets as Manager
                $userCommunity->setManager(true);

                $em = $this->getDoctrine()->getManager();
                $em->flush();

            } else if ($userCommunity && $userCommunity->isManager()) {

                $this->get('session')->getFlashBag()->add(
                    'warning',
                    $this->get('translator')->trans('community.manager.already', array( '%user%' => $newManager->getFullName(), '%community%' => $community->getName() ))
                );

            } else {

                $this->get('session')->getFlashBag()->add(
                    'error',
                    $this->get('translator')->trans('community.cannot.add')
                );
            }

            
        } else {

            $this->get('session')->getFlashBag()->add(
                'error',
                $this->get('translator')->trans('community.cannot.add')
            );
        }

        return $this->redirect($this->generateUrl('g_manage_community'));

    }

    /*
     * Remove a manager from a community
     */
    public function removeManagerAction(Request $request, $username)
    {

        if (!$this->get('security.csrf.token_manager')->isTokenValid(new CsrfToken('removeManager', $request->get('token')))) {
            $this->get('session')->getFlashBag()->add(
                'error',
                $this->get('translator')->trans('invalid.token', array(), 'errors')
            );
            return $this->redirect($this->generateUrl('g_manage_community'));
        }

        $userRepository = $this->getDoctrine()->getRepository('metaUserBundle:User');
        $authenticatedUser = $this->getUser();
        $community = $authenticatedUser->getCurrentCommunity();
        $toRemoveManager = $userRepository->findOneByUsernameInCommunity(array('username' => $username, 'community' => $community, 'includeGuests' => false));
        $managersCount = $userRepository->countManagersInCommunity(array('community' => $community));

        if ($toRemoveManager && $managersCount > 1 ) {
            
            // Is he manager in the community ?
            $userCommunity = $this->getDoctrine()->getRepository('metaUserBundle:UserCommunity')->findOneBy(array('community' => $community->getId(), 'user' => $toRemoveManager->getId(), 'manager' => true));

            if ($userCommunity && $userCommunity->isManager()){

                $this->get('session')->getFlashBag()->add(
                    'success',
                    $this->get('translator')->trans('community.manager.removed', array( '%user%' => $toRemoveManager->getFullName(), '%community%' => $community->getName() ))
                );

                // Sets as Manager
                $userCommunity->setManager(false);

                $em = $this->getDoctrine()->getManager();
                $em->flush();

            } else if ($userCommunity && !$userCommunity->isManager()) {

                $this->get('session')->getFlashBag()->add(
                    'warning',
                    $this->get('translator')->trans('community.manager.not', array( '%user%' => $toRemoveManager->getFullName(), '%community%' => $community->getName() ))
                );

            } else {

                $this->get('session')->getFlashBag()->add(
                    'error',
                    $this->get('translator')->trans('community.cannot.remove')
                );
            }

        } else if ($toRemoveManager){

            $this->get('session')->getFlashBag()->add(
                'error',
                $this->get('translator')->trans('community.manager.atleast', array('%community%' => $community->getName()))
            );

        }

        return $this->redirect($this->generateUrl('g_manage_community'));
    }

    /* ********************************************************************* */
    /*                           Non-routed actions                          */
    /* ********************************************************************* */

    /*
     * Output the timeline history
     */
    public function historyAction($page)
    {

        $authenticatedUser = $this->getUser();
        $community = $authenticatedUser->getCurrentCommunity();
        $lastNotified = $authenticatedUser->getLastNotifiedAt();

        $userCommunity = $this->getDoctrine()->getRepository('metaUserBundle:UserCommunity')->findOneBy(array('community' => $community->getId(), 'user' => $authenticatedUser->getId()));

        if ( is_null($community)){
            return null;
        }

        $format = $this->get('translator')->trans('date.timeline');
        $this->timeframe = array( 'today' => array( 'current' => true, 'name' => $this->get('translator')->trans('date.today'), 'data' => array()),
                            'd-1'   => array( 'name' => $this->get('translator')->trans('date.yesterday'), 'data' => array() ),
                            'd-2'   => array( 'name' => $this->get('translator')->trans('date.timeline', array( "%days%" => 2)), 'data' => array() ),
                            'd-3'   => array( 'name' => $this->get('translator')->trans('date.timeline', array( "%days%" => 3)), 'data' => array() ),
                            'd-4'   => array( 'name' => $this->get('translator')->trans('date.timeline', array( "%days%" => 4)), 'data' => array() ),
                            'd-5'   => array( 'name' => $this->get('translator')->trans('date.timeline', array( "%days%" => 5)), 'data' => array() ),
                            'd-6'   => array( 'name' => $this->get('translator')->trans('date.timeline', array( "%days%" => 6)), 'data' => array() ),
                            'before'=> array( 'name' => $this->get('translator')->trans('date.past.week'), 'data' => array() )
                            );

        $repository = $this->getDoctrine()->getRepository('metaGeneralBundle:Log\BaseLogEntry');
        $log_filters = $this->container->getParameter('general.log_filters');
        $entries = $repository->findByLogTypes($log_filters['community'], array('community' => $community));

        $history = array();

        // Logs
        $log_types = $this->container->getParameter('general.log_types');
        $logService = $this->container->get('logService');

        foreach ($entries as $entry) {

            if ($log_types[$entry->getType()]['displayable'] === false ) {
                continue; // We do not display them
            }

            // Strips private projects logs
            if ($log_types[$entry->getType()]['type'] === "project" && $entry->getSubject()->isPrivate()) {
                continue;
            }

            // If it's my own notif ... I don't care
            if ($entry->getUser() === $authenticatedUser) {
                continue;
            }

            // If I'm guest, I don't see idea logs, and projects where I'm not in
            if ($userCommunity->isGuest()) {
                if ($log_types[$entry->getType()]['type'] === "idea") {
                    continue;
                } elseif ($log_types[$entry->getType()]['type'] === "project" && 
                    !$entry->getSubject()->getOwners()->contains($authenticatedUser) &&
                    !$entry->getSubject()->getParticipants()->contains($authenticatedUser)
                    ) {
                    continue;
                }
            }

            $text = $logService->getHTML($entry, $lastNotified);
            $createdAt = date_create($entry->getCreatedAt()->format('Y-m-d H:i:s')); // not for display

            $history[] = array( 'createdAt' => $createdAt, 'text' => $text);

        }

        // Comments
        foreach ($community->getComments() as $comment) {
            if (!$comment->isDeleted()) {
                // We indicate if the current user can add a note to the comment or not 
                // In strict mode, PHP will complain but well ...
                $comment->contextable = $userCommunity->isManager();

                $text = $logService->getHTML($comment, $lastNotified);
                $createdAt = date_create($comment->getCreatedAt()->format('Y-m-d H:i:s')); // not for display

                $history[] = array( 'createdAt' => $createdAt, 'text' => $text);
            }
        }

        // Sort !
        if (!function_exists('meta\GeneralBundle\Controller\build_sorter')) {
          function build_sorter($key) {
              return function ($a, $b) use ($key) {
                  return $a[$key]>$b[$key];
              };
          }
        }
        usort($history, build_sorter('createdAt'));
        
        // Now put the entries in the correct timeframes
        $startOfToday = date_create('midnight');
        $before = date_create('midnight 6 days ago');
        $filter_groups = array();

        foreach ($history as $historyEntry) {
          
          if ( $historyEntry['createdAt'] > $startOfToday ) {
            
            // Today
            array_unshift($this->timeframe['today']['data'], $historyEntry['text']);

          } else if ( $historyEntry['createdAt'] < $before ) {

            // Before
            array_unshift($this->timeframe['before']['data'], $historyEntry['text']);

          } else {

            // Last seven days, by day
            $days = date_diff($historyEntry['createdAt'], $startOfToday)->days + 1;

            array_unshift($this->timeframe['d-'.$days]['data'], $historyEntry['text']);

          }

        }

        return $this->render('metaGeneralBundle:Timeline:timelineHistory.html.twig', 
            array('timeframe' => $this->timeframe,
                  'filter_groups' => array_unique($filter_groups)));

    }

}
