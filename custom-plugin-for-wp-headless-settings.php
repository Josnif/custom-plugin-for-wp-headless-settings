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

add_filter('graphql_post_object_connection_query_args', function ($query_args, $source, $input, $context, $info) {
    if ($info->fieldName === 'socialServices' && !empty($input['where']['language'])) {
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
