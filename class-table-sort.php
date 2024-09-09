<?php

class FacetWP_Facet_Table_Sort_Addon extends FacetWP_Facet {


    function __construct() {
        $this->label = __( 'Table Sort', 'facetwp-table-sort' );
        $this->fields = [ 'table_sort_default_label', 'table_sort_options' ];

        add_filter( 'facetwp_filtered_query_args', [ $this, 'apply_sort' ], 1, 2 );
        add_filter( 'facetwp_facet_choice_attributes', [ $this, 'option_title' ], 1, 2 );
        add_filter( 'facetwp_facet_choice_reserved_attributes', [ $this, 'reserved_attributes' ], 10, 2 );
        add_action( 'admin_enqueue_scripts', [ $this, 'load_admin_assets' ] );

    }


    /**
     * Render the Table Sort facet
     * @since 0.1
     */
    function render( $params ) {
        $facet = $this->parse_sort_facet( $params['facet'] );

        // Sort facets have no data source. Provide the key so facetwp_facet_display_value
        // callbacks that read $params['facet']['source'] (like the WC integration) don't cause an "undefined array key" error.
        $facet['source'] = $facet['source'] ?? '';

        $selected_values = (array) $params['selected_values'];
        $column_heading = facetwp_i18n( $facet['default_label'] );

        // Set column heading based on default label
        $active_class = empty( $selected_values ) ? '' : ' is-active';
        $heading_text = trim( wp_strip_all_tags( $column_heading ) );

        // Use the sort option label for the reset title, or the heading if the label is empty
        $sort_options = $facet['sort_options'];
        $first_option = reset( $sort_options );
        $label_text = ( false !== $first_option ) ? trim( wp_strip_all_tags( facetwp_i18n( $first_option['label'] ) ) ) : '';
        $reset_text = ( '' !== $label_text ) ? $label_text : $heading_text;
        $reset_title = empty( $selected_values ) ? '' : ' title="' . esc_attr( str_replace( '{label}', $reset_text, facetwp_i18n( __( 'Reset sort by {label}', 'facetwp-table-sort' ) ) ) ) . '"';

        $output = ( '' !== $heading_text ) ? '<div class="facetwp-table-sort-heading' . $active_class . '"' . $reset_title . '>' . $column_heading . '</div>' : '';

        // Output only the heading if the sort option was removed (non-sortable column)
        if ( empty( $facet['sort_options'] ) ) {
            return $output;
        }

        $output .= '<div class="facetwp-table-sort-options">';
        foreach ( $facet['sort_options'] as $key => $choice ) {

            // Add asc or desc class
            $asc_or_desc = strtolower( reset( $choice['query_args']['orderby'] ) );
            $asc_or_desc_class = ' ' . $asc_or_desc;

            // Set default sort icon
            $icon = '<svg viewBox="0 0 8 6" aria-hidden="true" focusable="false"><path d="M1 5l3-4 3 4" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';

            $selected = in_array( $key, $selected_values ) ? ' selected' : '';
            $row = [ 'facet_value' => $key, 'facet_display_value' => $choice['label'] ];

            $choice_params = [
                'selected' => ( '' !== $selected ),
                'disabled' => false,
                'facet' => $facet,
                'row' => $row,
                'asc_or_desc' => $asc_or_desc,
                'heading_text' => $heading_text
            ];

            $label = apply_filters( 'facetwp_facet_display_value', $icon, $choice_params );

            $extra = method_exists( FWP()->helper, 'get_facet_choice_extras' ) ? FWP()->helper->get_facet_choice_extras( $choice_params ) : [ 'class' => '', 'atts' => '' ];

            $classes = $selected;
            $classes .= $asc_or_desc_class;

            // Use the sort option label for the User Selections, or the heading if the label is empty
            $label_text = trim( wp_strip_all_tags( facetwp_i18n( $choice['label'] ) ) );
            $sort_text = ( '' !== $label_text ) ? $label_text : $heading_text;

            // Set User Selections value, e.g. "{label} (asc)"
            if ( '' !== $sort_text && 'asc' === $asc_or_desc ) {
                $selection_label = str_replace( '{label}', $sort_text, facetwp_i18n( __( '{label} (asc)', 'facetwp-table-sort' ) ) );
            } elseif ( '' !== $sort_text && 'desc' === $asc_or_desc ) {
                $selection_label = str_replace( '{label}', $sort_text, facetwp_i18n( __( '{label} (desc)', 'facetwp-table-sort' ) ) );
            } else {
                $selection_label = $sort_text;
            }

            $output .= '<div class="facetwp-table-sort-option' . $classes . $extra['class'] . '" data-value="' . esc_attr( $key ) . '" data-label="' . esc_attr( $selection_label ) . '"' . $extra['atts'] . '><div class="facetwp-table-sort-icon">' . $label . '</div></div>';

        }
        $output .= '</div>';

        return $output;
    }


