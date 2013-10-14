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
function format(item) { return item.name; };
function formatDiv(item) { return "<div class='select2-results'>" + item.name + "</div>"; }

function selectBox(type, params, url, onChangeType) {
    type.select2({
        ajax: {
            url: url,
            dataType: 'json',
            data: function () {
                return params;
            },
            results: function (data) {
                return {results: data};
            }
        },
        initSelection: function(element, callback) {
            var data = {id: element.val(), text: element.val()};
            callback(data);
        },
        formatResult: formatDiv,
        formatSelection: format,
        escapeMarkup: function (m) { return m; }

    }).on("change", function (e) {
        //$('#selectedId').val($("#select").select2("val"));  
    });
};