var exporter = {
    get: function(format) {
        $('#export-data').val(JSON.stringify(exporter.getData()));
        $('#form-export')
            .attr('action', Routing.generate('export', { format: format }))
            .submit();
    }, 
    getData: function() {
        var $criteria = $('.search-criteria');
        var $cards = $('.mdl-card:not(".search-criteria")');
        var data = {
            criteria: [],
            columns: [],
            results: [],
            footer: [],
            title: $.trim($('.mdl-layout-title').text()),
            url: window.location.href
        };
        
        // Criteria
        if ($criteria.length > 0) {
            $criteria.find('.mdl-textfield').each(function() {
                data.criteria.push({
                    label: $(this).find('.fake-label').text(),
                    value: $.trim($(this).find('.fake-input').text())
                });
            });
        }
        
        // Columns
        $($cards.get(0)).find('.mdl-card__title-text, .mdl-textfield').each(function() {
            data.columns.push($(this).data('label'));
        });
        
        // Results
        $cards.each(function() {
            var $card = $(this);
            var item  = [];
            
            $card.find('.mdl-card__title-text').each(function() {
                item.push($.trim($(this).text()));
            });
            
            $card.find('.mdl-textfield').each(function() {
                item.push($.trim($(this).find('.fake-input').text()));
            });
            
            data.results.push(item);
        });
        
        return data;
    }
};