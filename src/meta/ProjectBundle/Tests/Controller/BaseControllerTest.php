<?php

namespace meta\ProjectBundle\Tests\Controller;

use meta\UserBundle\Tests\SecuredWebTestCase,
    Symfony\Component\HttpFoundation\Response;

class BaseControllerTest extends SecuredWebTestCase
{

  public function testProjectsList()
  {

    $client = static::createClientWithAuthentication();
    $crawler = $client->request('GET', '/app/projects');
    
    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

  }


}
