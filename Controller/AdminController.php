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
use Symfony\Component\HttpFoundation\JsonResponse;
use Newscoop\CommentListsBundle\Entity\CommentList;
use Newscoop\CommentListsBundle\Entity\Comment;
use Newscoop\CommentListsBundle\Form\Type\ExternalCommentType;
use Newscoop\Entity\Comment as BaseComment;

/**
 * Featured comments controller
 */
class AdminController extends Controller
{

    /** @var bool */
    protected $colVis = false;
    /** @var bool */
    protected $search = true;
    /** @var array */
    protected $items = null;

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

        $externalForm = $this->container->get('form.factory')->create(new ExternalCommentType(), array(), array('em' => $em));

        return array(
            'externalForm' => $externalForm->createView(),
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
    * @Route("/admin/comment-lists/save-external")
    */
    public function saveExternalAction(Request $request)
    {
        $em = $this->container->get('em');
        $form = $this->container->get('form.factory')->create(new ExternalCommentType(), array(), array('em' => $em));
        $form->handleRequest($request);
        if ($request->isMethod('POST')) {
            if ($form->isValid()) {
                $data = $form->getData();
                $comment = new BaseComment();
                $articleInfo = explode("_", $data['articles']);
                $language = $em->getRepository('Newscoop\Entity\Article')
                    ->findOneBy(array('number' => $articleInfo[4]))
                    ->getLanguage()
                    ->getCode();

                $values = array(
                    'name' => $data['commenterName'],
                    'email' => null,
                    'ip' => null,
                    'time_created' => $data['date'],
                    'url' => $data['commenterUrl'],
                    'source' => $data['source'],
                    'ip' => '',
                    'time_updated' => $data['date'],
                    'subject' => $data['subject'],
                    'message' => $data['message'],
                    'status' => 'approved',
                    'thread' => $articleInfo[4],
                    'language' => $language,
                );

                $comment = $em->getRepository('Newscoop\Entity\Comment')->save($comment, $values);
                $em->flush();

               return new JsonResponse(array('status' => true));
            }
        }

        return new JsonResponse(array('status' => false));
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
                    $em->remove($comment);
                }

                $em->flush();
            }

