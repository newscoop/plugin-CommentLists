var queue = [];

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

            flashMessage('Processing...');

            if (handle) {
                handle(msg);
            }
        }, 
        'error': function(xhr, textStatus, errorThrown) {
            flashMessage('errrrror', 'error');

            queue.push({
                callback: url,
                args: params,
                handle: handle
            });
        }
    });


};