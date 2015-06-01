<?php
/**
 * @package Newscoop\CommentListsBundle
 * @author Rafał Muszyński <rafal.muszynski@sourcefabric.org>
 * @copyright 2014 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

namespace Newscoop\CommentListsBundle\Meta;

use Newscoop\CommentListsBundle\Entity\Comment;

/**
 * Meta comment class
 */
class MetaComment
{
    /**
     * @var Featured comment
     */
    private $featuredComment;

    /**
     * @var Original comment
     */
    private $originalComment;

    /**
     * @var text
     */
    public $editedMessage;

    /**
     * @var string
     */
    public $editedSubject;

    /**
     * @var string
     */
    public $subject;

    /**
     * @var text
     */
    public $message;

    /**
     * @var int
     */
    public $identifier;

    /**
     * @var string
     */
    public $nickname;

    /**
     * @var string
     */
    public $commenterUrl;

    /**
     * @var string
     */
    public $email;

    /**
     * @var boolean
     */
    public $isEdited;

    /**
     * @var boolean
     */
    public $anonymous_author;

    /**
     * @var datetime
     */
    public $submit_date;

    /**
     * @var Newscoop\Entity\Article
     */
    public $article;

    /**
     * @var Newscoop\Entity\User
     */
    public $user;

    /**
     * @var string
     */
    public $source;

    /**
     * @var Newscoop\CommentListsBundle\Services\ListCommentService
     */
    private $service;

    /**
     * @param int $commentId
     * @param int $listId
     */
    public function __construct($commentId = null, $listId = null)
    {
        if (!$commentId) {
            return;
        }

        $this->service = \Zend_Registry::get('container')->getService('commentlists.list');
        $this->featuredComment = $this->getComment($commentId, $listId);
        $this->originalComment = $this->featuredComment->getComment();
        $this->editedMessage = $this->featuredComment->getEditedMessage();
        $this->editedSubject = $this->featuredComment->getEditedSubject();
        $this->subject = $this->getSubject();
        $this->message = $this->getMessage();
        $this->identifier = $this->getId();
        $this->nickname = $this->getCommenter();
        $this->email = $this->getEmail();
        $this->commenterUrl = $this->originalComment->getCommenter()->getUrl();
        $this->anonymous_author = $this->isAuthorAnonymous();
        $this->submit_date = $this->getSubmitDate();
        $this->article = $this->getArticle();
        $this->user = $this->getUser();
        $this->source = $this->getSource();
        $this->isEdited = $this->isEdited();
    }


    /**
     * Get comment
     *
     * @param int $commentId
     * @param int $listId
     *
     * @return Newscoop\CommentListsBundle\Entity\Comment
     */
    protected function getComment($commentId, $listId)
    {
        return $this->service->findOneComment($commentId, $listId);
    }

    /**
     * Get comment id
     *
     * @return string
     */
    protected function getId()
    {
        return $this->originalComment->getId();
    }

    /**
     * Get commenter name
     *
     * @return string
     */
    protected function getCommenter()
    {
        return $this->originalComment->getCommenter()->getName();
    }

    /**
     * Check if commenter is anonymous
     *
     * @return bool
     */
    protected function isAuthorAnonymous()
    {
        $user = $this->originalComment->getCommenter()->getUser();

        return  $user ? false : true;
    }

    /**
     * Get commenter email
     *
     * @return string
     */
    protected function getEmail()
    {
        return $this->originalComment->getCommenter()->getEmail();
    }

    /**
     * Get submit date
     *
     * @return DateTime
     */
    protected function getSubmitDate()
    {
        return $this->originalComment->getTimeCreated()->format('Y-m-d H:i:s');
    }

    /**
     * Get subject
     *
     * @return string
     */
    protected function getSubject()
    {
        return $this->originalComment->getSubject();
    }

    /**
     * Get message
     *
     * @return text
     */
    protected function getMessage()
    {
        return $this->originalComment->getMessage();
    }

    /**
     * Get article
     *
     * @return MetaArticle
     */
    protected function getArticle()
    {
        return new \MetaArticle($this->originalComment->getLanguage()->getId(), $this->originalComment->getThread());
    }

    /**
     * Get user
     *
     * @return MetaUser
     */
    public function getUser()
    {
        $user = $this->originalComment->getCommenter()->getUser();

        return new \MetaUser($user);
    }

    /**
     * Get comment source
     *
     * @return string
     */
    protected function getSource()
    {
        return $this->originalComment->getSource();
    }

    /**
     * Checks if comment was edited
     *
     * @return boolean
     */
    protected function isEdited()
    {
        if ($this->featuredComment->getEditedMessage() || $this->featuredComment->getEditedSubject()) {
            return true;
        }

        return false;
    }
}
