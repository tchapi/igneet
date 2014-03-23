<?php

namespace meta\IdeaBundle\Tests\Controller;

use meta\UserBundle\Tests\SecuredWebTestCase,
    Symfony\Component\HttpFoundation\Response;

class IdeasControllerTest extends SecuredWebTestCase
{

  public function testIdeasList()
  {

    $client = static::createClientWithAuthentication();
    $crawler = $client->request('GET', '/app/ideas');
    
    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

  }


}
