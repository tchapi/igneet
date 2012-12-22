<?php

namespace meta\IdeaProfileBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Idea
 *
 * @ORM\Table(name="Idea")
 * @ORM\Entity
 */
class Idea
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
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $created_at;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime")
     */
    private $updated_at;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="headline", type="string", length=255)
     */
    private $headline;

    /**
     * @var string
     *
     * @ORM\Column(name="concept_text", type="text")
     */
    private $concept_text;

    /**
     * @var string
     *
     * @ORM\Column(name="knowledge_text", type="text")
     */
    private $knowledge_text;

    /** Project that resulted
     * @ORM\OneToOne(targetEntity="meta\StandardProjectProfileBundle\Entity\StandardProject", mappedBy="originalIdea")
     **/
    private $resultingProject;

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
     * Set created_at
     *
     * @param \DateTime $createdAt
     * @return Idea
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
     * @return Idea
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
     * Set name
     *
     * @param string $name
     * @return Idea
     */
    public function setName($name)
    {
        $this->name = $name;
    
        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set headline
     *
     * @param string $headline
     * @return Idea
     */
    public function setHeadline($headline)
    {
        $this->headline = $headline;
    
        return $this;
    }

    /**
     * Get headline
     *
     * @return string 
     */
    public function getHeadline()
    {
        return $this->headline;
    }

    /**
     * Set concept_text
     *
     * @param string $conceptText
     * @return Idea
     */
    public function setConceptText($conceptText)
    {
        $this->concept_text = $conceptText;
    
        return $this;
    }

    /**
     * Get concept_text
     *
     * @return string 
     */
    public function getConceptText()
    {
        return $this->concept_text;
    }

    /**
     * Set knowledge_text
     *
     * @param string $knowledgeText
     * @return Idea
     */
    public function setKnowledgeText($knowledgeText)
    {
        $this->knowledge_text = $knowledgeText;
    
        return $this;
    }

    /**
     * Get knowledge_text
     *
     * @return string 
     */
    public function getKnowledgeText()
    {
        return $this->knowledge_text;
    }

    /**
     * Set resultingProject
     *
     * @param \meta\StandardProjectProfileBundle\Entity\StandardProject $resultingProject
     * @return Idea
     */
    public function setResultingProject(\meta\StandardProjectProfileBundle\Entity\StandardProject $resultingProject = null)
    {
        $this->resultingProject = $resultingProject;
    
        return $this;
    }

    /**
     * Get resultingProject
     *
     * @return \meta\StandardProjectProfileBundle\Entity\StandardProject 
     */
    public function getResultingProject()
    {
        return $this->resultingProject;
    }
}