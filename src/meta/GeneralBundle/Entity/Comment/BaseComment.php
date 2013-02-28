<?php

namespace meta\GeneralBundle\Entity\Comment;

use Doctrine\Common\Collections\ArrayCollection,
    Doctrine\ORM\Mapping as ORM,
    Symfony\Component\Validator\Constraints as Assert;

/**
 * BaseComment
 *
 * @ORM\Table(name="Comments")
 * @ORM\Entity(repositoryClass="meta\GeneralBundle\Entity\Comment\BaseCommentRepository")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({"wikiPage" = "meta\StandardProjectProfileBundle\Entity\Comment\WikiPageComment", "list" = "meta\StandardProjectProfileBundle\Entity\Comment\CommonListComment", "project" = "meta\StandardProjectProfileBundle\Entity\Comment\StandardProjectComment", "idea" = "meta\IdeaProfileBundle\Entity\Comment\IdeaComment"})
 */

/*, "meta" = "meta\StandardProjectProfileBundle\Entity\Comment\MetaProjectComment"*/
class BaseComment
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
     * @var string
     *
     * @ORM\Column(name="text", type="text")
     * @Assert\NotBlank()
     */
    private $text;

    /**
     * @var boolean
     *
     * @ORM\Column(name="public", type="boolean", nullable=true)
     */
    private $public;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     * @Assert\NotBlank()
     * @Assert\DateTime()
     */
    private $created_at;

    /**
     * User that created this comment (OWNING SIDE)
     * @ORM\ManyToOne(targetEntity="meta\UserProfileBundle\Entity\User", inversedBy="comments")
     **/
    private $user;

    /**
     * Users that validate this comment
     * @ORM\JoinTable(name="User_validates_Comment")
     * @ORM\ManyToMany(targetEntity="meta\UserProfileBundle\Entity\User", inversedBy="validatedComments")
     **/
    private $validators;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->created_at = new \DateTime('now');
        $this->validators = new ArrayCollection();
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
     * is public
     *
     * @return boolean 
     */
    public function isPublic()
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
        if (!is_null($user)) {
            $user->addComment($this);
        } elseif (!is_null($this->user)) {
            $this->user->removeComment($this);
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
     * Add validator
     *
     * @param \meta\UserProfileBundle\Entity\User $validator
     * @return BaseComment
     */
    public function addValidator(\meta\UserProfileBundle\Entity\User $validator)
    {
        if(!is_null($validator) && $this->validators->indexOf($validator) === false ) {
            $validator->addValidatedComment($this);
            $this->validators[] = $validator;
        }

        return $this;
    }

    /**
     * Remove validator
     *
     * @param \meta\UserProfileBundle\Entity\User $validator
     */
    public function removeValidator(\meta\UserProfileBundle\Entity\User $validator)
    {
        if(!is_null($validator)) {
            $validator->removeValidatedComment($this);
        }

        $this->validators->removeElement($validator);
    }

    /**
     * Toggle validator
     *
     * @param \meta\UserProfileBundle\Entity\User $validator
     */
    public function toggleValidator(\meta\UserProfileBundle\Entity\User $validator)
    {
        if(!is_null($validator)){
            if ($this->validators->indexOf($validator) === false ) {
                $validator->addValidatedComment($this);
                $this->validators[] = $validator;
            } else {
                $validator->removeValidatedComment($this);
                $this->validators->removeElement($validator);
            }
        }
        
        return $this;
    }

    /**
     * Get validators
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getValidators()
    {
        return $this->validators;
    }
}