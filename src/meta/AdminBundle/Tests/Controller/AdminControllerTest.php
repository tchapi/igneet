<?php

namespace meta\AdminBundle\Tests\Controller;

use meta\UserBundle\Tests\SecuredWebTestCase,
    Symfony\Component\HttpFoundation\Response;

class AdminControllerTest extends SecuredWebTestCase
{
  
  public function testHomePage()
  {

    $client = static::createClientWithAuthentication();
    $crawler = $client->request('GET', '/admin/');
    
    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

  }

}

