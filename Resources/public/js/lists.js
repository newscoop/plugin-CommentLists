$(document).ready(function() {
    //$('#list_name').hide();
    //$('.save-button-bar').show(); 
    //$('#list_name').show(); 
    $("#selectLists").select2({
        placeholder: 'Select list',

    }).on("change", function (e) {
        $('#playlist-name').attr('value', $("#selectLists").select2('data').text);
        $('#playlist-id').attr('value', $("#selectLists").select2('data').id);
        $('.save-button-bar').show(); 
        $('#list_name').show();      
    });

    $('.add').live('click', function() {
        $('.save-button-bar').show(); 
        $('#list_name').show();
        $('#remove-ctrl').hide(); 
    });

   /* $("#issue_filter").select2({
        placeholder: 'Choose...'
    });*/
    /*$("#publication_filter").select2();
    $("#issue_filter").select2();
    $("#section_filter").select2();
    $("#article_filter").select2();*/
    

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
        $(".dataTables_filter input").attr("placeholder", "Search").addClass("context-search search");
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
            position : 'top',
            buttons:
            {
                "Delete" : function() {
                    callController(Routing.generate('newscoop_commentlists_admin_removelist'), {id: $('#playlist-id').val()}, function() {
                        location.reload();
                    }, true );
                    $(this).dialog( "close" );
                },
                "Cancel" : function() {
                    $(this).dialog( "close" );
                }
            }
        });
        $('#remove-ctrl').click(function(){ $( "#dialog-confirm" ).dialog('open') });
    });

    $('#filter_name').change(function()
    {
        refreshFilterIssues();
        refreshFilterSections();
        refreshFilterArticles();
    })

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
    });

    // filters managment
    $('fieldset.filters .extra').each(function()
    {
        var extra = $(this);
        $('dl', extra).hide();
        $('<select class="filters"></select>')
        .appendTo(extra)
            .each(function() { // init options
                var select = $(this);
                $('<option value="">Filter by...</option>')
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
        var resetMsg = 'Reset all filters';

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

contextListFilters = {};

filters = [];

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
    if(data.code == 200) {
        var items = data.items;
        for(i = 0; i < items.length; i++) {
            var item = items[i];
            console.log(item);
            appendItemToContextList(item.articleId, item.date.date, item.title, item.workflowStatus);
        }
    }
    toggleDragZonePlaceHolder();
}
function loadContextList()
{
    var relatedArticles = $('#context_list').sortable( "serialize");
    var aoData = new Array();
    var items = new Array('1_1','0_0');
    aoData.push("context_box_load_list");
    aoData.push(items);
    aoData.push({ 'playlistId': '1' });
    //callServer('{$dataUrl}', aoData, fnLoadContextList, true);
}
function appendItemToContextList(article_id, article_date, article_title, status)
{
    if (typeof status != 'undefined') {
       var articleStatus = ' ('+status+')';
   } else {
    var articleStatus = '';
}

$("#context_list").append
(
    '<li class="item" id="'+article_id+'">'+
    '<input type="hidden" name="article-id[]" value="'+article_id+'" />'+
    '<div class="context-item">'+
    '<div class="context-drag-topics"><a href="#" title="drag to sort"></a></div>'+
    '<div class="context-item-header">'+
    '<div class="context-item-date">'+ article_date + articleStatus +'</div>'+
    '</div>'+
    '<a href="#" class="corner-button" style="display: block" '+
    'onClick="$(this).parent(\'div\').parent(\'li.item\').remove();toggleDragZonePlaceHolder();"><span class="ui-icon ui-icon-closethick"></span></a>'+
    '<div class="context-item-summary"></div>'+
    '</div>'+
    '</li>'
    ).find('#' + article_id + ' .context-item-summary').text(article_title);
closeArticle();
}
function deleteContextList()
{
    $("#context_list").html('<div id="drag-here-to-add-to-list" style="">Drag here to add to list</div>');
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
            flashMessage('Duplicate comment entry found', 'error', false);
            cancelSave = true;
            return false;
        }
        comments.push(commentId);
    });

    if( cancelSave ) return false;

    var aoData =
    {
        'comments': comments,
        'id': '1', //playlist id here
        'name': $('#playlist-name').val()
    };
    console.log(aoData);
    callController(Routing.generate('newscoop_commentlists_admin_savelist'), aoData, fnSaveCallback);
}

function fnSaveCallback(data)
{
    if (typeof data['error'] != 'undefined' && data['error'])
    {
        var flash = flashMessage('Could not save the list', 'error', false);
        return false;
    }
    var pl = $(parent.document.body).find('#playlists option[value='+data.playlistId+']');
    if (pl.length == 0) {
        var opt = $('<option />').val(data.playlistId).text(data.playlistName);
        var sel = $(parent.document.body).find('#playlists');
        sel.append( opt );
        sel.val(data.playlistId)
        parent.triggerSelectClick();
    }
    else {
        pl.val(data.playlistId).text(data.playlistName).trigger('click');
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