<?php

namespace meta\GeneralBundle\Entity\Log;

use Doctrine\ORM\Mapping as ORM;

/**
 * StandardProjectLogEntry
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="meta\GeneralBundle\Entity\Log\StandardProjectLogEntryRepository")
 */
class StandardProjectLogEntry extends BaseLogEntry
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
     * Subject : StandardProject
     * @ORM\ManyToOne(targetEntity="\meta\ProjectBundle\Entity\StandardProject", inversedBy="logEntries")
     * @ORM\JoinColumn(name="standardProject_id", referencedColumnName="id", onDelete="CASCADE")
     **/
    private $standardProject;

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
     * @param \meta\ProjectBundle\Entity\StandardProject $standardProject
     * @return StandardProjectLogEntry
     */
    public function setSubject(\meta\ProjectBundle\Entity\StandardProject $standardProject = null)
    {
        return $this->setStandardProject($standardProject);
    }

    /**
     * Get subject
     *
     * @return \meta\ProjectBundle\Entity\StandardProject 
     */
    public function getSubject()
    {
        return $this->getStandardProject();
    }

    /**
     * Set standardProject
     *
     * @param \meta\ProjectBundle\Entity\StandardProject $standardProject
     * @return StandardProjectLogEntry
     */
    public function setStandardProject(\meta\ProjectBundle\Entity\StandardProject $standardProject = null)
    {
        if (!is_null($standardProject)){
            $standardProject->addLogEntrie($this);
        } elseif (!is_null($this->standardProject)){
            $this->standardProject->removeLogEntrie($this);
        }

        $this->standardProject = $standardProject;
        return $this;
    }

    /**
     * Get standardProject
     *
     * @return \meta\ProjectBundle\Entity\StandardProject 
     */
    public function getStandardProject()
    {
        return $this->standardProject;
    }
}