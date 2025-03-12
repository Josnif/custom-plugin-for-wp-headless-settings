<?php

namespace CustomWP;

use \WPGraphQL\JWT_Authentication\Auth;
use WP_User;

class CustomAuth extends Auth {

    public static function generateToken(WP_User $user) {
        // Ensure the user exists
        if (!$user || !isset($user->ID)) {
            return new \WP_Error('invalid_user', __('Invalid user.', 'customwp'));
        }

        // Create signed token
        return parent::get_signed_token($user);
    }

    public static function generateRefreshToken(WP_User $user) {
        return parent::get_refresh_token($user);
    }
}
