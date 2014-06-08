<?php
 
namespace meta\UserBundle\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
 
/**
 * @group functional
 */
class SecuredWebTestCase extends WebTestCase
{

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;

    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        static::$kernel = static::createKernel();
        static::$kernel->boot();
        $this->em = static::$kernel->getContainer()
            ->get('doctrine')
            ->getManager();

    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown()
    {
        parent::tearDown();
        $this->em->close();
    }
    
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