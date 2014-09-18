<?php

namespace meta\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response;

use PayPal\Auth\OAuthTokenCredential;

class BillingController extends Controller
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
    public function listPlansAction()
    {

        // Get billing plans
        $credential = new OAuthTokenCredential($this->container->getParameter('paypal_clientid'),$this->container->getParameter('paypal_secret'));
        $token = "Bearer " . $credential->getAccessToken($this->getApiMode());

        // Paypal PHP SDK still doesn't support billing plans (01/09/2014)
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL, $this->getApiEndpoint() . '/payments/billing-plans?status=CREATED');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
          'Content-Type: application/json',
          'Authorization: ' . $token
        ));
        $result = curl_exec($ch);
        curl_close($ch);

        if ($result != "") {

          $plans_created = json_decode($result, true);
          $plans_created = $plans_created['plans'];

        } else {

          $this->get('session')->getFlashBag()->add(
              'error',
              $this->get('translator')->trans('billing.error.retrieve')
          );
          $plans_created = null;

        }

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

          $plans_active = json_decode($result, true);
          $plans_active = $plans_active['plans'];

        } else {

          $this->get('session')->getFlashBag()->add(
              'error',
              $this->get('translator')->trans('billing.error.retrieve')
          );
          $plans_active = null;

        }

        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL, $this->getApiEndpoint() . '/payments/billing-plans?status=INACTIVE');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
          'Content-Type: application/json',
          'Authorization: ' . $token
        ));
        $result = curl_exec($ch);
        curl_close($ch);

        if ($result != "") {

          $plans_inactive = json_decode($result, true);
          $plans_inactive = $plans_inactive['plans'];

        } else {

          $this->get('session')->getFlashBag()->add(
              'error',
              $this->get('translator')->trans('billing.error.retrieve')
          );
          $plans_inactive = null;

        }

        $plans = array_merge($plans_created, $plans_active, $plans_inactive);

        $communityRepository = $this->getDoctrine()->getRepository('metaGeneralBundle:Community\Community');
        foreach ($plans as $key => $plan) {
          $plans[$key]['count'] = $communityRepository->countCommunitiesUsingBillingPlan($plan['id']);
        }

        return $this->render('metaAdminBundle:Billing:plans.html.twig', array('plans' => $plans));

    }

    public function listAgreementsAction()
    {

        return $this->render('metaAdminBundle:Default:home.html.twig');

    }

}