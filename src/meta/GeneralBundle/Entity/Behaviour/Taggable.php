<?php

namespace meta\GeneralBundle\Entity\Behaviour;

use Doctrine\Common\Collections\ArrayCollection,
    Doctrine\ORM\Mapping as ORM;

/**
 * meta\GeneralBundle\Entity\Behaviour\Taggable
 *
 * @ORM\Table(name="Taggable")
 * @ORM\Entity
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="taggable_type", type="string")
 * @ORM\DiscriminatorMap({"wikipage" = "meta\ProjectBundle\Entity\WikiPage", "resource" = "meta\ProjectBundle\Entity\Resource","idea" = "meta\IdeaBundle\Entity\Idea","project" = "meta\ProjectBundle\Entity\StandardProject","list" = "meta\ProjectBundle\Entity\CommonList"})
 */
abstract class Taggable
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
     * 
     * @ORM\ManyToMany(targetEntity="meta\GeneralBundle\Entity\Behaviour\Tag", mappedBy="tagged_objects")
    **/
    private $tags;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->tags = new ArrayCollection();
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
     * Add tags
     *
     * @param \meta\GeneralBundle\Entity\Behaviour\Tag $tag
     * @return Taggable
     */
    public function addTag(\meta\GeneralBundle\Entity\Behaviour\Tag $tag)
    {
        if (!is_null($tag)){
            $tag->addTaggedObject($this);
        }
        $this->tags[] = $tag;
    
        return $this;
    }

    /**
     * Remove tags
     *
     * @param \meta\GeneralBundle\Entity\Behaviour\Tag $tag
     */
    public function removeTag(\meta\GeneralBundle\Entity\Behaviour\Tag $tag)
    { 
        if(!is_null($tag)){
            $tag->removeTaggedObject($this);
        }
        $this->tags->removeElement($tag);
    }

    /**
     * Get tags
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * Clears all tags
     *
     * @return Taggable
     */
    public function clearTags()
    {
        foreach ($this->tags as $tag) {
            $this->removeTag($tag);
        }

        return $this;
    }


}
