(function($) {

    FWP.hooks.addAction('facetwp/refresh/table_sort', function($this, facet_name) {
        var selected_values = [];
        $this.find('.facetwp-table-sort-option.checked').each(function() {
            var val = $(this).attr('data-value');
            if ('' !== val) {
                selected_values.push(val);
            }
        });
        FWP.facets[facet_name] = selected_values;

        // Clear table sort if not the active facet
        if ( null !== FWP.active_facet  ) {
            var active_facet = $(FWP.active_facet.nodes[0]);
            if ( 'table_sort' == active_facet.attr('data-type' ) && facet_name != active_facet.attr('data-name' ) ) {
                FWP.facets[facet_name] = [];
                delete FWP.frozen_facets[facet_name];
            }
        }
    });

    $().on('click', '.facetwp-type-table_sort .facetwp-table-sort-option', function() {
        var is_checked = $(this).hasClass('checked');
        var facet = $(this).closest('.facetwp-facet');
        var facet_name = facet.attr('data-name');
        facet.find('.facetwp-table-sort-option').removeClass('checked');
        if (! is_checked) {
            $(this).addClass('checked');
        }
        if ('' !== $(this).attr('data-value')) {
            FWP.frozen_facets[facet_name] = 'hard';
        }
        FWP.autoload();
    });

    // Reset when clicking on active column heading
    $().on('click', '.facetwp-type-table_sort.is-active .facetwp-table-sort-heading', function() {
        var facet = $(this).closest('.facetwp-facet');
        var facet_name = facet.attr('data-name');
        FWP.reset(facet_name);
    });


    // Show "Reset column sort" title attribute only for active column headings
    FWP.hooks.addAction('facetwp/loaded', function() {
        $('.facetwp-type-table_sort.is-active .facetwp-table-sort-heading').each(function() {
            var title = $(this).attr('data-title');
            $(this).attr('title', title);
        });
    }, 1000);

})(fUtil);
