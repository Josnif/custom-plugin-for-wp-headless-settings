<?php

// service-order overview pages
add_filter( 'manage_service-order_posts_columns','add_service_order_custom_columns');
function add_service_order_custom_columns( $columns ) {  
	$new_columns = array();
    $new_columns['order_id'] = "Order ID";
    
    $first_column = array_slice( $columns, 0, 2, true );
    $remaining_columns = array_slice( $columns, 2, null, true );
    $columns = $first_column + $new_columns + $remaining_columns;
    
    $columns['social_account'] = "Social Account";
	$columns['order_status'] = "Order Status";
	unset($columns['date']);
    $columns['date'] = "Date";
    return $columns;
}

add_action( 'manage_service-order_posts_custom_column', 'fill_service_order_posts_custom_column', 10, 2 );
function fill_service_order_posts_custom_column( $column_id, $post_id ) {
    switch( $column_id ) { 
        case 'order_id':
            echo '<strong>' . get_post_field('ID', $post_id) . '</strong>';
            break;
		case 'order_status': 
			echo get_field('status', $post_id);
			break;
        case 'social_account':
            $acct = get_field('account_id', $post_id);
            if (is_array($acct)) {
                echo get_field('username', $acct[0]);
            } else {
                echo get_field('username', $acct);
            }

            break;
    }
}
add_filter( 'manage_edit-service-order_sortable_columns', 'sortable_service_order_posts_columns' );
function sortable_service_order_posts_columns( $columns ) {
    $columns['order_id'] = 'Order ID';
    return $columns;
}


function custom_social_service_columns($columns) {
    $first_column = array_slice($columns, 0, 2, true);
	$remaining_columns = array_slice( $columns, 2, null, true );
    $new_columns = array('language' => 'Language');
    return $first_column + $new_columns + $remaining_columns;
}
add_filter('manage_social-service_posts_columns', 'custom_social_service_columns');

function display_custom_social_service_columns($column_name, $post_id) {
    if ($column_name === 'language') {
        $language_value = get_field('language', $post_id);
        echo strtoupper($language_value);
    }
}
add_action('manage_social-service_posts_custom_column', 'display_custom_social_service_columns', 10, 2);
