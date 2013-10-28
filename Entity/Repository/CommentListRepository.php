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

        if($criteria->firstResult != 0) {
            $qb->setFirstResult($criteria->firstResult);
        }

        if($criteria->maxResults != 0) {
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
}