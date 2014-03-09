<?php

namespace meta\GeneralBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response,
    Symfony\Component\HttpFoundation\RedirectResponse;

use meta\GeneralBundle\Entity\Community\Community,
    meta\UserBundle\Entity\UserCommunity,
    meta\UserBundle\Entity\UserInviteToken;

class FirstRunController extends Controller
{

  public function doStepAction(Request $request, $step)
  {

    // Gets the array of steps
    $steps = $this->container->getParameter('steps');

    if (isset($steps[$step]) && $steps[$step] != null){

      $currentStep = $steps[$step];
      $nextStepId = $step + 1;

      if (!isset($steps[$nextStepId]) || $steps[$nextStepId] === null) {
        $nextStepId = null;
      }

      return $this->forward($currentStep["action"], array( 'currentStep' => $currentStep, 'nextStepId' =>  $nextStepId ));

    } else {

      // Step does not exist => we return to home
      return $this->redirect($this->generateUrl('g_home_community'));

    }

  }

  public function createCommunityAction(Request $request, $currentStep, $nextStepId)
  {

    $community = new Community();
    $form = $this->createFormBuilder($community)
        ->add('name', 'text', array('required' => true, 'attr' => array( 'autofocus' => "autofocus", 'placeholder' => 'community.createForm.namePlaceholder')))
        ->getForm();

    $authenticatedUser = $this->getUser();

    if ($request->isMethod('POST')) {

      $form->bind($request);

      if ($form->isValid()){

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

        $session = $request->getSession();
        $session->set('community', $community->getId());

        return new Response(json_encode(array('redirect' => $this->generateUrl('g_first_run', array( 'step' => $nextStepId)))), 200, array('Content-Type'=>'application/json'));

      } else {

        return new Response(json_encode(array('message' => $this->get('translator')->trans('invalid.values', array(), 'errors'))), 400, array('Content-Type'=>'application/json'));

      }

    } else {

      return $this->render("metaGeneralBundle:FirstRun:createCommunity.html.twig", array( 'form' => $form->createView(), 'currentStep' => $currentStep, 'nextStepId' => $nextStepId));

    }

  }

  public function inviteUsersAction(Request $request, $currentStep, $nextStepId)
  {

    $authenticatedUser = $this->getUser();

    if ($request->isMethod('POST')) {
    
      $emails = $request->request->get('emails');
      $repository = $this->getDoctrine()->getRepository('metaUserBundle:User');

      $session = $request->getSession();
      
      if ($session->has('community')) {

        $communityRepository = $this->getDoctrine()->getRepository('metaGeneralBundle:Community\Community');
        $community = $communityRepository->findOneById($session->get('community'));

      } else {

        return new Response(json_encode(array('message' => $this->get('translator')->trans('invalid.values', array(), 'errors'))), 400, array('Content-Type'=>'application/json'));

      }

      foreach ($emails as $email) {
        
        if ($email == "") continue;

        $user = $repository->findOneByEmail($email);
        $em = $this->getDoctrine()->getManager();

        if ($user && $user->isDeleted() == false) {

            $userCommunity = new UserCommunity();
            $userCommunity->setUser($user);
            $userCommunity->setCommunity($community);
            $userCommunity->setGuest(false);

            $em->persist($userCommunity);
            $em->persist($community);

            $logService = $this->container->get('logService');
            $logService->log($this->getUser(), 'user_enters_community', $user, array( 'community' => array( 'logName' => $community->getLogName(), 'identifier' => $community->getId()) ) );
                
            $community->extendValidityBy($this->container->getParameter('community.viral_extension'));
            
        } elseif ($user == null) {

            // Create token linked to email
            $token = new UserInviteToken($authenticatedUser, $email, $community, 'user', null, null);
            $em->persist($token);

        } /* else skip */

        $em->flush();

        // Sends mail to invitee
        $message = \Swift_Message::newInstance()
            ->setSubject($this->get('translator')->trans('user.invitation.mail.subject'))
            ->setFrom($this->container->getParameter('mailer_from'))
            ->setReplyTo($authenticatedUser->getEmail())
            ->setTo($email)
            ->setBody(
                $this->renderView(
                    'metaUserBundle:Mail:invite.mail.html.twig',
                    array('user' => $authenticatedUser, 'inviteToken' => $token?$token->getToken():null, 'invitee' => ($user && !$user->isDeleted()), 'community' => $community, 'project' => null )
                ), 'text/html'
            );
        $this->get('mailer')->send($message);

      }

      return new Response(json_encode(array('redirect' => $this->generateUrl('g_first_run', array( 'step' => $nextStepId)))), 200, array('Content-Type'=>'application/json'));

    } else {

      $session = $request->getSession();
      
      if ($session->has('community')) {
        
        $communityRepository = $this->getDoctrine()->getRepository('metaGeneralBundle:Community\Community');
        $community = $communityRepository->findOneById($session->get('community'));

        return $this->render("metaGeneralBundle:FirstRun:inviteUsers.html.twig", array( 'currentStep' => $currentStep, 'nextStepId' => $nextStepId, 'community' => $community));
      
      } else {
        
        return $this->forward("metaGeneralBundle:FirstRun:doStep", array( 'step' => $nextStepId - 2));
      
      }

    }

  }

  public function congratsAction(Request $request, $currentStep, $nextStepId)
  {

    $session = $request->getSession();
    $session->set('community', null);

    return $this->render("metaGeneralBundle:FirstRun:congrats.html.twig", array( 'currentStep' => $currentStep, 'nextStepId' => $nextStepId));

  }

}
