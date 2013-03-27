<?php
 
namespace meta\UserProfileBundle\Controller;
 
use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\Security\Core\SecurityContext;

use meta\UserProfileBundle\Entity\UserInviteToken;

/*
 * Importing Class definitions
 */
 
class SecurityController extends Controller
{
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
 
        return $this->render('metaUserProfileBundle:Security:login.html.twig', array(
            // last username entered by the user
            'last_username' => $session->get(SecurityContext::LAST_USERNAME),
            'error'         => $error,
        ));
    }

    public function currentUserMenuAction()
    {
        $authenticatedUser = $this->getUser();

        if ($authenticatedUser) {

            // Logs last activity
            $em = $this->getDoctrine()->getManager();
            $authenticatedUser->setLastSeenAt(new \DateTime('now'));
            $em->flush();

            return $this->render(
                'metaUserProfileBundle:Security:_authenticated.html.twig',
                array('user' => $authenticatedUser)
            );

        } else {

            return $this->render('metaUserProfileBundle:Security:_anonymous.html.twig');

        }

    }

    public function inviteAction(Request $request)
    {

        $authenticatedUser = $this->getUser();
        $community = $authenticatedUser->getCurrentCommunity();

        if ($community !== null && !$authenticatedUser->isGuestInCurrentCommunity() ) {

            if ($request->isMethod('POST')) {
            
                // Gets mail or username
                $mailOrUsername = $request->request->get('mailOrUsername');
                $isEmail = filter_var($mailOrUsername, FILTER_VALIDATE_EMAIL);

                // It might be a user
                $repository = $this->getDoctrine()->getRepository('metaUserProfileBundle:User');
                $em = $this->getDoctrine()->getManager();

                if($isEmail){
                    $user = $repository->findOneByEmail($mailOrUsername);
                } else {
                    $user = $repository->findOneByUsername($mailOrUsername);
                }

                if ($user && !$user->isDeleted()) {

                    $mail = $user->getEmail();
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

                        $this->get('session')->setFlash(
                            'success',
                            'The user ' . $user->getFullName() . ' now belongs to the community ' . $community->getName() . '. A notification mail was sent on your behalf.'
                        );

                    // The user has no link with the current community
                    } else {

                        $community->addUser($user);

                        $this->get('session')->setFlash(
                            'success',
                            'The user ' . $user->getFullName() . ' now belongs to the community ' . $community->getName() . '. A notification mail was sent on your behalf.'
                        );
                    }

                } elseif ($isEmail) {

                    // Create token linked to email
                    $token = new UserInviteToken($authenticatedUser, $mailOrUsername);
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
                            'metaUserProfileBundle:Mail:invite.mail.html.twig',
                            array('user' => $authenticatedUser, 'inviteToken' => $token?$token->getToken():null, 'invitee' => ($user && !$user->isDeleted()) )
                        ), 'text/html'
                    )
                ;
                $this->get('mailer')->send($message);

                return $this->redirect($this->generateUrl('u_me'));

            } else {

                return $this->render('metaUserProfileBundle:Security:invite.html.twig', array('community' => $community) );

            }

        } else {

            $this->get('session')->setFlash(
                'error',
                'You need to be in a non-guest community space to invite someone.'
            );

            return $this->redirect($this->generateUrl('home'));

        }

    }
}
