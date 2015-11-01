<?php

namespace meta\UserBundle\Tests\Controller;

use meta\UserBundle\Tests\SecuredWebTestCase,
    Symfony\Component\HttpFoundation\Response;

class CommunityControllerTest extends SecuredWebTestCase
{

  public function testCommunityManagePage()
  {
    $this->setUp();
    $community = $this->em->getRepository('metaGeneralBundle:Community\Community')->findOneByName("test_in");
    $this->tearDown();

    $client = static::createClientWithAuthentication("test_manager"); // test_manager is managing test_in 

    $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('switchCommunity')->getValue()));
    $crawler = $client->request('GET', '/app/community/manage');

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );
  }

  public function testCommunityManagePageNotManager()
  {
    $this->setUp();
    $community = $this->em->getRepository('metaGeneralBundle:Community\Community')->findOneByName("test_in");
    $this->tearDown();

    $client = static::createClientWithAuthentication("test"); // test is not managing test_in 

    $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('switchCommunity')->getValue()));
    $crawler = $client->request('GET', '/app/community/manage');
    
    $this->assertTrue(
        $client->getResponse()->isRedirect('/app/')
    );
  }

  public function testCommunityInvitePage()
  {
    
    $this->setUp();
    $community = $this->em->getRepository('metaGeneralBundle:Community\Community')->findOneByName("test_in");
    $this->tearDown();

    $client = static::createClientWithAuthentication("test_manager"); // test_manager is managing test_in 
    $tokenManager = $client->getContainer()->get('security.csrf.token_manager');
    $inviteToken = $tokenManager->getToken('invite');

    $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $tokenManager->getToken('switchCommunity')));
    $crawler = $client->request('GET', '/app/community/invite', array('token' => $inviteToken));

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );
  }

  public function testCommunityInvitePageNotManager()
  {
    $this->setUp();
    $community = $this->em->getRepository('metaGeneralBundle:Community\Community')->findOneByName("test_in");
    $this->tearDown();

    $client = static::createClientWithAuthentication("test"); // test is not managing test_in 
    $tokenManager = $client->getContainer()->get('security.csrf.token_manager');
    $inviteToken = $tokenManager->getToken('invite');

    $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('switchCommunity')->getValue()));
    $crawler = $client->request('GET', '/app/community/invite', array('token' => $inviteToken));

    $this->assertTrue(
        $client->getResponse()->isRedirect('/app/')
    );
  }

  public function testCommunityInvitePageNoToken()
  {
    
    $this->setUp();
    $community = $this->em->getRepository('metaGeneralBundle:Community\Community')->findOneByName("test_in");
    $this->tearDown();

    $client = static::createClientWithAuthentication("test_manager"); // test_manager is managing test_in 

    $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('switchCommunity')->getValue()));
    $crawler = $client->request('GET', '/app/community/invite');

    $this->assertTrue(
        $client->getResponse()->isRedirect('/app/')
    );
  }

  public function testCommunityProposePageManager()
  {
    
    $this->setUp();
    $community = $this->em->getRepository('metaGeneralBundle:Community\Community')->findOneByName("test_in");
    $this->tearDown();

    $client = static::createClientWithAuthentication("test_manager"); // test_manager is managing test_in 
    $tokenManager = $client->getContainer()->get('security.csrf.token_manager');
    $inviteToken = $tokenManager->getToken('propose');

    $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $tokenManager->getToken('switchCommunity')));
    $crawler = $client->request('GET', '/app/community/propose', array('token' => $inviteToken));

    $this->assertRegExp('/\/app\/community\/invite\?token.*$/', $client->getResponse()->headers->get('location'));

  }

  public function testCommunityProposePageNotManager()
  {
    $this->setUp();
    $community = $this->em->getRepository('metaGeneralBundle:Community\Community')->findOneByName("test_in");
    $this->tearDown();

    $client = static::createClientWithAuthentication("test"); // test is not managing test_in 
    $tokenManager = $client->getContainer()->get('security.csrf.token_manager');
    $inviteToken = $tokenManager->getToken('propose');

    $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('switchCommunity')->getValue()));
    $crawler = $client->request('GET', '/app/community/propose', array('token' => $inviteToken));

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );
  }

  public function testCommunityProposePageNoToken()
  {
    
    $this->setUp();
    $community = $this->em->getRepository('metaGeneralBundle:Community\Community')->findOneByName("test_in");
    $this->tearDown();

    $client = static::createClientWithAuthentication("test");

    $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('switchCommunity')->getValue()));
    $crawler = $client->request('GET', '/app/community/propose');

    $this->assertTrue(
        $client->getResponse()->isRedirect('/app/')
    );
  }

  public function testCommunityInvitePageNoTokenRefMail()
  {
    
    $this->setUp();
    $community = $this->em->getRepository('metaGeneralBundle:Community\Community')->findOneByName("test_in");
    $this->tearDown();

    $client = static::createClientWithAuthentication("test_manager");

    $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('switchCommunity')->getValue()));
    $crawler = $client->request('GET', '/app/community/invite?ref=mail&user=ABCDEFGHIJKLMNOPQRSTUVWXYZ');

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $this->assertContains(
        'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
        $client->getResponse()->getContent()
    );
  }

  public function testCommunityRemovePage()
  {
    $this->setUp();
    $community = $this->em->getRepository('metaGeneralBundle:Community\Community')->findOneByName("test_in");
    $this->tearDown();

    $client = static::createClientWithAuthentication("test_manager"); // test_manager is managing test_in 
    $tokenManager = $client->getContainer()->get('security.csrf.token_manager');
    $removeToken = $tokenManager->getToken('remove');

    $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $tokenManager->getToken('switchCommunity')));
    $crawler = $client->request('GET', '/app/community/remove', array('token' => $removeToken));

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );
    
  }

  public function testCommunityRemovePageNotManager()
  {
    $this->setUp();
    $community = $this->em->getRepository('metaGeneralBundle:Community\Community')->findOneByName("test_in");
    $this->tearDown();

    $client = static::createClientWithAuthentication("test"); // test_manager is managing test_in 
    $tokenManager = $client->getContainer()->get('security.csrf.token_manager');
    $removeToken = $tokenManager->getToken('remove');

    $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $tokenManager->getToken('switchCommunity')));
    $crawler = $client->request('GET', '/app/community/remove', array('token' => $removeToken));

    $this->assertTrue(
        $client->getResponse()->isRedirect('/app/community/manage')
    );
    
  }

  public function testCommunityRemovePageNoToken()
  {
    $this->setUp();
    $community = $this->em->getRepository('metaGeneralBundle:Community\Community')->findOneByName("test_in");
    $this->tearDown();

    $client = static::createClientWithAuthentication("test_manager"); // test_manager is managing test_in 

    $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('switchCommunity')->getValue()));
    $crawler = $client->request('GET', '/app/community/remove');

    $this->assertTrue(
        $client->getResponse()->isRedirect('/app/community/manage')
    );
    
  }

  public function testNewCommunity()
  {
    $this->setUp();
    $community = $this->em->getRepository('metaGeneralBundle:Community\Community')->findOneByName("test_in");
    $this->tearDown();

    $client = static::createClientWithAuthentication("test"); // no need to be manager
    $crawler = $client->request('GET', '/app/communities/new');

    $this->assertCount(2, $crawler->filter('form'));

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );
    
  }

  public function testSwitchPrivateSpace()
  {
    
    $client = static::createClientWithAuthentication();
    $crawler = $client->request('GET', '/app/switch/privatespace', array('token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('switchCommunity')->getValue()));

    $this->assertTrue(
        $client->getResponse()->isRedirect('/app/')
    );

  }

  public function testSwitchCommunity()
  {

    $this->setUp();
    $community = $this->em->getRepository('metaGeneralBundle:Community\Community')->findOneByName("test_in");
    $this->tearDown();

    $client = static::createClientWithAuthentication("test"); // test is in test_in 

    $crawler = $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('switchCommunity')->getValue()));

    $this->assertTrue(
        $client->getResponse()->isRedirect('/app/')
    );

  }

  public function testSwitchCommunityOther()
  {

    $this->setUp();
    $community = $this->em->getRepository('metaGeneralBundle:Community\Community')->findOneByName("test_in");
    $this->tearDown();

    $client = static::createClientWithAuthentication("other_test"); // test is in test_in 

    $crawler = $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('switchCommunity')->getValue()));

    $this->assertTrue(
        $client->getResponse()->isRedirect('/app/')
    );

  }

  public function testSwitchCommunityOtherOut()
  {

    $this->setUp();
    $community = $this->em->getRepository('metaGeneralBundle:Community\Community')->findOneByName("test_out");
    $this->tearDown();

    $client = static::createClientWithAuthentication("other_test"); // other_test is in test_out

    $crawler = $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('switchCommunity')->getValue()));

    $this->assertTrue(
        $client->getResponse()->isRedirect('/app/')
    );

  }

  public function testSwitchCommunityNotExist()
  {
    $communityId = "foobar";

    $client = static::createClientWithAuthentication("test");
    $crawler = $client->request('GET', '/app/community/switch/0' . $communityId, array('token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('switchCommunity')->getValue()));

    $this->assertEquals(
        Response::HTTP_NOT_FOUND,
        $client->getResponse()->getStatusCode()
    );

  }

  public function testSwitchCommunityGuest()
  {

    $this->setUp();
    $community = $this->em->getRepository('metaGeneralBundle:Community\Community')->findOneByName("test_guest");
    $this->tearDown();

    $client = static::createClientWithAuthentication("test"); // test is in test_guest

    $crawler = $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('switchCommunity')->getValue()));

    $this->assertTrue(
        $client->getResponse()->isRedirect('/app/')
    );

  }

  public function testSwitchCommunityGuestOther()
  {

    $this->setUp();
    $community = $this->em->getRepository('metaGeneralBundle:Community\Community')->findOneByName("test_guest");
    $this->tearDown();

    $client = static::createClientWithAuthentication("other_test"); // other_test is in test_guest

    $crawler = $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('switchCommunity')->getValue()));

    $this->assertTrue(
        $client->getResponse()->isRedirect('/app/')
    );

  }

  public function testSwitchCommunityUserNotIn()
  {

    $this->setUp();
    $community = $this->em->getRepository('metaGeneralBundle:Community\Community')->findOneByName("test_out");
    $this->tearDown();

    $client = static::createClientWithAuthentication("test"); // test is not in test_out

    $crawler = $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('switchCommunity')->getValue()));

    $this->assertEquals(
        Response::HTTP_NOT_FOUND,
        $client->getResponse()->getStatusCode()
    );

  }

  public function testSwitchPrivateSpaceBadCSRF()
  {
    
    $client = static::createClientWithAuthentication();
    $crawler = $client->request('GET', '/app/switch/privatespace', array('token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('foobar')->getValue()));

    $this->assertTrue(
        $client->getResponse()->isRedirect('/app/me')
    );

  }


  public function testSwitchCommunityBadCSRF()
  {
    $communityId = "3xsdgob0n"; // "Thirdplace"

    $client = static::createClientWithAuthentication();
    $crawler = $client->request('GET', '/app/community/switch/0' . $communityId, array('token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('foobar')->getValue()));

    $this->assertTrue(
        $client->getResponse()->isRedirect('/app/me')
    );

  }
}
