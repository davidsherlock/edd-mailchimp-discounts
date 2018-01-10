<?php
/**
 * Plugin Name:     Easy Digital Downloads - MailChimp Discounts
 * Plugin URI:      https://sellcomet.com/downloads/mailchimp-discounts
 * Description:     Reward MailChimp for WordPress subscribers with a customizable discount code.
 * Version:         1.0.0
 * Author:          Sell Comet
 * Author URI:      https://sellcomet.com
 * Text Domain:     edd-mailchimp-discounts
 *
 * @package         EDD\MailChimp_Discounts
 * @author          Sell Comet
 * @copyright       Copyright (c) Sell Comet
 */


// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;

if( !class_exists( 'EDD_MailChimp_Discounts' ) ) {

    /**
     * Main EDD_MailChimp_Discounts class
     *
     * @since       1.0.0
     */
    class EDD_MailChimp_Discounts {

        /**
         * @var         EDD_MailChimp_Discounts $instance The one true EDD_MailChimp_Discounts
         * @since       1.0.0
         */
        private static $instance;


        /**
         * Get active instance
         *
         * @access      public
         * @since       1.0.0
         * @return      object self::$instance The one true EDD_MailChimp_Discounts
         */
        public static function instance() {
            if( !self::$instance ) {
                self::$instance = new EDD_MailChimp_Discounts();
                self::$instance->setup_constants();
                self::$instance->load_textdomain();
                self::$instance->hooks();
            }

            return self::$instance;
        }


        /**
         * Setup plugin constants
         *
         * @access      private
         * @since       1.0.0
         * @return      void
         */
        private function setup_constants() {
            // Plugin version
            define( 'EDD_MAILCHIMP_DISCOUNTS_VER', '1.0.0' );

            // Plugin path
            define( 'EDD_MAILCHIMP_DISCOUNTS_DIR', plugin_dir_path( __FILE__ ) );

            // Plugin URL
            define( 'EDD_MAILCHIMP_DISCOUNTS_URL', plugin_dir_url( __FILE__ ) );
        }


        /**
         * Run action and filter hooks
         *
         * @access      private
         * @since       1.0.0
         * @return      void
         */
        private function hooks() {

            if ( is_admin() ) {
              // Is MailChimp for WordPress installed and activated?
              add_action( 'admin_notices', array( $this, 'mc4wp_admin_notice' ) );

              // Register MailChimp Discounts extension settings subsection
              add_filter( 'edd_settings_sections_extensions', array( $this, 'register_settings_subsection' ), 1, 1 );

              // Register MailChimp Discounts extension settings
              add_filter( 'edd_settings_extensions', array( $this, 'settings' ), 1 );

              // Register MailChimp Discounts emails settings subsection
              add_filter( 'edd_settings_sections_emails', array( $this, 'register_settings_subsection_emails' ), 1 );

              // Register MailChimp Discounts emails settings
              add_filter( 'edd_settings_emails', array( $this, 'settings_emails' ), 1 );

              if ( class_exists( 'MC4WP_MailChimp') ) {
                // Add filter to merge our template tags
                add_filter( 'edd_mailchimp_discounts_email_template_tags', array( $this, 'merge_email_template_tags'), 1 );

                // Add filter to populate our select with the MailChimp lists
                add_filter( 'edd_mailchimp_discounts_settings_mc4wp_lists', array( $this, 'get_mc4wp_lists' ), 1 );
              }

              // Handle licensing
              if( class_exists( 'EDD_License' ) ) {
                  $license = new EDD_License( __FILE__, 'MailChimp Discounts', EDD_MAILCHIMP_DISCOUNTS_VER, 'Sell Comet', null, 'https://sellcomet.com/', 269 );
              }
            }

            if ( class_exists( 'MC4WP_MailChimp') && (bool) edd_get_option( 'mailchimp_discounts_enabled', false ) == true ) {
              // Process MailChimp for WordPress form submission
              add_action( 'mc4wp_form_subscribed', array( $this, 'process_lead' ), 10, 4 );
            }

        }


        /**
         * Make sure MailChimp for WordPress is installed and activated.
         *
         * @since       1.0.0
         * @access      public
         * @return      void
         */
        public function mc4wp_admin_notice() {
          if ( ! class_exists( 'MC4WP_MailChimp') ) {
            echo '<div class="error"><p>' . sprintf( __( 'MailChimp Discounts requires %sMailChimp for WordPress%s. Please install and activate it to continue.', 'edd-mailchimp-discounts' ), '<a href="https://en-gb.wordpress.org/plugins/mailchimp-for-wp/" title="MailChimp for WordPress" target="_blank">', '</a>' ) . '</p></div>';
          }
        }


        /**
         * Internationalization
         *
         * @access      public
         * @since       1.0.0
         * @return      void
         */
        public function load_textdomain() {
            // Set filter for language directory
            $lang_dir = EDD_MAILCHIMP_DISCOUNTS_DIR . '/languages/';
            $lang_dir = apply_filters( 'edd_mailchimp_discounts_languages_directory', $lang_dir );

            // Traditional WordPress plugin locale filter
            $locale = apply_filters( 'plugin_locale', get_locale(), 'edd-mailchimp-discounts' );
            $mofile = sprintf( '%1$s-%2$s.mo', 'edd-mailchimp-discounts', $locale );

            // Setup paths to current locale file
            $mofile_local   = $lang_dir . $mofile;
            $mofile_global  = WP_LANG_DIR . '/edd-mailchimp-discounts/' . $mofile;

            if( file_exists( $mofile_global ) ) {
                // Look in global /wp-content/languages/edd-plugin-name/ folder
                load_textdomain( 'edd-mailchimp-discounts', $mofile_global );
            } elseif( file_exists( $mofile_local ) ) {
                // Look in local /wp-content/plugins/edd-plugin-name/languages/ folder
                load_textdomain( 'edd-mailchimp-discounts', $mofile_local );
            } else {
                // Load the default language files
                load_plugin_textdomain( 'edd-mailchimp-discounts', false, $lang_dir );
            }
        }


        /**
         * Registers the subsection for MailChimp Discounts extension Settings
         *
         * @access      public
         * @since       1.0.0
         * @param       array $sections The sections
         * @return      array Sections with tiered commission rates added
         */
        public function register_settings_subsection( $sections ) {
        	$sections['mailchimp_discounts'] = __( 'MailChimp Discounts', 'edd-mailchimp-discounts' );
        	return $sections;
        }


        /**
         * Registers the MailChimp Discounts extension settings
         *
         * @access      public
         * @since       1.0.0
         * @param       $settings array the existing plugin settings
         * @return      array The new EDD settings array with MailChimp Discounts added
         */
        public function settings( $settings ) {

        	$type_options = apply_filters( 'edd_mailchimp_discounts_settings_type_options', array(
        		'percentage'  => __( 'Percentage', 'edd-commission-fees' ),
        		'flat'        => __( 'Flat amount', 'edd-commission-fees' ),
        	) );

            $mc4wp_lists = apply_filters( 'edd_mailchimp_discounts_settings_mc4wp_lists', array(
            'all'             => __( 'All Lists', 'edd-commission-fees' ),
            ) );

        	$mailchimp_discounts_settings = array(
        		array(
        			'id'      => 'mailchimp_discounts_mailchimp_settings_header',
        			'name'    => '<strong>' . __( 'MailChimp Settings', 'edd-mailchimp-discounts' ) . '</strong>',
        			'desc'    => '',
        			'type'    => 'header',
        			'size'    => 'regular',
        		),
        		array(
        			'id'      => 'mailchimp_discounts_enabled',
        			'name'    => __( 'Enable Discounts', 'edd-mailchimp-discounts' ),
        			'desc'    => __( 'Check this to enable discounts on sucessful MailChimp for WordPress submissions.', 'edd-mailchimp-discounts' ),
        			'type'    => 'checkbox',
        		),
                array(
                  'id'      => 'mailchimp_discounts_lists',
                  'name'    => __( 'MailChimp List', 'edd-mailchimp-discounts' ),
                  'desc'    => __( 'Which list must the user subscribe to for the discount to be issued? The list fields will be available as email tags.', 'edd-mailchimp-discounts' ),
                  'type'    => 'select',
                  'options' => $mc4wp_lists,
                ),
                array(
                  'id'      => 'mailchimp_discounts_discount_settings_header',
                  'name'    => '<strong>' . __( 'Discount Settings', 'edd-mailchimp-discounts' ) . '</strong>',
                  'desc'    => '',
                  'type'    => 'header',
                  'size'    => 'regular',
                ),
        		array(
        			'id'      => 'mailchimp_discounts_type',
        			'name'    => __( 'Type', 'edd-commission-fees' ),
        			'desc'    => __( 'The kind of discount to apply for this discount.', 'edd-mailchimp-discounts' ),
        			'type'    => 'select',
        			'options' => $type_options,
        		),
                array(
                  'id'      => 'mailchimp_discounts_amount',
                  'name'    => __( 'Amount', 'edd-mailchimp-discounts' ),
                  'desc'    => __( 'Enter the discount flat amount or percentage rate customers should receive. 10 = 10%', 'edd-mailchimp-discounts' ),
                  'type'    => 'text',
                  'size'    => 'small',
                ),
                array(
                  'id'      => 'mailchimp_discounts_expiration',
                  'name'    => __( 'Expiration', 'edd-mailchimp-discounts' ),
                  'desc'    => __( 'How long should the discount be valid for? 1 = 1 Day', 'edd-mailchimp-discounts' ),
                  'type'    => 'text',
                  'size'    => 'small',
                ),
                array(
                  'id'      => 'mailchimp_discounts_max_uses',
                  'name'    => __( 'Max Uses', 'edd-mailchimp-discounts' ),
                  'desc'    => __( 'The maximum number of times the discount can be used. Leave blank for unlimited.', 'edd-mailchimp-discounts' ),
                  'type'    => 'text',
                  'size'    => 'small',
                ),
                array(
                  'id'      => 'mailchimp_discounts_min_amount',
                  'name'    => __( 'Minimum Amount', 'edd-mailchimp-discounts' ),
                  'desc'    => __( 'The minimum amount that must be purchased before this discount can be used. Leave blank for no minimum.', 'edd-mailchimp-discounts' ),
                  'type'    => 'text',
                  'size'    => 'small',
                ),
        	);

        	$mailchimp_discounts_settings = apply_filters( 'edd_mailchimp_discounts_settings', $mailchimp_discounts_settings );

        	if ( version_compare( EDD_VERSION, 2.5, '>=' ) ) {
        		$mailchimp_discounts_settings = array( 'mailchimp_discounts' => $mailchimp_discounts_settings );
        	}

        	return array_merge( $settings, $mailchimp_discounts_settings );
        }


        /**
         * Add the MailChimp Discounts emails subsection to the email settings
         *
         * @access      public
         * @since       1.0.0
         * @param       array $sections Sections for the emails settings tab
         * @return      array
         */
        public function register_settings_subsection_emails( $sections ) {
        	$sections['mailchimp_discounts'] = __( 'MailChimp Discount Notifications', 'edd-mailchimp-discounts' );
        	return $sections;
        }


        /**
         * Registers the new MailChimp Discounts options in Emails
         *
         * @access      public
         * @since       1.0.0
         * @param       $settings array the existing plugin settings
         * @return      array
        */
        public function settings_emails( $settings ) {

        	$mailchimp_discount_settings = array(
        		array(
        			'id'    => 'mailchimp_discounts_email_subject',
        			'name'  => __( 'Email Subject', 'edd-mailchimp-discounts' ),
        			'desc'  => __( 'Enter the subject for the discount notification email.', 'edd-mailchimp-discounts' ),
        			'type'  => 'text',
        			'size'  => 'regular',
        			'std'   => __( 'New Sale!', 'edd-mailchimp-discounts' )
        		),
                array(
                  'id'    => 'mailchimp_discounts_email_heading',
                  'name'  => __( 'Email Heading', 'edd-mailchimp-discounts' ),
                  'desc'  => __( 'Enter the heading for the discount notification email.', 'edd-mailchimp-discounts' ),
                  'type'  => 'text',
                  'std'   => sprintf( __( 'Discount code for %s', 'edd-mailchimp-discounts' ), get_bloginfo( 'name' ) )
                ),
        		array(
        			'id'    => 'mailchimp_discounts_email_message',
        			'name'  => __( 'Email Body', 'edd-mailchimp-discounts' ),
        			'desc'  => __( 'Enter the content for the discount notification email. HTML is accepted. Available template tags:', 'edd-mailchimp-discounts' ) . '<br />' . $this->display_email_template_tags(),
        			'type'  => 'rich_editor',
        			'std'   => $this->get_email_default_body()
        		)
        	);

        	if ( version_compare( EDD_VERSION, 2.5, '>=' ) ) {
        		$mailchimp_discount_settings = array( 'mailchimp_discounts' => $mailchimp_discount_settings );
        	}

        	return array_merge( $settings, $mailchimp_discount_settings );

        }


        /**
         * Retrieve default email body
         *
         * @access      public
         * @since       1.0.0
         * @return      string $body The default email
         */
        public function get_email_default_body() {
        	$message   = __( 'Dear {fname},', 'edd-mailchimp-discounts' ) . "\n\n";
      		$message  .= sprintf( __( 'Thank you submitting your email address!' ) . "\n\n" . __( 'As a small thank you, here is a discount code for {discount_amount} off your purchase at %s', 'edd-mailchimp-discounts' ), home_url() ) . "\n\n";
      		$message  .= __( 'Discount code: {discount_code}', 'edd-mailchimp-discounts' );

        	return apply_filters( 'edd_mailchimp_discounts_email_default_body', $message );
        }


        /**
         * Parse template tags for display
         *
         * @access      public
         * @since       1.0.0
         * @return      string $tags The parsed template tags
         */
        public function display_email_template_tags() {
        	$template_tags = $this->get_email_template_tags();
        	$tags = '';

        	foreach ( $template_tags as $template_tag ) {
        		$tags .= '{' . $template_tag['tag'] . '} - ' . $template_tag['description'] . '<br />';
        	}

        	return $tags;
        }


        /**
         * Merge template tags
         *
         * @access      public
         * @since       1.0.0
         * @param       string $tags The original parsed template tags
         * @return      string $tags The merged parsed template tags
         */
        public function merge_email_template_tags( $tags ) {
          $field_tags = $this->get_mc4wp_list_fields( edd_get_option( 'mailchimp_discounts_lists', 'all' ), true );
          return array_merge( $tags, $field_tags );
        }


        /**
         * Retrieve email template tags
         *
         * @access      public
         * @since       1.0.0
         * @return      array $tags The email template tags
         */
        public function get_email_template_tags() {
        	$tags = array(
        		array(
        			'tag'         => 'discount_amount',
        			'description' => __( 'The formatted discount amount', 'edd-mailchimp-discounts' ),
        		),
        		array(
        			'tag'         => 'discount_code',
        			'description' => __( 'The discount code', 'edd-mailchimp-discounts' ),
        		),
                array(
                  'tag'         => 'discount_uses',
                  'description' => __( 'The maximum number of times this discount can be used', 'edd-mailchimp-discounts' ),
                ),
                array(
                  'tag'         => 'discount_type',
                  'description' => __( 'The discount type', 'edd-mailchimp-discounts' ),
                ),
                array(
                  'tag'         => 'discount_expiry',
                  'description' => __( 'When the discount expires in days', 'edd-mailchimp-discounts' ),
                ),
                array(
                  'tag'         => 'discount_min',
                  'description' => __( 'The minimum amount that must be purchased before this discount can be used', 'edd-mailchimp-discounts' ),
                ),
                array(
                  'tag'         => 'sitename',
                  'description' => __( 'Your site name', 'edd-mailchimp-discounts' ),
                ),
        	);

        	return apply_filters( 'edd_mailchimp_discounts_email_template_tags', $tags );
        }


        /**
         * Retrieve Mailchimp for WordPress lists
         *
         * @access      public
         * @since       1.0.0
         * @param       array $lists The lists array containg "All Lists" option
         * @return      array $lists The new merged (sorted) lists
         */
        public function get_mc4wp_lists( $lists ) {

          // gets an instance with some helper methods, uses API key from settings
          $mailchimp = new MC4WP_MailChimp();
          $mc4wp_lists = $mailchimp->get_lists();

          $list_data = array();

          if ( ! empty( $mc4wp_lists ) ) {
            foreach( $mc4wp_lists as $list ) {
              $list_data[ $list->id ] = $list->name;
            }

            // sort our array in ascending order, according to the value
            asort( $list_data );
          }

          // Merge with existing list
          return array_merge( $lists, $list_data );
        }


        /**
         * Retrieve Mailchimp for WordPress fields from a list
         * Used to display all possible email tags available
         *
         * @access      public
         * @since       1.0.0
         * @param       string $list_id The lists array containg "All Lists" option
         * @param       bool $lowercase Set the 'tags' to display in lowercase for available email tags
         * @return      array $lists The new merged (sorted) lists
         */
        public function get_mc4wp_list_fields( $list_id, $lowercase = false ) {

          if ( empty( $list_id ) ) {
            return false;
          }

          // Gets an instance with some helper methods, uses API key from settings
          $mailchimp = new MC4WP_MailChimp();
          $list = $mailchimp->get_list( $list_id );

          $list_data = array();

          $i = 0;

          if ( ! empty( $list ) ) {
           foreach( $list->merge_fields as $merge_field ) {
             $list_data[$i]['tag'] = strtolower( $merge_field->tag );
             $list_data[$i]['description'] = $merge_field->name;
             $i++;
           }
          }

          return $list_data;
        }


        /**
         * The plugin main hook triggered on successful MailChimp for WordPress subscription
         *
         * @access      public
         * @since       1.0.0
         * @param       MC4WP_Form $form Instance of the submitted form
         * @param       string $email_address The subscriber email address
         * @param       array $data The data
         * @param       array $map The map
         * @return      void
         */
        public function process_lead( $form, $email_address, $data, $map ) {

          if ( array_key_exists( edd_get_option( 'mailchimp_discounts_lists', 'all' ), $map ) || edd_get_option( 'mailchimp_discounts_lists', 'all' ) == 'all' ) {
            $code = $this->generate_discount( $email_address, $data, $map );
          }

          do_action( 'edd_mailchimp_discounts_process_lead', $form, $email_address, $data, $map );

        }


        /**
         * Generate the EDD Discount Code
         *
         * @access      public
         * @since       1.0.0
         * @param       string $email_address The subscriber email address
         * @param       array $data The data
         * @param       array $map The map
         * @return      string $code The generated discount code
         */
        public function generate_discount( $email_address = '', $data = '', $map = '' ) {

          // Generate a 15 character code
          $code = substr( md5( $email_address ), 0, 15 );

          if( edd_get_discount_by_code( $code ) ) {
            return; // Discount already created
          }

          // Create query date string
          $days = absint ( edd_get_option( 'mailchimp_discounts_expiration', 3 ) );
          $expiration = '+' . $days . 'days';

          // Create our discount query args
          $details = apply_filters( 'edd_mailchimp_discounts_generate_discount_args', array(
            'name'       => $email_address,
            'code'       => $code,
            'min_price'  => (float) edd_get_option( 'mailchimp_discounts_min_amount', 0 ),
            'max'        => absint ( edd_get_option( 'mailchimp_discounts_max_uses', 1 ) ),
            'amount'     => (float) edd_get_option( 'mailchimp_discounts_amount', 10 ),
            'start'      => date( 'm/d/Y H:i:s', time() ),
            'expiration' => $expiration,
            'type'       => edd_get_option( 'mailchimp_discounts_type', 'percentage' ),
            'use_once'   => true
          ) );

          // Create the actual discount
          $discount_id = edd_store_discount( $details );

          do_action( 'edd_mailchimp_discounts_generate_discount', $email_address, $details, $data, $map, $code );

          // Send the email
          $this->send_discount( $email_address, $details, $data, $map, $code );

          return $code;
        }


        /**
         * This will take a discount amount and  type and return a formatted version for output.
         *
         * @access      public
         * @since       1.0.0
         * @param       float $unformatted_amount This is the number representing the amount.
         * @param       string $discount_type This is the type of the discount.
         * @return      string $formatted_amount This is the rate formatted for output.
         */
        public function get_formatted_discount_type( $unformatted_amount, $discount_type ){

          // If the discount type is "percentage"
          if ( 'percentage' == $discount_type ) {
            $formatted_amount = $unformatted_amount . '%';
          } else {
            $formatted_amount = edd_currency_filter( edd_sanitize_amount( $unformatted_amount ) );
          }

          // Filter the formatted amount so it can be modified if needed
          return apply_filters( 'edd_mailchimp_discounts_get_formatted_discount_type', $formatted_amount, $unformatted_amount, $discount_type );
        }


        /**
         * Parse email template tags
         *
         * @since       1.0.0
         * @param       string $email_address The subscriber email address
         * @param       string $message The email body
         * @param       string $code The generated discount code
         * @param       array $details The discount code args
         * @param       array $data The data
         * @param       array $map The map
         * @return      string $message The email body
         */
        public function parse_template_tags( $email_address = '', $message = '', $code = '', $details = '', $data = '', $map = '' ) {

          $discount_type = $this->get_formatted_discount_type( $details['amount'], $details['type'] );

          $expiration = absint ( edd_get_option( 'mailchimp_discounts_expiration', 3 ) );

          $message = str_replace( '{discount_code}', $code, $message );
          $message = str_replace( '{discount_amount}', $discount_type, $message );
          $message = str_replace( '{discount_type}', $details['type'], $message );
          $message = str_replace( '{discount_uses}', $details['max'], $message );
          $message = str_replace( '{discount_min}', edd_currency_filter( edd_sanitize_amount( $details['min_price'] ) ), $message );
          $message = str_replace( '{discount_expiry}', $expiration, $message );
          $message = str_replace( '{sitename}', get_bloginfo( 'name' ), $message );

          // Loop through the MailChimp field data and replace email tags with values
          if ( ! empty( $data ) ) {
            foreach( $data as $key => $value ) {
              $message = str_ireplace( '{' . $key . '}', $value, $message );
            }
          }

        	return $message;
        }


        /**
         * Send the discount email
         *
         * @access      public
         * @since       1.0.0
         * @param       string $email_address The subscriber email address
         * @param       array $details The discount code arguments
         * @param       array $data The data
         * @param       array $map The map
         * @param       string $code The generated discount code
         * @return      void $message The email body
         */
        public function send_discount( $email_address = '', $details = '', $data = '', $map = '', $code = '' ) {
          if( empty( $code ) ) {
            return;
          }

          $subject = edd_get_option( 'mailchimp_discounts_email_subject', sprintf( __( 'Discount code for %s', 'edd-mailchimp-discounts' ), get_bloginfo( 'name' ) ) );
          $heading = edd_get_option( 'mailchimp_discounts_email_heading', $subject );
          $message = edd_get_option( 'mailchimp_discounts_email_message', $this->get_email_default_body() );

          // Parse template tags
          $message = $this->parse_template_tags( $email_address, $message, $code, $details, $data, $map );
          $message = apply_filters( 'edd_mailchimp_discounts_email', $message, $email_address, $subject, $heading );

          if ( class_exists( 'EDD_Emails' ) ) {
        		EDD()->emails->__set( 'heading', $heading );
        		EDD()->emails->send( $email_address, $subject, $message );
        	} else {
        		$from_name = apply_filters( 'edd_mailchimp_discounts_email_from_name', $from_name, $email_address, $subject, $heading, $message, $details, $data, $map, $code );

        		$from_email = edd_get_option( 'from_email', get_option( 'admin_email' ) );
        		$from_email = apply_filters( 'edd_mailchimp_discounts_email_from_email', $from_email, $email_address, $subject, $heading, $message, $details, $data, $map, $code );

        		$headers = "From: " . stripslashes_deep( html_entity_decode( $from_name, ENT_COMPAT, 'UTF-8' ) ) . " <$from_email>\r\n";

        		wp_mail( $email_address, $subject, $message, $headers );
        	}
        }

    }
} // End if class_exists check


/**
 * The main function responsible for returning the one true EDD_MailChimp_Discounts
 * instance to functions everywhere
 *
 * @since       1.0.0
 * @return      \EDD_MailChimp_Discounts The one true EDD_MailChimp_Discounts
 */
function EDD_MailChimp_Discounts_load() {
    if( ! class_exists( 'Easy_Digital_Downloads' ) ) {
        if( ! class_exists( 'EDD_Extension_Activation' ) ) {
            require_once 'includes/class-activation.php';
        }

        $activation = new EDD_Extension_Activation( plugin_dir_path( __FILE__ ), basename( __FILE__ ) );
        $activation = $activation->run();
    } else {
        return EDD_MailChimp_Discounts::instance();
    }
}
add_action( 'plugins_loaded', 'EDD_MailChimp_Discounts_load' );
