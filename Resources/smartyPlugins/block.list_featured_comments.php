<?php
/**
 * @package Newscoop\CommentListsBundle
 * @author Rafał Muszyński <rafal.muszynski@sourcefabric.org>
 * @copyright 2013 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

/**
 * Newscoop list_featured_comments block plugin
 *
 * Type:     block
 * Name:     list_featured_comments
 *
 * @param  array  $params
 * @param  mixed  $content
 * @param  object $smarty
 * @param  bool   $repeat
 *
 * @return string
 */
function smarty_block_list_featured_comments($params, $content, &$smarty, &$repeat)
{
    $context = $smarty->getTemplateVars('gimme');
    //var_dump(\Zend_Registry::get('container')->get('newscoop.template_lists.commentlists'));die;
    
    if (!isset($content)) { // init
        $start = $context->next_list_start('Newscoop\CommentListsBundle\TemplateList\CommentsList');
        //$list = new UsersList($start, $params);
        $list = \Zend_Registry::get('container')->get('newscoop.template_lists.commentlists');
        $list->getList($start, $params);
        if ($list->isEmpty()) {
            $context->setCurrentList($list, array());
            $context->resetCurrentList();
            $repeat = false;
            return;
        }

        $context->setCurrentList($list, array('comment'));
        $context->comment = $context->current_comments_list->current;
        $repeat = true;
    } else { // next
        $context->current_comments_list->defaultIterator()->next();
        if (!is_null($context->current_comments_list->current)) {
            $context->comment = $context->current_comments_list->current;
            $repeat = true;
        } else {
            $context->resetCurrentList();
            $repeat = false;
        }
    }
    return $content;
}
