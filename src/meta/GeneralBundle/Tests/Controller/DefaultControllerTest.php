<?php

namespace meta\UserBundle\Tests\Controller;

use meta\UserBundle\Tests\SecuredWebTestCase,
    Symfony\Component\HttpFoundation\Response;

class DefaultControllerTest extends SecuredWebTestCase
{

  public function testCredits()
  {
    
    $client = static::createClientWithAuthentication("test");
    $crawler = $client->request('GET', '/app/credits');

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $this->assertCount(12, $crawler->filter('h2'));
    $this->assertCount(1, $crawler->filter('h3'));

  }

  public function testCreditsNotLogged()
  {
    
    $client = static::createClient();
    $crawler = $client->request('GET', '/app/credits');

    $this->assertTrue(
        $client->getResponse()->isRedirect()
    );

  }

  public function testTerms()
  {
    
    $client = static::createClientWithAuthentication("test");
    $crawler = $client->request('GET', '/terms');

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $this->assertCount(1, $crawler->filter('h1'));
    $this->assertCount(1, $crawler->filter('p:contains("Terms of use and Privacy")'));
    $this->assertCount(1, $crawler->filter('p:contains("utilisation et protection des données")'));

  }

  public function testTermsNotLogged()
  {
    
    $client = static::createClient();
    $crawler = $client->request('GET', '/terms');

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $this->assertCount(1, $crawler->filter('h1'));
    $this->assertCount(1, $crawler->filter('p:contains("Terms of use and Privacy")'));
    $this->assertCount(1, $crawler->filter('p:contains("utilisation et protection des données")'));

  }
}
