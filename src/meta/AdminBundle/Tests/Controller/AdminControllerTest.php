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

    $client = static::createClientWithAuthentication('test_admin');
    $crawler = $client->request('GET', '/admin/');
    
    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

  }

  public function testPages()
  {
    $client = static::createClientWithAuthentication('test_admin');
    $crawler = $client->request('GET', '/admin/stats');
    
    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $client = static::createClientWithAuthentication('test_admin');
    $crawler = $client->request('GET', '/admin/stats/users');
    
    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $client = static::createClientWithAuthentication('test_admin');
    $crawler = $client->request('GET', '/admin/announcements');
    
    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $client = static::createClientWithAuthentication('test_admin');
    $crawler = $client->request('GET', '/admin/communities');
    
    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $client = static::createClientWithAuthentication('test_admin');
    $crawler = $client->request('GET', '/admin/files/prune');
    
    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );
  
  }

}

