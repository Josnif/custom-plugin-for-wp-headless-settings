<?php

// Define resolver for querying service orders
function getServiceOrders($source, $args, $context, $info) {
    // Define WP_Query arguments
    $query_args = [
        'post_type' => 'service-order',
        'posts_per_page' => -1,
        'orderby' => 'date',
        'order' => 'DESC',
        'meta_query' => [
            [
                'key' => 'user_id',
                'value' => $args['user_id'], 
                'compare' => '=',
            ],
        ],
    ];

    // Perform WP_Query
    $service_order_query = new WP_Query($query_args);

    // Process query results
    $service_orders = [];
    if ($service_order_query->have_posts()) {
        while ($service_order_query->have_posts()) {
            $service_order_query->the_post();
            // Construct the service order object
            $acf_fields = get_fields();
            $service_order = [
				'id' => base64_encode( get_the_ID() ),
                'databaseId' => get_the_ID(),
                'title' => get_the_title(),
				'orderData' => $acf_fields
            ];
			log_it($service_order);
            $service_orders[] = $service_order;
        }
        wp_reset_postdata();
    }

    return $service_orders;
}

// Register the resolver function for querying service orders
add_action('graphql_register_types', function () {
    register_graphql_field('RootQuery', 'serviceOrdersByUser', [
        'type' => ['list_of' => 'ServiceOrder'],
        'args' => [
            'user_id' => [
                'type' => 'ID',
                'description' => __('User ID to filter service orders.'),
            ],
        ],
        'resolve' => 'getServiceOrders',
    ]);
});
