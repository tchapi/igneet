<?php

namespace meta\UserBundle\Entity;

use Symfony\Component\Security\Core\User\UserInterface;

use Doctrine\ORM\Mapping as ORM;

use Fp\OpenIdBundle\Entity\UserIdentity as BaseUserIdentity;
use Fp\OpenIdBundle\Model\UserIdentityInterface;

/**
 * @ORM\Entity
 * @ORM\Table(name="OpenIdIdentity")
 */
class OpenIdIdentity extends BaseUserIdentity
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
      * The relation is made eager by purpose. 
      * More info here: {@link https://github.com/formapro/FpOpenIdBundle/issues/54}
      * 
      * @var Symfony\Component\Security\Core\User\UserInterface
      *
      * @ORM\ManyToOne(targetEntity="meta\UserBundle\Entity\User", fetch="EAGER")
      */
    protected $user;

    /*
     * It inherits an "identity" string field,
     * and an "attributes" text field
     */

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set user
     *
     * @param \Symfony\Component\Security\Core\User\UserInterface $user
     * @return OpenIdIdentity
     */
    public function setUser(UserInterface $user = null)
    {
        $this->user = $user;
    
        return $this;
    }

    /**
     * Get user
     *
     * @return \meta\UserBundle\Entity\User 
     */
    public function getUser()
    {
        return $this->user;
    }
}