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

  public function testProjectWatch()
  {

    $this->setUp();
    $project = $this->em->getRepository('metaProjectBundle:StandardProject')->findOneByName("test_project_community_owner");
    $this->tearDown();

    $client = static::createClientWithAuthentication("test");

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/watch?token=' . $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('watch')
    );

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/unwatch?token=' . $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('unwatch')
    );

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/unwatch?token=' . $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('unwatch')
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
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/watch?token=' . $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('watch')
    );

    $this->assertEquals(
        Response::HTTP_NOT_FOUND,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/unwatch?token=' . $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('unwatch')
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
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/watch?token=' . $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('watch')
    );

    $this->assertEquals(
        Response::HTTP_NOT_FOUND,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/unwatch?token=' . $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('unwatch')
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
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('edit'),
        array( "name" => "name", "value" => "test_project_community_owner_TEST". rand())
    );

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('edit'),
        array( "name" => "name", "value" => "test_project_community_owner")
    );

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('edit'),
        array( "name" => "about", "value" => "test" . rand())
    );

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('edit'),
        array( "name" => "skills", "value" => "add", "key" => "management")
    );

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('edit'),
        array( "name" => "skills", "value" => "remove", "key" => "management")
    );

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('edit'),
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
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('edit'),
        array( "name" => "name", "value" => "test_project_community_owner_TEST". rand())
    );

    $this->assertEquals(
        Response::HTTP_NOT_ACCEPTABLE,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('edit'),
        array( "name" => "about", "value" => "test" . rand())
    );

    $this->assertEquals(
        Response::HTTP_NOT_ACCEPTABLE,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('edit'),
        array( "name" => "skills", "value" => "add", "key" => "management")
    );

    $this->assertEquals(
        Response::HTTP_NOT_ACCEPTABLE,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('edit'),
        array( "name" => "skills", "value" => "remove", "key" => "management")
    );

    $this->assertEquals(
        Response::HTTP_NOT_ACCEPTABLE,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('edit'),
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
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('edit'),
        array( "name" => "name", "value" => "test_guest_project_TEST". rand())
    );

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('edit'),
        array( "name" => "name", "value" => "test_guest_project")
    );

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('edit'),
        array( "name" => "about", "value" => "test" . rand())
    );

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('edit'),
        array( "name" => "skills", "value" => "add", "key" => "management")
    );

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('edit'),
        array( "name" => "skills", "value" => "remove", "key" => "management")
    );

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('edit'),
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
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('edit'),
        array( "name" => "name", "value" => "test_guest_project_TEST". rand())
    );

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('edit'),
        array( "name" => "name", "value" => "test_guest_project")
    );

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('edit'),
        array( "name" => "about", "value" => "test" . rand())
    );

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('edit'),
        array( "name" => "skills", "value" => "add", "key" => "management")
    );

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('edit'),
        array( "name" => "skills", "value" => "remove", "key" => "management")
    );

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('edit'),
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
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('edit'),
        array( "name" => "name", "value" => "test_guest_project_not_in_TEST". rand())
    );

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('edit'),
        array( "name" => "name", "value" => "test_guest_project_not_in")
    );

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('edit'),
        array( "name" => "about", "value" => "test" . rand())
    );

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('edit'),
        array( "name" => "skills", "value" => "add", "key" => "management")
    );

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('edit'),
        array( "name" => "skills", "value" => "remove", "key" => "management")
    );

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('edit'),
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
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('edit'),
        array( "name" => "name", "value" => "test_project_community_participant_TEST". rand())
    );

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('edit'),
        array( "name" => "name", "value" => "test_project_community_participant")
    );

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('edit'),
        array( "name" => "about", "value" => "test" . rand())
    );

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('edit'),
        array( "name" => "skills", "value" => "add", "key" => "management")
    );

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('edit'),
        array( "name" => "skills", "value" => "remove", "key" => "management")
    );

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('edit'),
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
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('edit'),
        array( "name" => "name", "value" => "test_project_community_not_in_TEST". rand())
    );

    $this->assertEquals(
        Response::HTTP_NOT_ACCEPTABLE,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('edit'),
        array( "name" => "about", "value" => "test" . rand())
    );

    $this->assertEquals(
        Response::HTTP_NOT_ACCEPTABLE,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('edit'),
        array( "name" => "skills", "value" => "add", "key" => "management")
    );

    $this->assertEquals(
        Response::HTTP_NOT_ACCEPTABLE,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('edit'),
        array( "name" => "skills", "value" => "remove", "key" => "management")
    );

    $this->assertEquals(
        Response::HTTP_NOT_ACCEPTABLE,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('edit'),
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
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('edit'),
        array( "name" => "name", "value" => "test_project_community_not_in_private_TEST". rand())
    );

    $this->assertEquals(
        Response::HTTP_NOT_FOUND,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('edit'),
        array( "name" => "about", "value" => "test" . rand())
    );

    $this->assertEquals(
        Response::HTTP_NOT_FOUND,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('edit'),
        array( "name" => "skills", "value" => "add", "key" => "management")
    );

    $this->assertEquals(
        Response::HTTP_NOT_FOUND,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('edit'),
        array( "name" => "skills", "value" => "remove", "key" => "management")
    );

    $this->assertEquals(
        Response::HTTP_NOT_FOUND,
        $client->getResponse()->getStatusCode()
    );

    $client->request(
        'POST',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/edit?token=' . $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('edit'),
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
    $tokenPrivate = $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('makePrivate');
    $tokenPublic = $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('makePublic');

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
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/make/private?token=' . $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('makePrivate')
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
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/make/private?token=' . $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('makePrivate')
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
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/comment?token=' . $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('comment'),
        array( "comment" => $comment )
    );

    $crawler = $client->request(
        'GET',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId())
    );
    $client->reload();

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
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/comment?token=' . $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('comment'),
        array( "comment" => $comment )
    );

    $crawler = $client->request(
        'GET',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId())
    );
    $client->reload();

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
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/comment?token=' . $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('comment'),
        array( "comment" => $comment )
    );

    $crawler = $client->request(
        'GET',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId())
    );
    $client->reload();

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
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/comment?token=' . $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('comment'),
        array( "comment" => $comment )
    );

    $crawler = $client->request(
        'GET',
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId())
    );
    $client->reload();

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
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/comment?token=' . $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('comment'),
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

    $this->assertCount(0, $crawler->filter('p:contains("'.$comment.'")'));

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
        '/app/project/0' . $client->getContainer()->get('uid')->toUId($project->getId()) . '/comment?token=' . $client->getContainer()->get('form.csrf_provider')->generateCsrfToken('comment'),
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

    $this->assertCount(0, $crawler->filter('p:contains("'.$comment.'")'));

  }
}