            if (!is_null($comments) && is_array($comments)) {
                foreach ($comments as $key => $commentId) {
                    $comment = $em->getRepository('Newscoop\CommentListsBundle\Entity\Comment')->findOneBy(array(
                        'comment' => (int) $commentId,
                        'list' => $list
                    ));

                    if (!$comment) {
                        $mainComment = $em->getRepository('Newscoop\Entity\Comment')->findOneBy(array(
                            'id' => (int) $commentId,
                        ));
                        $newComment = new Comment();
                        $newComment->setList($list);
                        $newComment->setComment($mainComment);
                        $newComment->setOrder($key);
                        $em->persist($newComment);
                    } else {
                        $comment->setOrder($key);
                        $em->merge($comment);
                    }
                }
            } else {
                $comments = $em->getRepository('Newscoop\CommentListsBundle\Entity\Comment')->findBy(array(
                    'list' => $list,
                    'is_active' => true,
                ));

                foreach ($comments as $comment) {
                    $em->remove($comment);
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
            foreach ($comments as $key => $comment) {
                $mainComment = $em->getRepository('Newscoop\Entity\Comment')->findOneBy(array(
                    'id' => (int) $comment,
                ));

                $newComment = new Comment();
                $newComment->setList($this->findListByName($em, $listName));
                $newComment->setComment($mainComment);
                $newComment->setOrder($key);
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
    * @Route("/admin/comment-lists/add-comment", options={"expose"=true})
    */
    public function addCommentToList(Request $request)
    {
        $em = $this->container->get('em');
        $comment = $request->request->get('comment');
        $list = $request->request->get('list');
        $mainComment = $em->getRepository('Newscoop\Entity\Comment')->findOneBy(array(
            'id' => $comment,
        ));

        $commentList = $em->getRepository('Newscoop\CommentListsBundle\Entity\CommentList')->findOneBy(array(
            'id' => $list,
            'is_active' => true
        ));

        $commentCheck = $em->getRepository('Newscoop\CommentListsBundle\Entity\Comment')->findOneBy(array(
            'comment' => $mainComment,
            'list' => $commentList,
        ));

        if (!$commentCheck) {
            $qb = $em->getRepository('Newscoop\CommentListsBundle\Entity\Comment')
                ->createQueryBuilder('c');

            $qb->select($qb->expr()->max('c.order'))
                ->where('c.list = :list')
                ->setParameter('list', $list);

            $order = $qb->getQuery()->getResult();

            $orderValue = $order[0][1];
            if ($orderValue == null) {
                $orderValue = 0;
            } else {
                $orderValue = (int) $order[0][1] + 1;
            }

            $newComment = new Comment();
            $newComment->setList($commentList);
            $newComment->setComment($mainComment);
            $newComment->setOrder($orderValue);
            $em->persist($newComment);
            $em->flush();

            return new JsonResponse(array('status' => true));
        }
    }

    /**
    * @Route("/admin/comment-lists/remove-comment", options={"expose"=true})
    */
    public function removeCommentFromList(Request $request)
    {
        $em = $this->container->get('em');
        $comment = $request->request->get('comment');
        $list = $request->request->get('list');
        $mainComment = $em->getRepository('Newscoop\Entity\Comment')->findOneBy(array(
            'id' => $comment,
        ));

        $commentList = $em->getRepository('Newscoop\CommentListsBundle\Entity\CommentList')->findOneBy(array(
            'id' => $list,
            'is_active' => true
        ));

        $toRemoveComment = $em->getRepository('Newscoop\CommentListsBundle\Entity\Comment')->findOneBy(array(
            'list' => $commentList,
            'comment' => $comment
        ));
        $em->remove($toRemoveComment);
        $em->flush();

        return new JsonResponse(array('status' => true));
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
        $publication = $request->get('publication', null);

        $issues = $em->getRepository('Newscoop\Entity\Issue')
            ->createQueryBuilder('i')
            ->where('i.publication = :publication')
            ->setParameter('publication', $publication)
            ->getQuery()
            ->getResult();

        $newIssues = array();
        $issuesNo = is_array($issues) ? count($issues) : 0;
        $menuIssueTitle = $issuesNo > 0 ? $translator->trans('plugin.lists.label.allissues') : $translator->trans('No issues found');
        foreach ($issues as $issue) {
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
        $publication = $request->get('publication', null);
        $issue = $request->get('issue');

        $qb = $em->getRepository('Newscoop\Entity\Section')
            ->createQueryBuilder('s')
            ->select('s', 'l.id', 'i.number');

        if ($request->get('language') > 0) {
            $language = $request->get('language');
        }

        $qb
            ->leftJoin('s.language', 'l')
            ->leftJoin('s.issue', 'i')
            ->where('s.publication = :publication');

        if ($issue > 0) {
            $issueArray = explode("_", $issue);
            $issue = $issueArray[1];
            if (isset($issueArray[2])) {
                $language = $issueArray[2];
                $qb->andWhere('l.id = :language');
            }

            $qb
                ->andWhere('i.number = :issue')
                ->setParameters(array(
                    'issue' => $issue,
                    'language' => $language,
                ));
        }

        $sections = $qb->setParameter('publication', $publication)
            ->groupBy('s.name')
            ->orderBy('s.name', 'asc')
            ->getQuery()
            ->getArrayResult();

        $newSections = array();
        foreach ($sections as $section) {
            $newSections[] = array('val' => $publication.'_'.$section['number'].'_'.$section['id'].'_'.$section[0]['number'], 'name' => $section[0]['name']);
        }

        $sectionsNo = is_array($newSections) ? count($newSections) : 0;
        $menuSectionTitle = $sectionsNo > 0 ? $translator->trans('plugin.lists.label.allsections') : $translator->trans('No sections found');

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
        $publication = $request->get('publication', null);
        $issue = $request->get('issue');
        $section = $request->get('section');
        $articleId = $request->get('article');
        $searchTerm = $request->get('term');
        $constraints = array();
        $operator = new \Operator('is', 'integer');

        $qb = $em->getRepository('Newscoop\Entity\Article')
            ->createQueryBuilder('a')
            ->select('a.issueId', 'l.id', 'a.sectionId', 'a.number', 'a.name')
            ->leftJoin('a.language', 'l');

        if ($searchTerm && $publication == 0) {
            $qb->where($qb->expr()->like('a.name', $qb->expr()->literal('%'.$searchTerm.'%')));
        } else {

            if ($request->get('language') > 0) {
                $language = $request->get('language');
            }

            $qb->where('a.publication = :publication');

            if ($issue > 0) {
                $issueArray = explode("_", $issue);
                $publication = $issueArray[0];
                $issue = $issueArray[1];
                if (isset($issueArray[2])) {
                    $language = $issueArray[2];
                }

                $constraints[] = new \ComparisonOperation('Articles.NrIssue', $operator, $issue);
                $qb
                    ->andWhere('a.issueId = :issue')
                    ->setParameter('issue', $issue);
            } else if ($section > 0) {
                $sectionArray = explode("_", $section);
                $publication = $sectionArray[0];
                $section = $sectionArray[3];
                $issue = $sectionArray[1];
                if (isset($issueArray[2])) {
                    $language = $issueArray[2];
                }

                $constraints[] = new \ComparisonOperation('Articles.NrIssue', $operator, $issue);
                $constraints[] = new \ComparisonOperation('Articles.NrSection', $operator, $section);
                $qb
                    ->andWhere('a.sectionId = :section')
                    ->andWhere('a.issueId = :issue')
                    ->setParameter('section', $section)
                    ->setParameter('issue', $issue);
            }

            if ($searchTerm) {
                $constraints[] = new \ComparisonOperation('Articles.IdPublication', $operator, $publication);
                $countTotal = 20;
                $articleNumbers = \Article::SearchByKeyword($searchTerm, true, $constraints, array(), 0, 0, $countTotal, false);

                foreach ($articleNumbers as $key => $value) {
                    $qb->andWhere($qb->expr()->orX($qb->expr()->eq('a.number', $value['number'])));
                }
            }

            $qb->setParameter('publication', $publication);
        }

        $articles = $qb
            ->setMaxResults(20)
            ->orderBy('a.name', 'asc')
            ->getQuery()
            ->getArrayResult();

        $newArticles = array();
        foreach ($articles as $article) {
            $newArticles[] = array(
                'val' => $publication.'_'.$article['issueId'].'_'.$article['id'].'_'.$article['sectionId'].'_'.$article['number'],
                'name' => $article['name']
            );
        }

        $articlesNo = is_array($newArticles) ? count($newArticles) : 0;
        $menuArticleTitle = $articlesNo > 0 ? $translator->trans('plugin.lists.label.allart') : $translator->trans('No articles found');

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
        $paging = $this->items === null ? 'ip' : 'i';

        return sprintf('<"H"%s%s>t<"F"%s%s>',
            $colvis,
            $search,
            $paging,
            $this->items === null ? 'l' : ''
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
        if (isset($issue)) {
            if ($issue != 0) {
                $issueFiltersArray = explode('_', $issue);
                if (count($issueFiltersArray) > 1) {
                    $publication = $issueFiltersArray[0];
                    $issue = $issueFiltersArray[1];
                    $language = $issueFiltersArray[2];
                }
            }
        }

        //fix for the new section filters
        if (isset($section)) {
            if ($section != 0) {
                $sectionFiltersArray = explode('_', $section);
                if (count($sectionFiltersArray) > 1) {
                    $publication = $sectionFiltersArray[0];
                    $issue = $sectionFiltersArray[1];
                    $language = $sectionFiltersArray[2];
                    $section = $sectionFiltersArray[3];
                }
            }
        }

        //fix for the new articles filters
        if (isset($articleId)) {
            if ($articleId != 0) {
                $articleFiltersArray = explode('_', $articleId);
                if (count($articleFiltersArray) > 1) {
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

        $searchPhrase = $request->get('sSearch');
        if (isset($searchPhrase) && strlen($searchPhrase) > 0) {
            $return = $this->searchComment($em, $searchPhrase, $return, $limit, $start);

            return new Response(json_encode(array(
                'iTotalRecords' => $allComments,
                'iTotalDisplayRecords' => $return['count'],
                'sEcho' => (int) $request->get('sEcho'),
                'aaData' => $return['result'],
            )));
        }

        $sortDir = 'desc';
        $sortingCols = min(1, (int) $request->get('iSortingCols'));
        for ($i = 0; $i < $sortingCols; $i++) {
            $sortOptionsKey = (int) $request->get('iSortCol_' . $i);
            if (!empty($sortOptions[$sortOptionsKey])) {
                $sortBy = $sortOptions[$sortOptionsKey];
                $sortDir = $request->get('sSortDir_' . $i);
                break;
            }
        }

        $params = array(
            'publication' => $publication,
            'issueId' => $issue,
            'sectionId' => $section,
            'thread' => $articleId,
            'commenter' => $commenter,
            'language' => $language,
            'time_created' => $time_created,
        );

        $result = $this->getList($params, $start, $limit, $sortDir);
        $return = $result[0];
        $filteredCommentsCount = $result[1];

        return new Response(json_encode(array(
            'iTotalRecords' => $allComments,
            'iTotalDisplayRecords' => $filteredCommentsCount,
            'sEcho' => (int) $request->get('sEcho'),
            'aaData' => $return,
        )));
    }

    /**
     * @Route("/admin/comment-lists/update", options={"expose"=true})
     */
    public function updateAction(Request $request)
    {
        if ($request->isMethod('POST')) {
            $em = $this->container->get('em');
            $values = $request->request->all();
            if (!$values['subject'] || !$values['message']) {
                return new JsonResponse(array('status' => false));
            }

            try {
                $queryBuilder = $em->createQueryBuilder();
                $query = $queryBuilder->update('Newscoop\CommentListsBundle\Entity\Comment', 'c')
                    ->set('c.editedSubject', ':subject')
                    ->set('c.editedMessage', ':message')
                    ->where('c.commentId = :commentId')
                    ->setParameters(array(
                        'commentId' => $values['commentId'],
                        'subject' => $values['subject'],
                        'message' => $values['message']
                    ))
                    ->getQuery();
                $query->execute();
            } catch (\Exception $e) {
                return new JsonResponse(array('status' => $e->getMessage()));
            }

            return new JsonResponse(array(
                'status' => true,
            ));
        }
    }

    /**
     * Process item
     *
     * @param array $comment
     *
     * @return array
     */
    public function processItem($comment)
    {
        return array(
            $comment[0]['id'],
            $comment['language'],
            'language' => $comment['language'],
            'time_created' => $comment[0]['time_created']->format('Y-m-d H:i:s'),
            'commenter' => $comment['name'],
            'message' => $comment[0]['message'],
            'subject' => $comment[0]['subject'],
            'source' => $comment[0]['source'],
        );
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
    private function getList($params, $start, $limit, $sortDir)
    {
        $em = $this->container->get('em');
        $return = array();

        $qb = $em->getRepository('Newscoop\Entity\Comment')
                ->createQueryBuilder('c');
        $qb
            ->select('c', 'l.id as language', 'cc.name')
            ->leftJoin('c.commenter', 'cc')
            ->leftJoin('c.language', 'l')
            ->leftJoin('c.thread', 't')
            ->where($qb->expr()->isNotNull('c.forum'));

        if ($params['publication'] != null && $params['publication'] != '0') {
            $qb->andWhere('c.forum = :publication')
                ->setParameter('publication', $params['publication']);
        }

        if ($params['commenter'] != null && $params['commenter'] != '0') {
            $qb->andWhere('c.commenter = :commenter')
                ->setParameter('commenter', $params['commenter']);
        }

        if ($params['language'] != null && $params['language'] != '0') {
            $qb->andWhere('c.language = :language')
                ->setParameter('language', $params['language']);
        }

        if ($params['time_created'] != null && $params['time_created'] != '0') {
            $date = strtotime($params['time_created']);
            $date = strtotime("+1 day", $date);
            $qb->andWhere($qb->expr()->between('c.time_created',
                $qb->expr()->literal($params['time_created']),
                $qb->expr()->literal(date('Y-m-d', $date))
            ));
        }

        if ($params['thread'] != null && $params['thread'] != '0') {
            $qb->andWhere('c.article_num = :articleNumber')
                ->setParameter('articleNumber', $params['thread']);
        }

        if ($params['issueId'] != null && $params['issueId'] != '0') {
            $qb->andWhere('t.issueId = :issueId')
                ->setParameter('issueId', $params['issueId']);
        }

        if ($params['sectionId'] != null && $params['sectionId'] != '0') {
            $qb->andWhere('t.sectionId = :sectionId')
                ->setParameter('sectionId', $params['sectionId']);
        }

        $countBuilder = clone $qb;
        $commentsCount = (int) $countBuilder->select('COUNT(c)')->getQuery()->getSingleScalarResult();

        $comments = $qb->setMaxResults($limit)
            ->setFirstResult($start)
            ->orderBy('c.time_created', $sortDir)
            ->getQuery()
            ->getArrayResult();

        foreach ($comments as $comment) {
            $return[] = $this->processItem($comment);
        }

        return array(
            $return,
            $commentsCount
        );
    }

    /**
     * Gets comment list by given phrase
     *
     * @param Doctrine\ORM\EntityManager $em           Entity Manager
     * @param string                     $searchPhrase Search phrase
     * @param array                      $return       Array for results
     * @param int                        $limit        Limit result
     * @param int                        $start        Start from
     *
     * @return array
     */
    public function searchComment($em, $searchPhrase, $return, $limit, $start)
    {
        $qb = $em->getRepository('Newscoop\Entity\Comment')
            ->createQueryBuilder('c');

        $qb
            ->leftJoin('c.commenter', 'cc')
            ->leftJoin('c.language', 'l')
            ->where($qb->expr()->like('c.message', ':phrase'))
            ->orWhere($qb->expr()->like('c.subject', ':phrase'))
            ->setParameter('phrase', '%'.$searchPhrase.'%');

        $countBuilder = clone $qb;
        $count = (int) $countBuilder->select('COUNT(c)')->getQuery()->getSingleScalarResult();

        $search = $qb->select('c', 'l.id as language', 'cc.name')
            ->setMaxResults($limit)
            ->setFirstResult($start)
            ->getQuery()
            ->getArrayResult();

        foreach ($search as $comment) {
            $return[] = $this->processItem($comment);
        }

        return array(
            'count' => $count,
            'result' => $return
        );
    }

    /**
     * Gets list by given name
     *
     * @param Doctrine\ORM\EntityManager $em       Entity Manager
     * @param string                     $listName List name
     *
     * @return object
     */
    public function findListByName($em, $listName)
    {
        $list = $em->getRepository('Newscoop\CommentListsBundle\Entity\CommentList')->findOneBy(array(
            'name' => $listName,
            'is_active' => true
        ));

        return $list;
    }

    /**
     * Returns comments for a given list
     *
     * @param string $list Comment list
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
            ->orderBy('c.order', 'asc')
            ->getQuery()
            ->getResult();

        if (!$comments) {
            return json_encode(array(
                'status' => false
            ));
        }

        $commentsArray = array();
        foreach ($comments as $value) {
            $commentsArray[] = $value->getComment()->getId();
        }

        $commentsData = $em->getRepository('Newscoop\CommentListsBundle\Entity\Comment')
            ->createQueryBuilder('c')
            ->select('c', 'cc', 'cm')
            ->leftJoin('c.comment', 'cc')
            ->leftJoin('cc.commenter', 'cm')
            ->where('cc.id IN (:ids)')
            ->andWhere('c.list = :list')
            ->setParameters(array(
                'ids' => $commentsArray,
                'list' => $list
            ))
            ->orderBy('c.order', 'ASC')
            ->getQuery()
            ->getArrayResult();

        return json_encode(array(
            'items' => $commentsData,
            'status' => true
        ));
    }
}
