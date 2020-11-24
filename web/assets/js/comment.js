var comment = {
    comments: {},
    init: function() {
        
    },
    add: function() {
        var $comment = $('#comment');
        
        if ($.trim($comment.val()).length === 0) {
            return false;
        }
        else {
            var promise = $.post(Routing.generate('comment_add', { ticketId: ticket.id }), { comment: $comment.val() })
                .done(function(response) {
                    var $comment = comment.model;
                
                    $comment.find('.comment-creator').text(response.createdBy.name);
                    $comment.find('.comment-datetime').text(response.createdAt);
                    $comment.find('.comment-text').text(response.text);
                    
                    $('#comments').prepend($comment);
                })
                .fail(function() {
                    notifier.notify({message: 'Não foi possível salvar seu comentário.'}, true);
                });
                
            $comment.val('');
            dialog.close();
            
            return promise;
        }
    },
    load: function() {
        
    },
    check: function() {
        
    },
    model: $('<div class="mdl-card__supporting-text npv"><div class="ticket-entries"><div class="ticket-entry-icon"><i class="material-icons">person</i></div><div class="comment-content"><div class="comment-header"><b class="comment-creator"></b><br><i class="comment-datetime"></i></div><div class="comment-text"></div></div></div></div>')
};