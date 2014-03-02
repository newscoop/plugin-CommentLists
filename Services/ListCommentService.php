<?php
/**
 * @package Newscoop\CommentListsBundle
 * @author Rafał Muszyński <rafal.muszynski@sourcefabric.org>
 * @copyright 2013 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

namespace Newscoop\CommentListsBundle\Services;

use Doctrine\ORM\EntityManager;
use Newscoop\CommentListsBundle\TemplateList\ListCriteria;

/**
 * List Comment service
 */
class ListCommentService
{
    /** @var Doctrine\ORM\EntityManager */
    protected $em;

    /**
     * @param Doctrine\ORM\EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Find by criteria
     *
     * @param Newscoop\CommentListsBundle\TemplateList\CommentCriteria $criteria
     *
     * @return Newscoop\ListResult;
     */
    public function findByCriteria(ListCriteria $criteria)
    {
        return $this->getRepository()->getListByCriteria($criteria);
    }

    /**
     * Count by given criteria
     *
     * @param array $criteria
     *
     * @return int
     */
    public function countBy(array $criteria = array())
    {
        return $this->getRepository()->findByCount($criteria);
    }

    /**
     * Find comments by given list id
     *
     * @param int $listId
     *
     * @return Newscoop\CommentListsBundle\Entity\Comment
     */
    public function findCommentsByOrder($listId, $maxResults)
    {
        return $this->getRepository()->findByListId($listId, $maxResults);
    }

    /**
     * Find comment by given id
     *
     * @param int $commentId
     *
     * @return Newscoop\CommentListsBundle\Entity\Comment
     */
    public function findOneComment($commentId)
    {
        return $this->getCommentRepository()->findOneBy(array('commentId' => $commentId));
    }

    /**
     * Get repository
     *
     * @return Newscoop\CommentListsBundle\Entity\CommentList
     */
    protected function getRepository()
    {
        return $this->em->getRepository('Newscoop\CommentListsBundle\Entity\CommentList');
    }

    /**
     * Get comment repository
     *
     * @return Newscoop\CommentListsBundle\Entity\Comment
     */
    protected function getCommentRepository()
    {
        return $this->em->getRepository('Newscoop\CommentListsBundle\Entity\Comment');
    }
}
