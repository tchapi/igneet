<?php

namespace meta\StandardProjectProfileBundle\Entity\Comment;

use Doctrine\Common\Collections\ArrayCollection;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * BaseComment
 *
 * @ORM\Table(name="Comments")
 * @ORM\Entity
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({"wikiPage" = "meta\StandardProjectProfileBundle\Entity\WikiPage", "list" = "meta\StandardProjectProfileBundle\Entity\CommonList", "project" = "meta\StandardProjectProfileBundle\Entity\StandardProject", "meta" = "meta\StandardProjectProfileBundle\Entity\MetaProject"})
 */
class BaseComment
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="text", type="string", length=255)
     */
    private $text;

    /**
     * @var boolean
     *
     * @ORM\Column(name="public", type="boolean")
     */
    private $public;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $created_at;

    /**
     * User that created this comment
     * @ORM\ManyToOne(targetEntity="meta\UserProfileBundle\Entity\User", inversedBy="comments")
     **/
    private $user;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->user = new ArrayCollection();
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
     * Set text
     *
     * @param string $text
     * @return BaseComment
     */
    public function setText($text)
    {
        $this->text = $text;
    
        return $this;
    }

    /**
     * Get text
     *
     * @return string 
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * Set public
     *
     * @param boolean $public
     * @return BaseComment
     */
    public function setPublic($public)
    {
        $this->public = $public;
    
        return $this;
    }

    /**
     * Get public
     *
     * @return boolean 
     */
    public function getPublic()
    {
        return $this->public;
    }

    /**
     * Set created_at
     *
     * @param \DateTime $createdAt
     * @return BaseComment
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
     * @return BaseComment
     */
    public function setUser(\meta\UserProfileBundle\Entity\User $user = null)
    {
        if ($user) {
            $user->addComment($this);
            $this->user = $user;
        } else {
            $this->user->removeComment($this);
            $this->user = null;
        }
    
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
}