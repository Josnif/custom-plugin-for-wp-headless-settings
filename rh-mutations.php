<?php

use CustomWP\CustomAuth;

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
            'currency' => [
                'type' => 'String',
                'description' => __('Currency'),
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
			'website_traffic' => [
                'type' => ['list_of' => 'WebsiteTrafficInput'],
                'description' => __('Site Traffic'),
            ],
			'upsells' => [
                'type' => ['list_of' => 'UpSellInput'],
                'description' => __('Upsells'),
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
            update_field('currency', $input['currency'], $post_id);
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
			
			// Update repeater field 'website_traffic'
            if (!empty($input['website_traffic'])) {
                $websites = [];
                foreach ($input['website_traffic'] as $website) {
                    $websites[] = [
                        'traffic_url' 		=> $website['traffic_url'],
						'traffic_location' 	=> $website['traffic_location']
                    ];
                }
                update_field('website_traffic', $websites, $post_id);
            }
			
			// Update repeater field 'upsells'
			error_log( print_r( $input['upsells'], true ) );
            if (!empty($input['upsells'])) {
                $websites = [];
                foreach ($input['upsells'] as $upsell) {
                    $websites[] = [
                        'name' 		=> $upsell['name'],
						'count' 	=> (int)$upsell['count'],
						'price' 	=> (float)$upsell['price'],
                    ];
                }
                update_field('upsells', $websites, $post_id);
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
	
	// Define input type for website traffic
    register_graphql_input_type('WebsiteTrafficInput', [
        'fields' => [
            'traffic_url' => [
                'type' => 'String',
                'description' => __('Traffic URL'),
            ],
			'traffic_location' => [
                'type' => 'String',
                'description' => __('Traffic Location'),
            ],
        ],
        'description' => 'Input type for website traffic',
    ]);
	
	// Define input type for upsells
    register_graphql_input_type('UpSellInput', [
        'fields' => [
            'name' => [
                'type' => 'String',
                'description' => __('Upsell Type'),
            ],
			'count' => [
                'type' => 'Int',
                'description' => __('Count'),
            ],
			'price' => [
                'type' => 'Float',
                'description' => __('Price'),
            ],
        ],
        'description' => 'Input type for Upsell',
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
			update_field('socialMedia', $input['socialMedia'], $post_id);
			
        // update_field('social_media', 48, $post_id);
			
			log_it($input['socialMedia']);

            // Return the created social media account
            return [
                'UserAccount' => get_post( $post_id ),
            ];
        },
    ] );
});


