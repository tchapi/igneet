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

}