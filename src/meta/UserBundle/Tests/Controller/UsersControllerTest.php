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
    $client->request('GET', '/app/switch/privatespace', array('token' => $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('switchCommunity')));
    $crawler = $client->request('GET', '/app/people');

    // No users in private space
    $this->assertEquals(
        Response::HTTP_NOT_FOUND,
        $client->getResponse()->getStatusCode()
    );

  }

  public function testListCommunity()
  {
    $communityId = "3xsdgob0n"; // "Thirdplace"

    $client = static::createClientWithAuthentication();
    $client->request('GET', '/app/community/switch/0' . $communityId, array('token' => $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('switchCommunity')));
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

  public function testChooseInPrivateSpace()
  {

  }

  public function testChooseInCommunity()
  {
    
  }

  public function testChooseInCommunityNoUserToChoose()
  {
    
  }


}
