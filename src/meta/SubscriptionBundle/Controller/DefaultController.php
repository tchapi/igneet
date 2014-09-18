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

    private function getApiEndpoint(){

      if ($this->container->getParameter('paypal_sandbox') === false) {
        return "https://api.paypal.com/v1";
      } else {
        return "https://api.sandbox.paypal.com/v1";
      }

    }

    private function getApiMode(){

      if ($this->container->getParameter('paypal_sandbox') === false) {
        return array();
      } else {
        return array('mode' => 'sandbox');
      }

    }


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

          // Get Customer billing agreement
          $credential = new OAuthTokenCredential($this->container->getParameter('paypal_clientid'),$this->container->getParameter('paypal_secret'));
          $token = "Bearer " . $credential->getAccessToken($this->getApiMode());

          // Paypal PHP SDK still doesn't support billing plans/agreements (01/09/2014)
          $ch = curl_init();
          curl_setopt($ch,CURLOPT_URL, $this->getApiEndpoint() . '/payments/billing-agreements/' . $agreementId);
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
          curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Authorization: ' . $token
          ));
          $result = curl_exec($ch);
          curl_close($ch);

          if ($result != "") {

            $agreement = json_decode($result, true);

            // If agreement not active, tell user his community will be invalid soon
            if (strtoupper($agreement['state']) != "ACTIVE") {
              $agreement = null;
            }

          } else {

            $this->get('session')->getFlashBag()->add(
                'error',
                $this->get('translator')->trans('billing.error.retrieve')
            );
            $agreement = null;

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

          // Get billing plans
          $credential = new OAuthTokenCredential($this->container->getParameter('paypal_clientid'),$this->container->getParameter('paypal_secret'));
          $token = "Bearer " . $credential->getAccessToken($this->getApiMode());

          // Paypal PHP SDK still doesn't support billing plans (01/09/2014)
          $ch = curl_init();
          curl_setopt($ch,CURLOPT_URL, $this->getApiEndpoint() . '/payments/billing-plans?status=ACTIVE');
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
          curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Authorization: ' . $token
          ));
          $result = curl_exec($ch);
          curl_close($ch);

          if ($result != "") {

            $plans = json_decode($result, true);
            $plans = $plans['plans'];

          } else {

            $this->get('session')->getFlashBag()->add(
                'error',
                $this->get('translator')->trans('billing.error.retrieve')
            );
            $plans = null;

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
    public function createBillingAgreementAction(Request $request, $uid, $plan)
    {
      if (!$this->get('form.csrf_provider')->isCsrfTokenValid('createBillingAgreement', $request->get('token')))
            return new Response($this->get('translator')->trans('invalid.token', array(), 'errors'), 400);

      $community = $this->getDoctrine()->getRepository('metaGeneralBundle:Community\Community')->findOneById($this->container->get('uid')->fromUId($uid));

      if ($community){

          // Get billing plans
          $credential = new OAuthTokenCredential($this->container->getParameter('paypal_clientid'),$this->container->getParameter('paypal_secret'));
          $token = "Bearer " . $credential->getAccessToken($this->getApiMode());

          // Paypal PHP SDK still doesn't support billing agreements (01/09/2014)
          // Creates billing agreement
          $ch = curl_init();
          curl_setopt($ch,CURLOPT_URL, $this->getApiEndpoint() . '/payments/billing-agreements');
          curl_setopt($ch, CURLOPT_POST, TRUE);
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
          curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Authorization: ' . $token
          ));
          $data = array(
                  "name" => "Igneet Mensuel",
                  "description" => "Igneet Mensuel | plan ID:" . $plan,
                  "start_date" => date('Y-m-d\TH:i:s\Z'),
                  "plan" => array(
                      "id" => $plan
                  ),
                  "payer" => array(
                      "payment_method" => "paypal"
                  )
          );
          curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));   
          $result = curl_exec($ch);
          $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
          curl_close($ch);

          // https://developer.paypal.com/docs/api/#create-an-agreement
          if ($http_status != 201) { // If status is not : "CREATED"
      
            $this->get('session')->getFlashBag()->add(
                'error',
                $this->get('translator')->trans('billing.error.create.agreemeent')
            );

            return $this->redirect($this->generateUrl('s_show_billing', array('uid' => $uid)));

          }

          $response = json_decode($result, true);

          foreach ($response['links'] as $link) {
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

          // Get Credentials
          $credential = new OAuthTokenCredential($this->container->getParameter('paypal_clientid'),$this->container->getParameter('paypal_secret'));
          $token = "Bearer " . $credential->getAccessToken($this->getApiMode());

          // Execute the billing agreement
          $ch = curl_init();
          curl_setopt($ch,CURLOPT_URL, $this->getApiEndpoint() . '/payments/billing-agreements/' . $payment_token . "/agreement-execute");
          curl_setopt($ch, CURLOPT_POST, TRUE);
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
          curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Authorization: ' . $token
          ));
          $data = array();
          curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));   
          $result = curl_exec($ch);
          curl_close($ch);

          if ($result != "") {

            $agreement = json_decode($result, true);
            $this->get('session')->getFlashBag()->add(
                'success',
                $this->get('translator')->trans('billing.success')
            );

            $community->setBillingPlan($plan);
            $community->setBillingAgreement($agreement['id']);
            $community->setValidUntil(new \DateTime('now + 1 month'));
            $em = $this->getDoctrine()->getManager();
            $em->flush();

          } else {

            $this->get('session')->getFlashBag()->add(
                'error',
                $this->get('translator')->trans('billing.error.execute')
            );
            $agreement = null;

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

        $planId = $community->getBillingPlan(); //"P-0KM92061C8742900WG75XLBA"; //
        $agreementId = $community->getBillingAgreement(); // I-95J8U2SK6LW6

        // No paying communities ?
        if (is_null($planId) || is_null($agreementId)) {

          $this->get('session')->getFlashBag()->add(
              'error',
              $this->get('translator')->trans('billing.error.no.plan')
          );

        } else {

          // Get Customer billing plan
          $credential = new OAuthTokenCredential($this->container->getParameter('paypal_clientid'),$this->container->getParameter('paypal_secret'));
          $token = "Bearer " . $credential->getAccessToken($this->getApiMode());

          // Cancels the billing agreement
          // Paypal PHP SDK still doesn't support billing agreements (01/09/2014)
          $ch = curl_init();
          curl_setopt($ch, CURLOPT_URL, $this->getApiEndpoint() . '/payments/billing-agreements/' . $agreementId . "/cancel");
          curl_setopt($ch, CURLOPT_POST, TRUE);
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
          curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Authorization: ' . $token
          ));
          $data = array(
                  "note" => "Canceling upon user request on " . date('c') . "."
              );
          curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));   
          $result = curl_exec($ch);
          $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
          curl_close($ch);

          if ($http_status != 204) { //https://developer.paypal.com/docs/api/#cancel-an-agreement

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
