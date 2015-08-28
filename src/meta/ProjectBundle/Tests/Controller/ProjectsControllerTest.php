<?php

namespace meta\ProjectBundle\Tests\Controller;

use meta\UserBundle\Tests\SecuredWebTestCase,
    Symfony\Component\HttpFoundation\Response;

class ProjectsControllerTest extends SecuredWebTestCase
{

  public function testProjectsList()
  {

    $this->setUp();
    $community = $this->em->getRepository('metaGeneralBundle:Community\Community')->findOneByName("test_in");
    $this->tearDown();

    $client = static::createClientWithAuthentication("test"); // test is in test_in 
    $crawler = $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('switchCommunity')->getValue()));

    $crawler = $client->request('GET', '/app/projects');
    
    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

  }

  public function testProjectsListMore()
  {

    $this->setUp();
    $community = $this->em->getRepository('metaGeneralBundle:Community\Community')->findOneByName("test_in");
    $this->tearDown();

    $client = static::createClientWithAuthentication("test"); // test is in test_in 

    $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('switchCommunity')->getValue()));
    $crawler = $client->request('POST', '/app/projects', array(), array(), array(
      'HTTP_X-Requested-With' => 'XMLHttpRequest',
    ), "page=2&full=false");

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $this->assertSame('application/json', $client->getResponse()->headers->get('Content-Type')); // Test if Content-Type is valid application/json
    $this->assertTrue(is_array(json_decode($client->getResponse()->getContent(), true)));
  }

  public function testProjectsListMoreFull()
  {
    
    $this->setUp();
    $community = $this->em->getRepository('metaGeneralBundle:Community\Community')->findOneByName("test_in");
    $this->tearDown();

    $client = static::createClientWithAuthentication("test"); // test is in test_in 

    $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('switchCommunity')->getValue()));
    $crawler = $client->request('POST', '/app/projects', array(), array(), array(
      'HTTP_X-Requested-With' => 'XMLHttpRequest',
    ), "page=2&full=true");

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $this->assertSame('application/json', $client->getResponse()->headers->get('Content-Type')); // Test if Content-Type is valid application/json
    $this->assertTrue(is_array(json_decode($client->getResponse()->getContent(), true)));
  }

  public function testProjectNewInCommunity()
  {

  }

  public function testProjectNewInPrivateSpace()
  {

  }

  public function testProjectDeleteInCommunity()
  {
    
  }

  public function testProjectDeleteInPrivateSpace()
  {

  }

  public function testProjectsSortUrls()
  {
    $this->setUp();
    $community = $this->em->getRepository('metaGeneralBundle:Community\Community')->findOneByName("test_in");
    $this->tearDown();

    $client = static::createClientWithAuthentication("test"); // test is in test_in 
    $crawler = $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('switchCommunity')->getValue()));

    $crawler = $client->request('GET', '/app/projects');

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $crawler = $client->request('GET', '/app/projects/1');

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $crawler = $client->request('GET', '/app/projects/1/newest');

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $crawler = $client->request('GET', '/app/projects/1/alpha');

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

  }

  public function testProjectsStatusesUrls()
  {
    $this->setUp();
    $community = $this->em->getRepository('metaGeneralBundle:Community\Community')->findOneByName("test_in");
    $this->tearDown();

    $client = static::createClientWithAuthentication("test"); // test is in test_in 
    $crawler = $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('switchCommunity')->getValue()));

    $crawler = $client->request('GET', '/app/projects/sleeping');

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $crawler = $client->request('GET', '/app/projects/archived');

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

  }

  public function testProjectsListPrivateSpace()
  {

    $client = static::createClientWithAuthentication("test");
    $crawler = $client->request('GET', '/app/switch/privatespace', array('token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('switchCommunity')->getValue()));

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
    $crawler = $client->request('GET', '/app/switch/privatespace', array('token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('switchCommunity')->getValue()));

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
    $crawler = $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('switchCommunity')->getValue()));

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
    $crawler = $client->request('GET', '/app/switch/privatespace', array('token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('switchCommunity')->getValue()));

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
    $crawler = $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('switchCommunity')->getValue()));

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
    $crawler = $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('switchCommunity')->getValue()));

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
    $crawler = $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('switchCommunity')->getValue()));

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
    $crawler = $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('switchCommunity')->getValue()));

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
    $crawler = $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('switchCommunity')->getValue()));

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
    $crawler = $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('switchCommunity')->getValue()));

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
    $crawler = $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('switchCommunity')->getValue()));

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
    $crawler = $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('switchCommunity')->getValue()));

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
    $crawler = $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('switchCommunity')->getValue()));

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
    $crawler = $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('switchCommunity')->getValue()));

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
    $crawler = $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('switchCommunity')->getValue()));

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
    $crawler = $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('switchCommunity')->getValue()));

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
    $crawler = $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('switchCommunity')->getValue()));

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
    $crawler = $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('switchCommunity')->getValue()));

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
    $crawler = $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('switchCommunity')->getValue()));

    $crawler = $client->request('GET', '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()));

    $this->assertEquals(
        Response::HTTP_NOT_FOUND,
        $client->getResponse()->getStatusCode()
    );

  }

  public function testProjectWatch()
  {

    $this->setUp();
    $project = $this->em->getRepository('metaProjectBundle:StandardProject')->findOneByName("test_project_community_owner");
    $this->tearDown();

    $client = static::createClientWithAuthentication("test");

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/watch?token=' . $client->getContainer()->get('security.csrf.token_manager')->getToken('watch')->getValue()
    );

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/unwatch?token=' . $client->getContainer()->get('security.csrf.token_manager')->getToken('unwatch')->getValue()
    );

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/unwatch?token=' . $client->getContainer()->get('security.csrf.token_manager')->getToken('unwatch')->getValue()
    );

    $this->assertEquals(
        Response::HTTP_NOT_ACCEPTABLE,
        $client->getResponse()->getStatusCode()
    );
  }

  public function testProjectWatchPrivateNotIn()
  {

    $this->setUp();
    $project = $this->em->getRepository('metaProjectBundle:StandardProject')->findOneByName("test_project_community_not_in_private");
    $this->tearDown();

    $client = static::createClientWithAuthentication("test");

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/watch?token=' . $client->getContainer()->get('security.csrf.token_manager')->getToken('watch')->getValue()
    );

    $this->assertEquals(
        Response::HTTP_NOT_FOUND,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/unwatch?token=' . $client->getContainer()->get('security.csrf.token_manager')->getToken('unwatch')->getValue()
    );

    $this->assertEquals(
        Response::HTTP_NOT_FOUND,
        $client->getResponse()->getStatusCode()
    );
  }

  public function testProjectWatchOut()
  {

    $this->setUp();
    $project = $this->em->getRepository('metaProjectBundle:StandardProject')->findOneByName("test_out_project");
    $this->tearDown();

    $client = static::createClientWithAuthentication("test");

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/watch?token=' . $client->getContainer()->get('security.csrf.token_manager')->getToken('watch')->getValue()
    );

    $this->assertEquals(
        Response::HTTP_NOT_FOUND,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/unwatch?token=' . $client->getContainer()->get('security.csrf.token_manager')->getToken('unwatch')->getValue()
    );

    $this->assertEquals(
        Response::HTTP_NOT_FOUND,
        $client->getResponse()->getStatusCode()
    );
  }

  public function testProjectModifOwner()
  {

    $this->setUp();
    $project = $this->em->getRepository('metaProjectBundle:StandardProject')->findOneByName("test_project_community_owner");
    $this->tearDown();

    $client = static::createClientWithAuthentication("test"); 

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('security.csrf.token_manager')->getToken('edit')->getValue(),
        array( "name" => "name", "value" => "test_project_community_owner_TEST". rand())
    );

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('security.csrf.token_manager')->getToken('edit')->getValue(),
        array( "name" => "name", "value" => "test_project_community_owner")
    );

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('security.csrf.token_manager')->getToken('edit')->getValue(),
        array( "name" => "about", "value" => "test" . rand())
    );

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('security.csrf.token_manager')->getToken('edit')->getValue(),
        array( "name" => "skills", "value" => "add", "key" => "management")
    );

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('security.csrf.token_manager')->getToken('edit')->getValue(),
        array( "name" => "skills", "value" => "remove", "key" => "management")
    );

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('security.csrf.token_manager')->getToken('edit')->getValue(),
        array( "name" => "status", "value" => "0")
    );

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );
  }


  public function testProjectModifOtherNotOwner()
  {

    $this->setUp();
    $project = $this->em->getRepository('metaProjectBundle:StandardProject')->findOneByName("test_project_community_owner");
    $this->tearDown();

    $client = static::createClientWithAuthentication("other_test"); 

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('security.csrf.token_manager')->getToken('edit')->getValue(),
        array( "name" => "name", "value" => "test_project_community_owner_TEST". rand())
    );

    $this->assertEquals(
        Response::HTTP_NOT_ACCEPTABLE,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('security.csrf.token_manager')->getToken('edit')->getValue(),
        array( "name" => "about", "value" => "test" . rand())
    );

    $this->assertEquals(
        Response::HTTP_NOT_ACCEPTABLE,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('security.csrf.token_manager')->getToken('edit')->getValue(),
        array( "name" => "skills", "value" => "add", "key" => "management")
    );

    $this->assertEquals(
        Response::HTTP_NOT_ACCEPTABLE,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('security.csrf.token_manager')->getToken('edit')->getValue(),
        array( "name" => "skills", "value" => "remove", "key" => "management")
    );

    $this->assertEquals(
        Response::HTTP_NOT_ACCEPTABLE,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('security.csrf.token_manager')->getToken('edit')->getValue(),
        array( "name" => "status", "value" => "0")
    );

    $this->assertEquals(
        Response::HTTP_NOT_ACCEPTABLE,
        $client->getResponse()->getStatusCode()
    );
  }

  public function testProjectModifGuestOwner()
  {

    $this->setUp();
    $project = $this->em->getRepository('metaProjectBundle:StandardProject')->findOneByName("test_guest_project");
    $this->tearDown();

    $client = static::createClientWithAuthentication("test"); 

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('security.csrf.token_manager')->getToken('edit')->getValue(),
        array( "name" => "name", "value" => "test_guest_project_TEST". rand())
    );

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('security.csrf.token_manager')->getToken('edit')->getValue(),
        array( "name" => "name", "value" => "test_guest_project")
    );

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('security.csrf.token_manager')->getToken('edit')->getValue(),
        array( "name" => "about", "value" => "test" . rand())
    );

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('security.csrf.token_manager')->getToken('edit')->getValue(),
        array( "name" => "skills", "value" => "add", "key" => "management")
    );

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('security.csrf.token_manager')->getToken('edit')->getValue(),
        array( "name" => "skills", "value" => "remove", "key" => "management")
    );

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('security.csrf.token_manager')->getToken('edit')->getValue(),
        array( "name" => "status", "value" => "0")
    );

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );
  }

  public function testProjectModifGuestOwnerOther()
  {

    $this->setUp();
    $project = $this->em->getRepository('metaProjectBundle:StandardProject')->findOneByName("test_guest_project");
    $this->tearDown();

    $client = static::createClientWithAuthentication("other_test"); 

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('security.csrf.token_manager')->getToken('edit')->getValue(),
        array( "name" => "name", "value" => "test_guest_project_TEST". rand())
    );

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('security.csrf.token_manager')->getToken('edit')->getValue(),
        array( "name" => "name", "value" => "test_guest_project")
    );

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('security.csrf.token_manager')->getToken('edit')->getValue(),
        array( "name" => "about", "value" => "test" . rand())
    );

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('security.csrf.token_manager')->getToken('edit')->getValue(),
        array( "name" => "skills", "value" => "add", "key" => "management")
    );

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('security.csrf.token_manager')->getToken('edit')->getValue(),
        array( "name" => "skills", "value" => "remove", "key" => "management")
    );

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('security.csrf.token_manager')->getToken('edit')->getValue(),
        array( "name" => "status", "value" => "0")
    );

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );
  }

  public function testProjectModifGuestOwnerOtherNotIn()
  {

    $this->setUp();
    $project = $this->em->getRepository('metaProjectBundle:StandardProject')->findOneByName("test_guest_project_not_in");
    $this->tearDown();

    $client = static::createClientWithAuthentication("other_test"); 

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('security.csrf.token_manager')->getToken('edit')->getValue(),
        array( "name" => "name", "value" => "test_guest_project_not_in_TEST". rand())
    );

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('security.csrf.token_manager')->getToken('edit')->getValue(),
        array( "name" => "name", "value" => "test_guest_project_not_in")
    );

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('security.csrf.token_manager')->getToken('edit')->getValue(),
        array( "name" => "about", "value" => "test" . rand())
    );

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('security.csrf.token_manager')->getToken('edit')->getValue(),
        array( "name" => "skills", "value" => "add", "key" => "management")
    );

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('security.csrf.token_manager')->getToken('edit')->getValue(),
        array( "name" => "skills", "value" => "remove", "key" => "management")
    );

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('security.csrf.token_manager')->getToken('edit')->getValue(),
        array( "name" => "status", "value" => "0")
    );

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );
  }

  public function testProjectModifParticipant()
  {

    $this->setUp();
    $project = $this->em->getRepository('metaProjectBundle:StandardProject')->findOneByName("test_project_community_participant");
    $this->tearDown();

    $client = static::createClientWithAuthentication("test"); 

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('security.csrf.token_manager')->getToken('edit')->getValue(),
        array( "name" => "name", "value" => "test_project_community_participant_TEST". rand())
    );

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('security.csrf.token_manager')->getToken('edit')->getValue(),
        array( "name" => "name", "value" => "test_project_community_participant")
    );

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('security.csrf.token_manager')->getToken('edit')->getValue(),
        array( "name" => "about", "value" => "test" . rand())
    );

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('security.csrf.token_manager')->getToken('edit')->getValue(),
        array( "name" => "skills", "value" => "add", "key" => "management")
    );

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('security.csrf.token_manager')->getToken('edit')->getValue(),
        array( "name" => "skills", "value" => "remove", "key" => "management")
    );

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('security.csrf.token_manager')->getToken('edit')->getValue(),
        array( "name" => "status", "value" => "0")
    );

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );
  }

  public function testProjectModifOtherNotIn()
  {

    $this->setUp();
    $project = $this->em->getRepository('metaProjectBundle:StandardProject')->findOneByName("test_project_community_not_in");
    $this->tearDown();

    $client = static::createClientWithAuthentication("test"); 

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('security.csrf.token_manager')->getToken('edit')->getValue(),
        array( "name" => "name", "value" => "test_project_community_not_in_TEST". rand())
    );

    $this->assertEquals(
        Response::HTTP_NOT_ACCEPTABLE,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('security.csrf.token_manager')->getToken('edit')->getValue(),
        array( "name" => "about", "value" => "test" . rand())
    );

    $this->assertEquals(
        Response::HTTP_NOT_ACCEPTABLE,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('security.csrf.token_manager')->getToken('edit')->getValue(),
        array( "name" => "skills", "value" => "add", "key" => "management")
    );

    $this->assertEquals(
        Response::HTTP_NOT_ACCEPTABLE,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('security.csrf.token_manager')->getToken('edit')->getValue(),
        array( "name" => "skills", "value" => "remove", "key" => "management")
    );

    $this->assertEquals(
        Response::HTTP_NOT_ACCEPTABLE,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('security.csrf.token_manager')->getToken('edit')->getValue(),
        array( "name" => "status", "value" => "0")
    );

    $this->assertEquals(
        Response::HTTP_NOT_ACCEPTABLE,
        $client->getResponse()->getStatusCode()
    );
  }

  public function testProjectModifOtherNotInPrivate()
  {

    $this->setUp();
    $project = $this->em->getRepository('metaProjectBundle:StandardProject')->findOneByName("test_project_community_not_in_private");
    $this->tearDown();

    $client = static::createClientWithAuthentication("test"); 

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('security.csrf.token_manager')->getToken('edit')->getValue(),
        array( "name" => "name", "value" => "test_project_community_not_in_private_TEST". rand())
    );

    $this->assertEquals(
        Response::HTTP_NOT_FOUND,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('security.csrf.token_manager')->getToken('edit')->getValue(),
        array( "name" => "about", "value" => "test" . rand())
    );

    $this->assertEquals(
        Response::HTTP_NOT_FOUND,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('security.csrf.token_manager')->getToken('edit')->getValue(),
        array( "name" => "skills", "value" => "add", "key" => "management")
    );

    $this->assertEquals(
        Response::HTTP_NOT_FOUND,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('security.csrf.token_manager')->getToken('edit')->getValue(),
        array( "name" => "skills", "value" => "remove", "key" => "management")
    );

    $this->assertEquals(
        Response::HTTP_NOT_FOUND,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('security.csrf.token_manager')->getToken('edit')->getValue(),
        array( "name" => "status", "value" => "0")
    );

    $this->assertEquals(
        Response::HTTP_NOT_FOUND,
        $client->getResponse()->getStatusCode()
    );
  }

  public function testProjectPrivatePublic()
  {

    $this->setUp();
    $project = $this->em->getRepository('metaProjectBundle:StandardProject')->findOneByName("test_project_community_owner");
    $this->tearDown();

    $client = static::createClientWithAuthentication("test");
    $tokenPrivate = $client->getContainer()->get('security.csrf.token_manager')->getToken('makePrivate')->getValue();
    $tokenPublic = $client->getContainer()->get('security.csrf.token_manager')->getToken('makePublic')->getValue();

    $client->request(
        'GET',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/make/private?token=' . $tokenPrivate
    );

    $this->setUp();
    $project = $this->em->getRepository('metaProjectBundle:StandardProject')->findOneByName("test_project_community_owner");
    $this->tearDown();

    $this->assertTrue(
        $project->isPrivate()
    );

    $client->request(
        'GET',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/make/public?token=' . $tokenPublic
    );

    $this->setUp();
    $project = $this->em->getRepository('metaProjectBundle:StandardProject')->findOneByName("test_project_community_owner");
    $this->tearDown();

    $this->assertFalse(
        $project->isPrivate()
    );
  }

  public function testProjectPrivatePublicNotOwner()
  {

    $this->setUp();
    $project = $this->em->getRepository('metaProjectBundle:StandardProject')->findOneByName("test_project_community_owner");
    $this->tearDown();

    $client = static::createClientWithAuthentication("other_test");

    $client->request(
        'GET',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/make/private?token=' . $client->getContainer()->get('security.csrf.token_manager')->getToken('makePrivate')->getValue()
    );

    $this->setUp();
    $project = $this->em->getRepository('metaProjectBundle:StandardProject')->findOneByName("test_project_community_owner");
    $this->tearDown();

    $this->assertFalse(
        $project->isPrivate()
    );

    $this->setUp();
    $project = $this->em->getRepository('metaProjectBundle:StandardProject')->findOneByName("test_project_community_participant");
    $this->tearDown();

    $client->reload();
    $client = static::createClientWithAuthentication("test");

    $client->request(
        'GET',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/make/private?token=' . $client->getContainer()->get('security.csrf.token_manager')->getToken('makePrivate')->getValue()
    );

    $this->assertFalse(
        $project->isPrivate()
    );

  }

  public function testProjectCommentOwner()
  {

    $this->setUp();
    $project = $this->em->getRepository('metaProjectBundle:StandardProject')->findOneByName("test_project_community_owner");
    $this->tearDown();

    $client = static::createClientWithAuthentication("test");
    $comment = "comment" . mt_rand() . " - " . mt_rand();

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/comment?token=' . $client->getContainer()->get('security.csrf.token_manager')->getToken('comment')->getValue(),
        array( "comment" => $comment )
    );

    $crawler = $client->request(
        'GET',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId())
    );
    $client->reload();

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $this->assertCount(1, $crawler->filter('p:contains("'.$comment.'")'));

  }

  public function testProjectCommentOwnerPrivate()
  {

    $this->setUp();
    $project = $this->em->getRepository('metaProjectBundle:StandardProject')->findOneByName("test_project_community_owner_private");
    $this->tearDown();

    $client = static::createClientWithAuthentication("test");
    $comment = "comment" . mt_rand() . " - " . mt_rand();

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/comment?token=' . $client->getContainer()->get('security.csrf.token_manager')->getToken('comment')->getValue(),
        array( "comment" => $comment )
    );

    $crawler = $client->request(
        'GET',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId())
    );
    $client->reload();

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $this->assertCount(1, $crawler->filter('p:contains("'.$comment.'")'));

  }

  public function testProjectCommentParticipant()
  {

    $this->setUp();
    $project = $this->em->getRepository('metaProjectBundle:StandardProject')->findOneByName("test_project_community_participant");
    $this->tearDown();

    $client = static::createClientWithAuthentication("test");
    $comment = "comment" . mt_rand() . " - " . mt_rand();

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/comment?token=' . $client->getContainer()->get('security.csrf.token_manager')->getToken('comment')->getValue(),
        array( "comment" => $comment )
    );

    $crawler = $client->request(
        'GET',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId())
    );
    $client->reload();

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $this->assertCount(1, $crawler->filter('p:contains("'.$comment.'")'));

  }

  public function testProjectCommentNotIn()
  {

    $this->setUp();
    $project = $this->em->getRepository('metaProjectBundle:StandardProject')->findOneByName("test_project_community_not_in");
    $this->tearDown();

    $client = static::createClientWithAuthentication("test");
    $comment = "comment" . mt_rand() . " - " . mt_rand();

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/comment?token=' . $client->getContainer()->get('security.csrf.token_manager')->getToken('comment')->getValue(),
        array( "comment" => $comment )
    );

    $crawler = $client->request(
        'GET',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId())
    );
    $client->reload();

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $this->assertCount(1, $crawler->filter('p:contains("'.$comment.'")'));

  }

  public function testProjectCommentNotInPrivate()
  {

    $this->setUp();
    $project = $this->em->getRepository('metaProjectBundle:StandardProject')->findOneByName("test_project_community_not_in_private");
    $this->tearDown();

    $client = static::createClientWithAuthentication("test");
    $comment = "comment" . mt_rand() . " - " . mt_rand();

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/comment?token=' . $client->getContainer()->get('security.csrf.token_manager')->getToken('comment')->getValue(),
        array( "comment" => $comment )
    );

    $this->assertEquals(
        Response::HTTP_NOT_FOUND,
        $client->getResponse()->getStatusCode()
    );

    $crawler = $client->request(
        'GET',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId())
    );
    $client->reload();

    $this->assertEquals(
        Response::HTTP_NOT_FOUND,
        $client->getResponse()->getStatusCode()
    );

  }

  public function testProjectCommentGuest()
  {

    $this->setUp();
    $project = $this->em->getRepository('metaProjectBundle:StandardProject')->findOneByName("test_guest_project");
    $this->tearDown();

    $client = static::createClientWithAuthentication("test");
    $comment = "comment" . mt_rand() . " - " . mt_rand();

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/comment?token=' . $client->getContainer()->get('security.csrf.token_manager')->getToken('comment')->getValue(),
        array( "comment" => $comment )
    );
    $crawler = $client->request(
        'GET',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId())
    );
    $client->reload();

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $this->assertCount(1, $crawler->filter('p:contains("'.$comment.'")'));

  }

  public function testProjectCommentGuestNotIn()
  {

    $this->setUp();
    $project = $this->em->getRepository('metaProjectBundle:StandardProject')->findOneByName("test_guest_project_not_in");
    $this->tearDown();

    $client = static::createClientWithAuthentication("test");
    $comment = "comment" . mt_rand() . " - " . mt_rand();

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/comment?token=' . $client->getContainer()->get('security.csrf.token_manager')->getToken('comment')->getValue(),
        array( "comment" => $comment )
    );

    $this->assertEquals(
        Response::HTTP_NOT_FOUND,
        $client->getResponse()->getStatusCode()
    );

    $crawler = $client->request(
        'GET',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId())
    );
    $client->reload();

    $this->assertEquals(
        Response::HTTP_NOT_FOUND,
        $client->getResponse()->getStatusCode()
    );

  }

  public function testProjectCommentOut()
  {

    $this->setUp();
    $project = $this->em->getRepository('metaProjectBundle:StandardProject')->findOneByName("test_out_project");
    $this->tearDown();

    $client = static::createClientWithAuthentication("test");
    $comment = "comment" . mt_rand() . " - " . mt_rand();

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/comment?token=' . $client->getContainer()->get('security.csrf.token_manager')->getToken('comment')->getValue(),
        array( "comment" => $comment )
    );

    $this->assertEquals(
        Response::HTTP_NOT_FOUND,
        $client->getResponse()->getStatusCode()
    );

    $crawler = $client->request(
        'GET',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId())
    );
    $client->reload();

    $this->assertEquals(
        Response::HTTP_NOT_FOUND,
        $client->getResponse()->getStatusCode()
    );

  }

  public function testProjectAddUserPrivateSpace()
  {

    $client = static::createClientWithAuthentication("test");
    $tokenAdd = $client->getContainer()->get('security.csrf.token_manager')->getToken('addParticipantOrOwner')->getValue();
    // add other_test as Owner

    $this->setUp();
    $project = $this->em->getRepository('metaProjectBundle:StandardProject')->findOneByName("test_project_private_space");
    $this->tearDown();

    $crawler = $client->request('GET', '/app/switch/privatespace', array('token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('switchCommunity')->getValue()));

    $targetOwnerAsBase64 = array('slug' => 'metaProjectBundle:Info:addParticipantOrOwner', 'external' => true, 'params' => array('uid' => $client->getContainer()->get('uid')->toUId($project->getId()),'owner' => true, 'guest' => false));
    $base64 = base64_encode(json_encode($targetOwnerAsBase64));

    $crawler = $client->request('POST', 
      '/app/people/choose/' . $base64 . '?token=' . $tokenAdd,
      array('mailOrUsername' => 'other_test')
    );

    $this->assertEquals(
        Response::HTTP_NOT_FOUND,
        $client->getResponse()->getStatusCode()
    );

    $this->setUp();
    $project = $this->em->getRepository('metaProjectBundle:StandardProject')->findOneByName("test_project_private_space");
    $this->tearDown();

    $this->assertEquals(
      1,
      count($project->getOwners())
    );

    // add other_test as Participant
    
    $this->setUp();
    $project = $this->em->getRepository('metaProjectBundle:StandardProject')->findOneByName("test_project_private_space");
    $this->tearDown();

    $crawler = $client->request('GET', '/app/switch/privatespace', array('token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('switchCommunity')->getValue()));

    $targetParticipantAsBase64 = array('slug' => 'metaProjectBundle:Info:addParticipantOrOwner', 'external' => true, 'params' => array('uid' => $client->getContainer()->get('uid')->toUId($project->getId()),'owner' => false, 'guest' => false));
    $base64 = base64_encode(json_encode($targetParticipantAsBase64));

    $crawler = $client->request('POST', 
      '/app/people/choose/' . $base64 . '?token=' . $tokenAdd,
      array('mailOrUsername' => 'other_test')
    );

    $this->assertEquals(
        Response::HTTP_NOT_FOUND,
        $client->getResponse()->getStatusCode()
    );

    $this->setUp();
    $project = $this->em->getRepository('metaProjectBundle:StandardProject')->findOneByName("test_project_private_space");
    $this->tearDown();

    $this->assertEquals(
      0,
      count($project->getParticipants())
    );

  }

  public function testProjectRemoveUserPrivateSpace()
  {

    $client = static::createClientWithAuthentication("test");
    $tokenRemove = $client->getContainer()->get('security.csrf.token_manager')->getToken('removeParticipantOrOwner')->getValue();
    $tokenRemoveMySelf = $client->getContainer()->get('security.csrf.token_manager')->getToken('removeMySelfParticipant')->getValue();
    // remove other_test as Owner

    $this->setUp();
    $project = $this->em->getRepository('metaProjectBundle:StandardProject')->findOneByName("test_project_private_space");
    $this->tearDown();

    $crawler = $client->request('GET', '/app/switch/privatespace', array('token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('switchCommunity')->getValue()));

    $targetOwnerAsBase64 = array('slug' => 'metaProjectBundle:Info:removeParticipantOrOwner', 'external' => true, 'params' => array('uid' => $client->getContainer()->get('uid')->toUId($project->getId()),'owner' => true, 'guest' => false));
    $base64 = base64_encode(json_encode($targetOwnerAsBase64));

    $crawler = $client->request('GET', 
      '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/team/remove/other_test/owner?token=' . $tokenRemove
    );

    $this->assertTrue(
        $client->getResponse()->isRedirect('/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/info')
    );

    $this->setUp();
    $project = $this->em->getRepository('metaProjectBundle:StandardProject')->findOneByName("test_project_private_space");
    $this->tearDown();

    $this->assertEquals(
      1,
      count($project->getOwners())
    );

    // remove test as Owner

    $this->setUp();
    $project = $this->em->getRepository('metaProjectBundle:StandardProject')->findOneByName("test_project_private_space");
    $this->tearDown();

    $crawler = $client->request('GET', '/app/switch/privatespace', array('token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('switchCommunity')->getValue()));

    $targetOwnerAsBase64 = array('slug' => 'metaProjectBundle:Info:removeParticipantOrOwner', 'external' => true, 'params' => array('uid' => $client->getContainer()->get('uid')->toUId($project->getId()),'owner' => true, 'guest' => false));
    $base64 = base64_encode(json_encode($targetOwnerAsBase64));

    $crawler = $client->request('GET', 
      '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/team/remove/test/owner?token=' . $tokenRemove
    );

    $this->assertTrue(
        $client->getResponse()->isRedirect('/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/info')
    );

    $this->setUp();
    $project = $this->em->getRepository('metaProjectBundle:StandardProject')->findOneByName("test_project_private_space");
    $this->tearDown();

    $this->assertEquals(
      1,
      count($project->getOwners())
    );


    // remove test as Owner (removeMyselfParticipant)

    $this->setUp();
    $project = $this->em->getRepository('metaProjectBundle:StandardProject')->findOneByName("test_project_private_space");
    $this->tearDown();

    $crawler = $client->request('GET', '/app/switch/privatespace', array('token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('switchCommunity')->getValue()));

    $targetOwnerAsBase64 = array('slug' => 'metaProjectBundle:Info:removeMySelfParticipant', 'external' => true, 'params' => array('uid' => $client->getContainer()->get('uid')->toUId($project->getId()),'owner' => false, 'guest' => false));
    $base64 = base64_encode(json_encode($targetOwnerAsBase64));

    $crawler = $client->request('GET', 
      '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/team/remove/other_test/participant/self?token=' . $tokenRemoveMySelf
    );

    $this->assertTrue(
        $client->getResponse()->isRedirect('/app/projects')
    );

    $this->setUp();
    $project = $this->em->getRepository('metaProjectBundle:StandardProject')->findOneByName("test_project_private_space");
    $this->tearDown();

    $this->assertEquals(
      0,
      count($project->getParticipants())
    );


    // remove other_test as Participant
    
    $this->setUp();
    $project = $this->em->getRepository('metaProjectBundle:StandardProject')->findOneByName("test_project_private_space");
    $this->tearDown();

    $crawler = $client->request('GET', '/app/switch/privatespace', array('token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('switchCommunity')->getValue()));

    $targetParticipantAsBase64 = array('slug' => 'metaProjectBundle:Info:removeParticipantOrOwner', 'external' => true, 'params' => array('uid' => $client->getContainer()->get('uid')->toUId($project->getId()),'owner' => false, 'guest' => false));
    $base64 = base64_encode(json_encode($targetParticipantAsBase64));

    $crawler = $client->request('GET', 
      '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/team/remove/other_test/owner?token=' . $tokenRemove
    );

    $this->assertTrue(
        $client->getResponse()->isRedirect('/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/info')
    );

    $this->setUp();
    $project = $this->em->getRepository('metaProjectBundle:StandardProject')->findOneByName("test_project_private_space");
    $this->tearDown();

    $this->assertEquals(
      0,
      count($project->getParticipants())
    );

  }


  public function testProjectAddUserOwner()
  {

    $client = static::createClientWithAuthentication("test");
    $tokenAdd = $client->getContainer()->get('security.csrf.token_manager')->getToken('addParticipantOrOwner')->getValue();
    $tokenRemove = $client->getContainer()->get('security.csrf.token_manager')->getToken('removeParticipantOrOwner')->getValue();
    
    // add other_test as Owner

    $this->setUp();
    $project = $this->em->getRepository('metaProjectBundle:StandardProject')->findOneByName("test_project_community_owner");
    $community = $this->em->getRepository('metaGeneralBundle:Community\Community')->findOneByName("test_in");
    $this->tearDown();

    $crawler = $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('switchCommunity')->getValue()));

    $targetOwnerAsBase64 = array('slug' => 'metaProjectBundle:Info:addParticipantOrOwner', 'external' => true, 'params' => array('uid' => $client->getContainer()->get('uid')->toUId($project->getId()),'owner' => true, 'guest' => false));
    $base64 = base64_encode(json_encode($targetOwnerAsBase64));

    $crawler = $client->request('POST', 
      '/app/people/choose/' . $base64 . '?token=' . $tokenAdd,
      array("username" => "other_test")
    );

    $this->assertTrue(
        $client->getResponse()->isRedirect('/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/info')
    );

    $this->setUp();
    $project = $this->em->getRepository('metaProjectBundle:StandardProject')->findOneByName("test_project_community_owner");
    $this->tearDown();

    $this->assertEquals(
      2,
      count($project->getOwners())
    );
  

    // remove other_test as Owner

    $this->setUp();
    $project = $this->em->getRepository('metaProjectBundle:StandardProject')->findOneByName("test_project_community_owner");
    $this->tearDown();

    $crawler = $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('switchCommunity')->getValue()));

    $targetOwnerAsBase64 = array('slug' => 'metaProjectBundle:Info:removeParticipantOrOwner', 'external' => true, 'params' => array('uid' => $client->getContainer()->get('uid')->toUId($project->getId()),'owner' => true, 'guest' => false));
    $base64 = base64_encode(json_encode($targetOwnerAsBase64));

    $crawler = $client->request('GET', 
      '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/team/remove/other_test/owner?token=' . $tokenRemove
    );

    $this->assertTrue(
        $client->getResponse()->isRedirect('/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/info')
    );

    $this->setUp();
    $project = $this->em->getRepository('metaProjectBundle:StandardProject')->findOneByName("test_project_community_owner");
    $this->tearDown();

    $this->assertEquals(
      1,
      count($project->getOwners())
    );
  
  }

  public function testProjectAddUserParticipant()
  {

    $client = static::createClientWithAuthentication("test");
    $tokenAdd = $client->getContainer()->get('security.csrf.token_manager')->getToken('addParticipantOrOwner')->getValue();
    $tokenRemove = $client->getContainer()->get('security.csrf.token_manager')->getToken('removeParticipantOrOwner')->getValue();
    
    // add other_test as participant

    $this->setUp();
    $project = $this->em->getRepository('metaProjectBundle:StandardProject')->findOneByName("test_project_community_owner");
    $community = $this->em->getRepository('metaGeneralBundle:Community\Community')->findOneByName("test_in");
    $this->tearDown();

    $crawler = $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('switchCommunity')->getValue()));

    $targetOwnerAsBase64 = array('slug' => 'metaProjectBundle:Info:addParticipantOrOwner', 'external' => true, 'params' => array('uid' => $client->getContainer()->get('uid')->toUId($project->getId()),'owner' => false, 'guest' => false));
    $base64 = base64_encode(json_encode($targetOwnerAsBase64));

    $crawler = $client->request('POST', 
      '/app/people/choose/' . $base64 . '?token=' . $tokenAdd,
      array("username" => "other_test")
    );

    $this->assertTrue(
        $client->getResponse()->isRedirect('/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/info')
    );

    $this->setUp();
    $project = $this->em->getRepository('metaProjectBundle:StandardProject')->findOneByName("test_project_community_owner");
    $this->tearDown();

    $this->assertEquals(
      1,
      count($project->getParticipants())
    );
  

    // remove other_test as participant

    $this->setUp();
    $project = $this->em->getRepository('metaProjectBundle:StandardProject')->findOneByName("test_project_community_owner");
    $this->tearDown();

    $crawler = $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('switchCommunity')->getValue()));

    $targetOwnerAsBase64 = array('slug' => 'metaProjectBundle:Info:removeParticipantOrOwner', 'external' => true, 'params' => array('uid' => $client->getContainer()->get('uid')->toUId($project->getId()),'owner' => false, 'guest' => false));
    $base64 = base64_encode(json_encode($targetOwnerAsBase64));

    $crawler = $client->request('GET', 
      '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/team/remove/other_test/participant?token=' . $tokenRemove
    );

    $this->assertTrue(
        $client->getResponse()->isRedirect('/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/info')
    );

    $this->setUp();
    $project = $this->em->getRepository('metaProjectBundle:StandardProject')->findOneByName("test_project_community_owner");
    $this->tearDown();

    $this->assertEquals(
      0,
      count($project->getParticipants())
    );
  
  }


  public function testProjectAddUserParticipantAndUpgradeOwner()
  {

    $client = static::createClientWithAuthentication("test");
    $tokenAdd = $client->getContainer()->get('security.csrf.token_manager')->getToken('addParticipantOrOwner')->getValue();
    $tokenRemove = $client->getContainer()->get('security.csrf.token_manager')->getToken('removeParticipantOrOwner')->getValue();
    
    // add other_test as participant

    $this->setUp();
    $project = $this->em->getRepository('metaProjectBundle:StandardProject')->findOneByName("test_project_community_owner");
    $community = $this->em->getRepository('metaGeneralBundle:Community\Community')->findOneByName("test_in");
    $this->tearDown();

    $crawler = $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('switchCommunity')->getValue()));

    $targetOwnerAsBase64 = array('slug' => 'metaProjectBundle:Info:addParticipantOrOwner', 'external' => true, 'params' => array('uid' => $client->getContainer()->get('uid')->toUId($project->getId()),'owner' => false, 'guest' => false));
    $base64 = base64_encode(json_encode($targetOwnerAsBase64));

    $crawler = $client->request('POST', 
      '/app/people/choose/' . $base64 . '?token=' . $tokenAdd,
      array("username" => "other_test")
    );

    $this->assertTrue(
        $client->getResponse()->isRedirect('/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/info')
    );

    $this->setUp();
    $project = $this->em->getRepository('metaProjectBundle:StandardProject')->findOneByName("test_project_community_owner");
    $this->tearDown();

    $this->assertEquals(
      1,
      count($project->getParticipants())
    );
  
    // add other_test as owner

    $this->setUp();
    $project = $this->em->getRepository('metaProjectBundle:StandardProject')->findOneByName("test_project_community_owner");
    $community = $this->em->getRepository('metaGeneralBundle:Community\Community')->findOneByName("test_in");
    $this->tearDown();

    $crawler = $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('switchCommunity')->getValue()));

    $targetOwnerAsBase64 = array('slug' => 'metaProjectBundle:Info:addParticipantOrOwner', 'external' => true, 'params' => array('uid' => $client->getContainer()->get('uid')->toUId($project->getId()),'owner' => true, 'guest' => false));
    $base64 = base64_encode(json_encode($targetOwnerAsBase64));

    $crawler = $client->request('POST', 
      '/app/people/choose/' . $base64 . '?token=' . $tokenAdd,
      array("username" => "other_test")
    );

    $this->assertTrue(
        $client->getResponse()->isRedirect('/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/info')
    );

    $this->setUp();
    $project = $this->em->getRepository('metaProjectBundle:StandardProject')->findOneByName("test_project_community_owner");
    $this->tearDown();

    $this->assertEquals(
      0,
      count($project->getParticipants())
    );
  
    $this->assertEquals(
      2,
      count($project->getOwners())
    );
  
    // remove other_test as owner

    $this->setUp();
    $project = $this->em->getRepository('metaProjectBundle:StandardProject')->findOneByName("test_project_community_owner");
    $this->tearDown();

    $crawler = $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('switchCommunity')->getValue()));

    $targetOwnerAsBase64 = array('slug' => 'metaProjectBundle:Info:removeParticipantOrOwner', 'external' => true, 'params' => array('uid' => $client->getContainer()->get('uid')->toUId($project->getId()),'owner' => true, 'guest' => false));
    $base64 = base64_encode(json_encode($targetOwnerAsBase64));

    $crawler = $client->request('GET', 
      '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/team/remove/other_test/owner?token=' . $tokenRemove
    );

    $this->assertTrue(
        $client->getResponse()->isRedirect('/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/info')
    );

    $this->setUp();
    $project = $this->em->getRepository('metaProjectBundle:StandardProject')->findOneByName("test_project_community_owner");
    $this->tearDown();

    $this->assertEquals(
      0,
      count($project->getParticipants())
    );

    $this->assertEquals(
      1,
      count($project->getOwners())
    );
  
  }


  public function testProjectAddSelfOwnerAndParticipant()
  {

    $client = static::createClientWithAuthentication("test");
    $tokenAdd = $client->getContainer()->get('security.csrf.token_manager')->getToken('addParticipantOrOwner')->getValue();
    $tokenRemove = $client->getContainer()->get('security.csrf.token_manager')->getToken('removeParticipantOrOwner')->getValue();
    
    // add test as owner

    $this->setUp();
    $project = $this->em->getRepository('metaProjectBundle:StandardProject')->findOneByName("test_project_community_owner");
    $community = $this->em->getRepository('metaGeneralBundle:Community\Community')->findOneByName("test_in");
    $this->tearDown();

    $crawler = $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('switchCommunity')->getValue()));

    $targetOwnerAsBase64 = array('slug' => 'metaProjectBundle:Info:addParticipantOrOwner', 'external' => true, 'params' => array('uid' => $client->getContainer()->get('uid')->toUId($project->getId()),'owner' => true, 'guest' => false));
    $base64 = base64_encode(json_encode($targetOwnerAsBase64));

    $crawler = $client->request('POST', 
      '/app/people/choose/' . $base64 . '?token=' . $tokenAdd,
      array("username" => "test")
    );

    $this->assertTrue(
        $client->getResponse()->isRedirect('/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/info')
    );

    $this->setUp();
    $project = $this->em->getRepository('metaProjectBundle:StandardProject')->findOneByName("test_project_community_owner");
    $this->tearDown();

    $this->assertEquals(
      1,
      count($project->getOwners())
    );
  

    // add test as participant

    $this->setUp();
    $project = $this->em->getRepository('metaProjectBundle:StandardProject')->findOneByName("test_project_community_owner");
    $community = $this->em->getRepository('metaGeneralBundle:Community\Community')->findOneByName("test_in");
    $this->tearDown();

    $crawler = $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('switchCommunity')->getValue()));

    $targetOwnerAsBase64 = array('slug' => 'metaProjectBundle:Info:addParticipantOrOwner', 'external' => true, 'params' => array('uid' => $client->getContainer()->get('uid')->toUId($project->getId()),'owner' => false, 'guest' => false));
    $base64 = base64_encode(json_encode($targetOwnerAsBase64));

    $crawler = $client->request('POST', 
      '/app/people/choose/' . $base64 . '?token=' . $tokenAdd,
      array("username" => "test")
    );

    $this->assertTrue(
        $client->getResponse()->isRedirect('/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/info')
    );

    $this->setUp();
    $project = $this->em->getRepository('metaProjectBundle:StandardProject')->findOneByName("test_project_community_owner");
    $this->tearDown();

    $this->assertEquals(
      1,
      count($project->getOwners())
    );
  
    $this->assertEquals(
      0,
      count($project->getParticipants())
    );
  }


  public function testProjectAddStrangerFromCommunity()
  {
    

    $client = static::createClientWithAuthentication("other_test");
    $tokenAdd = $client->getContainer()->get('security.csrf.token_manager')->getToken('addParticipantOrOwner')->getValue();
    $tokenRemove = $client->getContainer()->get('security.csrf.token_manager')->getToken('removeParticipantOrOwner')->getValue();
    
    // add test as participant

    $this->setUp();
    $project = $this->em->getRepository('metaProjectBundle:StandardProject')->findOneByName("test_out_project");
    $community = $this->em->getRepository('metaGeneralBundle:Community\Community')->findOneByName("test_out");
    $this->tearDown();

    $crawler = $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('switchCommunity')->getValue()));

    $targetOwnerAsBase64 = array('slug' => 'metaProjectBundle:Info:addParticipantOrOwner', 'external' => true, 'params' => array('uid' => $client->getContainer()->get('uid')->toUId($project->getId()),'owner' => false, 'guest' => false));
    $base64 = base64_encode(json_encode($targetOwnerAsBase64));

    $crawler = $client->request('POST', 
      '/app/people/choose/' . $base64 . '?token=' . $tokenAdd,
      array("username" => "test")
    );

    $this->assertTrue(
        $client->getResponse()->isRedirect('/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/info')
    );

    $this->setUp();
    $project = $this->em->getRepository('metaProjectBundle:StandardProject')->findOneByName("test_out_project");
    $this->tearDown();

    $this->assertEquals(
      1,
      count($project->getParticipants())
    );

    // add test as owner

    $this->setUp();
    $project = $this->em->getRepository('metaProjectBundle:StandardProject')->findOneByName("test_out_project");
    $community = $this->em->getRepository('metaGeneralBundle:Community\Community')->findOneByName("test_out");
    $this->tearDown();

    $crawler = $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('switchCommunity')->getValue()));

    $targetOwnerAsBase64 = array('slug' => 'metaProjectBundle:Info:addParticipantOrOwner', 'external' => true, 'params' => array('uid' => $client->getContainer()->get('uid')->toUId($project->getId()),'owner' => true, 'guest' => false));
    $base64 = base64_encode(json_encode($targetOwnerAsBase64));

    $crawler = $client->request('POST', 
      '/app/people/choose/' . $base64 . '?token=' . $tokenAdd,
      array("username" => "test")
    );

    $this->assertTrue(
        $client->getResponse()->isRedirect('/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/info')
    );

    $this->setUp();
    $project = $this->em->getRepository('metaProjectBundle:StandardProject')->findOneByName("test_out_project");
    $this->tearDown();

    $this->assertEquals(
      2,
      count($project->getOwners())
    );
  
    $this->assertEquals(
      0,
      count($project->getParticipants())
    );

    // remove test as owner

    $this->setUp();
    $project = $this->em->getRepository('metaProjectBundle:StandardProject')->findOneByName("test_out_project");
    $this->tearDown();

    $crawler = $client->request('GET', '/app/community/switch/0' . $client->getContainer()->get('uid')->toUId($community->getId()), array('token' => $client->getContainer()->get('security.csrf.token_manager')->getToken('switchCommunity')->getValue()));

    $targetOwnerAsBase64 = array('slug' => 'metaProjectBundle:Info:removeParticipantOrOwner', 'external' => true, 'params' => array('uid' => $client->getContainer()->get('uid')->toUId($project->getId()),'owner' => true, 'guest' => false));
    $base64 = base64_encode(json_encode($targetOwnerAsBase64));

    $crawler = $client->request('GET', 
      '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/team/remove/test/owner?token=' . $tokenRemove
    );

    $this->assertTrue(
        $client->getResponse()->isRedirect('/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/info')
    );

    $this->setUp();
    $project = $this->em->getRepository('metaProjectBundle:StandardProject')->findOneByName("test_out_project");
    $this->tearDown();

    $this->assertEquals(
      0,
      count($project->getParticipants())
    );

    $this->assertEquals(
      1,
      count($project->getOwners())
    );
  
  }

}