    /**
     * Table Sort facets don't narrow results
     * @since 0.1
     */
    function filter_posts( $params ) {
        return 'continue';
    }


    /**
     * Register admin settings
     * @since 0.1
     */
    function register_fields() {
        return [
            'table_sort_default_label' => [
                'type'  => 'alias',
                'items' => [
                    'default_label' => [
                        'label'   => __('Table column heading', 'facetwp-table-sort' ),
                        'notes'   => __('The heading text for the table column that this facet is placed above. Can contain HTML. By default it will display to the left of the sort icons and will trigger a sort reset when clicked. If left empty, only the sort icons will be displayed. The heading text is also used in User Selections and title attributes (tooltips) if the Sort label is left empty.', 'facetwp-table-sort' ),
                        'default' => ''
                    ]
                ]
            ],
            'table_sort_options' => [
                'type'  => 'alias',
                'items' => [
                    'sort_options' => [
                        'label' => __('Sort column by', 'facetwp-table-sort' ),
                        'notes' => __('Choose the field to sort this table column by. Both sort directions (ASC and DESC) are created automatically, so you don\'t need to add a descending sort yourself. Optionally, click + to add a secondary (fallback) sort for items with equal values. Its order (ASC or DESC) is used in both sort directions. The sort label is used in the User Selections facet and in the sort options\' title attributes (tooltips), e.g. \'Sort by {sort label} (asc)\'. If it is empty, the \'Table column heading\' text is used instead. Don\'t leave both settings empty. To display only the column heading, for a column that should not be sortable, remove the sort option with the − icon and add a heading text.', 'facetwp-table-sort' ),
                        'html'  => '<sort-options :facet="facet"></sort-options><input type="hidden" class="facet-sort-options" value="[]" />'
                    ]
                ]
            ]
        ];
    }


    /**
     * Convert a Table Sort facet's sort options into WP_Query arguments
     * @since 0.1
     */
    function parse_sort_facet( $facet ) {
        $sort_options = [];

        // Use only the first sort option, and generate an ASC and DESC version of it.
        // The primary sort order is forced, secondary sorts keep their configured order.
        $rows = $facet['sort_options'] ?? [];
        $row = reset( $rows );

        if ( ! empty( $row['orderby'] ) ) {
            foreach ( [ 'asc', 'desc' ] as $direction ) {
                $orderby = $row['orderby'];
                $orderby[0]['order'] = strtoupper( $direction );
                $parsed = FWP()->builder->parse_query_obj( [ 'orderby' => $orderby ] );

                $sort_options[ $direction ] = [
                    'label'      => $row['label'],
                    'query_args' => array_intersect_key( $parsed, [
                        'meta_query' => true,
                        'orderby'    => true
                    ] )
                ];
            }
        }

        $sort_options = apply_filters( 'facetwp_facet_sort_options', $sort_options, [
            'facet'         => $facet,
            'template_name' => FWP()->facet->template['name']
        ] );

        $facet['sort_options'] = $sort_options;

        return $facet;
    }


