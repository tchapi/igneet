<?php

namespace meta\AdminBundle\Tests\Controller;

use meta\UserBundle\Tests\SecuredWebTestCase,
    Symfony\Component\HttpFoundation\Response;

class AdminControllerTest extends SecuredWebTestCase
{
  
  public function testHomePage()
  {

    $client = static::createClient();
    $crawler = $client->request('GET', '/admin/');

    $this->assertTrue(
        $client->getResponse()->isRedirect()
    );

    $client = static::createClientWithAuthentication();
    $crawler = $client->request('GET', '/admin/');
    
    $this->assertEquals(
        Response::HTTP_FORBIDDEN,
        $client->getResponse()->getStatusCode()
    );

  }

}

