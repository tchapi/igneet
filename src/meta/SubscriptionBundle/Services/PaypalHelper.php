<?php

namespace meta\SubscriptionBundle\Services;

use PayPal\Auth\OAuthTokenCredential;

class PaypalHelper {

    private $sandbox, $clientid, $secret;

    public function __construct($sandbox, $clientid, $secret)
    {
        $this->sandbox = $sandbox;
        $this->clientid = $clientid;
        $this->secret = $secret;
    }

    private function getApiEndpoint()
    {

      if ($this->sandbox === false) {
        return "https://api.paypal.com/v1";
      } else {
        return "https://api.sandbox.paypal.com/v1";
      }

    }

    private function getApiMode()
    {

      if ($this->sandbox === false) {
        return array();
      } else {
        return array('mode' => 'sandbox');
      }

    }

    public function getToken()
    {

        // Get billing plans
        $credential = new OAuthTokenCredential($this->clientid,$this->secret);
        try{
          return ("Bearer " . $credential->getAccessToken($this->getApiMode()));
        } catch(Exception $e) {
          return false;
        }

    }

    public function getBillingPlans($token, $status)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->getApiEndpoint() . '/payments/billing-plans?status=' . $status);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
          'Content-Type: application/json',
          'Authorization: ' . $token
        ));
        $result = curl_exec($ch);
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($result != "" && $http_status == 200) {

          $plans = json_decode($result, true);
          $plans = $plans['plans'];

        } else {

          $plans = null;

        }

        return $plans;
    }

    public function getBillingPlan($token, $id)
    {
       
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

        if ($result != "" && $http_status == 200) {

          $plan = json_decode($result, true);

        } else {

          $plan = null;

        }

        return $plan;
    }

    public function createBillingPlan($token, $data)
    {

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
      $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      curl_close($ch);

      if ($result['state'] == "CREATED" && $http_status == 200) {

          return true;

      } else {

          return false;

      }

    }

    public function activateBillingPlan($token, $id, $activate = true)
    {
      
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
                            "state" => ( ($activate?"":"IN") . "ACTIVE" )
                        ),
                        "op" => "replace"
          ));
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        $result = curl_exec($ch);   
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ($http_status === 200);

    }

    public function updateBillingPlan($token, $id, $name, $description)
    {

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
                      "value" => array(),
                        "op" => "replace"
          ));

        if ($name != "" and $name != null) { $data[0]['value']['name'] = $name; }
        if ($description != "" and $description != null) { $data[0]['value']['description'] = $description; }
        
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data)); 
        $result = curl_exec($ch);  
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ($http_status === 200);
    }
}