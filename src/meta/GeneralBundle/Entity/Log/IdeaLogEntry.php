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
     * @ORM\ManyToOne(targetEntity="\meta\IdeaBundle\Entity\Idea", inversedBy="logEntries")
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
     * @param \meta\IdeaBundle\Entity\Idea $idea
     * @return IdeaLogEntry
     */
    public function setSubject(\meta\IdeaBundle\Entity\Idea $idea = null)
    {
        return $this->setIdea($idea);
    }

    /**
     * Get subject
     *
     * @return \meta\IdeaBundle\Entity\Idea 
     */
    public function getSubject()
    {
        return $this->getIdea();
    }

    /**
     * Set idea
     *
     * @param \meta\IdeaBundle\Entity\Idea $idea
     * @return IdeaLogEntry
     */
    public function setIdea(\meta\IdeaBundle\Entity\Idea $idea = null)
    {
        if (!is_null($idea)){
            $idea->addLogEntrie($this);
        } elseif (!is_null($this->idea)){
            $this->idea->removeLogEntrie($this);
        }
        
        $this->idea = $idea;
        return $this;
    }

    /**
     * Get idea
     *
     * @return \meta\IdeaBundle\Entity\Idea 
     */
    public function getIdea()
    {
        return $this->idea;
    }
}