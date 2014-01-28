<?php
/**
 * @package Newscoop\CommentListsBundle
 * @author Rafał Muszyński <rafal.muszynski@sourcefabric.org>
 * @copyright 2013 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

namespace Newscoop\CommentListsBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr;
use Newscoop\CommentListsBundle\TemplateList\ListCriteria;
use Newscoop\ListResult;

/**
 * User repository
 */
class CommentListRepository extends EntityRepository
{
    /**
     * Get list for given criteria
     *
     * @param Newscoop\CommentLists\Entity\ListCriteria $criteria
     * @return Newscoop\ListResult
     */
    public function getListByCriteria(ListCriteria $criteria)
    {
        $qb = $this->createQueryBuilder('cl');

        $qb->andWhere('cl.is_active = :is_active')
            ->setParameter('is_active', true);

        foreach ($criteria->perametersOperators as $key => $operator) {
            $qb->andWhere('cl.'.$key.' = :'.$key)
                ->setParameter($key, $criteria->$key);
        }

        $list = new ListResult();
        $countBuilder = clone $qb;
        $list->count = (int) $countBuilder->select('COUNT(cl)')->getQuery()->getSingleScalarResult();

        if ($criteria->firstResult != 0) {
            $qb->setFirstResult($criteria->firstResult);
        }

        if ($criteria->maxResults != 0) {
            $qb->setMaxResults($criteria->maxResults);
        }

        $metadata = $this->getClassMetadata();
        foreach ($criteria->orderBy as $key => $order) {
            if (array_key_exists($key, $metadata->columnNames)) {
                $key = 'cl.' . $key;
            }

            $qb->orderBy($key, $order);
        }

        $list->items = $qb->getQuery()->getResult();

        return $list;
    }

    /**
     * Get comment lists count for given criteria
     *
     * @param array $criteria
     * @return int
     */
    public function findByCount(array $criteria)
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(c)')
            ->from($this->getEntityName(), 'c');

        foreach ($criteria as $property => $value) {
            if (!is_array($value)) {
                $queryBuilder->andWhere("u.$property = :$property");
            }
        }

        $query = $queryBuilder->getQuery();
        foreach ($criteria as $property => $value) {
            if (!is_array($value)) {
                $query->setParameter($property, $value);
            }
        }

        return (int) $query->getSingleScalarResult();
    }

    /**
     * Get comments by given list id
     *
     * @param  int $listId
     * @return Newscoop\CommentListsBundle\Entity\Comment
     */
    public function findByListId($listId)
    {
        $comments = $this->getEntityManager()->createQueryBuilder()
            ->select('c')
            ->where('c.list = ?1')
            ->setParameter(1, $listId)
            ->orderBy('c.order', 'asc')
            ->from('Newscoop\CommentListsBundle\Entity\Comment', 'c');

        $commentsIds = array();
        foreach ($comments->getQuery()->getArrayResult() as  $value) {
            $commentsIds[] = $value['commentId'];
        }

        $qb = $this->getEntityManager()->createQueryBuilder();

        $comments = $qb->select('c.id')
            ->where($qb->expr()->in('c.id', $commentsIds))
            ->from('Newscoop\Entity\Comment', 'c');

        return $comments->getQuery()->getArrayResult();
    }
}