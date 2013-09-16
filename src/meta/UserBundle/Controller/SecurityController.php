<?php
 
namespace meta\UserBundle\Controller;
 
use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\Security\Core\SecurityContext;

use meta\UserBundle\Entity\UserInviteToken,
    meta\UserBundle\Entity\UserCommunity;
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

            $this->get('session')->getFlashBag()->add(
                'warning',
                $this->get('translator')->trans('user.already.logged.short', array( '%user%' => $authenticatedUser->getUsername()))
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

        if (!is_null($community)){
            $userCommunityGuest = $this->getDoctrine()->getRepository('metaUserBundle:UserCommunity')->findBy(array('user' => $authenticatedUser->getId(), 'community' => $community->getId(), 'guest' => true));
        } else {
            $userCommunityGuest = null;
        }
        
        if ( !is_null($community) && !$userCommunityGuest ) {

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

                    $userCommunity = $this->getDoctrine()->getRepository('metaUserBundle:UserCommunity')->findBy(array('user' => $user->getId(), 'community' => $community->getId(), 'guest' => false));

                    $userCommunityGuest = $this->getDoctrine()->getRepository('metaUserBundle:UserCommunity')->findBy(array('user' => $user->getId(), 'community' => $community->getId(), 'guest' => true));

                    // If the user is already in the community
                    if ($userCommunity){

                        $this->get('session')->getFlashBag()->add(
                            'warning',
                            $this->get('translator')->trans('user.already.in.community', array( '%user%' => $user->getFullName(), '%community%' => $community->getName() ))
                        );

                        return $this->redirect($this->generateUrl('invite'));

                    // If the user is already a guest in the community
                    } elseif ($userCommunityGuest) {

                        // User is not guest anymore, we already have a userCommunity object
                        $userCommunityGuest->setGuest(false);
                        $logService = $this->container->get('logService');
                        $logService->log($this->getUser(), 'user_enters_community', $user, array( 'community' => array( 'logName' => $community->getLogName(), 'identifier' => $community->getId()) ) );
                            
                        $this->get('session')->getFlashBag()->add(
                            'success',
                            $this->get('translator')->trans('user.belonging.community', array( '%user%' => $user->getFullName(), '%community%' => $community->getName() ))
                        );

                    // The user has no link with the current community
                    } else {

                        // Creates the userCommunity
                        $userCommunity = new UserCommunity();
                        $userCommunity->setUser($user);
                        $userCommunity->setCommunity($community);
                        $userCommunity->setGuest(false);

                        $em->persist($userCommunity);

                        $logService = $this->container->get('logService');
                        $logService->log($this->getUser(), 'user_enters_community', $user, array( 'community' => array( 'logName' => $community->getLogName(), 'identifier' => $community->getId()) ) );
                            
                        $this->get('session')->getFlashBag()->add(
                            'success',
                            $this->get('translator')->trans('user.belonging.community', array( '%user%' => $user->getFullName(), '%community%' => $community->getName() ))
                        );
                    }

                } elseif ($isEmail) {

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

                    return $this->redirect($this->generateUrl('invite'));
                }

                $em->flush();

                // Sends mail to invitee
                $message = \Swift_Message::newInstance()
                    ->setSubject($this->get('translator')->trans('user.invitation.mail.subject'))
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

            $this->get('session')->getFlashBag()->add(
                'error',
                $this->get('translator')->trans('user.invitation.impossible')
            );

            return $this->redirect($this->generateUrl('g_home_community'));

        }

    }

    /*
     * Reactivate a user account by sending a mail with an invite
     */
    public function reactivateOrRecoverAction(Request $request, $flavour)
    {

        // You should not be logged
        if ($this->getUser()){
            
            $this->get('session')->getFlashBag()->add(
                'error',
                $this->get('translator')->trans('user.already.logged.short', array( '%user%' => $this->getUser()->getFullName()))
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

                $this->get('session')->getFlashBag()->add(
                    'success',
                    $this->get('translator')->trans('user.reactivation.sent', array( '%mail%' => $mail))
                );

                // Sends mail to invitee
                $message = \Swift_Message::newInstance()
                    ->setSubject($this->get('translator')->trans('user.reactivation.mail.subject'))
                    ->setFrom($this->container->getParameter('mailer_from'))
                    ->setTo($mail)
                    ->setBody(
                        $this->renderView(
                            'metaUserBundle:Mail:reactivateOrRecover.mail.html.twig',
                            array('user' => $user, 'passwordToken' => $user->getToken(), 'flavour' => $flavour )
                        ), 'text/html'
                    );

                $this->get('mailer')->send($message);

                return $this->redirect($this->generateUrl('login'));

            } elseif ( $user && $flavour === 'recover' && !$user->isDeleted() ){

                $user->createNewRecoverToken();
                $em->flush();

                $this->get('session')->getFlashBag()->add(
                    'success',
                    $this->get('translator')->trans('user.passwordChange.sent', array( '%mail%' => $mail))
                );

                // Sends mail to invitee
                $message = \Swift_Message::newInstance()
                    ->setSubject($this->get('translator')->trans('user.passwordChange.mail.subject'))
                    ->setFrom($this->container->getParameter('mailer_from'))
                    ->setTo($mail)
                    ->setBody(
                        $this->renderView(
                            'metaUserBundle:Mail:reactivateOrRecover.mail.html.twig',
                            array('user' => $user, 'passwordToken' => $user->getToken(), 'flavour' => $flavour )
                        ), 'text/html'
                    );

                $this->get('mailer')->send($message);

                return $this->redirect($this->generateUrl('login'));

            } else {

                $this->get('session')->getFlashBag()->add(
                    'error',
                    $this->get('translator')->trans('user.cannot.recover')
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
                throw $this->createNotFoundException($this->get('translator')->trans('invalid.token', array(), 'errors'));
            } else {
                $em = $this->getDoctrine()->getManager();
                $this->getUser()->createNewRecoverToken();
                $em->flush();

                return $this->redirect($this->generateUrl('change_password', array('passwordToken' => $this->getUser()->getToken())));
            }

        } elseif (!is_null($passwordToken)) {

            $repository = $this->getDoctrine()->getRepository('metaUserBundle:User');
            $user = $repository->findOneByToken($passwordToken);

            $token_parts = explode(':',base64_decode($passwordToken));
            $flavour = $token_parts[0];

            if (!$user || $user == false){
                throw $this->createNotFoundException($this->get('translator')->trans('user.not.found'));
            }

        } else {

            throw $this->createNotFoundException($this->get('translator')->trans('invalid.request', array(), 'errors'));
        
        }

        if ($request->isMethod('POST')) {

            $newPassword = $request->request->get('password');
            $newPassword_2 = $request->request->get('password_2');

            if ($newPassword !== $newPassword_2){

                $this->get('session')->getFlashBag()->add(
                    'error',
                    $this->get('translator')->trans('invalid.password.match', array(), 'errors')
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

                        $this->get('session')->getFlashBag()->add(
                            'info',
                            $this->get('translator')->trans('user.reactivated')
                        );

                        $user->setDeletedAt(null);

                    }

                    // Now that it is validated, let's crypt the whole thing
                    $factory = $this->get('security.encoder_factory');
                    $encoder = $factory->getEncoder($user);
                    $user->setPassword($encoder->encodePassword($user->getPassword(), $user->getSalt()));
                    $em = $this->getDoctrine()->getManager();
                    $em->flush();

                    $this->get('session')->getFlashBag()->add(
                        'success',
                        $this->get('translator')->trans('user.changedPassword')
                    );

                    return $this->redirect($this->generateUrl('u_show_user_settings'));

                } else {

                    $this->get('session')->getFlashBag()->add(
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
     * Helper for logging
     */
    private function logLastSeenAt()
    {
        // Logs last activity
        $em = $this->getDoctrine()->getManager();
        $this->getUser()->setLastSeenAt(new \DateTime('now'));
        $em->flush();
    }

    /*
     * Output the top header menu
     */
    public function currentUserMenuAction()
    {
        $authenticatedUser = $this->getUser();

        if ($authenticatedUser) {

            $this->logLastSeenAt();

            $community = $authenticatedUser->getCurrentCommunity();

            if (is_null($community)){
                // Private space, you're not a guest
                $userCommunityGuest = null;
            } else {
                $userCommunityGuest = $this->getDoctrine()->getRepository('metaUserBundle:UserCommunity')->findBy(array('user' => $authenticatedUser->getId(), 'community' => $community->getId(), 'guest' => true));
            }
            
            return $this->render(
                'metaUserBundle:Security:_authenticated.html.twig',
                array('user' => $authenticatedUser, 'isGuest' => ($userCommunityGuest != null) )
            );

        } else {

            return $this->render('metaUserBundle:Security:_anonymous.html.twig');

        }

    }
}
