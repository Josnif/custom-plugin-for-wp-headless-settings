<?php

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
                    'type' => 'String',
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