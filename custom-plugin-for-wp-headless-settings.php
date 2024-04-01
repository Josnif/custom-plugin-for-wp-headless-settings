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


function remove_menus(){

    // remove_menu_page( 'index.php' );                  //Dashboard  
    remove_menu_page( 'edit.php?post_type=page' );    //Pages  
    remove_menu_page( 'edit.php' );                   //Posts  
    remove_menu_page( 'edit-comments.php' );          //Comments  
//     remove_menu_page( 'themes.php' );                 //Appearance  
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
       //you can specify a custom location if needed like this
       //error_log( $var, 0, "full-path-to/error_log.txt")
       error_log( print_r( $message, true ) );
     } else {
       error_log( $message );
     }
   }
}



// Define resolver for querying service orders
function getServiceOrders($source, $args, $context, $info) {
    // Define WP_Query arguments
    $query_args = [
        'post_type' => 'service-order',
        'posts_per_page' => -1, // Retrieve all service orders
        'orderby' => 'date',
        'order' => 'DESC',
        'meta_query' => [
            [
                'key' => 'user_id', // ACF field key
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

    // Return the queried service orders
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


// function custom_store_settings_register( $wp_customize ) {
//     // Create a section for store settings
//     $wp_customize->add_section( 'store_settings_section', array(
//         'title'    => __( 'Store Settings', 'custom-wp-headless-settings' ),
//         'priority' => 30,
//     ) );

//     // Add fields for store name, description, currency, and symbol
//     $wp_customize->add_setting( 'store_name' );
//     $wp_customize->add_control( 'store_name', array(
//         'label'   => __( 'Store Name', 'custom-wp-headless-settings' ),
//         'section' => 'store_settings_section',
//         'type'    => 'text',
//     ) );

//     $wp_customize->add_setting( 'store_description' );
//     $wp_customize->add_control( 'store_description', array(
//         'label'   => __( 'Store Description', 'custom-wp-headless-settings' ),
//         'section' => 'store_settings_section',
//         'type'    => 'textarea',
//     ) );

//     $wp_customize->add_setting( 'store_currency' );
//     $wp_customize->add_control( 'store_currency', array(
//         'label'   => __( 'Store Currency', 'custom-wp-headless-settings' ),
//         'section' => 'store_settings_section',
//         'type'    => 'text',
//     ) );

//     $wp_customize->add_setting( 'store_currency_symbol' );
//     $wp_customize->add_control( 'store_currency_symbol', array(
//         'label'   => __( 'Store Currency Symbol', 'custom-wp-headless-settings' ),
//         'section' => 'store_settings_section',
//         'type'    => 'text',
//     ) );
// }
// add_action( 'customize_register', 'custom_store_settings_register' );


function custom_store_settings_page() {
    add_options_page(
        'Store Settings',
        'Store Settings',
        'manage_options',
        'store-settings',
        'custom_render_store_settings'
    );
}
add_action('admin_menu', 'custom_store_settings_page');

function custom_render_store_settings() {
    ?>
    <div class="wrap">
        <h2>Store Settings</h2>
        <form method="post" action="options.php">
            <?php
            settings_fields('store_settings_group');
            do_settings_sections('store-settings');
            submit_button('Save Settings');
            ?>
        </form>
    </div>
    <?php
}

function custom_store_settings_init() {
    register_setting(
        'store_settings_group',
        'store_settings',
        'custom_store_settings_validate'
    );

    add_settings_section(
        'store_settings_section',
        'Store Settings',
        'custom_store_settings_section_callback',
        'store-settings'
    );

    add_settings_field(
        'store_name',
        'Store Name',
        'custom_store_name_field_callback',
        'store-settings',
        'store_settings_section'
    );

    add_settings_field(
        'store_description',
        'Store Description',
        'custom_store_description_field_callback',
        'store-settings',
        'store_settings_section'
    );

    add_settings_field(
        'store_currency',
        'Store Currency',
        'custom_store_currency_field_callback',
        'store-settings',
        'store_settings_section'
    );

    add_settings_field(
        'store_currency_symbol',
        'Store Currency Symbol',
        'custom_store_currency_symbol_field_callback',
        'store-settings',
        'store_settings_section'
    );
	
	add_settings_field(
        'store_currency_symbol_position',
        'Currency Symbol Position',
        'custom_store_currency_symbol_position_field_callback',
        'store-settings',
        'store_settings_section'
    );

    add_settings_field(
        'store_currency_decimals',
        'Currency Decimals',
        'custom_store_currency_decimals_field_callback',
        'store-settings',
        'store_settings_section'
    );

    add_settings_field(
        'store_currency_decimal_separator',
        'Currency Decimal Separator',
        'custom_store_currency_decimal_separator_field_callback',
        'store-settings',
        'store_settings_section'
    );
	
}

function custom_store_settings_section_callback() {
    echo '<p>Enter your store settings below:</p>';
}

function custom_store_name_field_callback() {
    $store_name = get_option('store_settings')['store_name'] ?? '';
    echo '<input type="text" id="store_name" name="store_settings[store_name]" value="' . esc_attr($store_name) . '" />';
}

function custom_store_description_field_callback() {
    $store_description = get_option('store_settings')['store_description'] ?? '';
    echo '<textarea id="store_description" name="store_settings[store_description]">' . esc_textarea($store_description) . '</textarea>';
}

function custom_store_currency_field_callback() {
    $store_currency = get_option('store_settings')['store_currency'] ?? '';
    echo '<input type="text" id="store_currency" name="store_settings[store_currency]" value="' . esc_attr($store_currency) . '" />';
}

function custom_store_currency_symbol_field_callback() {
    $store_currency_symbol = get_option('store_settings')['store_currency_symbol'] ?? '';
    echo '<input type="text" id="store_currency_symbol" name="store_settings[store_currency_symbol]" value="' . esc_attr($store_currency_symbol) . '" />';
}

function custom_store_currency_symbol_position_field_callback() {
    $store_currency_symbol_position = get_option('store_settings')['store_currency_symbol_position'] ?? '';
    echo '<input type="text" id="store_currency_symbol_position" name="store_settings[store_currency_symbol_position]" value="' . esc_attr($store_currency_symbol_position) . '" />';
}

function custom_store_currency_decimals_field_callback() {
    $store_currency_decimals = get_option('store_settings')['store_currency_decimals'] ?? '';
    echo '<input type="text" id="store_currency_decimals" name="store_settings[store_currency_decimals]" value="' . esc_attr($store_currency_decimals) . '" />';
}

function custom_store_currency_decimal_separator_field_callback() {
    $store_currency_decimal_separator = get_option('store_settings')['store_currency_decimal_separator'] ?? '';
    echo '<input type="text" id="store_currency_decimal_separator" name="store_settings[store_currency_decimal_separator]" value="' . esc_attr($store_currency_decimal_separator) . '" />';
}

add_action('admin_init', 'custom_store_settings_init');


// ADD SETTINGS TO GRAPHQL
function add_store_settings_to_graphql() {
    register_graphql_field(
        'RootQuery',
        'storeSettings',
        [
            'type' => 'StoreSettings',
            'resolve' => function () {
                return get_option('store_settings');
            }
        ]
    );
}
add_action('graphql_register_types', 'add_store_settings_to_graphql');
function register_store_settings_graphql_type() {
    register_graphql_object_type(
        'StoreSettings',
        [
            'fields' => [
                'storeName' => [
                    'type' => 'String',
                    'description' => 'The name of the store',
                    'resolve' => function ($settings) {
                        return $settings['store_name'] ?? null;
                    }
                ],
                'storeDescription' => [
                    'type' => 'String',
                    'description' => 'The description of the store',
                    'resolve' => function ($settings) {
                        return $settings['store_description'] ?? null;
                    }
                ],
                'storeCurrency' => [
                    'type' => 'String',
                    'description' => 'The currency of the store',
                    'resolve' => function ($settings) {
                        return $settings['store_currency'] ?? null;
                    }
                ],
                'storeCurrencySymbol' => [
                    'type' => 'String',
                    'description' => 'The currency symbol of the store',
                    'resolve' => function ($settings) {
                        return $settings['store_currency_symbol'] ?? null;
                    }
                ],
                'storeCurrencySymbolPosition' => [
                    'type' => 'String',
                    'description' => 'The position of the currency symbol (e.g., before or after the amount)',
                    'resolve' => function ($settings) {
                        return $settings['store_currency_symbol_position'] ?? null;
                    }
                ],
                'storeCurrencyDecimals' => [
                    'type' => 'Int',
                    'description' => 'The number of decimals for currency amounts',
                    'resolve' => function ($settings) {
                        return $settings['store_currency_decimals'] ?? null;
                    }
                ],
                'storeCurrencyDecimalSeparator' => [
                    'type' => 'String',
                    'description' => 'The decimal separator for currency amounts',
                    'resolve' => function ($settings) {
                        return $settings['store_currency_decimal_separator'] ?? null;
                    }
                ],
            ],
        ]
    );
}
add_action('graphql_register_types', 'register_store_settings_graphql_type');
