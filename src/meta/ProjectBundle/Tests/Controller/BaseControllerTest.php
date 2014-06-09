<?php

namespace meta\ProjectBundle\Tests\Controller;

use meta\UserBundle\Tests\SecuredWebTestCase,
    Symfony\Component\HttpFoundation\Response;

class BaseControllerTest extends SecuredWebTestCase
{

  public function testProjectsList()
  {

    $this->setUp();
    $community = $this->em->getRepository('metaGeneralBundle:Community\Community')->findOneByName("test_in");
    $this->tearDown();

    $client = static::createClientWithAuthentication("test"); // test is in test_in 
    $crawler = $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('switchCommunity')));

    $crawler = $client->request('GET', '/app/projects');
    
    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

  }

  public function testProjectsListPrivateSpace()
  {


    $client = static::createClientWithAuthentication("test");
    $crawler = $client->request('GET', '/app/switch/privatespace', array('token' => $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('switchCommunity')));

    $crawler = $client->request('GET', '/app/projects');
    
    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

  }

  public function testProjectInPrivateSpace()
  {

    $this->setUp();
    $project = $this->em->getRepository('metaProjectBundle:StandardProject')->findOneByName("test_project_private_space");
    $this->tearDown();

    $client = static::createClientWithAuthentication("test"); // test is in test_in 
    $crawler = $client->request('GET', '/app/switch/privatespace', array('token' => $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('switchCommunity')));

    $crawler = $client->request('GET', '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()));

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $this->assertCount(1, $crawler->filter('h2:contains("test_project_private_space")'));

  }

  public function testProjectInPrivateSpaceSwitch()
  {

    $this->setUp();
    $community = $this->em->getRepository('metaGeneralBundle:Community\Community')->findOneByName("test_in");
    $project = $this->em->getRepository('metaProjectBundle:StandardProject')->findOneByName("test_project_private_space");
    $this->tearDown();

    $client = static::createClientWithAuthentication("test"); // test is in test_in 
    $crawler = $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('switchCommunity')));

    $crawler = $client->request('GET', '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()));

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $this->assertCount(1, $crawler->filter('h2:contains("test_project_private_space")'));

  }

  public function testProjectInOtherPrivateSpace()
  {

    $this->setUp();
    $project = $this->em->getRepository('metaProjectBundle:StandardProject')->findOneByName("test_project_private_space");
    $this->tearDown();

    $client = static::createClientWithAuthentication("other_test");
    $crawler = $client->request('GET', '/app/switch/privatespace', array('token' => $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('switchCommunity')));

    $crawler = $client->request('GET', '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()));

    $this->assertEquals(
        Response::HTTP_NOT_FOUND,
        $client->getResponse()->getStatusCode()
    );

  }

  public function testProjectInCommunityOwner()
  {
 
    $this->setUp();
    $community = $this->em->getRepository('metaGeneralBundle:Community\Community')->findOneByName("test_in");
    $project = $this->em->getRepository('metaProjectBundle:StandardProject')->findOneByName("test_project_community_owner");
    $this->tearDown();

    $client = static::createClientWithAuthentication("test"); // test is in test_in 
    $crawler = $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('switchCommunity')));

    $crawler = $client->request('GET', '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()));

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $this->assertCount(1, $crawler->filter('h2:contains("test_project_community_owner")'));

  }

  public function testProjectInCommunityOtherOwner()
  {
 
    $this->setUp();
    $community = $this->em->getRepository('metaGeneralBundle:Community\Community')->findOneByName("test_in");
    $project = $this->em->getRepository('metaProjectBundle:StandardProject')->findOneByName("test_project_community_owner");
    $this->tearDown();

    $client = static::createClientWithAuthentication("other_test");
    $crawler = $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('switchCommunity')));

    $crawler = $client->request('GET', '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()));

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $this->assertCount(1, $crawler->filter('h2:contains("test_project_community_owner")'));

  }

  public function testProjectInCommunityOtherOwnerSwitch()
  {
 
    $this->setUp();
    $community = $this->em->getRepository('metaGeneralBundle:Community\Community')->findOneByName("test_out");
    $project = $this->em->getRepository('metaProjectBundle:StandardProject')->findOneByName("test_project_community_owner");
    $this->tearDown();

    $client = static::createClientWithAuthentication("other_test");
    $crawler = $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('switchCommunity')));

    $crawler = $client->request('GET', '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()));

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $this->assertCount(1, $crawler->filter('h2:contains("test_project_community_owner")'));

  }

  public function testProjectInCommunityParticipant()
  {
 
    $this->setUp();
    $community = $this->em->getRepository('metaGeneralBundle:Community\Community')->findOneByName("test_in");
    $project = $this->em->getRepository('metaProjectBundle:StandardProject')->findOneByName("test_project_community_participant");
    $this->tearDown();

    $client = static::createClientWithAuthentication("test"); // test is in test_in 
    $crawler = $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('switchCommunity')));

    $crawler = $client->request('GET', '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()));

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $this->assertCount(1, $crawler->filter('h2:contains("test_project_community_participant")'));

  }

  public function testProjectInCommunityOtherParticipant()
  {
 
    $this->setUp();
    $community = $this->em->getRepository('metaGeneralBundle:Community\Community')->findOneByName("test_in");
    $project = $this->em->getRepository('metaProjectBundle:StandardProject')->findOneByName("test_project_community_participant");
    $this->tearDown();

    $client = static::createClientWithAuthentication("other_test");
    $crawler = $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('switchCommunity')));

    $crawler = $client->request('GET', '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()));

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $this->assertCount(1, $crawler->filter('h2:contains("test_project_community_participant")'));

  }

  public function testProjectInCommunityOwnerPrivate()
  {
 
    $this->setUp();
    $community = $this->em->getRepository('metaGeneralBundle:Community\Community')->findOneByName("test_in");
    $project = $this->em->getRepository('metaProjectBundle:StandardProject')->findOneByName("test_project_community_owner_private");
    $this->tearDown();

    $client = static::createClientWithAuthentication("test"); // test is in test_in 
    $crawler = $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('switchCommunity')));

    $crawler = $client->request('GET', '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()));

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $this->assertCount(1, $crawler->filter('h2:contains("test_project_community_owner_private")'));

  }

  public function testProjectInCommunityOwnerPrivateSwitch()
  {
 
    $this->setUp();
    $community = $this->em->getRepository('metaGeneralBundle:Community\Community')->findOneByName("test_guest");
    $project = $this->em->getRepository('metaProjectBundle:StandardProject')->findOneByName("test_project_community_owner_private");
    $this->tearDown();

    $client = static::createClientWithAuthentication("test"); // test is in test_in 
    $crawler = $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('switchCommunity')));

    $crawler = $client->request('GET', '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()));

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $this->assertCount(1, $crawler->filter('h2:contains("test_project_community_owner_private")'));

  }

  public function testProjectInCommunityOtherOwnerPrivate()
  {
 
    $this->setUp();
    $community = $this->em->getRepository('metaGeneralBundle:Community\Community')->findOneByName("test_in");
    $project = $this->em->getRepository('metaProjectBundle:StandardProject')->findOneByName("test_project_community_owner_private");
    $this->tearDown();

    $client = static::createClientWithAuthentication("other_test");
    $crawler = $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('switchCommunity')));

    $crawler = $client->request('GET', '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()));

    $this->assertEquals(
        Response::HTTP_NOT_FOUND,
        $client->getResponse()->getStatusCode()
    );

  }

  public function testProjectInCommunityPrivateNotMe()
  {
 
    $this->setUp();
    $community = $this->em->getRepository('metaGeneralBundle:Community\Community')->findOneByName("test_in");
    $project = $this->em->getRepository('metaProjectBundle:StandardProject')->findOneByName("test_project_community_not_in_private");
    $this->tearDown();

    $client = static::createClientWithAuthentication("test");
    $crawler = $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('switchCommunity')));

    $crawler = $client->request('GET', '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()));

    $this->assertEquals(
        Response::HTTP_NOT_FOUND,
        $client->getResponse()->getStatusCode()
    );

  }

  public function testProjectInCommunityNotIn()
  {
 
    $this->setUp();
    $community = $this->em->getRepository('metaGeneralBundle:Community\Community')->findOneByName("test_in");
    $project = $this->em->getRepository('metaProjectBundle:StandardProject')->findOneByName("test_project_community_not_in");
    $this->tearDown();

    $client = static::createClientWithAuthentication("test"); // test is in test_in 
    $crawler = $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('switchCommunity')));

    $crawler = $client->request('GET', '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()));

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $this->assertCount(1, $crawler->filter('h2:contains("test_project_community_not_in")'));

  }

  public function testProjectInOtherCommunity()
  {
    
    $this->setUp();
    $community = $this->em->getRepository('metaGeneralBundle:Community\Community')->findOneByName("test_in");
    $project = $this->em->getRepository('metaProjectBundle:StandardProject')->findOneByName("test_out_project");
    $this->tearDown();

    $client = static::createClientWithAuthentication("test"); // test is in test_in 
    $crawler = $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('switchCommunity')));

    $crawler = $client->request('GET', '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()));

    $this->assertEquals(
        Response::HTTP_NOT_FOUND,
        $client->getResponse()->getStatusCode()
    );

  }

  public function testGuestProjectInCommunityImGuest()
  {    

    $this->setUp();
    $community = $this->em->getRepository('metaGeneralBundle:Community\Community')->findOneByName("test_guest");
    $project = $this->em->getRepository('metaProjectBundle:StandardProject')->findOneByName("test_guest_project");
    $this->tearDown();

    $client = static::createClientWithAuthentication("test"); // test is in test_in 
    $crawler = $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('switchCommunity')));

    $crawler = $client->request('GET', '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()));

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $this->assertCount(1, $crawler->filter('h2:contains("test_guest_project")'));

  }

  public function testGuestProjectInCommunityImOtherGuest()
  {    

    $this->setUp();
    $community = $this->em->getRepository('metaGeneralBundle:Community\Community')->findOneByName("test_guest");
    $project = $this->em->getRepository('metaProjectBundle:StandardProject')->findOneByName("test_guest_project");
    $this->tearDown();

    $client = static::createClientWithAuthentication("other_test");
    $crawler = $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('switchCommunity')));

    $crawler = $client->request('GET', '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()));

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $this->assertCount(1, $crawler->filter('h2:contains("test_guest_project")'));

  }

  public function testGuestProjectInCommunityImOtherGuest2()
  {    

    $this->setUp();
    $community = $this->em->getRepository('metaGeneralBundle:Community\Community')->findOneByName("test_guest");
    $project = $this->em->getRepository('metaProjectBundle:StandardProject')->findOneByName("test_guest_project_not_in");
    $this->tearDown();

    $client = static::createClientWithAuthentication("other_test");
    $crawler = $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('switchCommunity')));

    $crawler = $client->request('GET', '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()));

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $this->assertCount(1, $crawler->filter('h2:contains("test_guest_project_not_in")'));

  }

  public function testProjectInCommunityImGuest()
  {

    $this->setUp();
    $community = $this->em->getRepository('metaGeneralBundle:Community\Community')->findOneByName("test_guest");
    $project = $this->em->getRepository('metaProjectBundle:StandardProject')->findOneByName("test_guest_project_not_in");
    $this->tearDown();

    $client = static::createClientWithAuthentication("test"); // test is in test_in 
    $crawler = $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('switchCommunity')));

    $crawler = $client->request('GET', '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()));

    $this->assertEquals(
        Response::HTTP_NOT_FOUND,
        $client->getResponse()->getStatusCode()
    );

  }


}
