<?php

namespace meta\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\Security\Core\SecurityContext,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response,
    Symfony\Component\EventDispatcher\EventDispatcher,
    Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken,
    Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

/*
 * Importing Class definitions
 */
use meta\UserBundle\Entity\User,
    meta\UserBundle\Form\Type\UserType;

class SettingsController extends Controller
{

    /*
     * Show a user profile
     */
    public function showSettingsAction()
    {

        return $this->render('metaUserBundle:Settings:show.html.twig');
    }

    /*
     * Edit a user (via X-editable)
     */
    public function editSettingsAction(Request $request)
    {

        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('editSettings', $request->get('token')))
            return new Response($this->get('translator')->trans('invalid.token', array(), 'errors'), 400);

        $authenticatedUser = $this->getUser();
        $error = null;
        $response = null;

        if ($authenticatedUser) {

            $objectHasBeenModified = false;

            switch ($request->request->get('name')) {
                case 'email':
                    $authenticatedUser->setEmail($request->request->get('value'));
                    $objectHasBeenModified = true;
                    break;
            }

            $errors = $this->get('validator')->validate($authenticatedUser);

            if ($objectHasBeenModified === true && count($errors) == 0){

                // No need to log anything

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

            return $this->redirect($this->generateUrl('u_show_user_settings'));

        } else {
            
            if (!is_null($error)) {
                return new Response($error, 406);
            }

            return new Response($response);
        }

    }

}