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
/**
 * Comments List
 */
class CommentsList extends BaseList 
{

    protected function prepareList($criteria)
    {   
        $service = \Zend_Registry::get('container')->get('commentlists.list');

        $lists = $service->findByCriteria($criteria);
        foreach ($lists as $key => $commentList) {
            foreach ($commentList->getComments() as $key => $comment) {
                $lists->items[$key] = new \MetaComment($comment->getComment()->getId());
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