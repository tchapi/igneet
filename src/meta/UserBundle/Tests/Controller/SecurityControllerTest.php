<?php

namespace meta\UserBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase,
    Symfony\Component\HttpFoundation\Response;

class SecurityControllerTest extends WebTestCase
{

  public function testLoginPage()
  {

    $client = static::createClient();
    $crawler = $client->request('GET', '/app/login');
    
    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

  }

  public function testLogoutPage()
  {

    $client = static::createClient();
    $crawler = $client->request('GET', '/app/logout');
  
    $this->assertTrue($client->getResponse()->isRedirect());

  }

}