function duplicateServiceOrderMutation() {
    register_graphql_mutation('duplicateServiceOrder', [
        'inputFields' => [
            'id' => [
                'type' => 'ID',
                'description' => __('ID of the service order to duplicate'),
            ],
        ],
        'outputFields' => [
            'duplicatedServiceOrder' => [
                'type' => 'ServiceOrder',
                'description' => __('The duplicated service order.'),
                'resolve' => function ($source, $args, $context, $info) {
                    // Retrieve the duplicated service order
                    $post = $source['duplicatedServiceOrder'];
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
            $original_service_order = get_post($input['id']);

            if (!$original_service_order) {
                return new WP_Error('invalid_service_order', __('Invalid service order ID.'));
            }

            $service_order_data = [];
			$service_order_data['service_id'] = get_field('service_id', $original_service_order);
            $service_order_data['service_name'] = get_field('service_name', $original_service_order);
            $service_order_data['plan'] = get_field('plan', $original_service_order);
            $service_order_data['count'] = get_field('count', $original_service_order);
            $service_order_data['plan_amount'] = get_field('plan_amount', $original_service_order);
            $service_order_data['plan_discount'] = get_field('plan_discount', $original_service_order);
            $service_order_data['total_amount'] = get_field('total_amount', $original_service_order);
            $service_order_data['currency'] = get_field('currency', $original_service_order);
            $service_order_data['payment_method'] = get_field('payment_method', $original_service_order);
            $service_order_data['payment_id'] = get_field('payment_id', $original_service_order);
            $service_order_data['account_id'] = get_field('account_id', $original_service_order);
            $service_order_data['user_id'] = get_field('user_id', $original_service_order);
            $service_order_data['status'] = get_field('status', $original_service_order);
            $service_posts = get_field('service_posts', $original_service_order);
			
            $post_id = wp_insert_post([
                'post_type' => 'service-order',
                'post_title' => $service_order_data['service_name'],
                'post_status' => 'publish',
            ]);

            update_field('service_id', $service_order_data['service_id'], $post_id);
            update_field('service_name', $service_order_data['service_name'], $post_id);
            update_field('plan', $service_order_data['plan'], $post_id);
            update_field('count', $service_order_data['count'], $post_id);
            update_field('plan_amount', $service_order_data['plan_amount'], $post_id);
            update_field('plan_discount', $service_order_data['plan_discount'], $post_id);
            update_field('total_amount', $service_order_data['total_amount'], $post_id);
            update_field('currency', $service_order_data['currency'], $post_id);
            update_field('payment_method', $service_order_data['payment_method'], $post_id);
            update_field('payment_id', $service_order_data['payment_id'], $post_id);
            update_field('account_id', $service_order_data['account_id'], $post_id);
            update_field('user_id', $service_order_data['user_id'], $post_id);
            update_field('status', $service_order_data['status'], $post_id);
           	
			if (!empty($service_posts)) {
				update_field('service_posts', $service_posts, $post_id);
			}

            return [
                'duplicatedServiceOrder' => get_post($post_id),
            ];
        },
    ]);
}
add_action('graphql_register_types', 'duplicateServiceOrderMutation');

// Define a mutation for creating order invoice
add_action( 'graphql_register_types', function() {
    register_graphql_mutation( 'createServiceInvoice', [
        'inputFields' => [
			'order' => [
                'type' => 'ID',
                'description' => 'The related order.',
            ],
            'invoice_link' => [
                'type' => 'String',
                'description' => 'The invoice link.',
            ],
            'invoice_number' => [
                'type' => 'String',
                'description' => 'The invoice number.',
            ],
        ],
        'outputFields' => [
            'orderInvoice' => [
                'type' => 'orderInvoice',
                'description' => 'The newly created order invoice.',
				'resolve' => function ($source, $args, $context, $info) {
                    // Retrieve the newly created service order
                    $post = $source['orderInvoice'];
					// Check if $post is valid
					if ($post instanceof WP_Post) {
						$databaseId = $post->ID;

						$orderInvoiceData = [
							'databaseId' => $databaseId,
							'id' => $databaseId,
						];

					} else {
						$orderInvoiceData = null;
					}
					return $orderInvoiceData;
					
                },
            ],
        ],
        'mutateAndGetPayload' => function( $input, $context, $info ) {
            $post_id = wp_insert_post([
                'post_type' => 'order-invoice',
                'post_title' => $input['order'],
                'post_content' => $input['invoice_link'],
				'post_status' => 'publish'
            ]);
			
			update_field('order', $input['order'], $post_id);
			update_field('invoice_link', $input['invoice_link'], $post_id);
			update_field('invoice_number', $input['invoice_number'], $post_id);
			
			// log_it($input['socialMedia']);

            // Return the created order invoice
            return [
                'orderInvoice' => get_post( $post_id ),
            ];
        },
    ] );
});


function updateUserPasswordMutation() {
    register_graphql_mutation('updateUserPassword', [
        'inputFields' => [
            'userId' => [
                'type' => 'ID',
                'description' => __('ID of the user whose password is to be updated', 'customwp'),
            ],
            'currentPassword' => [
                'type' => 'String',
                'description' => __('Current password of the user', 'customwp'),
            ],
            'newPassword' => [
                'type' => 'String',
                'description' => __('New password for the user', 'customwp'),
            ],
        ],
        'outputFields' => [
            'success' => [
                'type' => 'Boolean',
                'description' => __('Whether the password was successfully updated', 'customwp'),
            ],
            'message' => [
                'type' => 'String',
                'description' => __('Message regarding the update process', 'customwp'),
            ],
        ],
        'mutateAndGetPayload' => function ($input, $context, $info) {
            $user_id = $input['userId'];
            $current_password = $input['currentPassword'];
            $new_password = $input['newPassword'];

            // Get the user object
            $user = get_user_by('ID', $user_id);

            if (!$user) {
                return [
                    'success' => false,
                    'message' => __('Invalid user ID.', 'customwp'),
                ];
            }

            // Check if the current password is correct
            if (!wp_check_password($current_password, $user->user_pass, $user->ID)) {
                return [
                    'success' => false,
                    'message' => __('Current password is incorrect.', 'customwp'),
                ];
            }

            // Update the user's password
            wp_set_password($new_password, $user->ID);

            return [
                'success' => true,
                'message' => __('Password updated successfully.', 'customwp'),
            ];
        },
    ]);
}
add_action('graphql_register_types', 'updateUserPasswordMutation');

function resetUserPasswordMutation() {
    register_graphql_mutation('resetPassword', [
        'inputFields' => [
            'email' => [
                'type' => 'String',
                'description' => __('Email of the user whose password is to be reset', 'customwp'),
            ],
            'password' => [
                'type' => 'String',
                'description' => __('New password for the user', 'customwp'),
            ],
        ],
        'outputFields' => [
            'success' => [
                'type' => 'Boolean',
                'description' => __('Whether the password was successfully reset', 'customwp'),
            ],
            'message' => [
                'type' => 'String',
                'description' => __('Message regarding the reset process', 'customwp'),
            ],
        ],
        'mutateAndGetPayload' => function ($input, $context, $info) {
            $email = $input['email'];
            $new_password = $input['password'];

            // Get the user object by email
            $user = get_user_by('email', $email);

            if (!$user) {
                return [
                    'success' => false,
                    'message' => __('Invalid email address.', 'customwp'),
                ];
            }

            // Update the user's password
            wp_set_password($new_password, $user->ID);

            return [
                'success' => true,
                'message' => __('Password reset successfully.', 'customwp'),
            ];
        },
    ]);
}
add_action('graphql_register_types', 'resetUserPasswordMutation');

function createUpdateServiceOrderMutation() {
    register_graphql_mutation('updateServiceOrderData', [
        'inputFields' => [
            'order_id' => [
                'type' => 'ID',
                'description' => __('The order ID of the service order to update'),
            ],
            'payment_id' => [
                'type' => 'String',
                'description' => __('The payment ID'),
            ],
            'payment_method' => [
                'type' => 'String',
                'description' => __('The new payment method'),
            ],
            'status' => [
                'type' => 'String',
                'description' => __('The new status of the order'),
            ],
        ],
        'outputFields' => [
            'serviceOrder' => [
                'type' => 'ServiceOrder',
                'description' => __('The updated service order'),
                'resolve' => function ($source) {
                    // return $source['serviceOrder'] ? get_post($source['serviceOrder']->ID) : null;
					$post = $source['serviceOrder'];
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
            // Validate input
            if (empty($input['order_id'])) {
                throw new \GraphQL\Error\UserError(__('Order ID is required.'));
            }

            // Fetch the order post by ID
            $post = get_post($input['order_id']);
            if (!$post || $post->post_type !== 'service-order') {
                throw new \GraphQL\Error\UserError(__('Order not found.'));
            }

            // Validate payment ID if provided
            // if (!empty($input['payment_id']) && get_field('payment_id', $post->ID) !== $input['payment_id']) {
                // throw new \GraphQL\Error\UserError(__('Payment ID does not match.'));
            // }
			
			if (isset($input['payment_id']) && !empty($input['payment_id'])) {
                update_field('payment_id', $input['payment_id'], $post->ID);
            }

            if (isset($input['payment_method'])) {
                update_field('payment_method', $input['payment_method'], $post->ID);
            }

            if (isset($input['status'])) {
                update_field('status', $input['status'], $post->ID);
            }

            // Return the updated service order
            return [
                'serviceOrder' => $post,
            ];
        },
    ]);
}
add_action('graphql_register_types', 'createUpdateServiceOrderMutation');

add_action('graphql_register_types', function() {
    register_graphql_mutation('loginWithoutPassword', [
        'inputFields' => [
            'username' => [
                'type' => 'String',
                'description' => 'Username or email of the user',
            ],
        ],
        'outputFields' => [
            'authToken' => [
                'type' => 'String',
                'description' => 'JWT authentication token',
            ],
            'refreshToken' => [
                'type' => 'String',
                'description' => 'JWT refresh token',
            ],
            'user' => [
                'type' => 'User',
                'description' => 'Authenticated user details',
            ],
            'id' => [
                'type' => 'Int',
                'description' => 'User ID',
            ],
        ],
        'mutateAndGetPayload' => function($input) {
            $username = sanitize_text_field($input['username']);

            // Get user by username or email
            $user = get_user_by('login', $username);
            if (!$user) {
                $user = get_user_by('email', $username);
            }

            if (!$user) {
                return new WP_Error('invalid_user', __('User not found', 'wp-graphql-jwt-authentication'));
            }

            // Set the current user
            wp_set_current_user($user->ID);

            // Check if JWT Auth class exists
            if (!class_exists('WPGraphQL\JWT_Authentication\Auth')) {
                return new WP_Error('jwt_auth_error', __('JWT Authentication class not found', 'wp-graphql-jwt-authentication'));
            }

            /// Generate JWT token using the new class
            $authToken = CustomAuth::generateToken($user);
            $refreshToken = CustomAuth::generateRefreshToken($user);

            if (is_wp_error($authToken)) {
                return new WP_Error('jwt_auth_error', __('Failed to generate token', 'customwp'));
            }

            return [
                'authToken' => $authToken,
                'refreshToken' => $refreshToken,
                'user' => new WPGraphQL\Model\User($user),
                'id' => $user->ID,
            ];
        }
    ]);
});
