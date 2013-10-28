function callController(url, params, handle)
{   
    if (!params) {
        params = [];
    }

    $.ajax({
        type: "POST",
        url: url,
        data: params,
        dataType: "json",
        success: function(msg){
            flashMessage(translations['plugin.lists.label.processing']);
            if (handle) {
                $.each(msg, function( key, value ) {
                    if (key == 'aaData') {
                        for ( var i = 0; i < value.length; i++ ) {
                            var tmp = value[i];
                            value[i] = {0: tmp[0], 1: tmp[1], 2: "<div class=\"context-item\" langid=\""+tmp.language+"\"><div class=\"context-drag-topics\"><a href=\"#\" title=\"drag to sort\"></a></div><div class=\"context-item-header\"><div class=\"context-item-date\">"+tmp.time_created+" ("+tmp.commenter+")</div></div><a href=\"javascript:void(0)\" class=\"corner-button\" style=\"display: none\" onClick=\"removeFromContext($(this).parent(\'div\').parent(\'td\').parent(\'tr\').attr(\'id\'));removeFromContext($(this).parents(\'.item:eq(0)\').attr(\'id\'));toggleDragZonePlaceHolder();\"><span class=\"ui-icon ui-icon-closethick\"></span></a><div class=\"context-item-summary\">"+tmp.message+"</div></div>"};
                        }
                    }   
                }) 
                handle(msg);
            }
        }, 
        'error': function(xhr, textStatus, errorThrown) {
            flashMessage(translations['plugin.lists.label.interrupted'], 'error');
        }
    });
};