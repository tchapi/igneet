<?php

namespace meta\IdeaBundle\Tests\Controller;

use meta\UserBundle\Tests\SecuredWebTestCase,
    Symfony\Component\HttpFoundation\Response;

class IdeasControllerTest extends SecuredWebTestCase
{

  public function testIdeasList()
  {

    $this->setUp();
    $community = $this->em->getRepository('metaGeneralBundle:Community\Community')->findOneByName("test_in");
    $this->tearDown();

    $client = static::createClientWithAuthentication("test"); // test is in test_in 
    $crawler = $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('switchCommunity')));

    $crawler = $client->request('GET', '/app/ideas');

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

  }

  public function testIdeasUnauthorizedAsGuest()
  {

    $this->setUp();
    $community = $this->em->getRepository('metaGeneralBundle:Community\Community')->findOneByName("test_guest");
    $this->tearDown();

    $client = static::createClientWithAuthentication("test"); // test is in test_guest but guest 
    $crawler = $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('switchCommunity')));

    $crawler = $client->request('GET', '/app/ideas');
    
    $this->assertEquals(
        Response::HTTP_FORBIDDEN,
        $client->getResponse()->getStatusCode()
    );

  }


}
