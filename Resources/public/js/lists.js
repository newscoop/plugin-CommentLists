$(document).ready(function() {
    $('#issue_filter').hide();
    $('#section_filter').hide();
    $("#playlists").select2({
        placeholder: translations['plugin.lists.label.selectlist'],

    }).on("change", function (e) {
        $('#playlist-name').attr('value', $("#playlists").select2('data').text);
        $('#playlist-id').attr('value', $("#playlists").select2('data').id);
        $('.save-button-bar').show(); 
        $('#list_name').show();
        $('#playlist-id').show();
        $('#playlist-name-label').show();
        $('#playlist-id-label').show();
        $('#remove-ctrl').show();
        $('#delete-all-btn').show();
        loadContextList();   
    });

    $('.add').live('click', function() {
        $('.save-button-bar').show(); 
        $('#list_name').show();
        $('#playlist-name-label').show();
        //$('#playlist-id').show();
        $('#remove-ctrl').hide();
        $('#delete-all-btn').show();
        if ($('#playlist-name').val() != '') {
           deleteContextList()
           $('#playlist-name').val('');
           $("#playlists").select2('val', '');
        }
    });

    $(".toggle.filters legend").toggle(function() {
        var filter = $(".toggle.filters");
        filter.removeClass("closed");
        filter.find('dl:first').show();
        
    }, function() {
        var filter = $(".toggle.filters");
        filter.addClass('closed');
        filter.find('dl:first').hide();
    });

    $(function()
    {
        $(".dataTables_filter input").attr("placeholder", translations['plugin.lists.label.search']).addClass("context-search search form-control").attr('style', 'width:388px');
        $(".fg-toolbar .ui-toolbar .ui-widget-header .ui-corner-tl .ui-corner-tr .ui-helper-clearfix").css("border","none");
        $(".fg-toolbar .ui-toolbar .ui-widget-header .ui-corner-bl .ui-corner-br .ui-helper-clearfix").css("background-color","#CCCCCC");
        $(".datatable").css("position","static");
    });

    $(function()
    {
        $( "#dialog-confirm" ).dialog
        ({
            resizable: false,
            height:140,
            modal: true,
            autoOpen : false,
            position : 'center',
            buttons:
            [
                {
                    text: translations['plugin.lists.btn.delete'],
                    click: function() { 
                        callController(Routing.generate('newscoop_commentlists_admin_removelist'), {id: $('#playlist-id').val()}, function() {
                            $(document.body).find('#playlists option[value='+$('#playlist-id').val()+']').remove();
                            deleteContextList()
                            $('#playlist-name').val('');
                            $('.save-button-bar').hide(); 
                            $('#list_name').hide();
                            $('#playlist-id').hide();
                            $('#playlist-name-label').hide();
                            $('#playlist-id-label').hide()
                            $('#remove-ctrl').hide();
                            $('#delete-all-btn').hide();
                            $("#playlists").select2('val', '');
                        }, true );
                        $(this).dialog( "close" );
                        flashMessage(translations['plugin.lists.label.listdeleted'], null, false);
                    }
                },
                {
                    text: translations['plugin.lists.btn.cancel'],
                    click: function() { 
                        $(this).dialog( "close" );
                    }
                }
            ]
        });
        $('#remove-ctrl').click(function(){ $( "#dialog-confirm" ).dialog('open') });
    });

    $('#publication_filter').change(function()
    {
        refreshFilterIssues();
        refreshFilterSections();
        refreshFilterArticles();
        $("#article_filter").select2("enable", true);
    })

    $('#issue_filter').change(function()
    {
        $("#article_filter").select2("enable", true);
        var smartlist = $(this).closest('.smartlist');
        var smartlistId = smartlist.attr('id').split('-')[1];
        filters[smartlistId]['section'] = 0;
        filters[smartlistId]['article'] = 0;
        refreshFilterSections();
        refreshFilterArticles();
    })

    $('#section_filter').change(function()
    {
        $("#article_filter").select2("enable", true);
        var smartlist = $(this).closest('.smartlist');
        var smartlistId = smartlist.attr('id').split('-')[1];
        filters[smartlistId]['article'] = 0;
        refreshFilterArticles();
    })

    // filters handle
    $('.smartlist .filters select, .smartlist .filters input').change(function()
    {
        var smartlist = $(this).closest('.smartlist');
        var smartlistId = smartlist.attr('id').split('-')[1];
        var name = $(this).attr('name');
        var value = $(this).val();

        if ($(this).attr('type') === "checkbox" && $(this).is(':checked')) {
            filters[smartlistId][name] = true;
        }
        else if ($(this).attr('type') === "checkbox" && !$(this).is(':checked')) {
            filters[smartlistId][name] = false;
        }
        else {
            filters[smartlistId][name] = value;
        }

        if($(this).attr('id') == 'filter_name' || $(this).attr('id') == 'publication_filter' ) {
            filters[smartlistId]['issue'] = 0;
            filters[smartlistId]['section'] = 0;
        }
        tables[smartlistId].fnDraw(true);
        return false;
    });

    // datepicker for dates
    $('input.date').datepicker({
        dateFormat: 'yy-mm-dd',
        maxDate: '+0d',
    }).addClass('form-control input-sm').attr('style', 'width: 255px; display: initial');

    // filters managment
    $('fieldset.filters .extra').each(function()
    {
        var extra = $(this);
        $('dl', extra).hide();
        $('<select class="filters form-control input-sm"></select>')
        .appendTo(extra)
            .each(function() { // init options
                var select = $(this);
                $('<option value="">'+translations['plugin.lists.label.filterby']+'</option>')
                .appendTo(select);
                $('dl dt label', extra).each(function() {
                    var label = $(this).text();
                    $('<option value="'+label+'">'+label+'</option>')
                    .appendTo(select);
                });
            }).change(function() {
                var select = $(this);
                var value = $(this).val();
                $(this).val('');
                $('dl', $(this).parent()).each(function() {
                    console.log($('label', $(this)).attr('for'));
                    var label = $('label', $(this)).text();
                    var option = $('option[value="' + label + '"]', select);
                    if ($('label', $(this)).attr('for') == 'filter_author') {
                        $("select[name='author']").css('width', '255px');
                        $("select[name='author']").parent().find('a.detach').remove();
                        $("select[name='author']").parent().append('<a class="detach">X</a>').click(function() {
                            $(this).parent('dl').hide();
                            option = $('option[value="' + label + '"]', select);
                            option.show();
                            $("select[name='author']").select2("val", "");
                            $('input, select', $(this).parent()).val('').change();
                        });
                        $("select[name='author']").removeClass('form-control input-sm');
                        $("select[name='author']").select2({
                            minimumInputLength: 3,
                        });
                    }
                    if (label == value) {
                        $(this).show();
                        $(this).insertBefore($('select.filters', $(this).parent()));
                        if ($('a', $(this)).length == 0) {
                            $('<a class="detach">X</a>').appendTo($('dd', $(this)))
                            .click(function() {
                                $(this).parent('dd').parent('dl').hide();
                                $('input, select', $(this).parent()).val('').change();
                                select.change();
                                option.show();
                            });
                        }
                        option.hide();
                    }
                });
        }); // change
});

$('fieldset.toggle.filters dl:first').each(function()
{
    var fieldset = $(this);
    var smartlist = fieldset.closest('.smartlist');
    var smartlistId = smartlist.attr('id').split('-')[1];

        // reset all button
        var resetMsg = translations['plugin.lists.label.reset'];

    $('<a href="#" class="reset" title="'+resetMsg+'">'+resetMsg+'</a>')
        .appendTo(fieldset)
        .click(function() {
                // reset extra filters
                $('.extra dl', fieldset).each(function() {
                    $(this).hide();
                    $('select, input', $(this)).val('');
                });
                $('select.filters', fieldset).val('');
                $('select.filters option', fieldset).show();

                resetFilterArticles();
                resetFilterSections();
                resetFilterIssues();
                $("#article_filter").select2("enable", false);
                $('#publication_filter').val('0');
                // reset main filters
                $('> select', fieldset).val('0');

                // redraw table
                filters[smartlistId] = {};
                tables[smartlistId].fnDraw(true);
                return false;
            });
    });

});

