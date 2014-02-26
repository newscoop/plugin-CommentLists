<?php
/**
 * @package Newscoop\CommentListsBundle
 * @author Paweł Mikołajczuk <pawel.mikolajczuk@sourcefabric.org>
 * @author Rafał Muszyński <rafal.muszynski@sourcefabric.org>
 * @copyright 2013 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

namespace Newscoop\CommentListsBundle\EventListener;

use Newscoop\EventDispatcher\Events\CollectObjectsDataEvent;

class ListObjectsListener
{
    /**
     * Register plugin list objects in Newscoop
     *
     * @param CollectObjectsDataEvent $event
     */
    public function registerObjects(CollectObjectsDataEvent $event)
    {
        $event->registerListObject('newscoop\commentlistsbundle\templatelist\comments', array(
            'class' => 'Newscoop\CommentListsBundle\TemplateList\Comments',
            'list' => 'comments',
            'url_id' => 'pclid',
        ));

        $event->registerObjectTypes('featured_comment', array(
            'class' => '\Newscoop\CommentListsBundle\Meta\MetaComment'
        ));
    }
}