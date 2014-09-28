<?php

namespace meta\SubscriptionBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response;

use PayPal\Auth\OAuthTokenCredential;

use meta\GeneralBundle\Entity\Community\Community,
    meta\UserBundle\Entity\UserCommunity;

class DefaultController extends Controller
{

    /*
     * STEP 1.
     * User should choose the community he wants to pay for in the list of communities he is manager in
     *
     */
    public function showCommunitiesAction()
    {

      $authenticatedUser = $this->getUser();

      // Cleans the session
      $this->get('session')->remove('current_billed_community');
      $this->get('session')->remove('current_billed_plan');

      // List managed communities 
      $userCommunities = $this->getDoctrine()->getRepository('metaUserBundle:UserCommunity')->findBy(array('user' => $authenticatedUser->getId(), 'manager' => true));

      foreach ($userCommunities as $userCommunity) {
        $communities[] = array(
            'uid' => $this->container->get('uid')->toUId($userCommunity->getCommunity()->getId()),
            'name' => $userCommunity->getCommunity()->getName(),
            'type' => $userCommunity->getCommunity()->getType(),
            'valid' => $userCommunity->getCommunity()->isValid(),
            'agreement' => $userCommunity->getCommunity()->getBillingAgreement()
          );
      }
      return $this->render('metaSubscriptionBundle::showCommunities.html.twig', array( 'communities' => $communities ));
    }

    /*
     * STEP 2.
     * User chooses the community and we display the current payment status
     *
     */
    public function showCommunityBillingAction($uid)
    {
      
      $community = $this->getDoctrine()->getRepository('metaGeneralBundle:Community\Community')->findOneById($this->container->get('uid')->fromUId($uid));

      if ($community){

        $planId = $community->getBillingPlan();
        $agreementId = $community->getBillingAgreement();
        $legacy = false; // Whether we display an old cancelled plan when community is still valid

        // No paying communities ?
        if (is_null($planId) || is_null($agreementId)) {

          $agreement = null;

        } else {

          $token = $this->get('paypalHelper')->getToken();
          if ($token === false){
            return $this->render('metaSubscriptionBundle::error.html.twig');
          }

          // Paypal PHP SDK still doesn't support billing plans/agreements (01/09/2014)
          $agreement = $this->get('paypalHelper')->getBillingAgreement($token, $agreementId);

          if ($agreement === false) {
            $this->get('session')->getFlashBag()->add(
                'error',
                $this->get('translator')->trans('billing.error.retrieve')
            );
          }

        }

        return $this->render('metaSubscriptionBundle::showBilling.html.twig', array( 'agreement' => $agreement, 'community' => $community) );

      } else {
        
        throw $this->createNotFoundException($this->get('translator')->trans('community.not.found'));

      }

    }

    /*
     * STEP 3.
     * User wants to pay for a community, we list the available plans
     *
     */
    public function listBillingPlansAction(Request $request, $uid)
    {
      if (!$this->get('form.csrf_provider')->isCsrfTokenValid('showBillingPlans', $request->get('token')))
            return new Response($this->get('translator')->trans('invalid.token', array(), 'errors'), 400);

      $community = $this->getDoctrine()->getRepository('metaGeneralBundle:Community\Community')->findOneById($this->container->get('uid')->fromUId($uid));

      if ($community){

          $token = $this->get('paypalHelper')->getToken();
          if ($token === false){
            return $this->render('metaSubscriptionBundle::error.html.twig');
          }

          // Paypal PHP SDK still doesn't support billing plans (01/09/2014)
          $plans = $this->get('paypalHelper')->getBillingPlans($token, 'ACTIVE');

          if ($plans === false) {

            $this->get('session')->getFlashBag()->add(
                'error',
                $this->get('translator')->trans('billing.error.retrieve')
            );

          }

          return $this->render('metaSubscriptionBundle::listBillingPlans.html.twig', array( 'plans' => $plans, 'community' => $community) );

      } else {
        
          throw $this->createNotFoundException($this->get('translator')->trans('community.not.found'));

      }

    }

    /*
     * STEP 4.
     * User wants a plan and chooses it, we create an agreement and ask for his approval through paypal
     *
     */
    public function createBillingAgreementAction(Request $request, $uid, $planId)
    {
      if (!$this->get('form.csrf_provider')->isCsrfTokenValid('createBillingAgreement', $request->get('token')))
            return new Response($this->get('translator')->trans('invalid.token', array(), 'errors'), 400);

      $community = $this->getDoctrine()->getRepository('metaGeneralBundle:Community\Community')->findOneById($this->container->get('uid')->fromUId($uid));

      if ($community){

          $token = $this->get('paypalHelper')->getToken();
          if ($token === false){
            return $this->render('metaSubscriptionBundle::error.html.twig');
          }

          // Get the plan
          $plan = $this->get('paypalHelper')->getBillingPlan($token, $planId);

          if ($plan === false) {
            $this->get('session')->getFlashBag()->add(
                'error',
                $this->get('translator')->trans('billing.error.retrieve')
            );
            return $this->render('metaSubscriptionBundle::error.html.twig');
          }

          // Choose a wise start date, if the community is still valid let's start the 
          // new agreement at this date and not now, this gices a bit more time for the user
          $startDate = max(time(),$community->getValidUntil()->getTimestamp());

          // Add relevant info to the description
          $info = $this->get('translator')->trans('billing.agreement.community', array('%community%' => $community->getName()));

          // Paypal PHP SDK still doesn't support billing agreements (01/09/2014)
          $agreement = $this->get('paypalHelper')->createBillingAgreement($token, $plan, $startDate, $info);

          if ($agreement === false) {
      
            $this->get('session')->getFlashBag()->add(
                'error',
                $this->get('translator')->trans('billing.error.create.agreemeent')
            );
            return $this->redirect($this->generateUrl('s_show_billing', array('uid' => $uid)));

          }

          foreach ($agreement['links'] as $link) {
            if ($link['rel'] == "approval_url") {

              // Agreement was created and we have the approval url. Let's store something in the session
              $this->get('session')->set('current_billed_community', $community->getId());
              $this->get('session')->set('current_billed_plan', $plan);

              return $this->redirect($link['href']);
            }
          }
          
          // No approval url ? Weird, but well, manage the case :
          $this->get('session')->getFlashBag()->add(
              'error',
              $this->get('translator')->trans('billing.error.create.agreemeent')
          );

          return $this->redirect($this->generateUrl('s_show_billing', array('uid' => $uid)));

      } else {
        
          throw $this->createNotFoundException($this->get('translator')->trans('community.not.found'));

      }

    }