    /**
     * Handle all Table Sort facets
     * @since 0.1
     */
    function apply_sort( $query_args, $class ) {

        foreach ( $class->facets as $facet ) {

            if ( 'table_sort' == $facet['type'] ) {

                // Select current table sort facet
                if ( ! empty( $facet['selected_values'] ) ) {
                    $sort_facet = $this->parse_sort_facet( $facet );
                }
            }
        }

        if ( ! empty( $sort_facet['selected_values'] ) ) {
            $chosen = $sort_facet['selected_values'][0];
            $sort_options = $sort_facet['sort_options'];

            if ( isset( $sort_options[ $chosen ] ) ) {
                $qa = $sort_options[ $chosen ]['query_args'];

                if ( isset( $qa['meta_query'] ) ) {

                    $meta_query = $query_args['meta_query'] ?? [];

                    // If the original meta_query uses an OR relation, append the Sort facet meta_query instead of merging it,
                    // so that both are then compared with (the default) AND relation.
                    if ( isset( $meta_query['relation'] ) && 'OR' === strtoupper( $meta_query['relation'] ) ) {
                        $query_args['meta_query'] = array(
                            $meta_query,
                            $qa['meta_query'],
                        );
                    } else {
                        $query_args['meta_query'] = array_merge( $meta_query, $qa['meta_query'] );
                    }
                }

                $query_args['orderby'] = $qa['orderby'];
            }
        }

        return $query_args;
    }


    /**
     * Pass the User Selections label to the front-end
     * @since 0.1
     */
    function settings_js( $params ) {
        return [ 'selection_label' => facetwp_i18n( __( 'Sort by', 'facetwp-table-sort' ) ) ];
    }


    /**
     * Add a default title to the sort options
     * Runs at priority 1, so it can be changed or removed with the same hook
     * @since 0.1
     */
    function option_title( $atts, $params ) {
        if ( 'table_sort' == $params['facet']['type'] && isset( $params['asc_or_desc'] ) ) {

            // Use the sort option label, or the heading if the label is empty
            $label_text = trim( wp_strip_all_tags( facetwp_i18n( $params['row']['facet_display_value'] ) ) );
            $sort_text = ( '' !== $label_text ) ? $label_text : $params['heading_text'];

            if ( '' !== $sort_text && 'asc' === $params['asc_or_desc'] ) {
                $title = str_replace( '{label}', $sort_text, facetwp_i18n( __( 'Sort by {label} (asc)', 'facetwp-table-sort' ) ) );
            } elseif ( '' !== $sort_text && 'desc' === $params['asc_or_desc'] ) {
                $title = str_replace( '{label}', $sort_text, facetwp_i18n( __( 'Sort by {label} (desc)', 'facetwp-table-sort' ) ) );
            } else {
                // Fall back to the sort option key (asc/desc) if both the label and heading are empty, like the User Selections
                $title_text = ( '' !== $sort_text ) ? $sort_text : $params['row']['facet_value'];
                $title = str_replace( '{label}', $title_text, facetwp_i18n( __( 'Sort by {label}', 'facetwp-table-sort' ) ) );
            }

            if ( '' !== $title ) {
                $atts['title'] = $title;
            }
        }
        return $atts;
    }


    /**
     * Reserve attributes that the front-end JS relies on
     * @since 0.1
     */
    function reserved_attributes( $reserved, $params ) {
        if ( 'table_sort' == $params['facet']['type'] ) {
            $reserved[] = 'data-label'; // Used for the User Selections
        }
        return $reserved;
    }


    /**
     * Add admin assets
     * @since 0.1
     */
    function load_admin_assets( $hook ) {
        if ( 'settings_page_facetwp' == $hook ) {
            wp_enqueue_style( 'facetwp-admin-table_sort', plugins_url( 'assets/css/admin.css', __FILE__ ), [], FACETWP_TABLE_SORT_VERSION );
            wp_enqueue_script( 'facetwp-admin-table_sort', plugins_url( 'assets/js/admin.js', __FILE__ ), [], FACETWP_TABLE_SORT_VERSION, true );
        }
    }


    /**
     * Add front JS and styles
     * @since 0.1
     */
    function front_scripts() {
        FWP()->display->assets['facetwp-table-sort.js'] = [ FACETWP_TABLE_SORT_URL . '/assets/js/front.js', FACETWP_TABLE_SORT_VERSION ];
        FWP()->display->assets['facetwp-table-sort.css'] = [ FACETWP_TABLE_SORT_URL . '/assets/css/front.css', FACETWP_TABLE_SORT_VERSION ];
    }

}
