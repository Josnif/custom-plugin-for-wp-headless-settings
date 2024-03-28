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
    remove_menu_page( 'edit.php' );                   //Posts  
//     remove_menu_page( 'edit.php?post_type=page' );    //Pages  
    remove_menu_page( 'edit-comments.php' );          //Comments  
    remove_menu_page( 'themes.php' );                 //Appearance  
    // remove_menu_page( 'plugins.php' );                //Plugins  
    // remove_menu_page( 'users.php' );                  //Users  
    // remove_menu_page( 'tools.php' );                  //Tools  
    // remove_menu_page( 'options-general.php' );        //Settings
    // remove_menu_page( 'wpcf7' );        //contact form
  
}

add_action( 'admin_menu', 'remove_menus' );

add_filter( 'graphql_jwt_auth_secret_key', function() {
  return ')d%^A1TUOU<X7G:ljd+~-l&<tg-0>q-++or.LRixazhWT1VFSzL!T1Uu2mI]kqD!';
});


// Extend GraphQL schema to include ACF fields
/*
add_filter('register_graphql_field_types', function ($fields) {
    $fields['ServiceOrderFields'] = [
        'type' => 'String',
        'description' => __('Custom fields for Service Order'),
        'resolve' => function ($source, $args, $context, $info) {
            return 'Custom fields for Service Order';
        },
    ];

    return $fields;
});
*/

// Define mutation for creating service order
function createServiceOrderMutation() {
    register_graphql_mutation('createServiceOrder', [
        'inputFields' => [
            'service_id' => [
                'type' => 'ID!',
                'description' => __('Service ID'),
            ],
            'service_name' => [
                'type' => 'String',
                'description' => __('Service Name'),
            ],
            'plan' => [
                'type' => 'String!',
                'description' => __('Plan'),
            ],
            'count' => [
                'type' => 'Int!',
                'description' => __('Plan Count'),
            ],
            'plan_amount' => [
                'type' => 'Float!',
                'description' => __('Plan Amount'),
            ],
            'plan_discount' => [
                'type' => 'Float',
                'description' => __('Plan Discount'),
            ],
            'total_amount' => [
                'type' => 'Float!',
                'description' => __('Total Amount'),
            ],
            'payment_method' => [
                'type' => 'String!',
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
                'type' => 'String',
                'description' => __('User ID'),
            ],
            // Add more input fields as needed
        ],
        'outputFields' => [
            'serviceOrder' => [
                'type' => 'ServiceOrder',
                'description' => __('The newly created service order.'),
                'resolve' => function ($source, $args, $context, $info) {
                    // Retrieve the newly created service order
                    return $source->serviceOrder;
                },
            ],
        ],
        'mutateAndGetPayload' => function ($input, $context, $info) {
            // Create service order post
            $post_id = wp_insert_post([
                'post_type' => 'service-order',
                'post_title' => $input['service_name'], // You can customize the title
                // Add more post fields as needed
            ]);

            // Set ACF field values
            if (isset($input['service_id'])) {
                // Serialize the relationship field value before saving
                $serialized_service_id = serialize([$input['service_id']]);
                update_field('service_id', $serialized_service_id, $post_id);
            }
            update_field('service_id', $input['service_id'], $post_id);
            update_field('service_name', $input['service_name'], $post_id);
            update_field('plan', $input['plan'], $post_id);
            update_field('count', $input['count'], $post_id);
            update_field('plan_amount', $input['plan_amount'], $post_id);
            update_field('plan_discount', $input['plan_discount'], $post_id);
            update_field('total_amount', $input['total_amount'], $post_id);
            update_field('payment_method', $input['payment_method'], $post_id);
            update_field('payment_id', $input['payment_id'], $post_id);
            
            if (isset($input['account_id'])) {
                // Serialize the relationship field value before saving
                $serialized_account_id = serialize([$input['account_id']]);
                update_field('account_id', $serialized_account_id, $post_id);
            }

            update_field('user_id', $input['user_id'], $post_id);
            // Update more ACF fields as needed

            // Return the created service order
            return [
                'serviceOrder' => get_post($post_id),
            ];
        },
    ]);
}
add_action('graphql_register_types', 'createServiceOrderMutation');
