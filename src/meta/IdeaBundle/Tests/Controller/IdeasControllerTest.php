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

  public function testIdeaModifOwner()
  {

    $this->setUp();
    $idea = $this->em->getRepository('metaIdeaBundle:Idea')->findOneByName("test_idea_community_owner");
    $this->tearDown();

    $client = static::createClientWithAuthentication("test"); 

    $client->request(
        'POST',
        '/app/idea/0' . $client->getContainer()->get('uid')->toUId($idea->getId()) . '/edit?token=' . $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('edit'),
        array( "name" => "name", "value" => "test_idea_community_owner_TEST". rand())
    );

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/idea/0' . $client->getContainer()->get('uid')->toUId($idea->getId()) . '/edit?token=' . $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('edit'),
        array( "name" => "name", "value" => "test_idea_community_owner")
    );

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/idea/0' . $client->getContainer()->get('uid')->toUId($idea->getId()) . '/edit?token=' . $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('edit'),
        array( "name" => "about", "value" => "test" . rand())
    );

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/idea/0' . $client->getContainer()->get('uid')->toUId($idea->getId()) . '/edit?token=' . $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('edit'),
        array( "name" => "content", "value" => "test" . rand())
    );

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );
  }

  public function testIdeaModifNotOwner()
  {

    $this->setUp();
    $idea = $this->em->getRepository('metaIdeaBundle:Idea')->findOneByName("test_idea_community_owner");
    $this->tearDown();

    $client = static::createClientWithAuthentication("other_test"); 

    $client->request(
        'POST',
        '/app/idea/0' . $client->getContainer()->get('uid')->toUId($idea->getId()) . '/edit?token=' . $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('edit'),
        array( "name" => "name", "value" => "test_idea_community_owner_TEST". rand())
    );

    $this->assertEquals(
        Response::HTTP_NOT_ACCEPTABLE,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/idea/0' . $client->getContainer()->get('uid')->toUId($idea->getId()) . '/edit?token=' . $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('edit'),
        array( "name" => "about", "value" => "test" . rand())
    );

    $this->assertEquals(
        Response::HTTP_NOT_ACCEPTABLE,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/idea/0' . $client->getContainer()->get('uid')->toUId($idea->getId()) . '/edit?token=' . $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('edit'),
        array( "name" => "content", "value" => "test" . rand())
    );

    $this->assertEquals(
        Response::HTTP_NOT_ACCEPTABLE,
        $client->getResponse()->getStatusCode()
    );
  }

  public function testIdeaModifParticipant()
  {

    $this->setUp();
    $idea = $this->em->getRepository('metaIdeaBundle:Idea')->findOneByName("test_idea_community_participant");
    $this->tearDown();

    $client = static::createClientWithAuthentication("test"); 

    $client->request(
        'POST',
        '/app/idea/0' . $client->getContainer()->get('uid')->toUId($idea->getId()) . '/edit?token=' . $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('edit'),
        array( "name" => "name", "value" => "test_idea_community_participant_TEST". rand())
    );

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/idea/0' . $client->getContainer()->get('uid')->toUId($idea->getId()) . '/edit?token=' . $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('edit'),
        array( "name" => "name", "value" => "test_idea_community_participant")
    );

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/idea/0' . $client->getContainer()->get('uid')->toUId($idea->getId()) . '/edit?token=' . $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('edit'),
        array( "name" => "about", "value" => "test" . rand())
    );

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/idea/0' . $client->getContainer()->get('uid')->toUId($idea->getId()) . '/edit?token=' . $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('edit'),
        array( "name" => "content", "value" => "test" . rand())
    );

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );
  }

  public function testIdeaModifNotIn()
  {

    $this->setUp();
    $idea = $this->em->getRepository('metaIdeaBundle:Idea')->findOneByName("test_idea_community_not_in");
    $this->tearDown();

    $client = static::createClientWithAuthentication("test"); 

    $client->request(
        'POST',
        '/app/idea/0' . $client->getContainer()->get('uid')->toUId($idea->getId()) . '/edit?token=' . $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('edit'),
        array( "name" => "name", "value" => "test_idea_community_not_in_TEST". rand())
    );

    $this->assertEquals(
        Response::HTTP_NOT_ACCEPTABLE,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/idea/0' . $client->getContainer()->get('uid')->toUId($idea->getId()) . '/edit?token=' . $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('edit'),
        array( "name" => "about", "value" => "test" . rand())
    );

    $this->assertEquals(
        Response::HTTP_NOT_ACCEPTABLE,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/idea/0' . $client->getContainer()->get('uid')->toUId($idea->getId()) . '/edit?token=' . $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('edit'),
        array( "name" => "content", "value" => "test" . rand())
    );

    $this->assertEquals(
        Response::HTTP_NOT_ACCEPTABLE,
        $client->getResponse()->getStatusCode()
    );
  }

  public function testIdeaWatch()
  {

    $this->setUp();
    $idea = $this->em->getRepository('metaIdeaBundle:Idea')->findOneByName("test_idea_community_owner");
    $this->tearDown();

    $client = static::createClientWithAuthentication("test");

    $client->request(
        'POST',
        '/app/idea/0' . $client->getContainer()->get('uid')->toUId($idea->getId()) . '/watch?token=' . $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('watch')
    );

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/idea/0' . $client->getContainer()->get('uid')->toUId($idea->getId()) . '/unwatch?token=' . $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('unwatch')
    );

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/idea/0' . $client->getContainer()->get('uid')->toUId($idea->getId()) . '/unwatch?token=' . $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('unwatch')
    );

    $this->assertEquals(
        Response::HTTP_NOT_ACCEPTABLE,
        $client->getResponse()->getStatusCode()
    );
  }

  public function testIdeaCommentOwner()
  {

    $this->setUp();
    $idea = $this->em->getRepository('metaIdeaBundle:Idea')->findOneByName("test_idea_community_owner");
    $this->tearDown();

    $client = static::createClientWithAuthentication("test");
    $comment = "comment" . mt_rand() . " - " . mt_rand();

    $client->request(
        'POST',
        '/app/idea/0' . $client->getContainer()->get('uid')->toUId($idea->getId()) . '/comment?token=' . $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('comment'),
        array( "comment" => $comment )
    );

    $crawler = $client->request(
        'GET',
        '/app/idea/0' . $client->getContainer()->get('uid')->toUId($idea->getId())
    );
    $client->reload();

    $this->assertCount(1, $crawler->filter('p:contains("'.$comment.'")'));

  }

  public function testIdeaCommentParticipant()
  {

    $this->setUp();
    $idea = $this->em->getRepository('metaIdeaBundle:Idea')->findOneByName("test_idea_community_participant");
    $this->tearDown();

    $client = static::createClientWithAuthentication("test");
    $comment = "comment" . mt_rand() . " - " . mt_rand();

    $client->request(
        'POST',
        '/app/idea/0' . $client->getContainer()->get('uid')->toUId($idea->getId()) . '/comment?token=' . $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('comment'),
        array( "comment" => $comment )
    );

    $crawler = $client->request(
        'GET',
        '/app/idea/0' . $client->getContainer()->get('uid')->toUId($idea->getId())
    );
    $client->reload();

    $this->assertCount(1, $crawler->filter('p:contains("'.$comment.'")'));

  }

  public function testIdeaCommentNotIn()
  {

    $this->setUp();
    $idea = $this->em->getRepository('metaIdeaBundle:Idea')->findOneByName("test_idea_community_not_in");
    $this->tearDown();

    $client = static::createClientWithAuthentication("test");
    $comment = "comment" . mt_rand() . " - " . mt_rand();

    $client->request(
        'POST',
        '/app/idea/0' . $client->getContainer()->get('uid')->toUId($idea->getId()) . '/comment?token=' . $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('comment'),
        array( "comment" => $comment )
    );

    $crawler = $client->request(
        'GET',
        '/app/idea/0' . $client->getContainer()->get('uid')->toUId($idea->getId())
    );
    $client->reload();

    $this->assertCount(1, $crawler->filter('p:contains("'.$comment.'")'));

  }

  public function testIdeaCommentOut()
  {

    $this->setUp();
    $idea = $this->em->getRepository('metaIdeaBundle:Idea')->findOneByName("test_out_idea");
    $this->tearDown();

    $client = static::createClientWithAuthentication("test");
    $comment = "comment" . mt_rand() . " - " . mt_rand();

    $client->request(
        'POST',
        '/app/idea/0' . $client->getContainer()->get('uid')->toUId($idea->getId()) . '/comment?token=' . $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('comment'),
        array( "comment" => $comment )
    );

    $this->assertEquals(
        Response::HTTP_NOT_FOUND,
        $client->getResponse()->getStatusCode()
    );
    
    $crawler = $client->request(
        'GET',
        '/app/idea/0' . $client->getContainer()->get('uid')->toUId($idea->getId())
    );
    $client->reload();

    $this->assertCount(0, $crawler->filter('p:contains("'.$comment.'")'));

  }

}
