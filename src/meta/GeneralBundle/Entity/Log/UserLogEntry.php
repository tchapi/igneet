<?php

namespace meta\GeneralBundle\Entity\Log;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserLogEntry
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class UserLogEntry extends BaseLogEntry
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
     * Subject : User
     * @ORM\ManyToOne(targetEntity="\meta\UserProfileBundle\Entity\User", inversedBy="logEntries")
     * @ORM\JoinColumn(name="other_user_id", referencedColumnName="id", onDelete="CASCADE")
     **/
    private $other_user;

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
     * Set subject
     *
     * @param \meta\UserProfileBundle\Entity\User $other_user
     * @return UserLogEntry
     */
    public function setSubject(\meta\UserProfileBundle\Entity\User $other_user = null)
    {
        $this->other_user = $other_user;
    
        return $this;
    }

    /**
     * Get subject
     *
     * @return \meta\UserProfileBundle\Entity\User 
     */
    public function getSubject()
    {
        return $this->other_user;
    }

    /**
     * Set other_user
     *
     * @param \meta\UserProfileBundle\Entity\User $otherUser
     * @return UserLogEntry
     */
    public function setOtherUser(\meta\UserProfileBundle\Entity\User $otherUser = null)
    {
        $this->other_user = $otherUser;
    
        return $this;
    }

    /**
     * Get other_user
     *
     * @return \meta\UserProfileBundle\Entity\User 
     */
    public function getOtherUser()
    {
        return $this->other_user;
    }
}