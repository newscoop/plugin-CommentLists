$(document).ready(function() {
    $("#playlists").select2({
        placeholder: translations['plugin.lists.label.selectlist'],

    }).on("change", function (e) {
        $('#playlist-name').attr('value', $("#playlists").select2('data').text);
        $('#playlist-id').attr('value', $("#playlists").select2('data').id);
        $('.save-button-bar').show(); 
        $('#list_name').show();
        $('#playlist-name-label').show();
        $('#remove-ctrl').show();
        $('#delete-all-btn').show();
        loadContextList();   
    });

    $('.add').live('click', function() {
        $('.save-button-bar').show(); 
        $('#list_name').show();
        $('#playlist-name-label').show();
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
                            $('#playlist-name-label').hide();
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
    })

    $('#issue_filter').change(function()
    {
        var smartlist = $(this).closest('.smartlist');
        var smartlistId = smartlist.attr('id').split('-')[1];
        filters[smartlistId]['section'] = 0;
        filters[smartlistId]['article'] = 0;
        refreshFilterSections();
        refreshFilterArticles();
    })

    $('#section_filter').change(function()
    {
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
                    var label = $('label', $(this)).text();
                    var option = $('option[value="' + label + '"]', select);
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

                // reset main filters
                $('> select', fieldset).val('0').change();

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

    args = new Array();
    args.push({
        'name': 'language',
        'value': langId
    });
    args.push({
        'name': 'publication',
        'value': publicationId
    });
    args.push({
        'name': 'issue',
        'value': issueId
    });
    args.push({
        'name': 'section',
        'value': sectionId
    });

    return args;
}

function triggerSelectClick()
{
    $('#playlists').change();
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
            appendItemToContextList(item.comment.id, item.comment.time_created.date, item.comment.message, item.comment.commenter.name);
        }
    }
    toggleDragZonePlaceHolder();
}
function loadContextList()
{
    var relatedComments = $('#context_list').sortable( "serialize");
    callController(Routing.generate('newscoop_commentlists_admin_loadlist'), {playlistId: $('#playlist-id').val()}, fnLoadContextList);
}
function appendItemToContextList(comment_id, comment_date, comment_message, comment_commenter)
{
  
$("#context_list").append
(
    '<li class="item" id="'+comment_id+'">'+
    '<input type="hidden" name="article-id[]" value="'+comment_id+'" />'+
    '<div class="context-item">'+
    '<div class="context-drag-topics"><a href="#" title="drag to sort"></a></div>'+
    '<div class="context-item-header">'+
    '<div class="context-item-date" style="float: none;">'+ comment_date+' ('+comment_commenter+')</div>'+
    '</div>'+
    '<a href="#" class="corner-button" style="display: block;" '+
    'onClick="$(this).parent(\'div\').parent(\'li.item\').remove();toggleDragZonePlaceHolder();"><span class="ui-icon ui-icon-closethick" style="margin-left: -5px;"></span></a>'+
    '<div class="context-item-summary"></div>'+
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
    $('#context-list-form').find('input[type=hidden]').each(function()
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
    var pl = $(parent.document.body).find('#playlists option[value='+data.listId+']');
    if (pl.length == 0) {
        var opt = $('<option />').val(data.listId).text(data.listName);
        var sel = $(parent.document.body).find('#playlists');
        sel.append( opt );
        sel.val(data.listId)
        triggerSelectClick();
    }
    else {
        pl.val(data.listId).text(data.listName).trigger('click');
    }
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

function handleFilterArticles(args)
{
    $('#article_filter >option').remove();
    $('#article_filter').append($("<option></option>").val('0').html(args.menuItemTitle));

    var items = args.items;
    for(var i=0; i < items.length; i++) {
        var item = items[i];
        $('#article_filter').append($("<option></option>").val(item.val).html(item.name));
    }
}

function resetFilterIssues() 
{
    $('#issue_filter >option').remove();
}

function resetFilterSections() 
{
    $('#section_filter >option').remove();
}

function resetFilterArticles() 
{
    $('#article_filter >option').remove();
}

function refreshFilterIssues()
{   
    if($('#publication_filter').val() <= 0) {
        resetFilterIssues();
    } else {
        var args = handleArgs();
        callController(Routing.generate('newscoop_commentlists_admin_getfilterissues'), args, handleFilterIssues);
    }
}

function refreshFilterSections()
{
    if($('#publication_filter').val() <= 0) {
        resetFilterSections();
    } else {
        var args = handleArgs();
        callController(Routing.generate('newscoop_commentlists_admin_getfiltersections'), args, handleFilterSections);
    }
}

function refreshFilterArticles()
{
    if($('#publication_filter').val() <= 0) {
        resetFilterArticles();
    } else {
        var args = handleArgs();
        callController(Routing.generate('newscoop_commentlists_admin_getfilterarticles'), args, handleFilterArticles);
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