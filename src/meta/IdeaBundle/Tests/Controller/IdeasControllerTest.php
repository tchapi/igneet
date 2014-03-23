<?php

namespace meta\IdeaBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase,
    Symfony\Component\HttpFoundation\Response;

class IdeasControllerTest extends WebTestCase
{

  public function testIdeasList()
  {

    $client = static::createClient();
    $crawler = $client->request('GET', '/app/ideas');
    
    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

  }


}
