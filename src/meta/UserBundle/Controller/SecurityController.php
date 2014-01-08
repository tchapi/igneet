<?php
 
namespace meta\UserBundle\Controller;
 
use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\Security\Core\SecurityContext,
    Symfony\Component\EventDispatcher\EventDispatcher,
    Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken,
    Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

use Fp\OpenIdBundle\RelyingParty\Exception\OpenIdAuthenticationCanceledException;
use Fp\OpenIdBundle\RelyingParty\RecoveredFailureRelyingParty;
use Fp\OpenIdBundle\Security\Core\Authentication\Token\OpenIdToken;
use meta\UserBundle\Entity\OpenIdIdentity;

/*
 * Importing Class definitions
 */
use meta\UserBundle\Entity\User,
    meta\UserBundle\Form\Type\UserType,
    meta\UserBundle\Entity\UserCommunity,
    meta\UserBundle\Entity\UserInviteToken;
 
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
                $this->get('translator')->trans('user.already.logged.short', array('%user%' => $authenticatedUser->getUsername()))
            );

            return $this->redirect($this->generateUrl('g_home_community'));
        } 

        $request = $this->getRequest();
        $session = $request->getSession();
 
        // Get the login error if there is one
        if ($request->attributes->has(SecurityContext::AUTHENTICATION_ERROR)) {
            $error = $request->attributes->get(SecurityContext::AUTHENTICATION_ERROR);
        } else {
            $error = $session->get(SecurityContext::AUTHENTICATION_ERROR);
        }
 
        return $this->render('metaUserBundle:Non-Auth:login.html.twig', array(
            // last username entered by the user
            'last_username' => $session->get(SecurityContext::LAST_USERNAME),
            'error'         => $error,
        ));
    }

    /*
     * Shows the user the different signin methods available
     */
    public function chooseSignupProviderAction($inviteToken)
    {
    
        $authenticatedUser = $this->getUser();

        if ($authenticatedUser) {

            $this->get('session')->getFlashBag()->add(
                'warning',
                $this->get('translator')->trans('user.already.logged.long', array( '%user%' => $authenticatedUser->getUsername()))
            );

            return $this->redirect($this->generateUrl('u_show_user_profile', array('username' => $authenticatedUser->getUsername())));
        }

        return $this->render('metaUserBundle:Non-Auth:chooseProvider.html.twig', array('inviteToken' => $inviteToken));

    }

    /*
     * Create a form for a new user to signin AND process the result when POSTed
     */
    public function createAction(Request $request, $inviteToken, $openid)
    {
        
        $authenticatedUser = $this->getUser();
        $em = $this->getDoctrine()->getManager();

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

        // In case it's open id, we need to check some basics
        if ($openid == true) {

             $failure = $request->getSession()->get(SecurityContext::AUTHENTICATION_ERROR);

            if (false == $failure) {
                $this->get('session')->getFlashBag()->add(
                    'error',
                    $this->get('translator')->trans('openid.error', array(), 'errors')
                );
                return $this->redirect($this->generateUrl('login'));
            }

            if ($failure instanceof OpenIdAuthenticationCanceledException) {
                
                // User cancelled
                $this->get('session')->getFlashBag()->add(
                    'warning',
                    $this->get('translator')->trans('user.cancelled.signup')
                );
                return $this->redirect($this->generateUrl('login'));
            }

            $token = $failure->getToken();

            if (false == $token instanceof OpenIdToken) {

                $this->get('session')->getFlashBag()->add(
                    'error',
                    $this->get('translator')->trans('openid.error', array(), 'errors')
                );
                return $this->redirect($this->generateUrl('login'));

            }

            // Merges
            $attributes = array_merge(array(
                'contact/email' => '',
                'namePerson/first' => '',
                'namePerson/last' => '',
                ), $token->getAttributes())
            ;

            // Already in ?
            $alreadyUser = $em->getRepository('metaUserBundle:User')->findOneBy(array(
                'email' => $attributes['contact/email']
            ));

            if ($alreadyUser){

                if ($alreadyUser->isDeleted()){
                    // Error will be screened automatically
                    return $this->redirect($this->generateUrl('login'));
                }
            }

        }

        $user = new User();

        if ($openid == true){
            // We already know some stuff
            $user->setEmail($attributes['contact/email']);
            $user->setFirstname($attributes['namePerson/first']);
            $user->setLastname($attributes['namePerson/last']);

            // Create a dummy password
            $factory = $this->get('security.encoder_factory');
            $encoder = $factory->getEncoder($user);
            $user->setPassword($encoder->encodePassword($user->getSalt(), $user->getSalt()));
        }

        $form = $this->createForm(new UserType(), $user, array( 'translator' => $this->get('translator'), 'openid' => $openid));

        if ($request->isMethod('POST')) {

            $form->bind($request);

            if ($form->isValid()) {

                if ($openid === true){

                    // We have to create an OpenId and persist it
                    $openIdIdentity = new OpenIdIdentity();
                    $openIdIdentity->setIdentity($token->getIdentity());
                    $openIdIdentity->setAttributes($attributes);
                    $openIdIdentity->setUser($user);

                    $em->persist($openIdIdentity);

                } else {

                    // Not open id, we just set the password :
                    $factory = $this->get('security.encoder_factory');
                    $encoder = $factory->getEncoder($user);
                    $user->setPassword($encoder->encodePassword($user->getPassword(), $user->getSalt()));
                    
                }

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

                            $logService = $this->container->get('logService');
                            $logService->log($this->getUser(), 'user_enters_community', $user, array( 'community' => array( 'logName' => $inviteTokenObject->getCommunity()->getLogName() ) ) );
                        
                        }

                        // Creates the userCommunity
                        $userCommunity = new UserCommunity();
                        $userCommunity->setUser($user);
                        $userCommunity->setCommunity($inviteTokenObject->getCommunity());
                        $userCommunity->setGuest( !($inviteTokenObject->getCommunityType() === 'user') );

                        // In case the user is not a guest, push the validity of the community by 'community.viral_extension'
                        if ($inviteTokenObject->getCommunityType() === 'user') {
                            $inviteTokenObject->getCommunity()->extendValidityBy($this->container->getParameter('community.viral_extension'));
                        }

                        $em->persist($userCommunity);
                        
                        $user->setCurrentCommunity($inviteTokenObject->getCommunity());

                    }

                    if (!is_null($inviteTokenObject->getProject())){

                        if ($inviteTokenObject->getProjectType() === 'owner'){
                            $user->addProjectsOwned($inviteTokenObject->getProject());
                            $logService = $this->container->get('logService');
                            $logService->log($inviteTokenObject->getReferalUser(), 'user_made_user_owner_project', $inviteTokenObject->getProject(), array( 'other_user' => array( 'logName' => $user->getLogName(), 'identifier' => $user->getUsername()) ));

                        } else {
                            $user->addProjectsParticipatedIn($inviteTokenObject->getProject());
                            $logService = $this->container->get('logService');
                            $logService->log($inviteTokenObject->getReferalUser(), 'user_made_user_participant_project', $inviteTokenObject->getProject(), array( 'other_user' => array( 'logName' => $user->getLogName(), 'identifier' => $user->getUsername()) ));

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

                // Returns and redirects
                if ($openid === true ) {

                    return $this->redirect($this->generateUrl('fp_openid_security_check', array(
                        RecoveredFailureRelyingParty::RECOVERED_QUERY_PARAMETER => 1
                    )));

                } else {

                    return $this->redirect($this->generateUrl('g_home_community'));
                }

            } else {
               
               $this->get('session')->getFlashBag()->add(
                    'error',
                    $this->get('translator')->trans('information.not.valid', array(), 'errors')
                );

            }

        }

        return $this->render('metaUserBundle:Non-Auth:create.html.twig', array('form' => $form->createView(), 'inviteToken' => $inviteToken, 'openid' => $openid));

    }

    /*
     * Recover a user account
     */
    public function recoverAction(Request $request)
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

            $mail = trim($request->request->get('mail'));

            $repository = $this->getDoctrine()->getRepository('metaUserBundle:User');
            $em = $this->getDoctrine()->getManager();
            
            $user = $repository->findOneByEmail($mail);

            if ( $user && !$user->isDeleted() ){

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
                            'metaUserBundle:Mail:recover.mail.html.twig',
                            array('user' => $user, 'passwordToken' => $user->getToken())
                        ), 'text/html'
                    );

                $this->get('mailer')->send($message);

                return $this->redirect($this->generateUrl('login'));

            } else {

                $this->get('session')->getFlashBag()->add(
                    'error',
                    $this->get('translator')->trans('user.cannot.recover')
                );

                return $this->redirect($this->generateUrl('recover'));

            }
            
        } else {

            return $this->render('metaUserBundle:Non-Auth:recover.html.twig');
        
        }
    
    }

    /*
     * Allows to change a password
     */
    public function changePasswordAction(Request $request, $passwordToken)
    {

        // It may be an internal request
        if (is_null($passwordToken) && $this->getUser()){
            
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

            if (!$user || $user == false){

                $this->get('session')->getFlashBag()->add(
                    'error',
                    $this->get('translator')->trans('invalid.token', array(), 'errors')
                );

                return $this->redirect($this->generateUrl('u_show_user_settings'));
        
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

                return $this->render('metaUserBundle:User:changePassword.html.twig', array('passwordToken' => $passwordToken));
        
            } else {

                $user->setPassword($newPassword);

                // Changes password
                $user->setSalt(md5(uniqid(null, true)));
                
                $errors = $this->get('validator')->validate($user);

                $em = $this->getDoctrine()->getManager();

                if( count($errors) === 0){

                    $user->setToken(null);

                    // Now that it is validated, let's crypt the whole thing
                    $factory = $this->get('security.encoder_factory');
                    $encoder = $factory->getEncoder($user);
                    $user->setPassword($encoder->encodePassword($user->getPassword(), $user->getSalt()));
                    $em->flush();

                    $this->get('session')->getFlashBag()->add(
                        'success',
                        $this->get('translator')->trans('user.changedPassword')
                    );

                    return $this->redirect($this->generateUrl('u_show_user_settings'));

                } else {

                    $this->get('session')->getFlashBag()->add(
                        'error',
                        $this->get('translator')->trans($errors[0]->getMessage())
                    );

                    // We need this otherwise the null token / password / etc might be flushed !
                    $em->refresh($user);

                    return $this->render('metaUserBundle:User:changePassword.html.twig', array('passwordToken' => $passwordToken));
        
                }

            }

        } else {
            
            if ($this->getUser()){
                // For users accessing via settings
                return $this->render('metaUserBundle:User:changePassword.html.twig', array('passwordToken' => $passwordToken));
            } else {
                // For user in recovery mode
                return $this->render('metaUserBundle:Non-Auth:changePassword.html.twig', array('passwordToken' => $passwordToken));
            }     
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
                $userCommunity = null;
            } else {
                $userCommunity = $this->getDoctrine()->getRepository('metaUserBundle:UserCommunity')->findOneBy(array('user' => $authenticatedUser->getId(), 'community' => $community->getId(), 'deleted_at' => null));
            }
            
            return $this->render(
                'metaUserBundle:Partials:_authenticated.html.twig',
                array('user' => $authenticatedUser, 'currentUserCommunity' => $userCommunity )
            );

        } else {

            return null;

        }

    }
}
