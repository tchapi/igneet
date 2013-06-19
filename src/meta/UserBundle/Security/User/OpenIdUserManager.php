<?php

namespace meta\UserBundle\Security\User;

use Fp\OpenIdBundle\Model\UserManager;
use Fp\OpenIdBundle\Model\IdentityManagerInterface;
use Doctrine\ORM\EntityManager;
use meta\UserBundle\Entity\OpenIdIdentity;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\InsufficientAuthenticationException;

class OpenIdUserManager extends UserManager
{
    // we will use an EntityManager, so inject it via constructor
    public function __construct(IdentityManagerInterface $identityManager, EntityManager $entityManager)
    {
        parent::__construct($identityManager);

        $this->entityManager = $entityManager;
    }

    /**
     * @param string $identity
     *  an OpenID token. With Google it looks like:
     *  https://www.google.com/accounts/o8/id?id=SOME_RANDOM_USER_ID
     * @param array $attributes
     *  requested attributes (explained later). At the moment just
     *  assume there's a 'contact/email' key
     */
    public function createUserFromIdentity($identity, array $attributes = array())
    {

        // We absolutely need an email address 
        if (false === isset($attributes['contact/email'])) {
            throw new \Exception('There has been an error retrieving your email address. Make sure you authorize your email to be disclosed to igneet.');
        }

        // 
        $user = $this->entityManager->getRepository('metaUserBundle:User')->findOneBy(array(
            'email' => $attributes['contact/email']
        ));

        if (null !== $user && $user->isDeleted() == false ) {

            // We create an OpenIdIdentity for this User
            $openIdIdentity = new OpenIdIdentity();
            $openIdIdentity->setIdentity($identity);
            $openIdIdentity->setAttributes($attributes);
            $openIdIdentity->setUser($user);

            $this->entityManager->persist($openIdIdentity);
            $this->entityManager->flush();

        } else if ($user !== null && $user->isDeleted()) {

            // We need to recover
            throw new BadCredentialsException('A deleted user with the same email address already exist. Please recover your account.');
        }

        return $user; // you must return an UserInterface instance (or throw an exception)
    }
}