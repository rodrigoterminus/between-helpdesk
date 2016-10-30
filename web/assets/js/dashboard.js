'use strict';

var dashboard = {
    init: function() {
        
    },
    admin: function() {
        // Stopwatches
        dashboard.stopwatch();
    },
    customer: function() {
        
    },
    stopwatch: function() {
//        setInterval(function() {
//            $('.stopwatch[data-datetime]').each(function() {
//                var datetime = $(this).data('datetime');
//                console.log(datetime)
//                if (datetime !== undefined) {
//                    $(this).text(moment().from(moment(datetime)));
//                }
//            });
//        }, 60000, true);
        
        setInterval(function datetimeUpdate() {
            $('.stopwatch[data-datetime]').each(function() {
                var datetime = $(this).data('datetime');
                
                if (datetime !== '') {
                    $(this).text(moment(datetime).from(moment()));
                }
            });
            
            return datetimeUpdate;
        }(), 60000);
    }
};