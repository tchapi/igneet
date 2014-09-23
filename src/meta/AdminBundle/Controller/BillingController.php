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

    public function activatePlanAction($id)
    {

        // Get billing plans
        $credential = new OAuthTokenCredential($this->container->getParameter('paypal_clientid'),$this->container->getParameter('paypal_secret'));
        $token = "Bearer " . $credential->getAccessToken($this->getApiMode());

        // Paypal PHP SDK still doesn't support billing plans (01/09/2014)
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL, $this->getApiEndpoint() . '/payments/billing-plans/' . $id);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
          'Content-Type: application/json',
          'Authorization: ' . $token
        ));
        $data = array(array(
                      "path" => "/",
                      "value" => array(
                            "state" => "ACTIVE"
                        ),
                        "op" => "replace"
          ));
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));   
        $result = curl_exec($ch);
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_status != 200) {

          $this->get('session')->getFlashBag()->add(
              'error',
              $this->get('translator')->trans('billing.error.activate')
          );
        } else {
          $this->get('session')->getFlashBag()->add(
              'success',
              $this->get('translator')->trans('billing.plan.updated')
          ); 
        }

        return $this->redirect($this->generateUrl('a_billing_plans'));
    }

    public function inactivatePlanAction($id)
    {

        // We can only inactivate if there is no user on this plan (of course)
        $communityRepository = $this->getDoctrine()->getRepository('metaGeneralBundle:Community\Community');
        $communityCount = $communityRepository->countCommunitiesUsingBillingPlan($id);
        
        if ($communityCount != 0) {
          $this->get('session')->getFlashBag()->add(
              'error',
              $this->get('translator')->trans('billing.error.in.use')
          );
          return $this->redirect($this->generateUrl('a_billing_plans'));
        }

        // Get billing plans
        $credential = new OAuthTokenCredential($this->container->getParameter('paypal_clientid'),$this->container->getParameter('paypal_secret'));
        $token = "Bearer " . $credential->getAccessToken($this->getApiMode());

        // Paypal PHP SDK still doesn't support billing plans (01/09/2014)
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL, $this->getApiEndpoint() . '/payments/billing-plans/' . $id);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
          'Content-Type: application/json',
          'Authorization: ' . $token
        ));
        $data = array(array(
                      "path" => "/",
                      "value" => array(
                            "state" => "INACTIVE"
                        ),
                        "op" => "replace"
          ));
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));   
        $result = curl_exec($ch);
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_status != 200) {

          $this->get('session')->getFlashBag()->add(
              'error',
              $this->get('translator')->trans('billing.error.inactivate')
          );
        } else {
          $this->get('session')->getFlashBag()->add(
              'success',
              $this->get('translator')->trans('billing.plan.updated')
          ); 
        }

        return $this->redirect($this->generateUrl('a_billing_plans'));
    }

    private function createBillingPlanForm($defaultData = array(), $full = true)
    {

      $form = $this->createFormBuilder($defaultData)
            ->add('name', 'text', array('label' => 'Name', 'attr' => array( 'placeholder' => "Igneet monthly plan")))
            ->add('description', 'textarea', array('label' => 'Description', 'attr' => array( 'placeholder' => "Describe the plan details")))
            ->add('type', 'choice', array(
              'choices' => array('fixed' => "Fixed", 'infinite' => "Infinite"), 
              'label' => 'Type'
            ));

      if ($full == true){
        $form->add('price', 'text', array('label' => 'Price (€, incl. VAT)', 'attr' => array( 'placeholder' => "10")))
            ->add('interval', 'text', array('label' => 'Interval (# of month)', 'attr' => array( 'placeholder' => "1")))
            ->add('cycles', 'text', array('label' => 'Cycles (0: no limit)', 'attr' => array( 'placeholder' => "0")))
            ->add('return_url', 'text', array('label' => 'Return URL'))
            ->add('cancel_url', 'text', array('label' => 'Cancel URL'));

      }
            
      return $form->getForm();

    }

    /*
     * Create a form for a new paypal plan AND process result when POSTed
     */
    public function createAction(Request $request)
    {

        $defaultData = array(
          "interval" => 1,
          "cycles" => 0,
          "type" => "INFINITE",
          "return_url" => "http://igneet.local/app_dev.php/app/secure/billing/confirm",
          "cancel_url" => "http://igneet.local/app_dev.php/app/secure/billing/abort",
        );

        $form = $this->createBillingPlanForm($defaultData);

        if ($request->isMethod('POST')) {

            $form->bind($request);

            if ($form->isValid()) {

                $postData = current($request->request->all());

                // Create the data Array
                $data = array(
                    "name" => $postData['name'],
                    "description" => $postData['description'],
                    "type" => strtoupper($postData['type']),
                    "payment_definitions" => array(
                        array(
                            "name" => "Igneet regular payments",
                            "type" => "REGULAR",
                            "frequency" => "MONTH",
                            "frequency_interval" => intval($postData['interval']),
                            "amount" => array(
                                "value" => intval($postData['price']),
                                "currency" => "EUR"
                            ),
                            "cycles" => intval($postData['cycles']),
                            "charge_models" => array(
                              /*  array(
                                    "type" => "TAX",
                                    "amount" => array(
                                        "value" => "1.2",
                                        "currency" => "EUR"
                                    )
                                )
                              */
                            )
                        )
                    ),
                    "merchant_preferences" => array(
                        "return_url" => $postData['return_url'],
                        "cancel_url" => $postData['cancel_url'],
                        "auto_bill_amount" => "YES",
                        "initial_fail_amount_action" => "CONTINUE",
                        "max_fail_attempts" => "0"
                    )
                  );

                // Get token
                $credential = new OAuthTokenCredential($this->container->getParameter('paypal_clientid'),$this->container->getParameter('paypal_secret'));
                $token = "Bearer " . $credential->getAccessToken($this->getApiMode());

                // Paypal PHP SDK still doesn't support billing plans (01/09/2014)
                $ch = curl_init();
                curl_setopt($ch,CURLOPT_URL, $this->getApiEndpoint() . '/payments/billing-plans');
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                  'Content-Type: application/json',
                  'Authorization: ' . $token
                ));

                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));   
                $result = curl_exec($ch);
                curl_close($ch);

                if ($result['state'] == "CREATED") {
              
                  $this->get('session')->getFlashBag()->add(
                      'success',
                      $this->get('translator')->trans('billing.plan.created')
                  );

                } else {

                  $this->get('session')->getFlashBag()->add(
                      'error',
                      $this->get('translator')->trans('billing.error.create')
                  );
                
                }

                return $this->redirect($this->generateUrl('a_billing_plans'));
           
            } else {
               
               $this->get('session')->getFlashBag()->add(
                    'error',
                    $this->get('translator')->trans('information.not.valid', array(), 'errors')
                );

            }

        }

        return $this->render('metaAdminBundle:Billing:create.html.twig', array('form' => $form->createView()));

    }

    /*
     * Give details about a plan, especially the pricing
     */
    public function showAction(Request $request, $id)
    {
    }

    /*
     * Create a form for edition of a plan
     */
    public function editAction(Request $request, $id)
    {

        $form = $this->createBillingPlanForm(array(), false);

        // Get billing plans
        $credential = new OAuthTokenCredential($this->container->getParameter('paypal_clientid'),$this->container->getParameter('paypal_secret'));
        $token = "Bearer " . $credential->getAccessToken($this->getApiMode());

        // Paypal PHP SDK still doesn't support billing plans (01/09/2014)
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL, $this->getApiEndpoint() . '/payments/billing-plans/' . $id);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
          'Content-Type: application/json',
          'Authorization: ' . $token
        ));   
        $result = curl_exec($ch);
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_status != 200) {

          $this->get('session')->getFlashBag()->add(
              'error',
              $this->get('translator')->trans('billing.error.retrieve')
          );

          return $this->redirect($this->generateUrl('a_billing_plans')); 
        }

        if ($request->isMethod('POST')) {

            $form->bind($request);

            if ($form->isValid()) {

                $postData = current($request->request->all());

                // Paypal PHP SDK still doesn't support billing plans (01/09/2014)
                $ch = curl_init();
                curl_setopt($ch,CURLOPT_URL, $this->getApiEndpoint() . '/payments/billing-plans/' . $id);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                  'Content-Type: application/json',
                  'Authorization: ' . $token
                ));
                $data = array(array(
                              "path" => "/",
                              "value" => array(
                                  "name" => $postData['name'],
                                  "description" => $postData['description'],
                                  "type" => strtoupper($postData['type'])
                                  ),
                                "op" => "replace"
                  ));
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));   
                $result = curl_exec($ch);
                $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($http_status != 200) {

                  $this->get('session')->getFlashBag()->add(
                      'error',
                      $this->get('translator')->trans('billing.error.update')
                  );

                } else {

                  $this->get('session')->getFlashBag()->add(
                      'success',
                      $this->get('translator')->trans('billing.plan.updated')
                  );     
                }

                return $this->redirect($this->generateUrl('a_billing_plans'));
           
            } else {
               
               $this->get('session')->getFlashBag()->add(
                    'error',
                    $this->get('translator')->trans('information.not.valid', array(), 'errors')
                );

            }

        }

        return $this->render('metaAdminBundle:Billing:edit.html.twig', array('form' => $form->createView()));

    }

    public function listAgreementsAction($id)
    {

        return $this->render('metaAdminBundle:Default:home.html.twig');

    }

}