<?php
/**
 * @package Newscoop\CommentListsBundle
 * @author Rafał Muszyński <rafal.muszynski@sourcefabric.org>
 * @copyright 2014 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

namespace Newscoop\CommentListsBundle\EventListener;

use Symfony\Component\HttpFoundation\Request;
use Newscoop\EventDispatcher\Events\PluginHooksEvent;
use Newscoop\CommentListsBundle\Form\Type\CommentButtonType;

/**
 * Plugin hook
 */
class HooksListener
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * Constructor
     */
    public function __construct($container)
    {
        $this->container = $container;
    }

    /**
     * Comment lists action button
     */
    public function listsButton(PluginHooksEvent $event)
    {
        $em = $this->container->get('em');
        $commentId = $event->getArgument('commentId');
        $lists = $em->getRepository('Newscoop\CommentListsBundle\Entity\Comment')
            ->createQueryBuilder('c')
            ->select('c', 'l.id', 'l.name')
            ->leftJoin('c.list', 'l')
            ->where('l.is_active = true')
            ->andWhere('c.comment = :comment')
            ->setParameter('comment', $commentId)
            ->getQuery()
            ->getArrayResult();

        $listsArray = array();
        foreach ($lists as $key => $list) {
            $listsArray[] = $list['id'];
        }

        $form = $this->container->get('form.factory')->create(new CommentButtonType(), array(
            'lists' => $listsArray
        ), array('em' => $em));

        $response = $this->container->get('templating')->renderResponse(
            'NewscoopCommentListsBundle:Hooks:listsButton.html.twig',
            array(
                'form' => $form->createView(),
                'lists' => $lists,
                'commentId' => $commentId
            )
        );

        $event->addHookResponse($response);
    }
}