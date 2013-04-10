<?php

namespace meta\IdeaBundle\Entity\Comment;

use meta\GeneralBundle\Entity\Comment\BaseComment;
use Doctrine\ORM\Mapping as ORM;

use meta\ProjectBundle\Entity\Comment\StandardProjectComment;

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
     * @ORM\ManyToOne(targetEntity="\meta\IdeaBundle\Entity\Idea", inversedBy="comments")
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
     * BINDING LOGIC IS DONE IN 'IDEA' CLASS 
     * @param \meta\IdeaBundle\Entity\Idea $idea
     * @return IdeaComment
     */
    public function setIdea(\meta\IdeaBundle\Entity\Idea $idea = null)
    {
        $this->idea = $idea;
        return $this;
    }

    /**
     * Get Idea
     *
     * @return \meta\IdeaBundle\Entity\Idea 
     */
    public function getIdea()
    {
        return $this->idea;
    }

    /**
     * Create a standardProject Comment from an idea Comment
     *
     * @return meta\ProjectBundle\Entity\Comment\StandardProjectComment
     */
    public function createProjectComment()
    {
        $standardProjectComment = new StandardProjectComment();

        $standardProjectComment->setText($this->getText())
                               ->setPublic($this->getPublic())
                               ->setCreatedAt($this->getCreatedAt())
                               ->setUser($this->getUser());

        foreach ($this->getValidators() as $user) {
            $standardProjectComment->addValidator($user);
        }

        return $standardProjectComment;
    }
}