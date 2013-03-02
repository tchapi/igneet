<?php

namespace meta\GeneralBundle\Entity\Log;

use Doctrine\Common\Collections\ArrayCollection;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * BaseLogEntry
 *
 * @ORM\Table(name="LogEntries")
 * @ORM\Entity(repositoryClass="meta\GeneralBundle\Entity\Log\BaseLogEntryRepository")
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
     * @var integer $combined_count
     * @ORM\Column(name="combined_count", type="integer")
     *
     */
    private $combined_count;

    /**
     * User that did this (OWNING SIDE)
     * @ORM\ManyToOne(targetEntity="meta\UserProfileBundle\Entity\User", inversedBy="initiatedLogEntries")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
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
        $this->combined_count = 1;
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
        if(!is_null($user)){
            $user->addInitiatedLogEntrie($this);
        } elseif (!is_null($this->user)){
            $this->user->removeInitiatedLogEntrie($this);
        }
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
    public function setObjects($objectsAsArray)
    {
        $this->objects = $objectsAsArray;
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

    /**
     * Set combined_count
     *
     * @param integer $combinedCount
     * @return BaseLogEntry
     */
    public function setCombinedCount($combinedCount)
    {
        $this->combined_count = $combinedCount;
        return $this;
    }

    /**
     * Increment combined_count by 1
     *
     * @param integer $combinedCount
     * @return BaseLogEntry
     */
    public function incrementCombinedCount()
    {
        $this->combined_count += 1 ;
        return $this;
    }

    /**
     * Get combined_count
     *
     * @return integer 
     */
    public function getCombinedCount()
    {
        return $this->combined_count;
    }
}