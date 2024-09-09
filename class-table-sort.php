<?php

class FacetWP_Facet_Table_Sort_Addon extends FacetWP_Facet {

    public $sort_options = [];


    function __construct() {
        $this->label = __( 'Table Sort', 'fwp' );
        $this->fields = [ 'table_sort_default_label', 'sort_options' ];

        add_action( 'admin_enqueue_scripts', [ $this, 'load_admin_styles' ] );
        add_filter( 'facetwp_filtered_query_args', [ $this, 'apply_sort' ], 1, 2 );
        add_filter( 'facetwp_assets', array( $this, 'assets' ) );

    }


    /**
     * Add admin styles
    * @since 0.1
     */
    function load_admin_styles( $hook ) {
        if ( 'settings_page_facetwp' == $hook ) {
            wp_enqueue_style( 'facetwp-admin-table_sort', plugins_url( 'assets/css/admin.css', __FILE__ ) );
        }
    }


    /**
     * Render the Table Sort facet
    * @since 0.1
     */
    function render( $params ) {
        $facet = $this->parse_sort_facet( $params['facet'] );
        $selected_values = (array) $params['selected_values'];
        $column_heading = facetwp_i18n( $facet['default_label'] );

        // Set column heading based on default label
        $reset_title = ' data-title="' . facetwp_i18n( __( 'Reset column sort', 'fwp-front' ) ) . '"';
        $output = isset( $column_heading) ? '<div class="facetwp-table-sort-heading"' . $reset_title . '>' . $column_heading . '</div>' : '';

        $output .= '<div class="facetwp-table-sort-options">';
        foreach ( $facet['sort_options'] as $key => $choice ) {

            // Add asc or desc class
            $asc_or_desc = strtolower( reset( $choice['query_args']['orderby'] ) );
            $asc_or_desc_class = isset($asc_or_desc) ? ' ' . $asc_or_desc : '';

            // Set default icon with hook to change
            $icon = apply_filters( 'facetwp_table_sort_icon', '&#8963;');

            $classes = in_array( $key, $selected_values ) ? ' checked' : '';
            $classes .= $asc_or_desc_class;
            $output .= '<div class="facetwp-table-sort-option' . $classes . '" data-value="' . esc_attr( $key ) . '"><span class="icon">' . $icon . '</span></div>';

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
                        'label'   => __( 'Table column heading', 'fwp' ),
                        'notes'   => 'The heading text for the table column that this facet is placed above.',
                        'default' => ''
                    ]
                ]
            ],
            'sort_options'       => [
                'label' => __( 'Sort options', 'fwp' ),
                'notes' => 'Define the two sort options for the table column that this facet is placed above. Use ASC order for the primary sort of the first, and DESC order for the primary sort of the second. If needed add a secondary sort to the two sort options, using any desired order.',
                'html'  => '<sort-options :facet="facet"></sort-options><input type="hidden" class="facet-sort-options" value="[]" />'
            ]
        ];
    }


    /**
     * Convert a Table Sort facet's sort options into WP_Query arguments
     * @since 0.1
     */
    function parse_sort_facet( $facet ) {
        $sort_options = [];

        foreach ( $facet['sort_options'] as $row ) {
            $parsed = FWP()->builder->parse_query_obj( [ 'orderby' => $row['orderby'] ] );

            $sort_options[ $row['name'] ] = [
                'label'      => $row['label'],
                'query_args' => array_intersect_key( $parsed, [
                    'meta_query' => true,
                    'orderby'    => true
                ] )
            ];
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

        $sort_value = 'default';

        // Preserve relevancy sort
        $use_relevancy = apply_filters( 'facetwp_use_search_relevancy', true, $class );
        $is_default_sort = ( 'default' == $sort_value && empty( $class->http_params['get']['orderby'] ) );
        if ( $class->is_search && $use_relevancy && $is_default_sort && FWP()->is_filtered ) {
            $query_args['orderby'] = 'post__in';
        }

        if ( ! empty( $sort_facet['selected_values'] ) ) {
            $chosen = $sort_facet['selected_values'][0];
            $sort_options = $sort_facet['sort_options'];

            if ( isset( $sort_options[ $chosen ] ) ) {
                $qa = $sort_options[ $chosen ]['query_args'];

                if ( isset( $qa['meta_query'] ) ) {
                    $meta_query = $query_args['meta_query'] ?? [];
                    $query_args['meta_query'] = array_merge( $meta_query, $qa['meta_query'] );
                }

                $query_args['orderby'] = $qa['orderby'];
            }
        }

        return $query_args;
    }


    /**
     * Add front JS and styles
     * @since 0.1
     */
    function assets( $assets ) {
        $assets['facetwp-table-sort.js'] = [ FACETWP_TABLE_SORT_URL . '/assets/js/front.js', FACETWP_TABLE_SORT_VERSION ];
        $assets['facetwp-table-sort.css'] = [ FACETWP_TABLE_SORT_URL . '/assets/css/front.css', FACETWP_TABLE_SORT_VERSION ];
        return $assets;
    }


}
