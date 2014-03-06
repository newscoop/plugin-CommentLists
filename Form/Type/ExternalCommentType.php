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
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManager;
use Newscoop\Entity\Comment;

class ExternalCommentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $em = $options['em'];
        $qb = $em->createQueryBuilder();
        $articles = $qb
            ->from('Newscoop\Entity\Article', 'a')
            ->select('a.name', 'a.number')
            ->getQuery()
            ->getArrayResult();

        $articlesArray = array();
        foreach ($articles as $key => $article) {
            $articlesArray[$article['number']] = $article['name'];
        }

        $statusMap = array(
            'approved' => 'plugin.lists.status.approved',
            'hidden' => 'plugin.lists.status.hidden',
            'pending' => 'plugin.lists.status.pending',
        );
        $statuses = array();
        foreach (Comment::$status_enum as $status) {
            if ($status != 'deleted') {
                $statuses[$status] = $statusMap[$status];
            }
        }

        $builder->add('commenterName', null, array(
            'label' => 'plugin.lists.label.commentername',
            'required' => true,
        ))
        ->add('commenterUrl', null, array(
            'label' => 'plugin.lists.label.commenterurl',
            'required' => false,
        ))
        ->add('date','dateTimePicker',array(
            'label' => 'plugin.lists.label.externaldate',
        ))
        ->add('articles', 'hidden', array(
            'label' => 'plugin.lists.label.article',
            'error_bubbling' => true,
            'required' => true,
        ))
        ->add('source', null, array(
            'label' => 'plugin.lists.label.externalsource',
            'required' => true,
        ))
        ->add('subject', null, array(
            'label' => 'plugin.lists.label.externalsubject',
            'required' => true,
        ))
        ->add('message', 'textarea', array(
            'label' => 'plugin.lists.label.externalmessage',
            'required' => true,
        ))
        ->add('status', 'choice', array(
            'label' => 'plugin.lists.label.status',
            'choices' => $statuses,
            'required' => true,
        ))
        ->add('recommended', 'checkbox', array(
            'label' => 'plugin.lists.label.recommended',
            'required' => false,
        ))
        ->add('filterButton', 'submit', array(
            'label' => 'plugin.lists.btn.submit'
        ));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'csrf_protection' => false
        ));

        $resolver->setRequired(array(
            'em',
        ));
    }

    public function getName()
    {
        return 'externalCommentForm';
    }
}