function handleArgs()
{
    if($('#filter_name').val() < 0) {
        langId = 0;
    } else {
        langId = $('#filter_name').val();
    }

    if($('#publication_filter').val() < 0) {
        publicationId = 0;
    } else {
        publicationId = $('#publication_filter').val();
    }

    if($('#issue_filter').val() < 0) {
        issueId = 0;
    } else {
        issueId = $('#issue_filter').val();
    }

    if($('#section_filter').val() < 0) {
        sectionId = 0;
    } else {
        sectionId = $('#section_filter').val();
    }

    args = {};
    args['language'] = langId;
    args['publication'] = publicationId;
    args['issue'] = issueId;
    args['section'] = sectionId;

    return args;
}

function toggleDragZonePlaceHolder()
{
    if($('#context_list').find('.context-item').html() != null) {
        $('#drag-here-to-add-to-list').css('display', 'none');
    } else {
        $('#drag-here-to-add-to-list').css('display', 'block');
    }
}
function fnLoadContextList(data)
{
    if(data.status == true) {
        var items = data.items;
        $("#context_list").html('');
        for(i = 0; i < items.length; i++) {
            var item = items[i];
            var subject = item.comment.subject;
            var message = item.comment.message;
            var edited = false;
            if (item.editedSubject) {
                subject = item.editedSubject;
                edited = true;
            }

            if (item.editedMessage) {
                message = item.editedMessage;
                edited = true;
            }

            appendItemToContextList(
                edited, item.comment.id, item.comment.subject, subject,
                item.comment.source, item.comment.time_created.date, item.comment.message, message, item.comment.commenter.name
            );
        }
    } else {
        deleteContextList();
    }

    toggleDragZonePlaceHolder();
}
function loadContextList()
{
    var relatedComments = $('#context_list').sortable( "serialize");
    callController(Routing.generate('newscoop_commentlists_admin_loadlist'), {playlistId: $('#playlist-id').val()}, fnLoadContextList);
}
function appendItemToContextList(edited, comment_id, comment_edited_subject, comment_subject, comment_source, comment_date, comment_edited_msg, comment_message, comment_commenter)
{

$("#context_list").append
(
    '<li class="item" id="'+comment_id+'">'+
    '<input type="hidden" name="comment-id[]" value="'+comment_id+'" />'+
    '<div class="context-item">'+
    '<div class="context-drag-topics"><a href="#" title="drag to sort"></a></div>'+
    '<div class="context-item-header">'+
    '<div class="context-item-date" style="float: none;">'+ comment_date+' ('+comment_commenter+') '+
    (comment_source ? '<span class=\"label label-info\">'+comment_source+'</span> ' : '')+
    (edited ? '<span class=\"label label-warning\">Edited</span>' : '')+
    '</div></div>'+
    '<a href="#" class="corner-button" style="display: block;" '+
    'onClick="$(this).parent(\'div\').parent(\'li.item\').remove();toggleDragZonePlaceHolder();"><span class="ui-icon ui-icon-closethick" style="margin-left: -5px;"></span></a>'+
    '<div class="context-item-subject">'+comment_subject+'</div>'+
    '<div class="context-item-summary"></div>'+
    '<input type="hidden" class="originalMessage" value="'+comment_edited_msg+'"/>'+
    '<input type="hidden" class="originalSubject" value="'+comment_edited_subject+'"/>'+
    '<div class="commentBtns" id="comment_'+comment_id+'" style="visibility: visible; display: none; float: right;">'+
    '<ul><li><button type="button" class="btn btn-default btn-xs action-edit"><span class="glyphicon glyphicon-edit"></span> '+translations['plugin.lists.btn.edit']+'</button>'+
    '</li></ul></div>'+
    '</div>'+
    '</li>'
    ).find('#' + comment_id + ' .context-item-summary').text(comment_message);
}
function deleteContextList()
{
    $("#context_list").html('<div id="drag-here-to-add-to-list" style="">'+translations['plugin.lists.label.drag']+'</div>');
}
function removeFromContext(param)
{
    $("#"+param).remove();
}

