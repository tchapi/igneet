<?php

namespace meta\GeneralBundle\Entity\Log;

use Doctrine\Common\Collections\ArrayCollection;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * BaseLogEntry
 *
 * @ORM\Table(name="LogEntries")
 * @ORM\Entity
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="subject_type", type="string")
 * @ORM\DiscriminatorMap({"idea" = "meta\GeneralBundle\Entity\Log\IdeaLogEntry", "project" = "meta\GeneralBundle\Entity\Log\StandardProjectLogEntry", "user" = "meta\GeneralBundle\Entity\Log\UserLogEntry"})
 */
class BaseLogEntry
{

    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string $type
     * @ORM\Column(name="type", type="string", length=255)
     * @Assert\NotBlank()
     *
     */
    private $type;

    /**
     * @var \DateTime $created_at
     *
     * @ORM\Column(name="created_at", type="datetime")
     * @Assert\NotBlank()
     * @Assert\DateTime()
     */
    private $created_at;

    /**
     * User that did this (OWNING SIDE)
     * @ORM\ManyToOne(targetEntity="meta\UserProfileBundle\Entity\User", inversedBy="logEntries")
     **/
    private $user;

    /**
     * @var array $objects
     * @ORM\Column(name="objects", type="array")
     *
     */
    private $objects;

    public function __construct(){

        $this->created_at = new \Datetime('now');
        $this->objects = array();
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
     * Set type
     *
     * @param string $type
     * @return LogEntry
     */
    public function setType($type)
    {
        $this->type = $type;
    
        return $this;
    }

    /**
     * Get type
     *
     * @return string 
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set created_at
     *
     * @param \DateTime $createdAt
     * @return LogEntry
     */
    public function setCreatedAt($createdAt)
    {
        $this->created_at = $createdAt;
    
        return $this;
    }

    /**
     * Get created_at
     *
     * @return \DateTime 
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * Set user
     *
     * @param \meta\UserProfileBundle\Entity\User $user
     * @return LogEntry
     */
    public function setUser(\meta\UserProfileBundle\Entity\User $user = null)
    {
        $this->user = $user;
    
        return $this;
    }

    /**
     * Get user
     *
     * @return \meta\UserProfileBundle\Entity\User 
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set objects
     *
     * @param array $objects
     * @return LogEntry
     */
    public function setObjects($objects)
    {
        $this->objects = $objects;
    
        return $this;
    }

    /**
     * Get objects
     *
     * @return array 
     */
    public function getObjects()
    {
        return $this->objects;
    }
}