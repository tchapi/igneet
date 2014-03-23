<?php

namespace meta\ProjectBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase,
    Symfony\Component\HttpFoundation\Response;

class BaseControllerTest extends WebTestCase
{

  public function testProjectsList()
  {

    $client = static::createClient();
    $crawler = $client->request('GET', '/app/projects');
    
    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

  }


}
