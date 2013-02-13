<?php

namespace meta\IdeaProfileBundle\Entity\Comment;

use meta\GeneralBundle\Entity\Comment\BaseComment;
use Doctrine\ORM\Mapping as ORM;

/**
 * IdeaComment
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class IdeaComment extends BaseComment
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
     * Idea commented
     * @ORM\ManyToOne(targetEntity="\meta\IdeaProfileBundle\Entity\Idea", inversedBy="comments")
     **/
    private $idea;


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
     * Set Idea
     *
     * @param \meta\IdeaProfileBundle\Entity\Idea $idea
     * @return IdeaComment
     */
    public function setIdea(\meta\IdeaProfileBundle\Entity\Idea $idea = null)
    {
        $this->idea = $idea;
    
        return $this;
    }

    /**
     * Get Idea
     *
     * @return \meta\IdeaProfileBundle\Entity\Idea 
     */
    public function getIdea()
    {
        return $this->idea;
    }
}