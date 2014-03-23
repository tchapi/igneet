<?php

namespace meta\ProjectBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase,
    Symfony\Component\HttpFoundation\Response;

class DefaultControllerTest extends WebTestCase
{

  public function testHome()
  {

    $client = static::createClient();
    $crawler = $client->request('GET', '/');
    
    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $this->assertCount(
        1,
        $crawler->filter('h1')
    );

    $this->assertGreaterThan(
        5,
        $crawler->filter('h2')->count()
    );

    $this->assertGreaterThan(
        1,
        $crawler->filter('h3')->count()
    );

    $this->assertCount(
        1,
        $crawler->filter('div.copyright')
    );
  }

  public function testTerms()
  {

    $client = static::createClient();
    $crawler = $client->request('GET', '/terms');
    
    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $this->assertCount(
        1,
        $crawler->filter('h1')
    );

    $this->assertGreaterThan(
        5,
        $crawler->filter('h2')->count()
    );

    $this->assertCount(
        1,
        $crawler->filter('div.copyright')
    );
  }

  public function testContact()
  {

    $client = static::createClient();
    $crawler = $client->request('GET', '/contact');
    
    $this->assertTrue($client->getResponse()->isNotFound());

  }

  public function test404()
  {

    $client = static::createClient();
    $crawler = $client->request('GET', '/rien');
    
    $this->assertTrue($client->getResponse()->isNotFound());

  }

}
