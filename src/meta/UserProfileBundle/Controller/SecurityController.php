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

        if ($authenticatedUser) {

            if ($request->isMethod('POST')) {
            
                // Gets mail 
                $mail = $request->request->get('mail');

                if(filter_var($mail, FILTER_VALIDATE_EMAIL)){

                    // Create token linked to email
                    $token = new UserInviteToken($authenticatedUser, $mail);

                    $em = $this->getDoctrine()->getManager();
                    $em->persist($token);
                    $em->flush();

                    // Sends mail to invitee
                    $message = \Swift_Message::newInstance()
                        ->setSubject('You\'ve been invited on igneet')
                        ->setFrom($this->container->getParameter('mailer_from'))
                        ->setReplyTo($authenticatedUser->getEmail())
                        ->setTo($mail)
                        ->setBody(
                            $this->renderView(
                                'metaUserProfileBundle:Mail:invite.mail.html.twig',
                                array('user' => $authenticatedUser, 'token' => $token->getToken())
                            ), 'text/html'
                        )
                    ;
                    $this->get('mailer')->send($message);

                    $this->get('session')->setFlash(
                        'success',
                        'An invitation was sent to ' . $mail . ' on your behalf.'
                    );

                    return $this->redirect($this->generateUrl('u_me'));
                }


            } else {

                return $this->render('metaUserProfileBundle:Security:invite.html.twig');

            }

        } else {

            $this->get('session')->setFlash(
                'error',
                'You need to be logged in to invite someone.'
            );

            return $this->redirect($this->generateUrl('login'));

        }

    }
}
