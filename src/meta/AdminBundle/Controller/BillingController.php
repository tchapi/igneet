<?php

namespace meta\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response;

use PayPal\Auth\OAuthTokenCredential;

class BillingController extends Controller
{

    public function listPlansAction()
    {

        $token = $this->get('paypalHelper')->getToken();

        if ($token === false){
          return $this->render('metaAdminBundle:Billing:error.html.twig');
        }

        // Paypal PHP SDK still doesn't support billing plans (01/09/2014)
        $plans_created = $this->get('paypalHelper')->getBillingPlans($token, 'CREATED');

        if ($plans_created === false) {
          $this->get('session')->getFlashBag()->add(
              'error',
              $this->get('translator')->trans('billing.error.retrieve')
          );
        }

        // Paypal PHP SDK still doesn't support billing plans (01/09/2014)
        $plans_active = $this->get('paypalHelper')->getBillingPlans($token, 'ACTIVE');

        if ($plans_active === false) {
          $this->get('session')->getFlashBag()->add(
              'error',
              $this->get('translator')->trans('billing.error.retrieve')
          );
        }

        // Paypal PHP SDK still doesn't support billing plans (01/09/2014)
        $plans_inactive = $this->get('paypalHelper')->getBillingPlans($token, 'INACTIVE');

        if ($plans_inactive === false) {
          $this->get('session')->getFlashBag()->add(
              'error',
              $this->get('translator')->trans('billing.error.retrieve')
          );
        }

        $plans = array_merge($plans_created, $plans_active, $plans_inactive);

        // We need to count the number of communities that have this plan active
        $communityRepository = $this->getDoctrine()->getRepository('metaGeneralBundle:Community\Community');
        foreach ($plans as $key => $plan) {
          $plans[$key]['count'] = $communityRepository->countCommunitiesUsingBillingPlan($plan['id']);
        }

        return $this->render('metaAdminBundle:Billing:plans.html.twig', array('plans' => $plans));

    }

    public function activatePlanAction($id)
    {

        $token = $this->get('paypalHelper')->getToken();

        if ($token === false){
          return $this->render('metaAdminBundle:Billing:error.html.twig');
        }

        // Paypal PHP SDK still doesn't support billing plans (01/09/2014)
        $result = $this->get('paypalHelper')->activateBillingPlan($token, $id, true);

        if ($result === false) {
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

        $token = $this->get('paypalHelper')->getToken();

        if ($token === false){
          return $this->render('metaAdminBundle:Billing:error.html.twig');
        }

        // Paypal PHP SDK still doesn't support billing plans (01/09/2014)
        $result = $this->get('paypalHelper')->activateBillingPlan($token, $id, false);

        if ($result === false) {
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

    /*
     * Helper to create the form to fill in Billing Plan details or edit them
     */
    private function createBillingPlanForm($defaultData = array(), $full = true)
    {

      $form = $this->createFormBuilder($defaultData)
            ->add('name', 'text', array('label' => 'Name', 'attr' => array( 'placeholder' => "Igneet monthly plan")))
            ->add('description', 'textarea', array('label' => 'Description', 'attr' => array( 'placeholder' => "Describe the plan details")));

      // Once created, these settings CANNOT be changed (the *PATCH* verb will return an error)
      if ($full == true){
        $form->add('type', 'choice', array(
              'choices' => array('fixed' => "Fixed", 'infinite' => "Infinite"), 
              'label' => 'Type'
            ))
            ->add('price', 'text', array('label' => 'Price (€, incl. VAT)', 'attr' => array( 'placeholder' => "10")))
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

                $token = $this->get('paypalHelper')->getToken();

                if ($token === false){
                  return $this->render('metaAdminBundle:Billing:error.html.twig');
                }

                // Paypal PHP SDK still doesn't support billing plans (01/09/2014)
                $result = $this->get('paypalHelper')->createBillingPlan($token, $data);

                if ($result === true) {
              
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
        $token = $this->get('paypalHelper')->getToken();

        if ($token === false){
          return $this->render('metaAdminBundle:Billing:error.html.twig');
        }

        // Paypal PHP SDK still doesn't support billing plans (01/09/2014)
        $plan = $this->get('paypalHelper')->getBillingPlan($token, $id);

        if ($plan === false) {

          $this->get('session')->getFlashBag()->add(
              'error',
              $this->get('translator')->trans('billing.error.retrieve')
          );
          return $this->redirect($this->generateUrl('a_billing_plans')); 

        } else {

          $communityRepository = $this->getDoctrine()->getRepository('metaGeneralBundle:Community\Community');
          $plan['count'] = $communityRepository->countCommunitiesUsingBillingPlan($plan['id']);

          return $this->render('metaAdminBundle:Billing:show.html.twig', array('plan' => $plan));

        }

    }

    /*
     * Create a form for edition of a plan
     */
    public function editAction(Request $request, $id)
    {

        $token = $this->get('paypalHelper')->getToken();

        if ($token === false){
          return $this->render('metaAdminBundle:Billing:error.html.twig');
        }

        // Paypal PHP SDK still doesn't support billing plans (01/09/2014)
        $plan = $this->get('paypalHelper')->getBillingPlan($token, $id);

        if ($plan === false) {

          $this->get('session')->getFlashBag()->add(
              'error',
              $this->get('translator')->trans('billing.error.retrieve')
          );
          return $this->redirect($this->generateUrl('a_billing_plans')); 

        } else {
        
          $form = $this->createBillingPlanForm($plan, false);
        
        }

        if ($request->isMethod('POST')) {

            $form->bind($request);

            if ($form->isValid()) {

                $postData = current($request->request->all());

                // Paypal PHP SDK still doesn't support billing plans (01/09/2014)
                $result = $this->get('paypalHelper')->updateBillingPlan($token, $id, $postData['name'], $postData['description']);

                if ($result !== true) {

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

        return $this->render('metaAdminBundle:Billing:edit.html.twig', array('form' => $form->createView(), 'id' => $plan['id']));

    }

    public function listAgreementsAction($id)
    {

        // TODO  FIXME
        return $this->render('metaAdminBundle:Default:home.html.twig');

    }

}