    /*
     * STEP 4a.
     * User cancel. Back to step 1.
     *
     */
    public function abortBillingAgreementAction(Request $request)
    {
      
      // Sorry to see you aborted
      $this->get('session')->getFlashBag()->add(
          'error',
          $this->get('translator')->trans('billing.aborted')
      );

      return $this->redirect($this->generateUrl('s_show_communities'));

    }

    /*
     * STEP 4b.
     * User agrees, we show him the final confirmation page
     *
     */
    public function confirmBillingAgreementAction(Request $request)
    {
      
      // Retrieve info from the session
      $community = $this->getDoctrine()->getRepository('metaGeneralBundle:Community\Community')->findOneById($this->get('session')->get('current_billed_community'));
      $plan = $this->get('session')->get('current_billed_plan');

      $payment_token = $request->get('token');

      if ($community && $plan){

        if ($payment_token == ""){

          $this->get('session')->getFlashBag()->add(
              'error',
              $this->get('translator')->trans('billing.no.token')
          );

          return $this->redirect($this->generateUrl('s_show_communities'));

        }

        return $this->render('metaSubscriptionBundle::confirm.html.twig', array('payment_token' => $payment_token, 'community' => $community, 'plan' => $plan ));
      
      } else {
        
          throw $this->createNotFoundException($this->get('translator')->trans('community.not.found'));

      }

    }

    /*
     * STEP 5.
     * User clicks pay and we execute the agreement, and store the agreement in the community
     *
     */
    public function executeBillingAgreementAction(Request $request, $payment_token)
    {

      if (!$this->get('form.csrf_provider')->isCsrfTokenValid('executeBillingAgreement', $request->get('token')))
            return new Response($this->get('translator')->trans('invalid.token', array(), 'errors'), 400);

      $community = $this->getDoctrine()->getRepository('metaGeneralBundle:Community\Community')->findOneById($this->get('session')->get('current_billed_community'));
      $plan = $this->get('session')->get('current_billed_plan');

      if ($community && $plan){

          $token = $this->get('paypalHelper')->getToken();
          if ($token === false){
            return $this->render('metaSubscriptionBundle::error.html.twig');
          }

          // Execute the billing agreement
          $agreement = $this->get('paypalHelper')->executeBillingAgreement($token, $payment_token);

          if ($agreement === false) {

            $this->get('session')->getFlashBag()->add(
                'error',
                $this->get('translator')->trans('billing.error.execute')
            );
            
          } else {

            $this->get('session')->getFlashBag()->add(
                'success',
                $this->get('translator')->trans('billing.success')
            );

            $community->setBillingPlan($plan['id']);
            $community->setBillingAgreement($agreement['id']);

            $startDate = new \DateTime('@' . max(time(),$community->getValidUntil()->getTimestamp()));

            $community->setValidUntil($startDate->add(new \DateInterval('P1M')));
            $em = $this->getDoctrine()->getManager();
            $em->flush();

          }

          return $this->redirect($this->generateUrl('s_show_communities'));

      } else {
        
          throw $this->createNotFoundException($this->get('translator')->trans('community.not.found'));

      }

    }


    /*  to REWRITE */
    public function cancelBillingAction(Request $request, $uid)
    {

      if (!$this->get('form.csrf_provider')->isCsrfTokenValid('cancelBilling', $request->get('token')))
            return new Response($this->get('translator')->trans('invalid.token', array(), 'errors'), 400);

      $community = $this->getDoctrine()->getRepository('metaGeneralBundle:Community\Community')->findOneById($this->container->get('uid')->fromUId($uid));

      if ($community){

        $planId = $community->getBillingPlan(); 
        $agreementId = $community->getBillingAgreement();

        // No paying communities ?
        if (is_null($planId) || is_null($agreementId)) {

          $this->get('session')->getFlashBag()->add(
              'error',
              $this->get('translator')->trans('billing.error.no.plan')
          );

        } else {

          $token = $this->get('paypalHelper')->getToken();
          if ($token === false){
            return $this->render('metaSubscriptionBundle::error.html.twig');
          }

          // Cancels the billing agreement
          // Paypal PHP SDK still doesn't support billing agreements (01/09/2014)
          $result = $this->get('paypalHelper')->cancelBillingAgreement($token, $agreementId);

          if ($result === false) {

            $this->get('session')->getFlashBag()->add(
                'error',
                $this->get('translator')->trans('billing.error.cancel')
            );

          } else {

            // Let's remove the agreement and plan from the community object
            $community->setBillingPlan(null);
            $community->setBillingAgreement(null);
            $em = $this->getDoctrine()->getManager();
            $em->flush();

            // We don't touch validity of course...

          }

        }

        return $this->redirect($this->generateUrl('s_show_billing', array('uid' => $this->container->get('uid')->toUId($community->getId()))));

      } else {
        
        throw $this->createNotFoundException($this->get('translator')->trans('community.not.found'));

      }

    }

}
