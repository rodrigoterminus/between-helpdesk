'use strict';

var notifier = {
    data: [],
    hasNotification: false,
    interval: 30000,
    unread: 0,
    snackbar: document.querySelector('.mdl-js-snackbar'),
    init: function() {
        if (Notification) {
            notifier.hasNotification = true;

            if (Notification.permission !== "granted") {
                Notification.requestPermission();
            }
        }

        notifier.load();
        setInterval(function() {
            notifier.load();
        }, notifier.interval);
    },
    load: function() {
        $.post(Routing.generate('notification_load'))
            .done(function(response) {
                $.removeCookie('notifications_read', {path: '/'});
                notifier.check(response);
            });
    },
    check: function(response) {
        var index = 0;
        var newNotifications = [];

        if (notifier.data.length < response.length) {
            index = response.length - notifier.data.length;
        }
        else {
            var data = JSON.parse(JSON.stringify(notifier.data));
            var last = response[response.length - 1];
            var i = 0;

            // Check number of new messages
            data.reverse().forEach(function(item) {
                if (item.timestamp === last.timestamp) {
                    index = i;
                    return false;
                }

                i++;
            });
        }

        // Get new messages
        if (index > 0) {
            for (var i = 0; i < index; i++) {
                newNotifications.push(response[i]);
            }
        }

        notifier.data = newNotifications.concat(notifier.data);
        notifier.show(newNotifications.reverse());
    },
    show: function(notifications) {
        notifications.forEach(function(item) {
            var message = notifier.resolveMessage(item.message);
            var icon = {
                icon: null,
                bgcolor: null,
                color: 'white'
            };

            switch (item.event) {
                case 'ticket.comment':
                    icon.icon = 'comment';
                    icon.bgcolor = 'blue-grey-300';
                    break;

                case 'ticket.finish':
                    icon.icon = 'check';
                    icon.bgcolor = 'green';
                    break;

                case 'ticket.post':
                    if (item.hasOwnProperty('origin') && item.origin === 'customer') {
                        icon.icon = 'face';
                        icon.bgcolor = 'indigo';
                    }
                    else {
                        icon.icon = 'mood';
                        icon.bgcolor = 'blue-grey';
                    }                    
                    break;

                case 'ticket.reopen':
                    icon.icon = 'lock_open';
                    icon.bgcolor = 'yellow-700';
                    break;

                case 'ticket.take':
                    icon.icon = 'person';
                    icon.bgcolor = 'blue-grey-300';
                    break;

                case 'ticket.transfer':
                    icon.icon = 'forward';
                    icon.bgcolor = 'blue-grey-300';
                    break;
            }

            var $wrapper = $('<a/>')
                .attr({
                    href: item.url,
                    'data-seen': item.seen +'',
                    'data-timestamp': item.timestamp
                })
                .addClass('notification vertical-align-top')
                .prependTo($('#dialog-notifications .mdl-dialog__content'));

            if (item.seen === false) {
                $wrapper.addClass('unread');
            }

            // Icon
            $('<div/>')
                .addClass('icon-text-content notification-icon mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-color--'+ icon.bgcolor)
                .append('<i class="material-icons mdl-color-text--'+ icon.color +'">'+ icon.icon +'</i>')
                .appendTo($wrapper);

            // Text
            var $text = $('<div/>')
                .addClass('notification-message text-content')
                .append(message.html)
                .append('<div><b>Cliente:</b> '+ item.customer.name +'</div>')
                .appendTo($wrapper);

            // Comment
            if (typeof item.comment !== 'undefined') {
                $('<div/>')
                    .addClass('notification-comment mdl-card mdl-shadow--2dp')
                    .text(item.comment)
                    .appendTo($text);
            }

            // Datetime
            $('<i/>')
                .addClass('notification-datetime')
                .text(moment(item.timestamp * 1000).format('DD/MM/YYYY HH:mm:ss'))
                .appendTo($text);

            // Notify
            if (item.seen === false && moment().diff(moment(item.timestamp * 1000)) < notifier.interval) {
                item['message'] = message;
                notifier.notify(item);
            }
        });

        notifier.updateBadge();
    },
    resolveMessage: function(item) {
        var message = {
            raw: item.raw,
            text: item.raw,
            html: item.raw,
        };

        item.params.forEach(function(param, i) {
            message.html = message.html.replace('{'+ i +'}', '<b>'+ param +'</b>');
            message.text = message.text.replace('{'+ i +'}', param);
        });

        return message;
    },
    view: function() {
        dialog = document.getElementById('dialog-notifications');

        if (! dialog.showModal) {
          dialogPolyfill.registerDialog(dialog);
        }

        dialog.showModal();
        notifier.markAsRead();
        $(dialog).find('a:first-child').blur();

        $(dialog).on('close', function() {
            $('.notification[data-seen=true]')
                .removeClass('unread');
        });
    },
    notify: function(item, snackbar) {
        var snackbarContainer = document.querySelector('.mdl-js-snackbar')
        if (notifier.hasNotification === false || snackbar === true) {
            var data = {
                message: (item.message.hasOwnProperty('text')) ? item.message.text : item.message,
                timeout: (item.hasOwnProperty('timeout')) ? item.timeout : 2760
            };
            
            if (item.hasOwnProperty('url')) {
                data.actionText = (item.hasOwnProperty('actionText')) ? item.actionText : 'Ver';
                data.actionHandler = function() {
                    window.location = item.url;
                };
            }
            
            snackbarContainer.MaterialSnackbar.showSnackbar(data);
        }
        else {
            var notification = new Notification(item.title, {
                icon: window.location.origin +'/assets/images/logo.png',
                body: item.message.text
            });

            notification.onclick = function () {
                this.close();
                notifier.markAsRead(item.timestamp);
                window.location = item.url;
            };

        }
    },
    markAsRead: function(timestamp) {
        if ($.cookie('notifications_read') !== undefined) {
            var cookieJSON = JSON.parse($.cookie('notifications_read'));            
        }
        else {
            var cookieJSON = [];
        }
        
        if (typeof timestamp !== 'undefined') {
            cookieJSON.push(timestamp);
            
            $('.notification[data-timestamp='+ timestamp +']')
                .attr('data-seen', 'true');
        }
        else {
            var $notifications = $('.notification');

            notifier.data.forEach(function(item, index) {
                notifier.data[index].seen = true;
                
                $notifications.filter('[data-timestamp='+ item.timestamp +']')
                    .attr('data-seen', 'true');
                
                cookieJSON.push(item.timestamp);
            });
        }

        $.cookie('notifications_read', JSON.stringify(cookieJSON), {path: '/'});
        notifier.updateBadge();
    },
    updateBadge: function() {
        var $badge = $('#notifications-badge');
        var unread = 0;

        notifier.data.forEach(function(item) {
            if (item.seen === false) {
                unread++;
            }
        });

        if (unread > 0) {
            $badge.attr('data-badge', unread);
        }
        else {
            $badge.removeAttr('data-badge');
        }

        notifier.unread = unread;
    }
};