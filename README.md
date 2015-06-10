Comment Lists plugin
===================

Group your favourite comments into lists and render them in any place of your website.

Exampe usage:

```smarty
{{ list_featured_comments length="7" name="example list" }}
{{ if $gimme->current_list->at_beginning }}
<div class="slideshow">
    <div class="slides">
{{ /if }}
        <div class="slide-item">
            {{ set_article number=$gimme->featured_comment->article->number }}
            <blockquote>{{ if $gimme->featured_comment->isEdited }}{{ $gimme->featured_comment->editedMessage|strip_tags:false }}{{ else }}{{ $gimme->featured_comment->message|strip_tags:false }}{{ /if }}</blockquote>

            <small>{{ $user=$gimme->featured_comment->user }}{{ if $gimme->featured_comment->source }}<a href="{{ $gimme->featured_comment->commenterUrl }}">{{ $gimme->featured_comment->nickname }}</a>{{ else }}<a>{{ $gimme->featured_comment->nickname }}</a>{{ /if }} zu <a href="{{ url options="article" }}">{{ $gimme->featured_comment->article->name }}</a></small>
        </div>
{{ if $gimme->current_list->at_end }}
    </div>
</div>
{{ /if }}
{{ /list_featured_comments }}
```