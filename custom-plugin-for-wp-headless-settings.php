<?php
/**
 * Plugin Name: Custom WP Headless Settings
 * Description: Custom plugin for custom WP Headless Settings
 * Version:     1.0.1
 * Author:      Joseph
 * Text Domain: customwp
 * Domain Path: /lang
*/


include 'rh-admin.php';
include 'rh-settings.php';
include 'rh-query.php';
include 'rh-mutations.php';

function remove_menus(){
    // remove_menu_page( 'index.php' );                  //Dashboard  
    // remove_menu_page( 'edit.php?post_type=page' );    //Pages  
    // remove_menu_page( 'edit.php' );                   //Posts  
    remove_menu_page( 'edit-comments.php' );          //Comments  
	remove_menu_page( 'themes.php' );                 //Appearance  
    remove_menu_page( 'wpcf7' );        //contact form
    // remove_menu_page( 'plugins.php' );                //Plugins  
    // remove_menu_page( 'users.php' );                  //Users  
    // remove_menu_page( 'tools.php' );                  //Tools  
    // remove_menu_page( 'options-general.php' );        //Settings
}
add_action( 'admin_menu', 'remove_menus' );

function custom_disable_new_user_notifications() {
	remove_action( 'register_new_user', 'wp_send_new_user_notifications' );
	//remove_action( 'edit_user_created_user', 'wp_send_new_user_notifications', 10, 2 );	
}
add_action( 'init', 'custom_disable_new_user_notifications' );


add_filter( 'graphql_jwt_auth_secret_key', function() {
  return ')d%^A1TUOU<X7G:ljd+~-l&<tg-0>q-++or.LRixazhWT1VFSzL!T1Uu2mI]kqD!';
});


function log_it( $message ) {
   if( WP_DEBUG === true ){
     if( is_array( $message ) || is_object( $message ) ){
       //you can specify a custom location if needed like this
       //error_log( $var, 0, "full-path-to/error_log.txt")
       error_log( print_r( $message, true ) );
     } else {
       error_log( $message );
     }
   }
}

add_action('graphql_register_types', function () {
    // Expose the ACF field to WPGraphQL
    //     register_graphql_field('SocialService', 'language', [
    //         'type' => 'String',
    //         'description' => __('The language of the social service', 'customwp'),
    //         'resolve' => function ($post) {
    //             return get_field('language', $post->ID);
    //         }
    //     ]);

    // Add custom where clause argument for filtering by language
    register_graphql_field('RootQueryToSocialServiceConnectionWhereArgs', 'language', [
        'type' => 'String',
        'description' => __('Filter social services by language', 'customwp'),
    ]);
});

add_action('graphql_register_types', function () {
//     register_graphql_field('Post', 'language', [
//         'type' => 'String',
//         'description' => __('The language of the post', 'customwp'),
//         'resolve' => function ($post) {
//             return get_field('language', $post->ID); // Assuming 'language' is an ACF field
//         }
//     ]);

    register_graphql_field('RootQueryToPostConnectionWhereArgs', 'language', [
        'type' => 'String',
        'description' => __('Filter posts by language', 'customwp'),
    ]);
});

add_filter('graphql_post_object_connection_query_args', function ($query_args, $source, $input, $context, $info) {
    if (($info->fieldName === 'socialServices' || $info->fieldName === 'posts') && !empty($input['where']['language'])) {
        $meta_query = [
            'key' => 'language',
            'value' => $input['where']['language'],
            'compare' => '='
        ];

        if (isset($query_args['meta_query'])) {
            $query_args['meta_query'][] = $meta_query;
        } else {
            $query_args['meta_query'] = [$meta_query];
        }
    }

    return $query_args;
}, 10, 5);


function set_language_for_posts() {
    $post_type = 'social-service'; 
    $posts = get_posts(array(
        'post_type' => $post_type,
        'posts_per_page' => -1
    ));

    foreach ($posts as $post) {
        $language = get_field('language', $post->ID);

        if (empty($language)) {
            update_field('language', 'ro', $post->ID);
        }
    }
}
add_action('init', 'set_language_for_posts');


add_action('rest_api_init', function () {
    register_rest_route('custom/v1', '/upsell', [
        'methods'  => 'GET',
        'callback' => 'get_upsell_recommendation',
//         'permission_callback' => '__return_true', // Change this for security
    ]);
});

