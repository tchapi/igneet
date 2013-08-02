<?php

namespace meta\GeneralBundle\Entity\Behaviour;

use Doctrine\Common\Collections\ArrayCollection,
    Doctrine\ORM\Mapping as ORM,
    Symfony\Component\Validator\Constraints as Assert;

/**
 * meta\GeneralBundle\Entity\Behaviour\Tag
 *
 * @ORM\Table(name="Tag")
 * @ORM\Entity
 */
class Tag
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
     * @var string $name
     *
     * @ORM\Column(name="name", type="string", length=255, unique=true)
     * @Assert\NotBlank()
     */
    private $name;

    /**
     * @var string $color
     *
     * @ORM\Column(name="color", type="string", length=7, nullable=true)
     */
    private $color;

    /**
     * 
     * @ORM\ManyToMany(targetEntity="meta\GeneralBundle\Entity\Behaviour\Taggable", inversedBy="tags")
     **/
    private $tagged_objects;

    /**
     * Constructor
     */
    public function __construct($tagName)
    {
        $this->name = $tagName;
        $this->tagged_objects = new ArrayCollection();
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
     * Add tagged_objects
     *
     * BINDING LOGIC IS DONE IN 'TAGGABLE' CLASS 
     * @param \meta\GeneralBundle\Entity\Behaviour\Taggable $taggedObject
     * @return Tag
     */
    public function addTaggedObject(\meta\GeneralBundle\Entity\Behaviour\Taggable $taggedObject)
    {
        $this->tagged_objects[] = $taggedObject;
        return $this;
    }

    /**
     * Remove tagged_objects
     *
     * BINDING LOGIC IS DONE IN 'TAGGABLE' CLASS 
     * @param \meta\GeneralBundle\Entity\Behaviour\Taggable $taggedObject
     */
    public function removeTaggedObject(\meta\GeneralBundle\Entity\Behaviour\Taggable $taggedObject)
    {
        $this->tagged_objects->removeElement($taggedObject);
    }

    /**
     * Get tagged_objects
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getTaggedObjects()
    {
        return $this->tagged_objects;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Tag
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
     * Set color
     *
     * @param string $color
     * @return Tag
     */
    public function setColor($color)
    {
        $this->color = $color;
        return $this;
    }

    /**
     * Get color
     *
     * @return string 
     */
    public function getColor()
    {
        return $this->color;
    }
}