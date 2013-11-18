<?php
/**
 * @package Newscoop\CommentListsBundle
 * @author Paweł Mikołajczuk <pawel.mikolajczuk@sourcefabric.org>
 * @copyright 2013 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

namespace Newscoop\CommentListsBundle\EventListener;

use Newscoop\EventDispatcher\Events\ListObjectsEvent;

class ListObjectsListener
{
    /**
     * Register plugin list objects in Newscoop
     * 
     * @param  ListObjectsEvent $event
     */
    public function registerListObject(ListObjectsEvent $event)
    {
        $event->registerListObject('newscoop\commentlistsbundle\templatelist\comments', array(
            'class' => 'Newscoop\CommentListsBundle\TemplateList\Comments',
            'list' => 'comments',
            'url_id' => 'clid',
        ));
    }
}