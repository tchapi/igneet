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

  public function testIdeaInPrivateSpace()
  {

    $this->setUp();
    $idea = $this->em->getRepository('metaIdeaBundle:Idea')->findOneByName("test_idea_private_space");
    $this->tearDown();

    $client = static::createClientWithAuthentication("test"); // test is in test_in 
    $crawler = $client->request('GET', '/app/switch/privatespace', array('token' => $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('switchCommunity')));

    $crawler = $client->request('GET', '/app/idea/0' . $client->getContainer()->get('uid')->toUId($idea->getId()));

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $this->assertCount(1, $crawler->filter('h2:contains("test_idea_private_space")'));

  }

  public function testIdeaInPrivateSpaceSwitch()
  {

    $this->setUp();
    $community = $this->em->getRepository('metaGeneralBundle:Community\Community')->findOneByName("test_in");
    $idea = $this->em->getRepository('metaIdeaBundle:Idea')->findOneByName("test_idea_private_space");
    $this->tearDown();

    $client = static::createClientWithAuthentication("test"); // test is in test_in 
    $crawler = $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('switchCommunity')));

    $crawler = $client->request('GET', '/app/idea/0' . $client->getContainer()->get('uid')->toUId($idea->getId()));

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $this->assertCount(1, $crawler->filter('h2:contains("test_idea_private_space")'));

  }

  public function testIdeaInOtherPrivateSpace()
  {

    $this->setUp();
    $idea = $this->em->getRepository('metaIdeaBundle:Idea')->findOneByName("test_idea_private_space");
    $this->tearDown();

    $client = static::createClientWithAuthentication("other_test"); // test is in test_in 
    $crawler = $client->request('GET', '/app/switch/privatespace', array('token' => $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('switchCommunity')));

    $crawler = $client->request('GET', '/app/idea/0' . $client->getContainer()->get('uid')->toUId($idea->getId()));

    $this->assertEquals(
        Response::HTTP_NOT_FOUND,
        $client->getResponse()->getStatusCode()
    );


  }

  public function testIdeaInCommunity()
  {

    $this->setUp();
    $idea = $this->em->getRepository('metaIdeaBundle:Idea')->findOneByName("test_idea_community_owner");
    $community = $this->em->getRepository('metaGeneralBundle:Community\Community')->findOneByName("test_in");
    $this->tearDown();

    $client = static::createClientWithAuthentication("test"); // test is in test_in 
    $crawler = $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('switchCommunity')));

    $crawler = $client->request('GET', '/app/idea/0' . $client->getContainer()->get('uid')->toUId($idea->getId()));

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $this->assertCount(1, $crawler->filter('h2:contains("test_idea_community_owner")'));

  }

  public function testIdeaInCommunityOther()
  {

    $this->setUp();
    $idea = $this->em->getRepository('metaIdeaBundle:Idea')->findOneByName("test_idea_community_owner");
    $community = $this->em->getRepository('metaGeneralBundle:Community\Community')->findOneByName("test_in");
    $this->tearDown();

    $client = static::createClientWithAuthentication("other_test");
    $crawler = $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('switchCommunity')));

    $crawler = $client->request('GET', '/app/idea/0' . $client->getContainer()->get('uid')->toUId($idea->getId()));

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $this->assertCount(1, $crawler->filter('h2:contains("test_idea_community_owner")'));

  }

  public function testIdeaInCommunityParticipant()
  {

    $this->setUp();
    $idea = $this->em->getRepository('metaIdeaBundle:Idea')->findOneByName("test_idea_community_participant");
    $community = $this->em->getRepository('metaGeneralBundle:Community\Community')->findOneByName("test_in");
    $this->tearDown();

    $client = static::createClientWithAuthentication("test"); // test is in test_in 
    $crawler = $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('switchCommunity')));

    $crawler = $client->request('GET', '/app/idea/0' . $client->getContainer()->get('uid')->toUId($idea->getId()));

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $this->assertCount(1, $crawler->filter('h2:contains("test_idea_community_participant")'));

  }

  public function testIdeaInCommunityParticipantOther()
  {

    $this->setUp();
    $idea = $this->em->getRepository('metaIdeaBundle:Idea')->findOneByName("test_idea_community_participant");
    $community = $this->em->getRepository('metaGeneralBundle:Community\Community')->findOneByName("test_in");
    $this->tearDown();

    $client = static::createClientWithAuthentication("other_test");
    $crawler = $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('switchCommunity')));

    $crawler = $client->request('GET', '/app/idea/0' . $client->getContainer()->get('uid')->toUId($idea->getId()));

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $this->assertCount(1, $crawler->filter('h2:contains("test_idea_community_participant")'));

  }

  public function testIdeaInCommunityNotIn()
  {

    $this->setUp();
    $idea = $this->em->getRepository('metaIdeaBundle:Idea')->findOneByName("test_idea_community_not_in");
    $community = $this->em->getRepository('metaGeneralBundle:Community\Community')->findOneByName("test_in");
    $this->tearDown();

    $client = static::createClientWithAuthentication("test");
    $crawler = $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('switchCommunity')));

    $crawler = $client->request('GET', '/app/idea/0' . $client->getContainer()->get('uid')->toUId($idea->getId()));

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $this->assertCount(1, $crawler->filter('h2:contains("test_idea_community_not_in")'));

  }

  public function testIdeaOutCommunity()
  {

    $this->setUp();
    $idea = $this->em->getRepository('metaIdeaBundle:Idea')->findOneByName("test_out_idea");
    $this->tearDown();

    $client = static::createClientWithAuthentication("test"); // test is in test_in 
    $crawler = $client->request('GET', '/app/switch/privatespace', array('token' => $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('switchCommunity')));

    $crawler = $client->request('GET', '/app/idea/0' . $client->getContainer()->get('uid')->toUId($idea->getId()));

    $this->assertEquals(
        Response::HTTP_NOT_FOUND,
        $client->getResponse()->getStatusCode()
    );

  }

  public function testIdeaInCommunityGuest()
  {

    $this->setUp();
    $idea = $this->em->getRepository('metaIdeaBundle:Idea')->findOneByName("test_guest_idea");
    $community = $this->em->getRepository('metaGeneralBundle:Community\Community')->findOneByName("test_guest");
    $this->tearDown();

    $client = static::createClientWithAuthentication("test");
    $crawler = $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('switchCommunity')));

    $crawler = $client->request('GET', '/app/idea/0' . $client->getContainer()->get('uid')->toUId($idea->getId()));

    $this->assertEquals(
        Response::HTTP_NOT_FOUND,
        $client->getResponse()->getStatusCode()
    );

  }

  public function testIdeaInCommunityOtherGuest()
  {

    $this->setUp();
    $idea = $this->em->getRepository('metaIdeaBundle:Idea')->findOneByName("test_guest_idea");
    $community = $this->em->getRepository('metaGeneralBundle:Community\Community')->findOneByName("test_guest");
    $this->tearDown();

    $client = static::createClientWithAuthentication("other_test");
    $crawler = $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('switchCommunity')));

    $crawler = $client->request('GET', '/app/idea/0' . $client->getContainer()->get('uid')->toUId($idea->getId()));

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $this->assertCount(1, $crawler->filter('h2:contains("test_guest_idea")'));

  }
}
