<?php

namespace meta\StandardProjectProfileBundle\Entity\Comment;

use Doctrine\ORM\Mapping as ORM;

/**
 * WikiPageComment
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class WikiPageComment extends BaseComment
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
     * Wiki Page commented
     * @ORM\ManyToOne(targetEntity="\meta\StandardProjectProfileBundle\Entity\WikiPage", inversedBy="comments")
     **/
    private $wikiPage;

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
     * Set wikiPage
     *
     * @param \meta\StandardProjectProfileBundle\Entity\WikiPage $wikiPage
     * @return WikiPageComment
     */
    public function setWikiPage(\meta\StandardProjectProfileBundle\Entity\WikiPage $wikiPage = null)
    {
        $this->wikiPage = $wikiPage;
    
        return $this;
    }

    /**
     * Get wikiPage
     *
     * @return \meta\StandardProjectProfileBundle\Entity\WikiPage 
     */
    public function getWikiPage()
    {
        return $this->wikiPage;
    }
}