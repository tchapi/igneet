<?php
 
namespace meta\UserBundle\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
 
/**
 * @group functional
 */
class SecuredWebTestCase extends WebTestCase
{

    /**
     * @param array $options
     * @param array $server
     * @return Symfony\Component\BrowserKit\Client
     */
    protected static function createClientWithAuthentication($username = 'test', array $options = array(), array $server = array())
    {
        /* @var $client \Symfony\Component\BrowserKit\Client */
        $client = static::createClient($options, array_merge($server, array(
            'PHP_AUTH_USER' => $username,
            'PHP_AUTH_PW'   => 'test',
        )));

        return $client;
    }
}