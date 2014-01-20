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

        $builder->add('commenterName', null, array(
            'label' => 'plugin.lists.label.commentername',
            'required' => true,
        ))
        ->add('commenterUrl', null, array(
            'label' => 'plugin.lists.label.commenterurl',
            'required' => false,
        ))
        ->add('date', 'datetime', array(
            'label' => 'plugin.lists.label.externaldate',
            'with_seconds' => false,
            'date_widget' => 'single_text',
            'time_widget' => 'single_text',
            'date_format' => 'yyyy-MM-dd',
            'required' => true,
        ))
        ->add('articles', 'choice', array(
            'label' => 'plugin.lists.label.article',
            'choices' => $articlesArray,
            'empty_value' => 'plugin.lists.label.selectarticle',
        ))
        ->add('source', null, array(
            'label' => 'plugin.lists.label.externalsource',
            'required' => true,
        ))
        ->add('subject', null, array(
            'label' => 'plugin.lists.label.externalsubject',
            'required' => false,
        ))
        ->add('message', 'textarea', array(
            'label' => 'plugin.lists.label.externalmessage',
            'required' => true,
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