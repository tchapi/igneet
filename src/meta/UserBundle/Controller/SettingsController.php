<?php

namespace meta\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\Security\Core\SecurityContext,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response;

/*
 * Importing Class definitions
 */
use meta\UserBundle\Entity\User;

class SettingsController extends Controller
{

    /*
     * Show a user profile
     */
    public function showSettingsAction()
    {

        return $this->render('metaUserBundle:User:showSettings.html.twig');
    }

    /*
     * Edit the settings
     * NEEDS JSON
     */
    public function editSettingsAction(Request $request)
    {

        if (!$this->get('form.csrf_provider')->isCsrfTokenValid('editSettings', $request->get('token'))) {
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

        $response = null;

        if ($authenticatedUser) {

            $objectHasBeenModified = false;

            switch ($request->request->get('name')) {
                case 'email':
                    $email = $request->request->get('value');
                    if (trim($email) != "") {
                        $authenticatedUser->setEmail($email);
                        $objectHasBeenModified = true;
                    }
                    break;
                case 'digestToggle':
                    $authenticatedUser->setEnableDigest(($request->request->get('value') == true));
                    $objectHasBeenModified = true;
                    break;
                case 'frequency':
                    $frequencies = array('daily', 'weekly', 'bimonthly');
                    $authenticatedUser->setDigestFrequency($frequencies[intval($request->request->get('value')) - 1]);
                    $objectHasBeenModified = true;
                    break;
                case 'day':
                    $days = array('monday', 'tuesday', 'wednesday', 'thursday', 'friday','saturday', 'sunday');
                    $authenticatedUser->setDigestDay($days[intval($request->request->get('value')) - 1]);
                    $objectHasBeenModified = true;
                    break;
                case 'specificDayToggle':
                    $authenticatedUser->setEnableSpecificDay(($request->request->get('value') == true));
                    $objectHasBeenModified = true;
                    break;
                case 'specificEmailsToggle':
                    $authenticatedUser->setEnableSpecificEmails(($request->request->get('value') == true));
                    $objectHasBeenModified = true;
                    break;
                case 'community':
                    $repository = $this->getDoctrine()->getRepository('metaUserBundle:UserCommunity');
                    $userRepository = $repository->findOneBy(array( 'user' => $authenticatedUser->getId(), 'community' => $this->container->get('uid')->fromUId($request->request->get('key')) ));
                    if ($userRepository){
                        $userRepository->setEmail($request->request->get('value'));
                        $objectHasBeenModified = true;
                    }                   
                    break;
            }

            $errors = $this->get('validator')->validate($authenticatedUser);
            if ( count($errors) == 0 && isset($userRepository)){
                $errors = $this->get('validator')->validate($userRepository);
            }

            if ($objectHasBeenModified === true && count($errors) == 0){

                // No need to log anything
                $em = $this->getDoctrine()->getManager();
                $em->flush();

                return new Response(json_encode($response), 200, array('Content-Type'=>'application/json'));

            } elseif (count($errors) > 0) {

                    $response = array('message' => $this->get('translator')->trans($errors[0]->getMessage()));

            } else {
                
                if ($response == null) {
                    $response = array('message' => $this->get('translator')->trans('unnecessary.request', array(), 'errors'));
                }
            }

        }
        
        return new Response(json_encode(array('message' =>  $this->get('translator')->trans('invalid.request', array(), 'errors'))), 406, array('Content-Type'=>'application/json'));

    }

}
