<?php

namespace meta\AdminBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection,
    Doctrine\ORM\Mapping as ORM,
    Symfony\Component\Validator\Constraints as Assert;

/**
 * Announcement
 * @ORM\Table(name="Announcement")
 * @ORM\Entity()
 */
class Announcement
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string $text
     *
     * @ORM\Column(name="text", type="string", length=255)
     * @Assert\NotBlank()
     */
    private $text;

    /**
     * @var boolean
     *
     * @ORM\Column(name="active", type="boolean", nullable=true)
     */
    private $active;

    /**
     * @var string $type
     *
     * @ORM\Column(name="type", type="string", length=30)
     */
    private $type;
    // info, warning, technical

    /**
     * @var \DateTime $created_at
     *
     * @ORM\Column(name="created_at", type="datetime")
     * @Assert\NotBlank()
     * @Assert\DateTime()
     */
    private $created_at;

    /**
     * @var \DateTime $valid_from
     *
     * @ORM\Column(name="valid_from", type="datetime")
     * @Assert\NotBlank()
     * @Assert\DateTime()
     */
    private $valid_from;

    /**
     * @var \DateTime $valid_until
     *
     * @ORM\Column(name="valid_until", type="datetime")
     * @Assert\NotBlank()
     * @Assert\DateTime()
     */
    private $valid_until;
    
    /**
     * Users targeted
     * @ORM\ManyToMany(targetEntity="meta\UserBundle\Entity\User", mappedBy="targeted_announcements")
     **/
    private $targetedUsers;
    
    /**
     * Users hit by the announcement
     * @ORM\ManyToMany(targetEntity="meta\UserBundle\Entity\User", mappedBy="viewed_announcements")
     **/
    private $hitUsers;

    /**
     * Constructor
     */
    public function __construct($span = '1 week')
    {
        $this->created_at = new \DateTime('now');
        $this->active = false;

        $this->type = "info";
        $this->usersTargetted = new ArrayCollection();
        $this->usersHit = new ArrayCollection();

        $this->valid_from = new \DateTime('now + ' . $span); // Default validity for an announcement
        $this->valid_until = new \DateTime('now + ' . $span); // Default validity for an announcement

    }

    /**
     * Add targeted_user
     *
     * BINDING LOGIC IS DONE IN 'USER' CLASS 
     * @param \meta\UserBundle\Entity\User $user
     */
    public function addTargetedUser(meta\UserBundle\Entity\User $user)
    {
        $this->targetedUsers[] = $user;
        return $this;
    }

    /**
     * Remove targeted_user
     *
     * BINDING LOGIC IS DONE IN 'USER' CLASS 
     * @param \meta\UserBundle\Entity\User $user
     */
    public function removeTargetedUser(meta\UserBundle\Entity\User $user)
    {
        $this->targetedUsers->removeElement($user);
        return $this;
    }

    /**
     * Add hit_user
     *
     * BINDING LOGIC IS DONE IN 'USER' CLASS 
     * @param \meta\UserBundle\Entity\User $user
     */
    public function addHitUser(meta\UserBundle\Entity\User $user)
    {
        $this->hitUsers[] = $user;
        return $this;
    }

    /**
     * Remove hit_user
     *
     * BINDING LOGIC IS DONE IN 'USER' CLASS 
     * @param \meta\UserBundle\Entity\User $user
     */
    public function removeHitUser(meta\UserBundle\Entity\User $user)
    {
        $this->hitUsers->removeElement($user);
        return $this;
    }
}
