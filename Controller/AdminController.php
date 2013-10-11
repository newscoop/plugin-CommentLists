<?php
/**
 * @package Newscoop\CommentListsBundle
 * @author Rafał Muszyński <rafal.muszynski@sourcefabric.org>
 * @copyright 2013 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

namespace Newscoop\CommentListsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Newscoop\CommentListsBundle\Entity\CommentList;
use Newscoop\CommentListsBundle\Entity\Comment;

class AdminController extends Controller
{
    /**
    * @Route("/admin/comment-lists")
    * @Template()
    */
    public function indexAction(Request $request)
    {   
        $em = $this->container->get('em');
        $lists = $em->getRepository('Newscoop\CommentListsBundle\Entity\CommentList')
            ->createQueryBuilder('c')
            ->where('c.is_active = true')
            ->getQuery()
            ->getResult();

        $publications = $em->getRepository('Newscoop\Entity\Publication')
            ->createQueryBuilder('p')
            ->getQuery()
            ->getResult();

        return array(
            'lists' => $lists,
            'publications' => $publications
        );
    }

    /**
    * @Route("/admin/comment-lists/getfilterissues", options={"expose"=true})
    */
    public function getFilterIssues(Request $request) {

        $translator = $this->container->get('translator');
        $em = $this->container->get('em');
        $publication = $request->get('publication', NULL);

        $issues = $em->getRepository('Newscoop\Entity\Issue')
            ->createQueryBuilder('i')
            ->where('i.publication = :publication')
            ->setParameter('publication', $publication)
            ->getQuery()
            ->getResult();

        $newIssues = array();
        $issuesNo = is_array($issues) ? sizeof($issues) : 0;
        $menuIssueTitle = $issuesNo > 0 ? $translator->trans('All Issues') : $translator->trans('No issues found');
        foreach($issues as $issue) {
            $newIssues[] = array('val' => $issue->getPublicationId().'_'.$issue->getNumber().'_'.$issue->getLanguageId() , 'name' => $issue->getName());
        }

        return new Response(json_encode(array(
            'items' => $newIssues,
            'itemsNo' => $issuesNo,
            'menuItemTitle' => $menuIssueTitle
        )));
    }

    /**
    * @Route("/admin/comment-lists/getfiltersections", options={"expose"=true})
    */
    public function getFilterSections(Request $request) {

        $translator = $this->container->get('translator');
        $em = $this->container->get('em');
        $publication = $request->get('publication', NULL);

        $sections = $em->getRepository('Newscoop\Entity\Section')
            ->createQueryBuilder('s')
            ->innerJoin('s.issue', 'i', 'WITH', 'i.number = ?2')
            ->where('s.publication = ?1')
            ->setParameter(1, $publication)
            ->setParameter(2, $request->get('issue', NULL))
            ->getQuery()
            ->getResult();

        $newSections = array();
        foreach($sections as $section) {
            $newSections[] = array('val' => $section->getIssue()->getPublicationId().'_'.$section->getIssue()->getNumber().'_'.$section->getLanguageId().'_'.$section->getNumber(), 'name' => $section->getName());
        }

        $sectionsNo = is_array($newSections) ? sizeof($newSections) : 0;
        $menuSectionTitle = $sectionsNo > 0 ? $translator->trans('All Sections') : $translator->trans('No sections found');
        
        return new Response(json_encode(array(
            'items' => $newSections,
            'itemsNo' => $sectionsNo,
            'menuItemTitle' => $menuSectionTitle
        )));
    }
}