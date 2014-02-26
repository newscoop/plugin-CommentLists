<?php
/**
 * @package Newscoop\CommentListsBundle
 * @author Rafał Muszyński <rafal.muszynski@sourcefabric.org>
 * @copyright 2014 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

namespace Newscoop\CommentListsBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class CommentButtonType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $em = $options['em'];
        $qb = $em->createQueryBuilder();
        $commentLists = $qb
            ->from('Newscoop\CommentListsBundle\Entity\CommentList', 'l')
            ->select('l.id', 'l.name')
            ->where('l.is_active = true')
            ->getQuery()
            ->getArrayResult();

        $listsArray = array();
        foreach ($commentLists as $key => $list) {
            $listsArray[$list['id']] = $list['name'];
        }

        $builder->add('lists', 'choice', array(
            'choices' => $listsArray,
            'multiple'  => true,
            'empty_value' => 'plugin.lists.label.selectlist',
        ));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(array(
            'em',
        ));
    }

    public function getName()
    {
        return 'commentButtonForm';
    }
}