function get_upsell_recommendation(WP_REST_Request $request) {
    global $wpdb;

    $service_id = $request->get_param('service_id');
    $current_count = $request->get_param('count');
    $plan_group_title = $request->get_param('title'); // Title of plan group 
	$locale = $request->get_param('locale');

    if (!$service_id || !$current_count || !$plan_group_title) {
        return new WP_Error('missing_params', 'Missing required parameters', ['status' => 400]);
    }

    // Step 1: Fetch the Social Service Post
    $social_service = get_post($service_id);
    if (!$social_service) {
        return new WP_Error('no_service', 'No matching social service found', ['status' => 404]);
    }

    // Step 2: Retrieve plan_group and type from the social service
    $type = get_field('type', $service_id);
    $plan_groups = get_field('plan_group', $service_id);
    if (!$plan_groups || !is_array($plan_groups)) {
        return new WP_Error('no_plan_groups', 'No plan groups found for this service', ['status' => 404]);
    }

    // Step 3: Find the specific plan group by title
    $matched_plan_group = null;
    foreach ($plan_groups as $group) {
        if ($group['title'] === $plan_group_title) {
            $matched_plan_group = $group;
            break;
        }
    }

    if (!$matched_plan_group) {
        return new WP_Error('no_matching_group', 'No matching plan group found', ['status' => 404]);
    }

    // Step 4: Get the upsell levels from the social service
    $upsells = get_field('up-sell', $service_id);
    if (!$upsells || !is_array($upsells)) {
        return new WP_Error('no_upsell_levels', 'No upsell levels found for this service', ['status' => 404]);
    }

    // Step 5: Get the pricing options from the matched plan group
    $pricing_options = $matched_plan_group['pricing'] ?? [];
    if (!$pricing_options) {
        return new WP_Error('no_pricing', 'No pricing options found for this plan group', ['status' => 404]);
    }

    // Sort pricing options by count (ascending)
    usort($pricing_options, fn($a, $b) => $a['count'] <=> $b['count']);

    $upsell_recommendations = [];

    // Step 6: Iterate through upsells to determine the next levels
    foreach ($upsells as $upsell) {
        $upsell_name = $upsell['name'];
        $upsell_level = intval($upsell['level']);
        $upsell_discount = floatval($upsell['discount']);

		$formatted_pricings = null;
		if ($type != $upsell_name) {
			$socialMedia = get_field('social_media', $service_id);
			$social_id = !empty($socialMedia[0]) ? $socialMedia[0] : $socialMedia;
			$social_services = get_social_services_by_social_media_and_type($social_id, $upsell_name, $locale);
			
			if (is_wp_error($social_services)) {
				break;
			} 
			
			if (!empty($social_services[0]['id'])) {
				$new_groups = get_field('plan_group', $social_services[0]['id']); // social services returns an array of objects
				$formatted_pricings = $new_groups[0]['pricing'] ?? null;
				// return $new_groups;
			}
		}
		if (empty($formatted_pricings)) $formatted_pricings = $pricing_options;
		
        // Find the index of the current count in pricing options
        $current_index = null;
        foreach ($formatted_pricings as $index => $option) {
            if ($option['count'] >= $current_count) {
                $current_index = $index;
                break;
            }
        }

        // If current count is higher than all available options, pick the highest available
        if ($current_index === null) {
            $current_index = count($formatted_pricings) - 1;
        }

        // Determine the upsell index based on the level
        $upsell_index = $current_index + $upsell_level;
        if ($upsell_index >= count($formatted_pricings)) {
            // If the upsell level exceeds the available pricing options, count backward
            $upsell_index = abs(count($formatted_pricings) - $upsell_level);
        }

        $upsell_option = $formatted_pricings[$upsell_index];

        // Format the final upsell object
        $upsell_recommendations[] = [
            'name'     				=> $upsell_name,
            'count'    				=> $upsell_option['count'],
            'amount'   				=> $upsell_option['amount'],
			'original_discount'   	=> $upsell_option['discount'],
            'discount' 				=> $upsell_discount
        ];
    }

    return ['upsell' => $upsell_recommendations];
}

function get_social_services_by_social_media_and_type($social_media_id, $type, $locale='ro') {
    if (!$social_media_id || !$type) {
        return new WP_Error('missing_param', 'Missing social media ID or type', ['status' => 400]);
    }
	
    $args = [
		'post_type'      => 'social-service',
		'posts_per_page' => -1,
		'meta_query'     => [
			'relation' => 'AND',
			[
				'key'     => 'social_media',
				'value'   => '"' . $social_media_id . '"',
				'compare' => 'LIKE'
			],
			[
				'key'     => 'type',
				'value'   => $type,
				'compare' => '='
			],
			[
				'key'     => 'language',
				'value'   => $locale,
				'compare' => '='
			]
		]
	];

	$query = new WP_Query($args);

    if (!$query->have_posts()) {
        return new WP_Error('no_services', 'No social services found for this social media and type', ['status' => 404]);
    }

    $social_services = [];
    while ($query->have_posts()) {
        $query->the_post();
        $social_services[] = [
            'id'    => get_the_ID(),
            'title' => get_the_title(),
            'type'  => get_field('type'), // Fetch ACF 'type' field
        ];
    }

    wp_reset_postdata();

    return $social_services;
}

// Load dependencies after all plugins are loaded
add_action('plugins_loaded', function() {
    // Check if WPGraphQL JWT Authentication is active before loading CustomAuth
    if (class_exists('WPGraphQL\JWT_Authentication\Auth')) {
        include_once __DIR__ . '/includes/CustomAuth.php';
    }
});