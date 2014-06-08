<?php

namespace meta\UserBundle\Tests\Controller;

use meta\UserBundle\Tests\SecuredWebTestCase,
    Symfony\Component\HttpFoundation\Response;

class CommunityControllerTest extends SecuredWebTestCase
{

  public function testSwitchPrivateSpace()
  {
    
    $client = static::createClientWithAuthentication();
    $crawler = $client->request('GET', '/app/switch/privatespace', array('token' => $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('switchCommunity')));

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

    $crawler = $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('switchCommunity')));

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

    $crawler = $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('switchCommunity')));

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

    $crawler = $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('switchCommunity')));

    $this->assertTrue(
        $client->getResponse()->isRedirect('/app/')
    );

  }

  public function testSwitchCommunityNotExist()
  {
    $communityId = "foobar";

    $client = static::createClientWithAuthentication("test");
    $crawler = $client->request('GET', '/app/community/switch/0' . $communityId, array('token' => $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('switchCommunity')));

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

    $crawler = $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('switchCommunity')));

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

    $crawler = $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('switchCommunity')));

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

    $crawler = $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('switchCommunity')));

    $this->assertEquals(
        Response::HTTP_NOT_FOUND,
        $client->getResponse()->getStatusCode()
    );

  }

  public function testSwitchPrivateSpaceBadCSRF()
  {
    
    $client = static::createClientWithAuthentication();
    $crawler = $client->request('GET', '/app/switch/privatespace', array('token' => $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('foobar')));

    $this->assertTrue(
        $client->getResponse()->isRedirect('/app/me')
    );

  }


  public function testSwitchCommunityBadCSRF()
  {
    $communityId = "3xsdgob0n"; // "Thirdplace"

    $client = static::createClientWithAuthentication();
    $crawler = $client->request('GET', '/app/community/switch/0' . $communityId, array('token' => $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('foobar')));

    $this->assertTrue(
        $client->getResponse()->isRedirect('/app/me')
    );

  }

  // click sur l'icone du menubar -> renvoie vers communauté courante ou privatespace


}
