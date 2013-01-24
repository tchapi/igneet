<?php
 
namespace meta\UserProfileBundle\Controller;
 
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\SecurityContext;

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

            return $this->render(
                'metaUserProfileBundle:Security:_anonymous.html.twig'
            );

        }

    }
}