<?php

namespace meta\ProjectBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection,
    Doctrine\ORM\Mapping as ORM,
    Symfony\Component\Validator\Constraints as Assert;

use meta\GeneralBundle\Entity\Behaviour\Taggable;

/**
 * meta\ProjectBundle\Entity\WikiPage
 *
 * @ORM\Table(name="WikiPage")
 * @ORM\Entity(repositoryClass="meta\ProjectBundle\Entity\WikiPageRepository")
 */
class WikiPage extends Taggable
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
     * @var string $title
     *
     * @ORM\Column(name="title", type="string", length=255)
     * @Assert\NotBlank()
     */
    private $title;

    /**
     * @var text $content
     *
     * @ORM\Column(name="content", type="text", nullable=true)
     */
    private $content;

    /**
     * @var integer $rank
     *
     * @ORM\Column(name="rank", type="integer")
     */
    private $rank;

    /**
     * @var \DateTime $created_at
     *
     * @ORM\Column(name="created_at", type="datetime")
     * @Assert\NotBlank()
     * @Assert\DateTime()
     */
    private $created_at;

    /**
     * @var \DateTime $updated_at
     *
     * @ORM\Column(name="updated_at", type="datetime")
     * @Assert\NotBlank()
     * @Assert\DateTime()
     */
    private $updated_at;

    /**
     * Wiki this page is linked to (REVERSE SIDE)
     * @ORM\ManyToOne(targetEntity="Wiki", inversedBy="pages")
     **/
    private $wiki;

    /**
     * Parent of this page
     * @ORM\ManyToOne(targetEntity="WikiPage", inversedBy="children")
     **/
    private $parent;

    /**
     * Children of this pages (first order children)
     * @ORM\OneToMany(targetEntity="WikiPage", mappedBy="parent")
     * @ORM\OrderBy({"rank" = "ASC"})
     **/
    private $children;

    /**
     * Comments on this page (OWNING SIDE)
     * @ORM\OneToMany(targetEntity="meta\ProjectBundle\Entity\Comment\WikiPageComment", mappedBy="wikiPage", cascade="remove")
     * @ORM\OrderBy({"created_at" = "DESC"})
     **/
    private $comments;

    public function __construct() {

        $this->created_at = $this->updated_at = new \DateTime('now');

        $this->children = new ArrayCollection();
        $this->parent = null;

        $this->comments = new ArrayCollection();
        
        $this->rank = 1000; // Big enough to be the last
    }


    public function getLogName()
    {
        return $this->title;
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
     * Set wiki
     *
     * BINDING LOGIC IS DONE IN 'WIKI' CLASS
     * @param \meta\ProjectBundle\Entity\Wiki $wiki
     * @return WikiPage
     */
    public function setWiki($wiki)
    {
        $this->wiki = $wiki;
        return $this;
    }

    /**
     * Get wiki
     *
     * @return \stdClass 
     */
    public function getWiki()
    {
        return $this->wiki;
    }

    /**
     * Set parent
     *
     * @param \meta\ProjectBundle\Entity\WikiPage $parent
     * @return WikiPage
     */
    public function setParent(\meta\ProjectBundle\Entity\WikiPage $parent = null)
    {
        if (!is_null($parent)){
            $parent->addChild($this);
        } else if (!is_null($this->parent)) {
            $this->parent->removeChild($this);
        }

        $this->parent = $parent;
    
        return $this;
    }

    /**
     * Get parent
     *
     * @return \meta\ProjectBundle\Entity\WikiPage 
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Add child
     *
     * BINDING LOGIC IS DONE IN 'WIKIPAGE' CLASS > setParent
     * @param \meta\ProjectBundle\Entity\WikiPage $child
     * @return WikiPage
     */
    public function addChild(\meta\ProjectBundle\Entity\WikiPage $child)
    {
        $this->children[] = $child;
        return $this;
    }

    /**
     * Remove child
     *
     * BINDING LOGIC IS DONE IN 'WIKIPAGE' CLASS > setParent
     * @param \meta\ProjectBundle\Entity\WikiPage $child
     */
    public function removeChild(\meta\ProjectBundle\Entity\WikiPage $child)
    {
        $this->children->removeElement($child);
    }

    /**
     * Get children
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return WikiPage
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Get title
     *
     * @return string 
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set content
     *
     * @param string $content
     * @return WikiPage
     */
    public function setContent($content)
    {
        $this->content = $content;
        return $this;
    }

    /**
     * Get content
     *
     * @return string 
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set created_at
     *
     * @param \DateTime $createdAt
     * @return WikiPage
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
     * @return WikiPage
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
     * @ORM\PreUpdate()
     */
    public function update()
    {
        $this->updated_at = new \DateTime('now');
    }
    
    /**
     * Add comments
     *
     * @param \meta\ProjectBundle\Entity\Comment\WikiPageComment $comment
     * @return WikiPage
     */
    public function addComment(\meta\ProjectBundle\Entity\Comment\WikiPageComment $comment)
    {
        if (!is_null($comment)){
            $comment->setWikiPage($this);
        }
        $this->comments[] = $comment;
    
        return $this;
    }

    /**
     * Remove comments
     *
     * @param \meta\ProjectBundle\Entity\Comment\WikiPageComment $comment
     */
    public function removeComment(\meta\ProjectBundle\Entity\Comment\WikiPageComment $comment)
    {
        if (!is_null($comment)){
            $comment->setWikiPage(null);
        }
        $this->comments->removeElement($comment);
    }

    /**
     * Get comments
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getComments()
    {
        return $this->comments;
    }


    /**
     * Set rank
     *
     * @param integer $rank
     * @return WikiPage
     */
    public function setRank($rank)
    {
        $this->rank = $rank;
        return $this;
    }

    /**
     * Get rank
     *
     * @return integer 
     */
    public function getRank()
    {
        return $this->rank;
    }


    /**
     * Add children
     *
     * @param \meta\ProjectBundle\Entity\WikiPage $children
     * @return WikiPage
     */
    public function addChildren(\meta\ProjectBundle\Entity\WikiPage $children)
    {
        $this->children[] = $children;
    
        return $this;
    }

    /**
     * Remove children
     *
     * @param \meta\ProjectBundle\Entity\WikiPage $children
     */
    public function removeChildren(\meta\ProjectBundle\Entity\WikiPage $children)
    {
        $this->children->removeElement($children);
    }
}
