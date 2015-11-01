<?php

namespace meta\UserBundle\Tests\Controller;

use meta\UserBundle\Tests\SecuredWebTestCase,
    Symfony\Component\HttpFoundation\Response;

class SearchControllerTest extends SecuredWebTestCase
{

  public function testSearchIsAvailable()
  {
    
    $client = static::createClientWithAuthentication();
    $crawler = $client->request('GET', '/app/');
    
    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $this->assertCount(1, $crawler->filter('div.search'));

    $crawler = $client->request('GET', '/app/search');

    $this->assertTrue($client->getResponse()->isRedirect());

  }

  public function testSearchResults()
  {
  }

  public function testSearchNoResults()
  {
  }

}