function popup_save()
{
    var comments = [];
    var cancelSave = false;
    $('#context-list-form').find('input[name="comment-id[]"]').each(function()
    {
        var commentId = $(this).val();
        if( $.inArray(commentId, comments) != -1 ) {
            flashMessage(translations['plugin.lists.label.duplicate'], 'error', false);
            cancelSave = true;
            return false;
        }
        comments.push(commentId);
    });

    if( cancelSave ) return false;

    var aoData =
    {
        'comments': comments,
        'name': $('#playlist-name').val()
    };
    callController(Routing.generate('newscoop_commentlists_admin_savelist'), aoData, fnSaveCallback);
}

function fnSaveCallback(data)
{
    if (typeof data['error'] != 'undefined' && data['error'])
    {
        var flash = flashMessage(translations['plugin.lists.label.couldnotsave'], 'error', false);
        return false;
    }

    var listName = $("#playlist-name").val();
    var listId = $("#playlist-id").val();
    $("#playlists").select2('data', {id: data.listId, text: listName });
    var flash = flashMessage('List saved', null, false);
}

function handleFilterIssues(args)
{
    $('#issue_filter >option').remove();
    $('#issue_filter').append($("<option></option>").val('0').html(args.menuItemTitle));

    var items = args.items;
    for(var i=0; i < items.length; i++) {
        var item = items[i];
        $('#issue_filter').append($("<option></option>").val(item.val).html(item.name));
    }
}

