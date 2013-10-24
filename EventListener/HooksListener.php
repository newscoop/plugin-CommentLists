<?php
/**
 * @package Newscoop\CommentListsBundle
 * @author Rafał Muszyński <rafal.muszynski@sourcefabric.org>
 * @copyright 2013 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

namespace Newscoop\CommentListsBundle\EventListener;

use Symfony\Component\HttpFoundation\Request;
use Newscoop\EventDispatcher\Events\PluginHooksEvent;

class HooksListener
{
    private $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    public function hook(PluginHooksEvent $event)
    {   
        $translator = $this->container->get('translator');

        $response = $this->container->get('templating')->renderResponse(
            'NewscoopCommentListsBundle:Hooks:sidebar.html.twig',
            array()
        );

        $event->addHookResponse($response);
    }
}