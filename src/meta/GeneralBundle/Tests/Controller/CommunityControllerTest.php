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
    $communityId = "3xsdgob0n"; // "Thirdplace"

    $client = static::createClientWithAuthentication();
    $crawler = $client->request('GET', '/app/community/switch/0' . $communityId, array('token' => $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('switchCommunity')));

    $this->assertTrue(
        $client->getResponse()->isRedirect('/app/')
    );

  }

  public function testSwitchCommunityNotExist()
  {
    $communityId = "foobar"; // "Thirdplace"

    $client = static::createClientWithAuthentication();
    $crawler = $client->request('GET', '/app/community/switch/0' . $communityId, array('token' => $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('switchCommunity')));

    $this->assertEquals(
        Response::HTTP_NOT_FOUND,
        $client->getResponse()->getStatusCode()
    );

  }

  public function testSwitchCommunityUserNotIn()
  {
    $communityId = "????"; // Community tchap is not in

    $client = static::createClientWithAuthentication();
    $crawler = $client->request('GET', '/app/community/switch/0' . $communityId, array('token' => $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('switchCommunity')));

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
