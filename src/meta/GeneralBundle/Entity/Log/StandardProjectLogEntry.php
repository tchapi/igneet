<?php

namespace meta\GeneralBundle\Entity\Log;

use Doctrine\ORM\Mapping as ORM;

/**
 * StandardProjectLogEntry
 *
 * @ORM\Table()
 * @ORM\Entity
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
     * @ORM\ManyToOne(targetEntity="\meta\StandardProjectProfileBundle\Entity\StandardProject", inversedBy="logEntries")
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
     * @param \meta\StandardProjectProfileBundle\Entity\StandardProject $standardProject
     * @return StandardProjectLogEntry
     */
    public function setSubject(\meta\StandardProjectProfileBundle\Entity\StandardProject $standardProject = null)
    {
        $this->standardProject = $standardProject;

        return $this;
    }

    /**
     * Get subject
     *
     * @return \meta\StandardProjectProfileBundle\Entity\StandardProject 
     */
    public function getSubject()
    {
        return $this->standardProject;
    }

    /**
     * Set standardProject
     *
     * @param \meta\StandardProjectProfileBundle\Entity\StandardProject $standardProject
     * @return StandardProjectLogEntry
     */
    public function setStandardProject(\meta\StandardProjectProfileBundle\Entity\StandardProject $standardProject = null)
    {
        $this->standardProject = $standardProject;
    
        return $this;
    }

    /**
     * Get standardProject
     *
     * @return \meta\StandardProjectProfileBundle\Entity\StandardProject 
     */
    public function getStandardProject()
    {
        return $this->standardProject;
    }
}