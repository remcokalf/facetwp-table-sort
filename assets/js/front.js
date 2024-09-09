(function($) {

    FWP.hooks.addAction('facetwp/refresh/table_sort', function($this, facet_name) {
        var selected_values = [];
        $this.find('.facetwp-table-sort-option.selected').each(function() {
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
            }
        }
    });

    FWP.hooks.addFilter('facetwp/selections/table_sort', function(output, params) {
        var choices = [];
        $.each(params.selected_values, (val) => {
            var $item = params.el.find('.facetwp-table-sort-option[data-value="' + val + '"]');
            if ($item.len()) {
                choices.push({
                    value: val,
                    label: $item.first().attr('data-label') || val
                });
            }
        });
        return choices;
    });

    // Use "Sort by" as User Selections label instead of the facet label
    FWP.hooks.addFilter('facetwp/selections/facet_selection_label_html', function(html, facet_name) {
        if ('table_sort' === FWP.facet_type[facet_name] && FWP.settings[facet_name]) {
            html = html.replace('{selection_label_content}', FWP.settings[facet_name].selection_label);
        }
        return html;
    });

    $().on('click', '.facetwp-type-table_sort .facetwp-table-sort-option', function() {
        var is_selected = $(this).hasClass('selected');
        var facet = $(this).closest('.facetwp-facet');
        facet.find('.facetwp-table-sort-option').removeClass('selected');
        if (! is_selected) {
            $(this).addClass('selected');
        }
        FWP.autoload();
    });

    // Reset when clicking on active column heading
    $().on('click', '.facetwp-type-table_sort .facetwp-table-sort-heading.is-active', function() {
        var facet = $(this).closest('.facetwp-facet');
        var facet_name = facet.attr('data-name');
        FWP.reset(facet_name);
    });

})(fUtil);
