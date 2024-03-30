<?php
/**
 * Plugin Name: Custom WP Headless Settings
 * Description: Custom plugin for custom WP Headless Settings
 * Version:     0.0.1
 * Author:      Joseph
 * Author URI:  https://josnif.vercel.app/
 * Text Domain: cwh
 * Domain Path: /lang
 */

// define( 'GRAPHQL_JWT_AUTH_SECRET_KEY', '=7SdO_S-;(eaIS *L[x!r<[{SeLz0tgcK$Bhv>M$vi-7%@QztYx W%BXmPq} V<1' );

function remove_menus(){

    // remove_menu_page( 'index.php' );                  //Dashboard  
    remove_menu_page( 'edit.php?post_type=page' );    //Pages  
    remove_menu_page( 'edit.php' );                   //Posts  
    remove_menu_page( 'edit-comments.php' );          //Comments  
    remove_menu_page( 'themes.php' );                 //Appearance  
    remove_menu_page( 'wpcf7' );        //contact form
    // remove_menu_page( 'plugins.php' );                //Plugins  
    // remove_menu_page( 'users.php' );                  //Users  
    // remove_menu_page( 'tools.php' );                  //Tools  
    // remove_menu_page( 'options-general.php' );        //Settings
  
}

add_action( 'admin_menu', 'remove_menus' );

add_filter( 'graphql_jwt_auth_secret_key', function() {
  return ')d%^A1TUOU<X7G:ljd+~-l&<tg-0>q-++or.LRixazhWT1VFSzL!T1Uu2mI]kqD!';
});


// Extend GraphQL schema to include ACF fields

// add_filter('register_graphql_field_types', function ($fields) {
//     $fields['OrderData'] = [
//         'type' => 'String',
//         'description' => __('Custom fields for Service Order'),
//         'resolve' => function ($source, $args, $context, $info) {
//             return 'Custom fields for Service Order';
//         },
//     ];

//     return $fields;
// });


// Define mutation for creating service order
function createServiceOrderMutation() {
    register_graphql_mutation('createServiceOrderData', [
        'inputFields' => [
            'service_id' => [
                'type' => 'ID',
                'description' => __('Service ID'),
            ],
            'service_name' => [
                'type' => 'String',
                'description' => __('Service Name'),
            ],
            'plan' => [
                'type' => 'String',
                'description' => __('Plan'),
            ],
            'count' => [
                'type' => 'Int',
                'description' => __('Plan Count'),
            ],
            'plan_amount' => [
                'type' => 'Float',
                'description' => __('Plan Amount'),
            ],
            'plan_discount' => [
                'type' => 'Float',
                'description' => __('Plan Discount'),
            ],
            'total_amount' => [
                'type' => 'Float',
                'description' => __('Total Amount'),
            ],
            'payment_method' => [
                'type' => 'String',
                'description' => __('Payment Method'),
            ],
            'payment_id' => [
                'type' => 'String',
                'description' => __('Payment ID'),
            ],
            'account_id' => [
                'type' => 'ID',
                'description' => __('Account ID'),
            ],
            'user_id' => [
                'type' => 'ID',
                'description' => __('User ID'),
            ],
			'service_posts' => [
                'type' => ['list_of' => 'ServicePostInput'],
                'description' => __('Service Posts'),
            ],
			'status' => [
                'type' => 'String',
                'description' => __('Status'),
            ],
            // Add more input fields as needed
        ],
        'outputFields' => [
            'serviceOrder' => [
                'type' => 'ServiceOrder',
                'description' => __('The newly created service order.'),
                'resolve' => function ($source, $args, $context, $info) {
                    // Retrieve the newly created service order
                    
					$post = $source['serviceOrder'];
					// Check if $post is valid
					if ($post instanceof WP_Post) {
						$databaseId = $post->ID;

						$serviceOrder = [
							'databaseId' => $databaseId,
							'id' => $databaseId,
						];

					} else {
						$serviceOrder = null;
					}
					return $serviceOrder;
                },
            ],
        ],
        'mutateAndGetPayload' => function ($input, $context, $info) {
            // Validate input fields here
            
            // Create service order post
            $post_id = wp_insert_post([
                'post_type' => 'service-order',
                'post_title' => $input['service_name'],
                'post_status' => 'publish'
            ]);
			
            // Set ACF field values
            if (!empty($input['service_id'])) {
				// $serialized_service_id = serialize([$input['service_id']]);
                $serialized_service_id = $input['service_id'];
                update_field('service_id', $serialized_service_id, $post_id);
            }
            update_field('service_name', $input['service_name'], $post_id);
            update_field('plan', $input['plan'], $post_id);
            update_field('count', $input['count'], $post_id);
            update_field('plan_amount', $input['plan_amount'], $post_id);
            update_field('plan_discount', $input['plan_discount'], $post_id);
            update_field('total_amount', $input['total_amount'], $post_id);
            update_field('payment_method', $input['payment_method'], $post_id);
            update_field('payment_id', $input['payment_id'], $post_id);
            
            if (!empty($input['account_id'])) {
                // Serialize the relationship field value before saving
				// $serialized_account_id = serialize([$input['account_id']]);
                $serialized_account_id = $input['account_id'];
                update_field('account_id', $serialized_account_id, $post_id);
            }

            update_field('user_id', $input['user_id'], $post_id);
            update_field('status', $input['status'], $post_id);
            
            // Update repeater field 'service_posts'
            if (!empty($input['service_posts'])) {
                $service_posts = [];
                foreach ($input['service_posts'] as $service_post) {
                    $service_posts[] = [
                        'post' => $service_post['post']
                    ];
                }
                update_field('service_posts', $service_posts, $post_id);
            }


            // Return the created service order
            return [
                'serviceOrder' => get_post($post_id),
            ];
        },
    ]);
	
	// Define input type for service post
    register_graphql_input_type('ServicePostInput', [
        'fields' => [
            'post' => [
                'type' => 'String',
                'description' => __('Post Title|Link'),
            ],
        ],
        'description' => 'Input type for service post',
    ]);
}
add_action('graphql_register_types', 'createServiceOrderMutation');

