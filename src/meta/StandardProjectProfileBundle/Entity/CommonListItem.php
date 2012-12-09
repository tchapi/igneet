<?php

namespace meta\StandardProjectProfileBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * meta\StandardProjectProfileBundle\Entity\CommonListItem
 *
 * @ORM\Table(name="CommonListItem")
 * @ORM\Entity(repositoryClass="meta\StandardProjectProfileBundle\Entity\CommonListItemRepository")
 */
class CommonListItem
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
     * @var string $text
     *
     * @ORM\Column(name="text", type="string", length=255)
     * @Assert\NotBlank()
     */
    private $text;

    /**
     * @var \DateTime $created_at
     *
     * @ORM\Column(name="created_at", type="datetime")
     * @Assert\NotBlank()
     * @Assert\DateTime()
     */
    private $created_at;

    /**
     * @var \DateTime $updated_at
     *
     * @ORM\Column(name="updated_at", type="datetime")
     * @Assert\NotBlank()
     * @Assert\DateTime()
     */
    private $updated_at;

    /**
     * @var boolean $done
     *
     * @ORM\Column(name="done", type="boolean", nullable=true)
     */
    private $done;

    /**
     * @var \DateTime $done_at
     *
     * @ORM\Column(name="done_at", type="datetime", nullable=true)
     * @Assert\DateTime()
     */
    private $done_at;

    /**
     * Common List (reverse side)
     * @ORM\ManyToOne(targetEntity="CommonList", inversedBy="items")
     **/
    private $commonList;

    public function __construct()
    {
        $this->created_at = $this->updated_at = new \DateTime('now');
        $this->done = false;
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
     * @return ListItem
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
     * Set created_at
     *
     * @param \DateTime $createdAt
     * @return ListItem
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
     * Set updated_at
     *
     * @param \DateTime $updatedAt
     * @return ListItem
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updated_at = $updatedAt;
    
        return $this;
    }

    /**
     * Get updated_at
     *
     * @return \DateTime 
     */
    public function getUpdatedAt()
    {
        return $this->updated_at;
    }

    /**
     * Set done
     *
     * @param boolean $done
     * @return ListItem
     */
    public function setDone($done)
    {
        $this->done = $done;

        if ($done == true){
            $this->done_at = new \DateTime('now');
        } else {
            $this->done_at = null;
        }

        return $this;
    }

    /**
     * Get done
     *
     * @return boolean 
     */
    public function getDone()
    {
        return $this->done;
    }

    /**
     * Set done_at
     *
     * @param \DateTime $doneAt
     * @return ListItem
     */
    public function setDoneAt($doneAt)
    {
        $this->done_at = $doneAt;
        return $this;
    }

    /**
     * Get done_at
     *
     * @return \DateTime 
     */
    public function getDoneAt()
    {
        return $this->done_at;
    }


    /**
     * Set commonList
     *
     * @param meta\StandardProjectProfileBundle\Entity\CommonList $commonList
     * @return CommonListItem
     */
    public function setCommonList(\meta\StandardProjectProfileBundle\Entity\CommonList $commonList = null)
    {
        $this->commonList = $commonList;
    
        return $this;
    }

    /**
     * Get commonList
     *
     * @return meta\StandardProjectProfileBundle\Entity\CommonList 
     */
    public function getCommonList()
    {
        return $this->commonList;
    }
}