<?php

namespace meta\GeneralBundle\Entity\Log;

use Doctrine\ORM\Mapping as ORM;

/**
 * IdeaLogEntry
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class IdeaLogEntry extends BaseLogEntry
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
     * Subject : idea
     * @ORM\ManyToOne(targetEntity="\meta\IdeaProfileBundle\Entity\Idea", inversedBy="logEntries")
     * @ORM\JoinColumn(name="idea_id", referencedColumnName="id", onDelete="CASCADE")
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
     * Set subject
     *
     * @param \meta\IdeaProfileBundle\Entity\Idea $idea
     * @return IdeaLogEntry
     */
    public function setSubject(\meta\IdeaProfileBundle\Entity\Idea $idea = null)
    {
        $this->idea = $idea;
    
        return $this;
    }

    /**
     * Get subject
     *
     * @return \meta\IdeaProfileBundle\Entity\Idea 
     */
    public function getSubject()
    {
        return $this->idea;
    }

    /**
     * Set idea
     *
     * @param \meta\IdeaProfileBundle\Entity\Idea $idea
     * @return IdeaLogEntry
     */
    public function setIdea(\meta\IdeaProfileBundle\Entity\Idea $idea = null)
    {
        $this->idea = $idea;
    
        return $this;
    }

    /**
     * Get idea
     *
     * @return \meta\IdeaProfileBundle\Entity\Idea 
     */
    public function getIdea()
    {
        return $this->idea;
    }
}