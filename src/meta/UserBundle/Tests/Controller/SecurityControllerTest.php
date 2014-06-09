<?php

namespace meta\UserBundle\Tests\Controller;

use meta\UserBundle\Tests\SecuredWebTestCase,
    Symfony\Component\HttpFoundation\Response;

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

  }

  public function testSignupExistingLogin()
  {
    
  }

  public function testSignupExistingEmail()
  {
    
  }

  public function testSignupWithInvitation()
  {
    
  }

}
