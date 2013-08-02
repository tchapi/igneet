<?php

namespace meta\ProjectBundle\Entity\Comment;

use meta\GeneralBundle\Entity\Comment\BaseComment;
use Doctrine\ORM\Mapping as ORM;

/**
 * StandardProjectComment
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class StandardProjectComment extends BaseComment
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
     * Standard Project commented
     * @ORM\ManyToOne(targetEntity="\meta\ProjectBundle\Entity\StandardProject", inversedBy="comments")
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
     * Set standardProject
     *
     * BINDING LOGIC IS DONE IN 'STANDARDPROJECT' CLASS 
     * @param \meta\ProjectBundle\Entity\StandardProject $standardProject
     * @return StandardProjectComment
     */
    public function setStandardProject(\meta\ProjectBundle\Entity\StandardProject $standardProject = null)
    {
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