// add_action( 'graphql_register_types', function() {
//     register_graphql_field( 'UserAccount', 'username', [
//         'type' => 'String',
//         'description' => 'The username of the social media account.',
//     ] );

//     register_graphql_field( 'UserAccount', 'socialMedia', [
//         'type' => 'String',
//         'description' => 'The social media handle of the account.',
//     ] );
// });


// Define a custom mutation for creating a social media account
add_action( 'graphql_register_types', function() {
    register_graphql_mutation( 'createSocialAccount', [
        'inputFields' => [
			'user' => [
                'type' => 'ID',
                'description' => 'The user of the social media account.',
            ],
            'username' => [
                'type' => 'String',
                'description' => 'The username of the social media account.',
            ],
            'socialMedia' => [
                'type' => 'String',
                'description' => 'The social media handle of the account.',
            ],
        ],
        'outputFields' => [
            'userAccount' => [
                'type' => 'UserAccount',
                'description' => 'The newly created social media account.',
				'resolve' => function ($source, $args, $context, $info) {
                    // Retrieve the newly created service order
                    $post = $source['UserAccount'];
					// Check if $post is valid
					if ($post instanceof WP_Post) {
						$databaseId = $post->ID;

						$userAccountData = [
							'databaseId' => $databaseId,
							'id' => $databaseId,
							'socialAccount' => (object)[
								'socialMedia' => $post->post_content ?? '',
								'username' => $post->post_title ?? ''
							]
						];

					} else {
						$userAccountData = null;
					}
					return $userAccountData;
					
                },
            ],
        ],
        'mutateAndGetPayload' => function( $input, $context, $info ) {
            // Create the social media account using ACF functions
            $post_id = wp_insert_post([
                'post_type' => 'social-media-account',
                'post_title' => $input['username'],
                'post_content' => $input['socialMedia'],
				'post_status' => 'publish'
            ]);
			
			update_field('user', $input['user'], $post_id);
			update_field('username', $input['username'], $post_id);
			update_field('social_media', $input['socialMedia'], $post_id);

            // Return the created social media account
            return [
                'UserAccount' => get_post( $post_id ),
            ];
        },
    ] );
});


function log_it( $message ) {
   if( WP_DEBUG === true ){
     if( is_array( $message ) || is_object( $message ) ){
       //error_log will be located according to server configuration
       //you can specify a custom location if needed like this
       //error_log( $var, 0, "full-path-to/error_log.txt")
       error_log( print_r( $message, true ) );
     } else {
       error_log( $message );
     }
   }
}
