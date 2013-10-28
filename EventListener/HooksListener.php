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
        $em = $this->container->get('em');
        $translator = $this->container->get('translator');

        $commentLists = $em->getRepository('Newscoop\CommentListsBundle\Entity\CommentList')
            ->createQueryBuilder('c')
            ->select('c.id', 'c.name')
            ->getQuery()
            ->getResult();
        //var_dump($event->getArgument('comment')->getId());die;

        $response = $this->container->get('templating')->renderResponse(
            'NewscoopCommentListsBundle:Hooks:options.html.twig',
            array(
                'lists' => $commentLists,
                'commentId' => $event->getArgument('comment')->getId()
            )
        );

        $event->addHookResponse($response);
    }
}