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
use Newscoop\Entity\Comment as BaseComment;

class AdminController extends Controller
{

    /** @var bool */
    protected $colVis = FALSE;
    /** @var bool */
    protected $search = true;
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

        $commenters = $em->getRepository('Newscoop\Entity\Comment\Commenter')
            ->createQueryBuilder('c')
            ->getQuery()
            ->getResult();

        return array(
            'lists' => $lists,
            'publications' => $publications,
            'languages' => $languages,
            'commenters' => $commenters,
            'sDom' => $this->getContextSDom(),
            'id' => substr(sha1(1), -6),
            'items' => false, //no items on start, will auto load
            'colVis' => true,
            'order' => true
        );
    }

    /**
    * @Route("/admin/comment-lists/savelist", options={"expose"=true})
    */
    public function saveList(Request $request) 
    {
        $em = $this->container->get('em');
        $comments = $request->get('comments');
        $listName = $request->get('name');

        $date = new \DateTime('now');

        if (!$listName) {
            $listName = 'CommentList-'.$date->format('Y-m-d H:i:s');
        }

        $list = $this->findListByName($em, $listName);
        if (count($list) > 0 && $list->getIsActive(true)) {
            $commentsToRemove = $em->getRepository('Newscoop\CommentListsBundle\Entity\Comment')
                ->createQueryBuilder('c')
                ->where('c.comment NOT IN (:ids)')
                ->andWhere('c.list = :list')
                ->setParameter('ids', $comments)
                ->setParameter('list', $list)
                ->getQuery()
                ->getResult();

            if ($commentsToRemove) {
                foreach ($commentsToRemove as $comment) {
                    $comment->setIsActive(false);
                }
            }

            if (!is_null($comments) && is_array($comments)) {
                foreach ($comments as $commentId) {
                    $comment = $em->getRepository('Newscoop\CommentListsBundle\Entity\Comment')->findOneBy(array(
                        'comment' => (int)$commentId,
                        'list' => $list
                    ));

                    if (!$comment) { 
                        $newComment = new Comment();
                        $newComment->setList($list);
                        $newComment->setComment((int)$commentId);
                        $em->persist($newComment);
                    } else {
                        $comment->setIsActive(true);
                    }
                }
            } else {
                $comments = $em->getRepository('Newscoop\CommentListsBundle\Entity\Comment')->findBy(array(
                    'list' => $list,
                    'is_active' => true,
                ));

                foreach ($comments as $comment) {
                    $comment->setIsActive(false);
                }
            }

            $em->flush();

            return new Response(json_encode(array('error' => false)));
        }

        $commentList = new CommentList();
        $commentList->setName($listName);
        $em->persist($commentList);
        $em->flush();

        if (!is_null($comments) && is_array($comments)) {
            foreach ($comments as $comment) {
                $newComment = new Comment();
                $newComment->setList($this->findListByName($em, $listName));
                $newComment->setComment((int)$comment);
                $em->persist($newComment);
            }

            $em->flush();
        }

        return new Response(json_encode(array(
            'error' => false,
            'listName' => $this->findListByName($em, $listName)->getName(),
            'listId' => $this->findListByName($em, $listName)->getId()
        )));
    }

    /**
    * @Route("/admin/comment-lists/removelist", options={"expose"=true})
    */
    public function removeList(Request $request) 
    {   
        $em = $this->container->get('em');
        $commentList = $em->getRepository('Newscoop\CommentListsBundle\Entity\CommentList')->findOneBy(array(
            'id' => $request->get('id'),
            'is_active' => true
        ));

        if ($commentList) {
            $commentList->setIsActive(false);
            $em->flush();
        }

        return new Response(json_encode(array('status' => false)));
    }

    /**
    * @Route("/admin/comment-lists/loadlist", options={"expose"=true})
    */
    public function loadList(Request $request) 
    {   
        return new Response($this->load($request->get('playlistId')));
    }

    /**
    * @Route("/admin/comment-lists/getfilterissues", options={"expose"=true})
    */
    public function getFilterIssues(Request $request) 
    {
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
    public function getFilterSections(Request $request) 
    {
        $translator = $this->container->get('translator');
        $em = $this->container->get('em');
        $publication = $request->get('publication', NULL);
        
        $issue = $request->get('issue');

        if($request->get('language') > 0) {
            $language = $request->get('language');
        }

        $sections = $em->getRepository('Newscoop\Entity\Section')
            ->createQueryBuilder('s')
            ->where('s.publication = ?1')
            ->setParameter(1, $publication)
            ->groupBy('s.name')
            ->orderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();
        
        if($issue > 0) {
            $issueArray = explode("_", $issue);
            $issue = $issueArray[1];
            if (isset($issueArray[2])) {
                $language = $issueArray[2];
            }

            $sections = $em->getRepository('Newscoop\Entity\Section')
                ->createQueryBuilder('s')
                ->innerJoin('s.issue', 'i', 'WITH', 'i.number = :issue')
                ->where('s.publication = :publication')
                ->setParameters(array(
                    'publication' => $publication,
                    'issue' => $issue
                ))
                ->groupBy('s.name')
                ->orderBy('s.name', 'ASC')
                ->getQuery()
                ->getResult();
        }

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
    public function getFilterArticles(Request $request) 
    {
        $translator = $this->container->get('translator');
        $em = $this->container->get('em');
        $publication = $request->get('publication', NULL);
        $issue = $request->get('issue');
        $section = $request->get('section');
        $articleId = $request->get('article');
        
        if($request->get('language') > 0) {
            $language = $request->get('language');
        }

        $articles = $em->getRepository('Newscoop\Entity\Article')
            ->createQueryBuilder('s')
            ->where('s.publication = :publication')
            ->setParameters(array(
                'publication' => $publication,
            ))
            ->orderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();

        if($issue > 0) {
            $issueArray = explode("_", $issue);
            $issue = $issueArray[1];
            if (isset($issueArray[2])) {
                $language = $issueArray[2];
            }

           $articles = $em->getRepository('Newscoop\Entity\Article')
                ->createQueryBuilder('s')
                ->innerJoin('s.issue', 'i', 'WITH', 'i.number = :issue')
                ->where('s.publication = :publication')
                ->setParameters(array(
                    'publication' => $publication,
                    'issue' => $issue,
                ))
                ->orderBy('s.name', 'ASC')
                ->getQuery()
                ->getResult();
        }

        if($section > 0) {
            $sectionArray = explode("_", $section);
            $section = $sectionArray[3];
            if (isset($issueArray[2])) {
                $language = $issueArray[2];
            }

            $articles = $em->getRepository('Newscoop\Entity\Article')
                ->createQueryBuilder('s')
                ->innerJoin('s.issue', 'i', 'WITH', 'i.number = :issue')
                ->where('s.publication = :publication AND s.sectionId = :section')
                ->setParameters(array(
                    'publication' => $publication,
                    'issue' => $issue,
                    'section' => $section
                ))
                ->orderBy('s.name', 'ASC')
                ->getQuery()
                ->getResult();
        }

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
        $issue = $request->get('issue');
        $publication = $request->get('publication');
        $section = $request->get('section');
        $articleId = $request->get('article');
        $commenter = $request->get('author');
        $time_created = $request->get('publish_date');
        $language = $request->get('language');

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

        $return = array();
        $params = array();
        $filteredCommentsCount = 0;
        $allComments = 0;

        if ($request->get('language') != null) {
            $language = $request->get('language');
        }

        $allComments = $em->getRepository('Newscoop\Entity\Comment')
            ->createQueryBuilder('c')
            ->select('count(c)')
            ->getQuery()
            ->getSingleScalarResult();
        
        $filteredCommentsCount = $allComments;

        $searchPhrase = $request->get('sSearch');
        if (isset($searchPhrase) && strlen($searchPhrase) > 0) {
            $return = $this->searchComment($em, $searchPhrase, $return);

            return new Response(json_encode(array(
                'iTotalRecords' => $allComments,
                'iTotalDisplayRecords' => count($return),
                'sEcho' => (int) $request->get('sEcho'),
                'aaData' => $return,
            )));
        }

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

        if ($publication) {
            $params['forum'] = $publication;
            $params = array(
                'publication' => $publication,
                'issueId' => $issue,
                'sectionId' => $section,
                'thread' => $articleId,
                'commenter' => $commenter,
                'language' => $language,
                'time_created' => $time_created
            );

        $result = $this->getList($params, $start, $limit, $sortDir);
        $return = $result[0];
        $filteredCommentsCount = $result[1];

        } else {

            $comments = $em->getRepository('Newscoop\Entity\Comment')->findBy(
                $params,
                array('id' => $sortDir),
                $limit,
                $start
            );

            foreach($comments as $comment) {
                $return[] = $this->processItem($comment);
            }

            //find all comments by extra filter
            if ($commenter  || $time_created || $language != null) {
                
                $return = array();
                $result = $this->getArticleComments(null, $commenter, $language, $time_created, $sortDir, $em);
                foreach($result as $comment) {
                    $return[] = $this->processItem($comment);
                }
            }
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
     *
     * @param  int|null                   $article   Article number
     * @param  string                     $commenter Commenter id
     * @param  string                     $language  Language id
     * @param  string                     $createdAt Time when comment was created id
     * @param  string                     $sortDir   Sorting type
     * @param  Doctrine\ORM\EntityManager $em   Entity Manager
     *
     * @return Newscoop\Entity\Comment    $comments  Comments
     */
    public function getArticleComments($article = null, $commenter, $language, $createdAt, $sortDir, $em)
    {   
        $parameters = array();

        if ($article != null) {
            $parameters['thread'] = $article;
        }

        if ($commenter != null && $commenter != '0') {
            $parameters['commenter'] = $commenter;
        }

        if ($language != null && $language != '0') {
            $parameters['language'] = $language;
        }

        $comments = $em->getRepository('Newscoop\Entity\Comment')->findBy(
            $parameters,
            array('id' => $sortDir),
            null,
            null
        );

        if ($createdAt != null && $createdAt != '0') {
            foreach ($comments as $comment) {
                if ($createdAt == $comment->getTimeCreated()->format('Y-m-d')) {
                    return array($comment);
                }
            }

            return new BaseComment();
        }

        return $comments;
    }

    /**
     * Gets comment list by given parameters
     *
     * @param array  $params  Parameters
     * @param string $start   Offset
     * @param string $limit   Max results
     * @param string $sortDir Sorting type
     *
     * @return array
     */
    private function getList($params, $start, $limit, $sortDir) {

        $em = $this->container->get('em');
        $return = array();
        $result = array();
        $commenter = null;
        $language = null;
        $createdAt = null;
        $query = "";

        if ($params['commenter'] != NULL && $params['commenter'] != '0') {
            $commenter = $params['commenter'];
        }

        if ($params['language'] != NULL && $params['language'] != '0') {
            $language = $params['language'];
        }

        if ($params['time_created'] != NULL && $params['time_created'] != '0') {
            $createdAt = $params['time_created'];
        }

        if ($params['thread'] != NULL && $params['thread'] != '0') {
            foreach ($this->getArticleComments($params['thread'], $commenter, $language, $createdAt, $sortDir, $em) as $comment) {
                $return[] = $this->processItem($comment);
            }
        } else {

            foreach($params as $key => $param) {
                if ($param != NULL && $param != '0' && $key != 'commenter' && $key != 'time_created') {
                    $query .= 'a.'.$key.' = '. $param .' AND ';
                }
            }

            $articles = $em->getRepository('Newscoop\Entity\Article')
                ->createQueryBuilder('a')
                ->select('a.number')
                ->where(substr($query, 0, -5))
                ->getQuery()
                ->getResult();

            foreach ($articles as $article) {
                foreach ($this->getArticleComments($article['number'], $commenter, $language, $createdAt, $sortDir, $em) as $comment) {
                    $return[] = $this->processItem($comment);
                }
            }
        }

        $count = count($return);
        $result = array_slice($return, $start, $limit);

        return array(
            $result,
            $count
        );
    }

    /**
     * Gets comment list by given phrase
     *
     * @param Doctrine\ORM\EntityManager $em           Entity Manager
     * @param string                     $searchPhrase Search phrase
     * @param array                      $return       Array for results
     *
     * @return array
     */
    public function searchComment($em, $searchPhrase, $return) {
        $search = $em->getRepository('Newscoop\Entity\Comment')
            ->createQueryBuilder('c')
            ->where('c.message LIKE :phrase')
            ->setParameter('phrase', '%'.$searchPhrase.'%')
            ->getQuery()
            ->getResult();

            foreach($search as $comment) {
                $return[] = $this->processItem($comment);
            }

        return $return;
    }

    /**
     * Gets list by given name
     *
     * @param Doctrine\ORM\EntityManager $em       Entity Manager
     * @param string                     $listName List name
     *
     * @return object
     */
    public function findListByName($em, $listName) {
        $list = $em->getRepository('Newscoop\CommentListsBundle\Entity\CommentList')->findOneBy(array(
                'name' => $listName,
                'is_active' => true
        ));

        return $list;
    }

    /**
     * Returns comments for a given list
     *
     * @param Newscoop\CommentListsBundle\Entity\CommentList $list      Comment list
     * @param int                                            $limit     Max results
     * @param int                                            $offset    Offset
     * @param bool                                           $is_active Status
     *
     * @return array
     */
    public function load($list)
    {
        $em = $this->container->get('em');
        $comments = $em->getRepository('Newscoop\CommentListsBundle\Entity\Comment')
            ->createQueryBuilder('c')
            ->innerJoin('c.list', 'l', 'WITH', 'l.id = ?1')
            ->where('c.is_active = true')
            ->setParameter(1, $list)
            ->getQuery()
            ->getResult();

        if (!$comments) {
            return json_encode(array(
                'status' => false
            ));
        }

        $commentsArray = array();
        foreach ($comments as $value) {
            $commentsArray[] = $value->getComment(); 
        }

        $commentsData = $em->getRepository('Newscoop\Entity\Comment')
            ->createQueryBuilder('c')
            ->select('c, cc')
            ->leftJoin('c.commenter', 'cc')
            ->where('c.id IN (:ids)')
            ->setParameter('ids', $commentsArray)
            ->getQuery()
            ->getArrayResult();

        return json_encode(array(
            'items' => $commentsData,
            'status' => true
        ));
    }
}