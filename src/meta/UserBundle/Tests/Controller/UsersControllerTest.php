<?php

namespace meta\UserBundle\Tests\Controller;

use meta\UserBundle\Tests\SecuredWebTestCase,
    Symfony\Component\HttpFoundation\Response;

class UsersControllerTest extends SecuredWebTestCase
{

  public function testSkillsList()
  {
    
    $client = static::createClientWithAuthentication();
    $crawler = $client->request('GET', '/app/skills', array(), array(), array(
      'HTTP_X-Requested-With' => 'XMLHttpRequest',
    ));

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $skills = json_decode($client->getResponse()->getContent(), true);

    $this->assertTrue(
        is_array($skills)
    );

    $this->assertGreaterThan(
        4,
        count($skills)
    );

  }

  public function testListPrivateSpace()
  {
    
    $client = static::createClientWithAuthentication();
    $client->request('GET', '/app/switch/privatespace', array('token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('switchCommunity')->getValue()));
    $crawler = $client->request('GET', '/app/people');

    // No users in private space
    $this->assertEquals(
        Response::HTTP_NOT_FOUND,
        $client->getResponse()->getStatusCode()
    );

  }

  public function testListCommunityUserIsIn()
  {

    $this->setUp();
    $community = $this->em->getRepository('metaGeneralBundle:Community\Community')->findOneByName("test_in");
    $this->tearDown();

    $client = static::createClientWithAuthentication("test"); // test is in test_in 

    $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('switchCommunity')->getValue()));
    $crawler = $client->request('GET', '/app/people');

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $this->assertGreaterThan(
        1,
        count($crawler->filter('.wrapper.list table tr'))
    );

  }

  public function testUsersListMore()
  {
    $this->setUp();
    $community = $this->em->getRepository('metaGeneralBundle:Community\Community')->findOneByName("test_in");
    $this->tearDown();

    $client = static::createClientWithAuthentication("test"); // test is in test_in 

    $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('switchCommunity')->getValue()));
    $crawler = $client->request('POST', '/app/people', array(), array(), array(
      'HTTP_X-Requested-With' => 'XMLHttpRequest',
    ), "page=2&full=false");

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $this->assertSame('application/json', $client->getResponse()->headers->get('Content-Type')); // Test if Content-Type is valid application/json
    $this->assertTrue(is_array(json_decode($client->getResponse()->getContent(), true)));
  }

  public function testUsersListMoreFull()
  {
    $this->setUp();
    $community = $this->em->getRepository('metaGeneralBundle:Community\Community')->findOneByName("test_in");
    $this->tearDown();

    $client = static::createClientWithAuthentication("test"); // test is in test_in 

    $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('switchCommunity')->getValue()));
    $crawler = $client->request('POST', '/app/people', array(), array(), array(
      'HTTP_X-Requested-With' => 'XMLHttpRequest',
    ), "page=2&full=true");

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $this->assertSame('application/json', $client->getResponse()->headers->get('Content-Type')); // Test if Content-Type is valid application/json
    $this->assertTrue(is_array(json_decode($client->getResponse()->getContent(), true)));
  }

  public function testUsersSortUrls()
  {

    $this->setUp();
    $community = $this->em->getRepository('metaGeneralBundle:Community\Community')->findOneByName("test_in");
    $this->tearDown();

    $client = static::createClientWithAuthentication("test"); // test is in test_in 

    $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('switchCommunity')->getValue()));
    $crawler = $client->request('GET', '/app/people/1/update');

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $this->assertGreaterThan(
        1,
        count($crawler->filter('.wrapper.list table tr'))
    );

    $crawler = $client->request('GET', '/app/people/1/active');

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $this->assertGreaterThan(
        1,
        count($crawler->filter('.wrapper.list table tr'))
    );

    $crawler = $client->request('GET', '/app/people/1/alpha');

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $this->assertGreaterThan(
        1,
        count($crawler->filter('.wrapper.list table tr'))
    );
  }

  public function testSettingsPage()
  {

    $client = static::createClientWithAuthentication();
    $crawler = $client->request('GET', '/app/settings');

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );
    
  }

  public function testNotificationsPage()
  {

    $client = static::createClientWithAuthentication();
    $crawler = $client->request('GET', '/app/notifications');

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );
    
  }

  public function testNotificationsPageMore()
  {
   
    $client = static::createClientWithAuthentication();
    $crawler = $client->request('GET', '/app/notifications/2013-01-01');

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

  }

  public function testNotificationsMark()
  {
    $user = $this->em->getRepository('metaUserBundle:User')->findOneByUsername('test');

    if ($user){
        $user->setLastNotifiedAt(new \DateTime("2012-07-08 11:14:15.638276"));
        $this->em->flush();

        $client = static::createClientWithAuthentication();
        $crawler = $client->request('GET', '/app/notifications');

        $this->assertEquals(
            Response::HTTP_OK,
            $client->getResponse()->getStatusCode()
        );
    
        $this->assertCount(1, $crawler->filter('button#markRead'));

        $link = $crawler->filter('button#markRead')->attr('data-url');
        $crawler = $client->request('POST', $link);

        $this->assertEquals(
            Response::HTTP_OK,
            $client->getResponse()->getStatusCode()
        );
    
        $crawler = $client->request('GET', '/app/notifications');

        $this->assertEquals(
            Response::HTTP_OK,
            $client->getResponse()->getStatusCode()
        );
    
        $this->assertCount(1, $crawler->filter('section[name=no_notifications]'));
    }

  }

  public function testChangePasswordPage()
  {

    $client = static::createClientWithAuthentication();
    $crawler = $client->request('GET', '/app/settings');

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $this->assertCount(1, $crawler->filter('a#password'));

    $link = $crawler->filter('a#password')->link();
    $crawler = $client->click($link);

    $this->assertTrue($client->getResponse()->isRedirect());

    $this->assertRegExp('/\/app\/change\/password\/.*?$/', $client->getResponse()->headers->get('location'));
    $crawler = $client->followRedirect();

    $this->assertCount(1, $crawler->filter('input#password'));
    $this->assertCount(1, $crawler->filter('input#password_2'));

  }

}
