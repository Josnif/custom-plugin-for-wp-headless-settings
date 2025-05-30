<?php

// service-order overview pages
add_filter( 'manage_service-order_posts_columns','add_service_order_custom_columns');
function add_service_order_custom_columns( $columns ) {  
	$new_columns = array();
    $new_columns['order_id'] = "Order ID";
    
    $first_column = array_slice( $columns, 0, 2, true );
    $remaining_columns = array_slice( $columns, 2, null, true );
    $columns = $first_column + $new_columns + $remaining_columns;
    
    $columns['amount'] = "Amount";
	$columns['user'] = "User Email";
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
		case 'amount': 
			$amount = get_field('total_amount', $post_id);
			$currency = get_field('currency', $post_id);
			echo $amount . " " . $currency;
			break;
		case 'order_status': 
			// error_log($post_id);
			echo get_field('status', $post_id, false);
			break;
		case 'user': 
			$user_id = get_field('user_id', $post_id);
			$user_info = get_userdata($user_id);
			$user_email = !empty($user_info->user_email) ? $user_info->user_email : null;
			echo $user_email ?? '-';
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

add_action('restrict_manage_posts', function($post_type) {
    global $typenow;

    if ($typenow === 'service-order') {
        $selected = $_GET['order_status'] ?? '';
        $statuses = ['PENDING', 'PROCESSING', 'PAID', 'CANCELLED', 'FAILED'];

        echo '<select name="order_status" style="margin-left: 10px;">';
        echo '<option value="">' . __('All Order Statuses', 'textdomain') . '</option>';

        foreach ($statuses as $status) {
            printf(
                '<option value="%s"%s>%s</option>',
                esc_attr($status),
                selected($selected, $status, false),
                esc_html($status)
            );
        }

        echo '</select>';
    }
	
	 // Only show filter for post types that support 'language' field
    if (!post_type_supports($post_type, 'custom-fields')) return;
    if (!in_array($post_type, ['post', 'social-service'])) return;

	// Language Filter
	$selected_lang = $_GET['language'] ?? '';
	$languages = ['ro', 'en', 'hu', 'pl', 'es', 'it', 'cz'];

	echo '<select name="language" style="margin-left: 10px;">';
	echo '<option value="">' . __('All Languages', 'textdomain') . '</option>';
	foreach ($languages as $lang) {
		printf(
			'<option value="%s"%s>%s</option>',
			esc_attr($lang),
			selected($selected_lang, $lang, false),
			strtoupper($lang)
		);
	}
	echo '</select>';
});


add_filter('pre_get_posts', function($query) {
    global $pagenow, $wpdb;

    if (!is_admin() || $pagenow !== 'edit.php') {
        return;
    }

     $post_type = $query->get('post_type') ?? 'post'; // default to 'post' if not explicitly set
    $meta_query = ['relation' => 'AND'];

    // Language filter (only apply to post and social-service)
    if (in_array($post_type, ['post', 'social-service']) && !empty($_GET['language'])) {
        $meta_query[] = [
            'key'     => 'language',
            'value'   => sanitize_text_field($_GET['language']),
            'compare' => '='
        ];
    }

    // Service-order-specific filters
    if (!empty($query->query['post_type']) && $query->query['post_type'] === 'service-order') {

        // Order status filter
        if (!empty($_GET['order_status'])) {
            $meta_query[] = [
                'key'     => 'status',
                'value'   => sanitize_text_field($_GET['order_status']),
                'compare' => '='
            ];
        }

        // Search logic
        if ($query->is_search()) {
            $search_term = trim($query->get('s'));

            $search_meta_query = [
                'relation' => 'OR',
                [
                    'key'     => 'total_amount',
                    'value'   => $search_term,
                    'compare' => 'LIKE'
                ],
                [
                    'key'     => 'currency',
                    'value'   => $search_term,
                    'compare' => 'LIKE'
                ],
                [
                    'key'     => 'status',
                    'value'   => $search_term,
                    'compare' => 'LIKE'
                ],
            ];

            // Search by user email
            $user = get_user_by('email', $search_term);
            if ($user) {
                $search_meta_query[] = [
                    'key'     => 'user_id',
                    'value'   => $user->ID,
                    'compare' => '='
                ];
            }

            // Search by Post ID
            if (is_numeric($search_term)) {
                $query->set('post__in', [(int) $search_term]);
            }

            // Search by social account username
            $account_ids = $wpdb->get_col($wpdb->prepare("
                SELECT post_id FROM {$wpdb->postmeta}
                WHERE meta_key = 'username'
                AND meta_value LIKE %s", '%' . $wpdb->esc_like($search_term) . '%'
            ));

            if (!empty($account_ids)) {
                $search_meta_query[] = [
                    'key'     => 'account_id',
                    'value'   => $account_ids,
                    'compare' => 'IN'
                ];
            }

            // Add the OR search block to the AND meta_query
            $meta_query[] = $search_meta_query;

            // Disable default search
            $query->set('s', '');
        }
    }

    // Only apply if there are meta conditions
    if (count($meta_query) > 1) {
        $query->set('meta_query', $meta_query);
    }
});

// social_service overview pages
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

function custom_posts_columns($columns) {
    $first_column = array_slice($columns, 0, 2, true);
	$remaining_columns = array_slice( $columns, 2, null, true );
    $new_columns = array('language' => 'Language');
    return $first_column + $new_columns + $remaining_columns;
}
add_filter('manage_post_posts_columns', 'custom_posts_columns');

function display_custom_posts_columns($column_name, $post_id) {
    if ($column_name === 'language') {
        $language_value = get_field('language', $post_id);
        echo strtoupper($language_value);
    }
}
add_action('manage_post_posts_custom_column', 'display_custom_posts_columns', 10, 2);
