<?php
 
namespace meta\UserBundle\Controller;
 
use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\Security\Core\SecurityContext;

use meta\UserBundle\Entity\UserInviteToken;

/*
 * Importing Class definitions
 */
 
class SecurityController extends Controller
{
    /*
     * Login a user
     */
    public function loginAction()
    {

        $authenticatedUser = $this->getUser();

        if ($authenticatedUser) {

            $this->get('session')->setFlash(
                'warning',
                'You are already logged in as '.$authenticatedUser->getUsername().'.'
            );

            return $this->redirect($this->generateUrl('u_show_user_profile', array('username' => $authenticatedUser->getUsername())));
        } 

        $request = $this->getRequest();
        $session = $request->getSession();
 
        // Get the login error if there is one
        if ($request->attributes->has(SecurityContext::AUTHENTICATION_ERROR)) {
            $error = $request->attributes->get(SecurityContext::AUTHENTICATION_ERROR);
        } else {
            $error = $session->get(SecurityContext::AUTHENTICATION_ERROR);
        }
 
        return $this->render('metaUserBundle:Security:login.html.twig', array(
            // last username entered by the user
            'last_username' => $session->get(SecurityContext::LAST_USERNAME),
            'error'         => $error,
        ));
    }

    /*
     * Display invite page or invite a user in a community by username or email
     */
    public function inviteAction(Request $request)
    {

        $authenticatedUser = $this->getUser();
        $community = $authenticatedUser->getCurrentCommunity();

        if ( !is_null($community) && !$authenticatedUser->isGuestInCurrentCommunity() ) {

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

                    $mailOrUsername = $user->getEmail();
                    $token = null;

                    // If the user is already in the community
                    if ($user->belongsTo($community)){

                        $this->get('session')->setFlash(
                            'warning',
                            'The user ' . $user->getFullName() . ' is already a member of the community ' . $community->getName() . '.'
                        );

                        return $this->redirect($this->generateUrl('invite'));

                    // If the user is already a guest in the community
                    } elseif ($user->isGuestOf($community)) {

                        $community->removeGuest($user);
                        $community->addUser($user);
                        $logService = $this->container->get('logService');
                        $logService->log($this->getUser(), 'user_enters_community', $user, array( 'community' => array( 'routing' => 'community', 'logName' => $community->getLogName(), 'args' => null) ) );
                            
                        $this->get('session')->setFlash(
                            'success',
                            'The user ' . $user->getFullName() . ' now belongs to the community ' . $community->getName() . '. A notification mail was sent on your behalf.'
                        );

                    // The user has no link with the current community
                    } else {

                        $community->addUser($user);
                        $logService = $this->container->get('logService');
                        $logService->log($this->getUser(), 'user_enters_community', $user, array( 'community' => array( 'routing' => 'community', 'logName' => $community->getLogName(), 'args' => null) ) );
                            
                        $this->get('session')->setFlash(
                            'success',
                            'The user ' . $user->getFullName() . ' now belongs to the community ' . $community->getName() . '. A notification mail was sent on your behalf.'
                        );
                    }

                } elseif ($isEmail) {

                    // Create token linked to email
                    $token = new UserInviteToken($authenticatedUser, $mailOrUsername, $community, 'user', null, null);
                    $em->persist($token);
                
                    $this->get('session')->setFlash(
                        'success',
                        'An invitation was sent to ' . $mailOrUsername . ' on your behalf.'
                    );

                } else {

                    $this->get('session')->setFlash(
                        'error',
                        'Neither the email you have indicated is valid, nor it is a valid username.'
                    );

                    return $this->redirect($this->generateUrl('invite'));
                }

                $em->flush();

                // Sends mail to invitee
                $message = \Swift_Message::newInstance()
                    ->setSubject('You\'ve been invited to a community on igneet')
                    ->setFrom($this->container->getParameter('mailer_from'))
                    ->setReplyTo($authenticatedUser->getEmail())
                    ->setTo($mailOrUsername)
                    ->setBody(
                        $this->renderView(
                            'metaUserBundle:Mail:invite.mail.html.twig',
                            array('user' => $authenticatedUser, 'inviteToken' => $token?$token->getToken():null, 'invitee' => ($user && !$user->isDeleted()), 'community' => $community, 'project' => null )
                        ), 'text/html'
                    );
                $this->get('mailer')->send($message);

                return $this->redirect($this->generateUrl('u_me'));

            } else {

                return $this->render('metaUserBundle:Security:invite.html.twig', array('community' => $community) );

            }

        } else {

            $this->get('session')->setFlash(
                'error',
                'You need to be in a non-guest community space to invite someone.'
            );

            return $this->redirect($this->generateUrl('home'));

        }

    }

    /*
     * Reactivate a user account by sending a mail with an invite
     */
    public function reactivateOrRecoverAction(Request $request, $flavour)
    {

        // You should not be logged
        if ($this->getUser()){
            
            $this->get('session')->setFlash(
                'error',
                'You are already logged.'
            );

            return $this->redirect($this->generateUrl('u_me'));
        }

        if ($request->isMethod('POST')) {

            $mail = $request->request->get('mail');

            $repository = $this->getDoctrine()->getRepository('metaUserBundle:User');
            $em = $this->getDoctrine()->getManager();
            
            $user = $repository->findOneByEmail($mail);

            if ( $user && $flavour === 'reactivate' && $user->isDeleted() ){

                $user->createNewReactivateToken();
                $em->flush();

                $this->get('session')->setFlash(
                    'success',
                    'An email was sent to ' . $mail . ' so you can reactivate your account.'
                );

                // Sends mail to invitee
                $message = \Swift_Message::newInstance()
                    ->setSubject('Reactivate your account')
                    ->setFrom($this->container->getParameter('mailer_from'))
                    ->setTo($mail)
                    ->setBody(
                        $this->renderView(
                            'metaUserBundle:Mail:reactivateOrRecover.mail.html.twig',
                            array('user' => $user, 'token' => $user->getToken(), 'flavour' => $flavour )
                        ), 'text/html'
                    );

                $this->get('mailer')->send($message);

                return $this->redirect($this->generateUrl('login'));

            } elseif ( $user && $flavour === 'recover' && !$user->isDeleted() ){

                $user->createNewRecoverToken();
                $em->flush();

                $this->get('session')->setFlash(
                    'success',
                    'An email was sent to ' . $mail . ' so you can change your password.'
                );

                // Sends mail to invitee
                $message = \Swift_Message::newInstance()
                    ->setSubject('Recover your password')
                    ->setFrom($this->container->getParameter('mailer_from'))
                    ->setTo($mail)
                    ->setBody(
                        $this->renderView(
                            'metaUserBundle:Mail:reactivateOrRecover.mail.html.twig',
                            array('user' => $user, 'token' => $user->getToken(), 'flavour' => $flavour )
                        ), 'text/html'
                    );

                $this->get('mailer')->send($message);

                return $this->redirect($this->generateUrl('login'));

            } else {

                $this->get('session')->setFlash(
                    'error',
                    'You cannot recover your account.'
                );

                return $this->redirect($this->generateUrl('u_me'));

            }
            
        } else {

            return $this->render('metaUserBundle:Security:reactivateOrRecover.html.twig', array( 'flavour' => $flavour));
        
        }
    
    }

    /*
     * Allows to change a password
     */
    public function changePasswordAction(Request $request, $passwordToken)
    {

        // It may be an internal request
        if(is_null($passwordToken) && $this->getUser()){
            
            if (!$this->get('form.csrf_provider')->isCsrfTokenValid('changePassword', $request->get('token'))){
                throw $this->createNotFoundException();
            } else {
                $em = $this->getDoctrine()->getManager();
                $this->getUser()->createNewRecoverToken();
                $em->flush();

                return $this->redirect($this->generateUrl('change_password', array('passwordToken' => $this->getUser()->getToken())));
            }

        } else {

            $repository = $this->getDoctrine()->getRepository('metaUserBundle:User');
            $user = $repository->findOneByToken($passwordToken);

            $token_parts = explode(':',base64_decode($passwordToken));
            $flavour = $token_parts[0];

            if (!$user || $user == false){
                throw $this->createNotFoundException();
            }

        }

        if ($request->isMethod('POST')) {

            $newPassword = $request->request->get('password');
            $newPassword_2 = $request->request->get('password_2');

            if ($newPassword !== $newPassword_2){

                $this->get('session')->setFlash(
                    'error',
                    'Password entries do not match.'
                );

                return $this->render('metaUserBundle:Security:changePassword.html.twig', array('passwordToken' => $passwordToken, 'flavour' => $flavour));
        
            } else {

                $user->setPassword($newPassword);

                // Changes password
                $user->setSalt(md5(uniqid(null, true)));
                $user->setToken(null);

                $errors = $this->get('validator')->validate($user);
            
                if( count($errors) === 0){

                    if ($flavour === 'reactivate'){

                        $this->get('session')->setFlash(
                            'info',
                            'Your account has been reactivated.'
                        );

                        $user->setDeletedAt(null);

                    }

                    // Now that it is validated, let's crypt the whole thing
                    $factory = $this->get('security.encoder_factory');
                    $encoder = $factory->getEncoder($user);
                    $user->setPassword($encoder->encodePassword($user->getPassword(), $user->getSalt()));
                    $em = $this->getDoctrine()->getManager();
                    $em->flush();

                    $this->get('session')->setFlash(
                        'success',
                        'Your password has been changed successfully.'
                    );

                    return $this->redirect($this->generateUrl('u_me'));

                } else {

                    $this->get('session')->setFlash(
                        'error',
                        $errors[0]->getMessage()
                    );

                    return $this->render('metaUserBundle:Security:changePassword.html.twig', array('passwordToken' => $passwordToken, 'flavour' => $flavour));
        
                }

            }

        } else {
            
            return $this->render('metaUserBundle:Security:changePassword.html.twig', array('passwordToken' => $passwordToken, 'flavour' => $flavour));
        
        }

    }

    /* ********************************************************************* */
    /*                           Non-routed actions                          */
    /* ********************************************************************* */

    /*
     * Output the top header menu
     */
    public function currentUserMenuAction()
    {
        $authenticatedUser = $this->getUser();

        if ($authenticatedUser) {

            // Logs last activity
            $em = $this->getDoctrine()->getManager();
            $authenticatedUser->setLastSeenAt(new \DateTime('now'));
            $em->flush();

            return $this->render(
                'metaUserBundle:Security:_authenticated.html.twig',
                array('user' => $authenticatedUser)
            );

        } else {

            return $this->render('metaUserBundle:Security:_anonymous.html.twig');

        }

    }
}
