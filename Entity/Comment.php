<?php
/**
 * @package Newscoop\CommentListsBundle
 * @author Rafał Muszyński <rafal.muszynski@sourcefabric.org>
 * @copyright 2013 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

namespace Newscoop\CommentListsBundle\Entity;

use Doctrine\ORM\Mapping AS ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Newscoop\CommentListsBundle\TemplateList\CommentCriteria;

/**
 * Comment entity
 *
 * @ORM\Entity()
 * @ORM\Table(name="plugin_comment_lists_comments")
 */
class Comment 
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer", name="id")
     * @var int
     */
    private $id;

    /**
     * @ORM\Column(type="integer", name="fk_comment_id")
     * @var int
     */
    private $comment;

    /**
     * @ORM\ManyToOne(targetEntity="Newscoop\CommentListsBundle\Entity\CommentList", inversedBy="comments")
     * @ORM\JoinColumn(name="list_id", referencedColumnName="id")
     * @var Newscoop\CommentListsBundle\Entity\CommentList
     */
    private $list;

    /**
     * @ORM\Column(type="datetime", name="created_at")
     * @var datetime
     */
    private $created_at;

    /**
     * @ORM\Column(type="boolean", name="is_active")
     * @var boolean
     */
    private $is_active;

    public function __construct() {
        $this->list = new ArrayCollection();
        $this->setCreatedAt(new \DateTime());
        $this->setIsActive(true);
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
     * Get comment
     *
     * @return int
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * Set comment
     *
     * @param  int $comment
     * @return int
     */
    public function setComment($comment)
    {
        $this->comment = $comment;
        
        return $comment;
    }

    /**
     * Get list
     *
     * @return int
     */
    public function getList()
    {
        return $this->list;
    }

    /**
     * Set list
     *
     * @param  int $list
     * @return int
     */
    public function setList($list)
    {
        $this->list = $list;
        
        return $list;
    }

    /**
     * Get status
     *
     * @return boolean
     */
    public function getIsActive()
    {
        return $this->is_active;
    }

    /**
     * Set status
     *
     * @param boolean $is_active
     * @return boolean
     */
    public function setIsActive($is_active)
    {
        $this->is_active = $is_active;
        
        return $this;
    }

    /**
     * Get create date
     *
     * @return datetime
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * Set create date
     *
     * @param datetime $created_at
     * @return datetime
     */
    public function setCreatedAt(\DateTime $created_at)
    {
        $this->created_at = $created_at;
        
        return $this;
    }
}