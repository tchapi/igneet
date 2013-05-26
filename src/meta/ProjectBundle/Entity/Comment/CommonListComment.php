<?php

namespace meta\ProjectBundle\Entity\Comment;

use meta\GeneralBundle\Entity\Comment\BaseComment;
use Doctrine\ORM\Mapping as ORM;

/**
 * CommonListComment
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class CommonListComment extends BaseComment
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
     * Common list commented
     * @ORM\ManyToOne(targetEntity="\meta\ProjectBundle\Entity\CommonList", inversedBy="comments")
     **/
    private $commonList;

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
     * Set commonList
     *
     * BINDING LOGIC IS DONE IN 'COMMONLIST' CLASS 
     * @param \meta\ProjectBundle\Entity\CommonList $commonList
     * @return CommonListComment
     */
    public function setCommonList(\meta\ProjectBundle\Entity\CommonList $commonList = null)
    {
        $this->commonList = $commonList;
        return $this;
    }

    /**
     * Get commonList
     *
     * @return \meta\ProjectBundle\Entity\CommonList 
     */
    public function getCommonList()
    {
        return $this->commonList;
    }
}
