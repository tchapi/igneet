<?php

namespace meta\ProjectBundle\Entity\Comment;

use meta\GeneralBundle\Entity\Comment\BaseComment;
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
     * @ORM\ManyToOne(targetEntity="\meta\ProjectBundle\Entity\WikiPage", inversedBy="comments")
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
     * BINDING LOGIC IS DONE IN 'WIKIPAGE' CLASS 
     * @param \meta\ProjectBundle\Entity\WikiPage $wikiPage
     * @return WikiPageComment
     */
    public function setWikiPage(\meta\ProjectBundle\Entity\WikiPage $wikiPage = null)
    {
        $this->wikiPage = $wikiPage;
        return $this;
    }

    /**
     * Get wikiPage
     *
     * @return \meta\ProjectBundle\Entity\WikiPage 
     */
    public function getWikiPage()
    {
        return $this->wikiPage;
    }
}
