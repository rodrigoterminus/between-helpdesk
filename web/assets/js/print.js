var printer = {
    orientation: 'portrait',
    hsize: 950,
    init: function () {
        $(document).ready(function () {
            $('#btn-print').on('click', function () {
                switch (pagetype) {
                    case 'form':
                        route = Routing.generate('print_form');
                        break;
                    case 'list':
                        route = Routing.generate('print_list');
                        break;
                    default    :
                        route = Routing.generate('index');
                }

                window.open(
                    route,
                    $('.page-title h3').text(),
                    'location=no, menubar=no, resizable=no, status=no, titlebar=no, left=0, top=0, width=760, height=' + ($(window).height() - 20)
                    );
            });

            return true;
        });
    },
    pageInit: function () {
        $(document).ready(function () {
            $('select').select2();

            return true;
        });
    },
    getContent: function () {
        setTimeout(function () {
            $clone = $('#clone');
            $opener = $(window.opener.document);
            $table = $opener.find('table.printable:first').clone();
            title = $opener.find('.page-title h3').text();

            $('title').text(title);
            $('.name').html('<h3 class="semi-bold">' + title + '</h3>');
            $clone.html($table);

            // criteria
            // options = dtoolbar.toolbar('getSearchOptions');

            // if(options.length>0){
            //     for(i in options)
            //         $('.options').append('<b>'+ options[i]['label'] +':</b> '+ options[i]['value'] +'<br />');
            // }
            $opener.find('.criteria .label').each(function () {
                $('.options').append($(this).clone());
                $('.options').append('<br />');
            });

            $('.tr-table-list-selected').removeClass('tr-table-list-selected');
            $('tr').removeAttr('onclick');

            // $('#div-orientation').animate({ opacity : 0.001 }, 1500);
            // $('#div-orientation').hover(function(){
            //     $(this).stop().animate({ opacity : 1 }, 300);
            // }, function(){
            //     $(this).stop().animate({ opacity : 0.01 }, 300);
            // })

            printer.create();
        }, 200);
    },
    changeOrientation: function (type) {
        if (printer.orientation === type)
            return false;
        else {
            printer.orientation = type;

            if (printer.orientation === 'portrait') {
                printer.hsize = 950;

                window.resizeTo(760, ($(window.opener).height()));
                $('style[media="print"]').text('@page port {size: portrait;}');
            } else if (printer.orientation === 'landscape') {
                printer.hsize = 640;

                window.resizeTo(1050, 760);
                $('style[media="print"]').text('@page port {size: landscape;}');
            }

            setTimeout(function () {
                printer.create();
            }, 50);

            return true;
        }
    },
    create: function () {
        // message({
        //     title       : 'Processando',
        //     message     : 'Por favor, aguarde...',
        //     width       : 200,
        //     height      : 100,
        //     closeEnable : false
        // });

        $('#bridge').html($('#clone').html());

        rc = $('#report-content');
        rc.html('');
        rc.removeClass('potrait landscape');
        rc.addClass(printer.orientation);

        setTimeout(function () {
            printer.pageCreator();

            $('.pcontent:last').css('page-break-after', 'none');

            setTimeout(function () {
                //$('.overlay').width($('body').width()).height($('body').height())
                // $('.ui-dialog-content').dialog('close');
            }, 500);
        }, 500);

    },
    pageCreator: function () {
        btable = $('#bridge table:first');
        btbody = $('#bridge table:first > tbody:first');
        bthead = $('#bridge table:first > thead:first');
        trs = $('#bridge table:first > tbody:first > tr');

        div = $('<div/>');
        div.addClass('page');
        div.css('margin-bottom', '20px');

        header = $('#header').clone().show();
        header.removeAttr('id');
        header.addClass('header');
        div.append(header);

        pcontent = $('<div/>');
        pcontent.addClass('pcontent');
        div.append(pcontent);

        footer = $('#footer').clone().show();
        header.removeAttr('id');
        footer.addClass('footer');
        footer.find('.page-number').text($('.page').length + 1);
        div.append(footer);

        $('#report-content').append(div);

        table = $('<table/>');
        table.addClass('table data-table');
        table.attr({
            'cellspacing': btable.attr('cellspacing'),
            'cellpadding': btable.attr('cellpadding')
        });
        table.append(bthead.clone());
        pcontent.append(table);

        tbody = $('<tbody/>');
        table.append(tbody);

        trs.each(function () {
            if (trs.length > 0) {
                tr = $('#bridge table:first > tbody:first > tr:first');

                tbody.append(tr);

                if (div.height() > printer.hsize) {
                    btbody.prepend(tr);

                    return false;
                }
            }
        });

        pcontent.height(printer.hsize - header.height() - footer.height() + 20);

        if ($('#bridge table:first > tbody:first > tr').length > 0)
            printer.pageCreator();
        else {
            tfoot = $('#bridge table:first > tfoot');
            table.append(tfoot);
        }

        // Remove links
        $('a').each(function () {
            $(this).after($(this).text());
            $(this).remove();
        });
    }
};