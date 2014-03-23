<?php

namespace meta\UserBundle\Tests\Controller;

use meta\UserBundle\Tests\SecuredWebTestCase,
    Symfony\Component\HttpFoundation\Response;

class SecurityControllerTest extends SecuredWebTestCase
{

  public function testLoginPageNotAuthenticated()
  {

    $client = static::createClient();
    $crawler = $client->request('GET', '/app/login');
    
    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

  }

  public function testLoginPageAuthenticated()
  {

    $client = static::createClientWithAuthentication();
    $crawler = $client->request('GET', '/app/login');
    
    $this->assertTrue(
        $client->getResponse()->isRedirect('/app/')
    );

  }

  public function testLogoutPage()
  {

    $client = static::createClientWithAuthentication();
    $crawler = $client->request('GET', '/app/logout');
  
    $this->assertTrue($client->getResponse()->isRedirect());

  }

}
