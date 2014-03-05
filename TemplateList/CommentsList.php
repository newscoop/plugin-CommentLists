<?php
/**
 * @package Newscoop\CommentListsBundle
 * @author Rafał Muszyński <rafal.muszynski@sourcefabric.org>
 * @copyright 2013 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

namespace Newscoop\CommentListsBundle\TemplateList;

use Newscoop\ListResult;
use Newscoop\TemplateList\BaseList;
use Newscoop\CommentListsBundle\Meta\MetaComment;

/**
 * Comments List
 */
class CommentsList extends BaseList
{

    protected function prepareList($criteria, $parameters)
    {
        $service = \Zend_Registry::get('container')->get('commentlists.list');
        $lists = $service->findByCriteria($criteria);
        foreach ($lists as $key => $commentList) {
            foreach ($service->findCommentsByOrder($commentList->getId(), $criteria->maxResults) as $key => $comment) {
                $lists->items[$key] = new MetaComment($comment['id']);
            }
        }

        return $lists;
    }

    protected function convertParameters($firstResult, $parameters)
    {
        $this->criteria->orderBy = array();
        // run default simple parameters converting
        parent::convertParameters($firstResult, $parameters);
    }
}