function handleFilterSections(args)
{
    $('#section_filter >option').remove();
    $('#section_filter').append($("<option></option>").val('0').html(args.menuItemTitle));

    var items = args.items;
    for(var i=0; i < items.length; i++) {
        var item = items[i];
        $('#section_filter').append($("<option></option>").val(item.val).html(item.name));
    }
}

function resetFilterIssues()
{
    $('#issue_filter').val('0');
}

function resetFilterSections()
{
    $('#section_filter').val('0');
}

function resetFilterArticles()
{
    $('#article_filter').select2("val", "");
}

function refreshFilterIssues()
{
    if($('#publication_filter').val() <= 0) {
        resetFilterIssues();
    } else {
        $('#issue_filter').show();
        var args = handleArgs();
        callController(Routing.generate('newscoop_commentlists_admin_getfilterissues'), args, handleFilterIssues);
    }
}

function refreshFilterSections()
{
    if($('#publication_filter').val() <= 0) {
        resetFilterSections();
    } else {
        $('#section_filter').show();
        var args = handleArgs();
        callController(Routing.generate('newscoop_commentlists_admin_getfiltersections'), args, handleFilterSections);
    }
}

function refreshFilterArticles()
{
    if($('#publication_filter').val() <= 0) {
        resetFilterArticles();
    } else {
        callServer('ping', [], function(json) {
            $('#article_filter').select2({
                placeholder: translations['plugin.lists.label.selectarticle'],
                id: function(article) { return article.val; },
                ajax: {
                    url: Routing.generate('newscoop_commentlists_admin_getfilterarticles'),
                    dataType: 'json',
                    quietMillis: 100,
                    data:function (term, page) {
                        var args = handleArgs();
                        args['term'] = term;
                        args['page'] = page;

                        return args;
                    },
                    results: function (data, page) {
                        return { results: data.items };
                    }
                },
                formatResult: function(article) { return article.name; },
                formatSelection: function(article) { return article.name; },
            });
        });
    }
}

jQuery.fn.dataTableExt.oApi.fnSetFilteringDelay = function ( oSettings, iDelay ) {
    /*
     * Inputs:      object:oSettings - dataTables settings object - automatically given
     *              integer:iDelay - delay in milliseconds
     * Usage:       $('#example').dataTable().fnSetFilteringDelay(250);
     * Author:      Zygimantas Berziunas (www.zygimantas.com) and Allan Jardine
     * License:     GPL v2 or BSD 3 point style
     * Contact:     zygimantas.berziunas /AT\ hotmail.com
     */
     var
     _that = this,
     iDelay = (typeof iDelay == 'undefined') ? 250 : iDelay;

     this.each( function ( i ) {
        $.fn.dataTableExt.iApiIndex = i;
        var
        $this = this, 
        oTimerId = null, 
        sPreviousSearch = null,
        anControl = $( 'input', _that.fnSettings().aanFeatures.f );

        anControl.unbind( 'keyup' ).bind( 'keyup', function(event) {
            var $$this = $this;
            var searchKeyword;
            var inputKeyword;

            inputKeyword = anControl.val();
            searchKeyword = inputKeyword;

            if (sPreviousSearch === null || sPreviousSearch != anControl.val()) {
                window.clearTimeout(oTimerId);
                sPreviousSearch = anControl.val();  
                oTimerId = window.setTimeout(function() {
                    $.fn.dataTableExt.iApiIndex = i;
                    searchKeyword = inputKeyword; 
                    _that.fnFilter( searchKeyword );
                }, iDelay);
            }
        });

        return this;
    } );
     return this;
 }