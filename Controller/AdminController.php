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

    /** @var bool */
    protected $colVis = FALSE;
    /** @var bool */
    protected $search = FALSE;
    /** @var array */
    protected $items = NULL;
    /** @var bool */
    protected $order = FALSE;

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

        $languages = $em->getRepository('Newscoop\Entity\Language')
            ->createQueryBuilder('l')
            ->getQuery()
            ->getResult();

        return array(
            'lists' => $lists,
            'publications' => $publications,
            'languages' => $languages,
            'sDom' => $this->getContextSDom(),
            'id' => substr(sha1(1), -6),
            'items' => false, //no items on start, will auto load
            'colVis' => true,
            'order' => false
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
        
        $issue = $request->get('issue');
        
        if($issue > 0) {
            $issueArray = explode("_", $issue);
            $issue = $issueArray[1];
            if (isset($issueArray[2])) {
                $language = $issueArray[2];
            }
        }
        
        if($request->get('language') > 0) {
            $language = $request->get('language');
        }

        $sections = $em->getRepository('Newscoop\Entity\Section')
            ->createQueryBuilder('s')
            ->innerJoin('s.issue', 'i', 'WITH', 'i.number = ?2')
            ->where('s.publication = ?1')
            ->setParameter(1, $publication)
            ->setParameter(2, $issue)
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

    /**
    * @Route("/admin/comment-lists/getfilterarticles", options={"expose"=true})
    */
    public function getFilterArticles(Request $request) {

        $translator = $this->container->get('translator');
        $em = $this->container->get('em');
        $publication = $request->get('publication', NULL);
        $issue = $request->get('issue');
        $section = $request->get('section');
        
        if($section > 0) {
            $sectionArray = explode("_", $section);
            $section = $sectionArray[3];
            if (isset($issueArray[2])) {
                $language = $issueArray[2];
            }
        }
        
        if($request->get('language') > 0) {
            $language = $request->get('language');
        }

        $articles = $em->getRepository('Newscoop\Entity\Article')
            ->createQueryBuilder('s')
            ->innerJoin('s.issue', 'i', 'WITH', 'i.number = ?2')
            ->where('s.publication = ?1 AND s.sectionId = ?3')
            ->setParameter(1, $publication)
            ->setParameter(2, $issue)
            ->setParameter(3, $section)
            ->getQuery()
            ->getResult();


        $newArticles = array();
        foreach($articles as $article) {
            $newArticles[] = array('val' => $article->getPublicationId().'_'.$article->getIssueId().'_'.$article->getLanguageId().'_'.$article->getSectionId().'_'.$article->getNumber(), 'name' => $article->getName());
        }

        $articlesNo = is_array($newArticles) ? sizeof($newArticles) : 0;
        $menuArticleTitle = $articlesNo > 0 ? $translator->trans('All Articles') : $translator->trans('No articles found');
        
        return new Response(json_encode(array(
            'items' => $newArticles,
            'itemsNo' => $articlesNo,
            'menuItemTitle' => $menuArticleTitle
        )));
    }

    /**
     * Get Context Box sDom property.
     * @return string
     */
    public function getContextSDom()
    {
        $colvis = $this->colVis ? 'C' : '';
        $search = $this->search ? 'f' : '';
        $paging = $this->items === NULL ? 'ip' : 'i';
        return sprintf('<"H"%s%s>t<"F"%s%s>',
            $colvis,
            $search,
            $paging,
            $this->items === NULL ? 'l' : ''
        );
    }

    /**
    * @Route("/admin/comment-lists/dodata", options={"expose"=true})
    */
    public function doData(Request $request)
    {   
        // start >= 0
        $start = max(0, !$request->get('iDisplayStart') ? 0 : (int) $request->get('iDisplayStart'));

        // results num >= 10 && <= 100
        $limit = min(100, min(10, !$request->get('iDisplayLength') ? 0 : (int) $request->get('iDisplayLength')));

        $em = $this->container->get('em');
        // filters - common
        $filters = array(
            'publication' => array('is', 'integer'),
            'issue' => array('is', 'integer'),
            'section' => array('is', 'integer'),
            'language' => array('is', 'integer'),
            'publish_date' => array('is', 'date'),
            'publish_date_from' => array('greater_equal', 'date'),
            'publish_date_to' => array('smaller_equal', 'date'),
            'author' => array('is', 'integer'),
            'topic' => array('is', 'integer'),
            'workflow_status' => array('is', 'string'),
            'creator' => array('is', 'integer'),
            'type' => array('is', 'string')
        );

        // mapping form name => db name
        $fields = array(
            'publish_date_from' => 'publish_date',
            'publish_date_to' => 'publish_date',
            'language' => 'idlanguage',
            'creator' => 'iduser',
        );

        $issue = $request->get('issue');
        $publication = $request->get('publication');
        $language = $request->get('language');
        $section = $request->get('section');
        $articleId = $request->get('article');

        //fix for the new issue filters
        if(isset($issue)) {
            if($issue != 0) {
                $issueFiltersArray = explode('_', $issue);
                if(count($issueFiltersArray) > 1) {
                    $publication = $issueFiltersArray[0];
                    $issue = $issueFiltersArray[1];
                    $language = $issueFiltersArray[2];
                }
            }
        }
        
        //fix for the new section filters
        if(isset($section)) {
            if($section != 0) {
                $sectionFiltersArray = explode('_', $section);
                if(count($sectionFiltersArray) > 1) {
                    $publication = $sectionFiltersArray[0];
                    $language = $sectionFiltersArray[2];
                    $section = $sectionFiltersArray[3];
                }
            }
        }

        //fix for the new articles filters
        if(isset($articleId)) {
            if($articleId != 0) {
                $articleFiltersArray = explode('_', $articleId);
                if(count($articleFiltersArray) > 1) {
                    $publication = $articleFiltersArray[0];
                    $issue = $articleFiltersArray[1];
                    $language = $articleFiltersArray[2];
                    $section = $articleFiltersArray[3];
                    $articleId = $articleFiltersArray[4];
                }
            }
        }

        foreach ($filters as $name => $opts) {
            if ($request->get($name)) {
                $field = !empty($fields[$name]) ? $fields[$name] : $name;
                $articlesParams[] = new \ComparisonOperation($field, new \Operator($opts[0], $opts[1]), $request->get($name));
            }
        }

        $search = $request->get('sSearch');
        // search
        if (isset($search) && strlen($search) > 0) {
            $search_phrase = $search;
            $articlesParams[] = new \ComparisonOperation('search_phrase', new \Operator('like', 'string'), "__match_all.".$search_phrase);
        }

        // sorting
        $sortOptions = array(
            0 => 'bynumber',
            2 => 'bysectionorder',
            3 => 'byname',
            12 => 'bycomments',
            13 => 'bypopularity',
            16 => 'bycreationdate',
            17 => 'bypublishdate',
        );

        $sortBy = 'bysectionorder';
        $sortDir = 'asc';
        $sortingCols = min(1, (int) $request->get('iSortingCols'));
        for ($i = 0; $i < $sortingCols; $i++) {
            $sortOptionsKey = (int) $request->get('iSortCol_' . $i);
            if (!empty($sortOptions[$sortOptionsKey])) {
                $sortBy = $sortOptions[$sortOptionsKey];
                $sortDir = $request->get('sSortDir_' . $i);
                break;
            }
        }

        $params = array();
        $return = array();
        $filteredCommentsCount = 0;
        $allComments = 0;

        if ($publication) {
            $params['forum'] = $publication;
        }

        $comments = $em->getRepository('Newscoop\Entity\Comment')->findBy(
            $params,
            array('id' => $sortDir),
            $limit,
            $start
        );

        foreach($comments as $comment) {
            $return[] = $this->processItem($comment);
        }

        $allComments = $em->getRepository('Newscoop\Entity\Comment')
            ->createQueryBuilder('c')
            ->select('count(c)')
            ->getQuery()
            ->getSingleScalarResult();
        
        $filteredCommentsCount = $allComments;

        if ($issue != '0' && $issue != NULL) {
            $return = array();
            $articlesByIssue = $em->getRepository('Newscoop\Entity\Article')
                ->createQueryBuilder('a')
                ->select('a.number')
                ->where('a.publication = :publication')
                ->andWhere('a.issueId = :issueId')
                ->setParameters(array(
                    'publication' => $publication,
                    'issueId' => $issue
                ))
                ->getQuery()
                ->getResult();

            foreach ($articlesByIssue as $article) {
                foreach ($this->getArticleComments($article['number'], $language, $sortDir, $em) as $comment) {
                    $return[] = $this->processItem($comment);
                }
            }

            $filteredCommentsCount = count($return);
            $return = array_slice($return, $start, $limit+$start); 
        }

        if ($section != '0' && $section != NULL) {
            $return = array();
            $articlesBySection = $em->getRepository('Newscoop\Entity\Article')
                ->createQueryBuilder('a')
                ->select('a.number')
                ->where('a.publication = :publication')
                ->andWhere('a.issueId = :issueId')
                ->andWhere('a.sectionId = :sectionId')
                ->setParameters(array(
                    'publication' => $publication,
                    'issueId' => $issue,
                    'sectionId' => $section
                ))
                ->getQuery()
                ->getResult();

            foreach ($articlesBySection as $article) {
                foreach ($this->getArticleComments($article['number'], $language, $sortDir, $em) as $comment) {
                    $return[] = $this->processItem($comment);
                }
            }

            $filteredCommentsCount = count($return);
            $return = array_slice($return, $start, $limit+$start); 
        }

        if ($articleId != '0' && $articleId != NULL) {
            $return = array();
            foreach ($this->getArticleComments($articleId, $language, $sortDir, $em) as $comment) {
                $return[] = $this->processItem($comment);
            }

            $filteredCommentsCount = count($return);
            $return = array_slice($return, $start, $limit+$start);
        }

        if ($language != '0' && $language != NULL) {
            $return = array();
            $comments = $em->getRepository('Newscoop\Entity\Comment')
                ->createQueryBuilder('c')
                ->where('c.language = :language')
                ->setParameters(array(
                    'language' => $language,
                ))
                ->getQuery()
                ->getResult();

            foreach ($comments as $comment) {
                $return[] = $this->processItem($comment);
            }

            $filteredCommentsCount = count($return);
            $return = array_slice($return, $start, $limit+$start); 
        }

        return new Response(json_encode(array(
            'iTotalRecords' => $allComments,
            'iTotalDisplayRecords' => $filteredCommentsCount,
            'sEcho' => (int) $request->get('sEcho'),
            'aaData' => $return,
        )));
    }

    /**
     * Process item
     * @param Newscoop\Entity\Comment $comment
     * @return array
     */
    public function processItem($comment)
    {   
        $translator = $this->container->get('translator');

        return array(
            $comment->getId(),
            $comment->getLanguage()->getId(),
            'language' => $comment->getLanguage()->getId(),
            'time_created' => $comment->getTimeCreated()->format('Y-m-d H:i:s'),
            'commenter' => $comment->getCommenterName(),
            'message' => $comment->getMessage()
        );
    }

    /**
     * Get comments for article
     * @param  int                     $article  Article number
     * @param  string                     $language Language id
     * @return Newscoop\Entity\Comment $comment Comment
     */
    public function getArticleComments($article, $language, $sortDir, $em)
    {
        $comment = $em->getRepository('Newscoop\Entity\Comment')->findBy(
            array(
                'thread' => $article, 
                'language' => $language,
            ),
            array('id' => $sortDir),
            null,
            null
        );

        return $comment;
    }
}