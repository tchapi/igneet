<?php

namespace meta\UserBundle\Tests\Controller;

use meta\UserBundle\Tests\SecuredWebTestCase,
    Symfony\Component\HttpFoundation\Response;

use meta\UserBundle\Entity\UserInviteToken;

class SecurityControllerTest extends SecuredWebTestCase
{

  public function testLoginPageNotAuthenticated()
  {

    $client = static::createClient();
    $client->insulate();
    $crawler = $client->request('GET', '/app/login');

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $this->assertCount(
        1,
        $crawler->filter('form')
    );

    $link = $crawler->filter('.recover')->children()->first()->link();
    $client->click($link);

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $link = $crawler->filter('.signup')->first()->link();
    $client->click($link);

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $link = $crawler->filter('.signup')->first()->link();
    $client->click($link);

    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $buttonCrawlerNode = $crawler->filter('input[type=submit]');
    $form = $buttonCrawlerNode->form();

    // By username
    $client->submit($form, array(
        '_username'  => 'test',
        '_password'  => 'test',
    ));
    
    $this->assertRegExp('/\/app\/$/', $client->getResponse()->headers->get('location'));

    // By email
    $client->submit($form, array(
        '_username'  => 'test@igneet.com',
        '_password'  => 'test',
    ));

    $this->assertRegExp('/\/app\/$/', $client->getResponse()->headers->get('location'));

    // By wrong email
    $client->submit($form, array(
        '_username'  => 'regbasket+test+false@gmail.com',
        '_password'  => 'test',
    ));

    $this->assertRegExp('/\/app\/login$/', $client->getResponse()->headers->get('location'));

    // By wrong password
    $client->submit($form, array(
        '_username'  => 'test',
        '_password'  => 'foobar',
    ));

    $this->assertRegExp('/\/app\/login$/', $client->getResponse()->headers->get('location'));

  }

  public function testLoginPageAuthenticated()
  {

    $client = static::createClientWithAuthentication();
    $crawler = $client->request('GET', '/app/login');
    
    $this->assertTrue(
        $client->getResponse()->isRedirect('/app/')
    );

  }

  public function testSignupChoice()
  {

    $client = static::createClient();
    $crawler = $client->request('GET', '/app/signup_choice');
    
    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $this->assertCount(
        3,
        $crawler->filter('.content a.button')
    );

  }

  public function testSignupPage()
  {

    $client = static::createClient();
    $crawler = $client->request('GET', '/app/signup');
    
    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

    $this->assertCount(
        1,
        $crawler->filter('form')
    );

  }

  public function testRecover()
  {

    $client = static::createClient();
    $crawler = $client->request('GET', '/app/recover');
    
    $this->assertEquals(
        Response::HTTP_OK,
        $client->getResponse()->getStatusCode()
    );

  }

  public function testLogoutPage()
  {

    $client = static::createClientWithAuthentication();
    $crawler = $client->request('GET', '/app/logout');
  
    $this->assertTrue($client->getResponse()->isRedirect());

  }

  public function testSignup()
  {

    $client = static::createClient();
    $crawler = $client->request('GET', '/app/signup');

    $buttonCrawlerNode = $crawler->filter('input[type=submit]');
    $form = $buttonCrawlerNode->form();

    // By username
    $client->submit($form, array(
        'user[username]'    => 'testsignup',
        'user[first_name]'  => 'test',
        'user[last_name]'  => 'test',
        'user[email]'  => 'test+signup@igneet.com',
        'user[password]'  => 'test'
    ));

    $this->assertTrue($client->getResponse()->isRedirect());

    if ($client->getResponse()->headers->get('location') !== null) {
      $this->assertRegExp('/\/app\/welcome$/', $client->getResponse()->headers->get('location'));
    
      $this->setUp();
      $user = $this->em->getRepository('metaUserBundle:User')->findOneByUsername('testsignup');
      
      $this->assertTrue($user !== null);

      if ($user){
          $this->em->remove($user);
          $this->em->flush();
      }
    }

  }

  public function testSignupExistingLogin()
  {
    $client = static::createClient();
    $crawler = $client->request('GET', '/app/signup');

    $buttonCrawlerNode = $crawler->filter('input[type=submit]');
    $form = $buttonCrawlerNode->form();

    // By username
    $client->submit($form, array(
        'user[username]'    => 'test',
        'user[first_name]'  => 'test',
        'user[last_name]'  => 'test',
        'user[email]'  => 'test+testagain@igneet.com',
        'user[password]'  => 'test'
    ));

    $this->assertFalse($client->getResponse()->isRedirect());

  }

  public function testSignupExistingEmail()
  {
    $client = static::createClient();
    $crawler = $client->request('GET', '/app/signup');

    $buttonCrawlerNode = $crawler->filter('input[type=submit]');
    $form = $buttonCrawlerNode->form();

    // By username
    $client->submit($form, array(
        'user[username]'    => 'testsignupexisting',
        'user[first_name]'  => 'test',
        'user[last_name]'  => 'test',
        'user[email]'  => 'test@igneet.com',
        'user[password]'  => 'test'
    ));

    $this->assertFalse($client->getResponse()->isRedirect());

    $this->setUp();
    $user = $this->em->getRepository('metaUserBundle:User')->findOneByUsername('testsignupexisting');
    
    $this->assertTrue($user === null);

    // Just in case ...
    if ($user){
        $this->em->remove($user);
        $this->em->flush();
    }
  }

  public function testSignupInvitation()
  {
    // test --> test+token@igneet.com  
    $token = $this->em->getRepository('metaUserBundle:UserInviteToken')->findOneByEmail("test+token@igneet.com");

    $client = static::createClient();
    $crawler = $client->request('GET', '/app/signup/' . $token->getToken());

    $buttonCrawlerNode = $crawler->filter('input[type=submit]');
    $form = $buttonCrawlerNode->form();

    // By username
    $client->submit($form, array(
        'user[username]'    => 'testinvited',
        'user[first_name]'  => 'test',
        'user[last_name]'  => 'test',
        'user[email]'  => 'test+token@igneet.com',
        'user[password]'  => 'test'
    ));

    $this->assertTrue($client->getResponse()->isRedirect());

    if ($client->getResponse()->headers->get('location') !== null) {
      $this->assertRegExp('/\/app\/welcome$/', $client->getResponse()->headers->get('location'));

      $user = $this->em->getRepository('metaUserBundle:User')->findOneByUsername('testinvited');
      $this->assertTrue($user !== null);

      $token_nulled = $this->em->getRepository('metaUserBundle:UserInviteToken')->findOneByEmail("test+token@igneet.com");
      $this->assertTrue($token_nulled->getUsedAt() !== null);

      $token_nulled->setResultingUser(null);
      $this->em->flush();
      $token_nulled->setUsedAt(null);
      $this->em->flush();

      if ($user){
          $userCommunity = $this->em->getRepository('metaUserBundle:UserCommunity')->findOneBy(array('user' => $user, 'community' => $token_nulled->getCommunity()));
          $this->em->remove($userCommunity);
          $this->em->flush();

          $this->em->remove($user);
          $this->em->flush();
      }
    }

  }

}
