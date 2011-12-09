var Pop = {};

$(document).ready(function() {
    Pop.initDelete('main');
    Pop.initToggle('main');
    Pop.initDragging();
});

Pop.initDragging = function() {
    $('#tasks li').draggable({
        revert: 'invalid',
        helper: "clone"
    });
    $( "#projects li ul").droppable({
        drop: function( event, ui ) {
        Pop.recordTask(ui.draggable,$(this));
        }
     });
};


Pop.recordTask = function( $item,$target ) {
    $item.fadeOut(function() {
            $item.appendTo($target).fadeIn(function() {
                var v = $item.attr('data-value');
                var curr = $target.parents('li').attr('data-value');
                var project_id = $target.parents('li').attr('data-project_id');
                var total = parseFloat(v)+parseFloat(curr);
                $target.parents('li').attr('data-value',total).find('span').text(total);
                var _date = $('meta[name="dcterms.date"]').attr('content');
                var mydata = {
                'ymd': _date,
                'count': total,
                'project_id':project_id
                };
                var post_o = {
                'url': '/tracker/tasks/tasks',
                'type':'POST',
                'data':mydata,
                'success': function(resp) {
//pass
                },
                'error': function() {
                alert('sorry, error');
                }
                };
                $.ajax(post_o);
                });
    });
};

Pop.initToggle = function(id) {
    $('#'+id).find('a[class="toggle"]').click(function() {
        var id = $(this).attr('id');
        var tar = id.replace('toggle','target');
        $('#'+tar).toggle();
        return false;
    }); 
    $('input[type="button"][value="cancel"]').click(function() {
            $(this).parent('form').toggle();
            });
};

Pop.initFormDelete = function() {
    $("form[method='delete']").submit(function() {
        if (confirm('are you sure?')) {
            var del_o = {
                'url': $(this).attr('action'),
                'type':'DELETE',
                'success': function() {
                    location.reload();
                },
                'error': function() {
                    alert('sorry, cannot delete');
                }
            };
            $.ajax(del_o);
        }
        return false;
    });
};

Pop.initFormPrompt = function() {
    $("form[method='post'][class='prompt']").submit(function() {
        var msg = $(this).find("input[name='message']").attr('value');
        if (confirm(msg)) {
            return true;
        }
        return false;
    });
};

Pop.initDelete = function(id) {
    $('#'+id).find("a[class='delete']").click(function() {
        if (confirm('are you sure?')) {
            var del_o = {
                'url': $(this).attr('href'),
                'type':'DELETE',
                'success': function() {
                    location.reload();
                },
                'error': function() {
                    alert('sorry, cannot delete');
                }
            };
            $.ajax(del_o);
        }
        return false;
    });
};

Pop.initSortable = function(id) {
    $('#'+id).sortable({ 
        cursor: 'crosshair',
        opacity: 0.6,
        revert: true, 
        start: function(event,ui) {
            ui.item.addClass('highlight');
        },  
        stop: function(event,ui) {
            $('#proceed-button').addClass('hide');
            $('#unsaved-changes').removeClass('hide');
            $('#'+id).find("li").each(function(index){
                $(this).find('span.key').text(index+1);
            }); 
            ui.item.removeClass('highlight');
        }   
    });
};
 
Pop.initPutStatus = function() {
    $('#deptsList').find("form[method='put']").each(function() {
        $(this).submit(function() {
            var _o = {
                'url': $(this).attr('action'),
                'type':'PUT',
                'data':$(this).find("input[name='is_active']").attr('value'),
                'dataType':'text/plain',
                'success': function() {
                    location.reload();
                },
                'error': function() {
                    alert('sorry, cannot set status');
                }
            };
            $.ajax(_o);
            return false;
        });
    });
};



