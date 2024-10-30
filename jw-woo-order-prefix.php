<?php
/*
Plugin Name: JW Woo Order Prefix
Description: Adds a prefix to WooCommerce orders numbers
Version: 0.1.3
Author: iJasonWhite
Author URI: https://jasonwhite.uk
Text Domain: jw-woocommerce-order-number-prefix
Copyright: (c) 2020, Jason White (plugins@jasonwhite.uk)
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html
Plugin URI: https://jasonwhite.uk/plugins/jw-woocommerce-order-number-prefix

*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly


if (!class_exists('cls_wc_settings_jw_woocom_order_prefix')):

    function jw_woo_order_prefix__add_settings()
    {

        /**
         * Settings class
         *
         * @since 0.0.1
         */
        class cls_wc_settings_jw_woocom_order_prefix extends WC_Settings_Page
        {

            /**
             * Setup settings class
             *
             * @since  1.0
             */
            public function __construct()
            {

                $this->id = 'jw_woocom_order_prefix';
                $this->label = __('Order Prefix', 'my-textdomain');

                add_filter('woocommerce_settings_tabs_array', array(
                    $this,
                    'add_settings_page'
                ) , 20);

                add_action('woocommerce_settings_' . $this->id, array(
                    $this,
                    'output'
                ));
                add_action('woocommerce_settings_save_' . $this->id, array(
                    $this,
                    'save'
                ));
                add_action('woocommerce_sections_' . $this->id, array(
                    $this,
                    'output_sections'
                ));

            }

            /**
             * Get sections
             *
             * @return array
             */
            public function get_sections()
            {

                $sections = array(
                    '' => __('Prefix', 'my-textdomain') ,
                    'second' => __('About', 'my-textdomain')
                );

                return apply_filters('woocommerce_get_sections_' . $this->id, $sections);
            }

            /**
             * Get settings array
             *
             * @since 0.0.1
             * @param string $current_section Optional. Defaults to empty string.
             * @return array Array of settings
             */
            public function get_settings($current_section = '')
            {

                if ('second' == $current_section)
                {

                    /**
                     * Filter Plugin jw-woo-section-two Settings
                     *
                     * @since 0.0.1
                     * @param array $settings Array of the plugin settings
                     */
                    $settings = apply_filters('jw_woocom_order_prefix_section2_settings', array(

                        array(
                            'name' => __('About Plugin', 'my-textdomain') ,
                            'type' => 'title',
                            'desc' => 'A description',
                            'id' => 'jw_woocom_order_prefix_group2_options',
                        ) ,
                        array(
                            'type' => 'sectionend',
                            'id' => 'jw_woo_order_prefix_options'
                        ) ,

                    ));

                }
                else
                {

                    /**
                     * Filter Plugin jw-woo-section-one Settings
                     *
                     * @since 0.0.1
                     * @param array $settings Array of the plugin settings
                     */
                    $settings = apply_filters('jw_woocom_order_prefix_section1_settings', array(

                        array(
                            'name' => __('Custom Prefix', 'my-textdomain') ,
                            'type' => 'title',
                            'desc' => 'You may have a need to prefix order numbers, this section allows you to define the prefix you require.',
                            'id' => 'jw_woo_order_prefix_options_',
                        ) ,

                        array(
                            'type' => 'text',
                            'id' => 'ijw_woo_order_number_prefix',
                            'name' => __('Enter your prefix', 'my-textdomain') ,
                            'class' => 'wc-enhanced-text',
                            'desc_tip' => __('This text will prefix the order number eg. eg WC', 'my-textdomain') ,
                            'default' => '',
                        ) ,
                        array(
                            'type' => 'sectionend',
                            'id' => 'jw_woo_order_prefix_options__'
                        ) ,

                    ));

                }

                /**
                 * Filter jw_woocom_order_prefix Settings
                 *
                 * @since 0.0.1
                 * @param array $settings Array of the plugin settings
                 */
                return apply_filters('woocommerce_get_settings_' . $this->id, $settings, $current_section);

            }

            /**
             * Output the settings
             *
             * @since 1.0
             */
            public function output()
            {

                global $current_section;

                $settings = $this->get_settings($current_section);
                WC_Admin_Settings::output_fields($settings);
            }

            /**
             * Save settings
             *
             * @since 1.0
             */
            public function save()
            {

                global $current_section;

                $settings = $this->get_settings($current_section);
                WC_Admin_Settings::save_fields($settings);
            }

        }

        return new cls_wc_settings_jw_woocom_order_prefix();

    }

    function jw_woo_order_prefix()
    {
        class cls_jw_woo_order_prefix
        {
            protected static $instance;
            public function __construct()
            {

                add_action('wp_insert_post', array(
                    $this,
                    'set_prefix_order_number'
                ) , 10, 2);
                add_action('woocommerce_process_shop_order_meta', array(
                    $this,
                    'set_prefix_order_number'
                ) , 10, 2);
                add_filter('woocommerce_order_number', array(
                    $this,
                    'change_woocommerce_order_number'
                ) , 20);
            }
            public static function instance()
            {

                //if no object instance then instantiate.
                if (null === self::$instance)
                {
                    self::$instance = new self();
                }
                return self::$instance;
            }
            /**
             * Update the order number with a prefix
             *
             * @since 0.1.1
             * @param string $order_id
             * @return string prefixed order id
             */
            public function change_woocommerce_order_number($order_id)
            {

                $prefix = (get_option('ijw_woo_order_number_prefix')) ? get_option('ijw_woo_order_number_prefix') : '';
                $new_order_id = $prefix . $order_id;
                return $new_order_id;
            }

            /**
             * Update the order number with a prefix
             *
             * @since 0.1.1
             * @param string $post_id
             * @param string $post
             * @return boolean
             */
            public function set_prefix_order_number($post_id, $post)
            {
                global $wpdb;
                if ('shop_order' === $post->post_type && 'auto-draft' !== $post->post_status)
                {

                    $order = wc_get_order($post_id);
                    $order_number = $order->get_meta('_order_number', true, 'edit');

                    $prefix = (get_option('ijw_woo_order_number_prefix')) ? get_option('ijw_woo_order_number_prefix') : '';
                    if ('' === $order_number)
                    {

                        $success = false;
						$sql = "INSERT INTO {$wpdb->postmeta} (post_id, meta_key, meta_value) VALUES('".$wpdb->_real_escape($post_id)."','_order_number', '".$wpdb->_real_escape($prefix.$post_id)."'  )";
                        $success = $wpdb->query($sql);
						return $success;

                    } else {
				
						return false;
					}
                }
            }
        }
        return cls_jw_woo_order_prefix::instance();
    }

    // make it so!
    add_filter('woocommerce_get_settings_pages', 'jw_woo_order_prefix__add_settings', 15);
    jw_woo_order_prefix();

endif;
