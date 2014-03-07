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
     * Process item
     *
     * @param array $comment
     *
     * @return array
     */
    private function processItem($comment)
    {
        return array(
            $comment[0]['id'],
            $comment['language'],
            'language' => $comment['language'],
            'time_created' => $comment[0]['time_created']->format('Y-m-d H:i:s'),
            'commenter' => $comment['name'],
            'message' => $comment[0]['message'],
            'subject' => $comment[0]['subject'],
            'source' => $comment[0]['source'],
        );
    }

    /**
     * Gets comment list by given parameters
     *
     * @param array  $params  Parameters
     * @param string $start   Offset
     * @param string $limit   Max results
     * @param string $sortDir Sorting type
     *
     * @return array
     */
    public function getList($params, $start, $limit, $sortDir)
    {
        $return = array();

        $qb = $this->em->getRepository('Newscoop\Entity\Comment')
                ->createQueryBuilder('c');
        $qb
            ->select('c', 'l.id as language', 'cc.name')
            ->leftJoin('c.commenter', 'cc')
            ->leftJoin('c.language', 'l')
            ->leftJoin('c.thread', 't')
            ->where($qb->expr()->isNotNull('c.forum'));

        if ($params['publication'] != null && $params['publication'] != '0') {
            $qb->andWhere('c.forum = :publication')
                ->setParameter('publication', $params['publication']);
        }

        if ($params['commenter'] != null && $params['commenter'] != '0') {
            $qb->andWhere('c.commenter = :commenter')
                ->setParameter('commenter', $params['commenter']);
        }

        if ($params['language'] != null && $params['language'] != '0') {
            $qb->andWhere('c.language = :language')
                ->setParameter('language', $params['language']);
        }

        if ($params['time_created'] != null && $params['time_created'] != '0') {
            $date = strtotime($params['time_created']);
            $date = strtotime("+1 day", $date);
            $qb->andWhere($qb->expr()->between('c.time_created',
                $qb->expr()->literal($params['time_created']),
                $qb->expr()->literal(date('Y-m-d', $date))
            ));
        }

        if ($params['thread'] != null && $params['thread'] != '0') {
            $qb->andWhere('c.article_num = :articleNumber')
                ->setParameter('articleNumber', $params['thread']);
        }

        if ($params['issueId'] != null && $params['issueId'] != '0') {
            $qb->andWhere('t.issueId = :issueId')
                ->setParameter('issueId', $params['issueId']);
        }

        if ($params['sectionId'] != null && $params['sectionId'] != '0') {
            $qb->andWhere('t.sectionId = :sectionId')
                ->setParameter('sectionId', $params['sectionId']);
        }

        $countBuilder = clone $qb;
        $commentsCount = (int) $countBuilder->select('COUNT(c)')->getQuery()->getSingleScalarResult();

        $comments = $qb->setMaxResults($limit)
            ->setFirstResult($start)
            ->orderBy('c.time_created', $sortDir)
            ->getQuery()
            ->getArrayResult();

        foreach ($comments as $comment) {
            $return[] = $this->processItem($comment);
        }

        return array(
            $return,
            $commentsCount
        );
    }

    /**
     * Gets comment list by given phrase
     *
     * @param string $searchPhrase Search phrase
     * @param array  $return       Array for results
     * @param int    $limit        Limit result
     * @param int    $start        Start from
     *
     * @return array
     */
    public function searchComment($searchPhrase, $return, $limit, $start)
    {
        $qb = $this->em->getRepository('Newscoop\Entity\Comment')
            ->createQueryBuilder('c');

        $qb
            ->leftJoin('c.commenter', 'cc')
            ->leftJoin('c.language', 'l')
            ->where($qb->expr()->like('c.message', ':phrase'))
            ->orWhere($qb->expr()->like('c.subject', ':phrase'))
            ->orWhere($qb->expr()->like('cc.name', ':phrase'))
            ->setParameter('phrase', '%'.$searchPhrase.'%');

        $countBuilder = clone $qb;
        $count = (int) $countBuilder->select('COUNT(c)')->getQuery()->getSingleScalarResult();

        $search = $qb->select('c', 'l.id as language', 'cc.name')
            ->setMaxResults($limit)
            ->setFirstResult($start)
            ->getQuery()
            ->getArrayResult();

        foreach ($search as $comment) {
            $return[] = $this->processItem($comment);
        }

        return array(
            'count' => $count,
            'result' => $return
        );
    }

    /**
     * Gets list by given name
     *
     * @param integer $listName List name
     * @param integer $listId   List id
     *
     * @return object
     */
    public function findListBy($listName = null, $listId = null)
    {
        $params = array(
            'is_active' => true
        );

        if ($listId) {
            $params['id'] = $listId;
        }

        if ($listName) {
            $params['name'] = $listName;
        }

        $list = $this->em->getRepository('Newscoop\CommentListsBundle\Entity\CommentList')->findOneBy($params);

        return $list;
    }

    /**
     * Returns comments for a given list
     *
     * @param string $list Comment list
     *
     * @return array
     */
    public function load($list)
    {
        $qb = $this->em->createQueryBuilder();
        $comments = $this->em->getRepository('Newscoop\CommentListsBundle\Entity\Comment')
            ->createQueryBuilder('c')
            ->select('c.commentId', 'cc.id')
            ->innerJoin('c.list', 'l', 'WITH', 'l.id = ?1')
            ->leftJoin('c.comment', 'cc')
            ->where('c.is_active = true')
            ->setParameter(1, $list)
            ->orderBy('c.order', 'asc')
            ->getQuery()
            ->getArrayResult();

        if (!$comments) {
            return json_encode(array(
                'status' => false
            ));
        }

        $commentsArray = array();
        foreach ($comments as $value) {
            if (!$value['id']) {
                $query = $qb->delete('Newscoop\CommentListsBundle\Entity\Comment', 'c')
                    ->where($qb->expr()->eq('c.list', ':list'))
                    ->andWhere($qb->expr()->eq('c.commentId', ':commentId'))
                    ->setParameter(':list', $list)
                    ->setParameter(':commentId', $value['commentId'])
                    ->getQuery();
                $query->execute();
            }

            $commentsArray[] = $value['commentId'];
        }

        $commentsData = $this->em->getRepository('Newscoop\CommentListsBundle\Entity\Comment')
            ->createQueryBuilder('c')
            ->select('c', 'cc', 'cm')
            ->leftJoin('c.comment', 'cc')
            ->leftJoin('cc.commenter', 'cm')
            ->where('cc.id IN (:ids)')
            ->andWhere('c.list = :list')
            ->setParameters(array(
                'ids' => $commentsArray,
                'list' => $list
            ))
            ->orderBy('c.order', 'ASC')
            ->getQuery()
            ->getArrayResult();

        return json_encode(array(
            'items' => $commentsData,
            'status' => true
        ));
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
     * Find comment by given id and list id
     *
     * @param int $commentId
     * @param int $listId
     *
     * @return Newscoop\CommentListsBundle\Entity\Comment
     */
    public function findOneComment($commentId, $listId)
    {
        return $this->getCommentRepository()->findOneBy(array(
            'commentId' => $commentId,
            'listId' => $listId,
        ));
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
