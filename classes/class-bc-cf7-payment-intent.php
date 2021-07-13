<?php

if(!class_exists('BC_CF7_Payment_Intent')){
    final class BC_CF7_Payment_Intent {

    	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    	//
    	// private static
    	//
    	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

        private static $instance = null;

    	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    	//
    	// public static
    	//
    	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

        public static function get_instance($file = ''){
            if(null !== self::$instance){
                return self::$instance;
            }
            if('' === $file){
                wp_die(__('File doesn&#8217;t exist?'));
            }
            if(!is_file($file)){
                wp_die(sprintf(__('File &#8220;%s&#8221; doesn&#8217;t exist?'), $file));
            }
            self::$instance = new self($file);
            return self::$instance;
    	}

    	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    	//
    	// private
    	//
    	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

        private $fields = [], $file = '', $meta_data = [], $post_id = 0, $posted_data = [];

    	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    	private function __clone(){}

    	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    	private function __construct($file = ''){
            $this->file = $file;
            add_action('plugins_loaded', [$this, 'plugins_loaded']);
        }

        // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

		private function get_type($contact_form = null){
            if(null === $contact_form){
                $contact_form = wpcf7_get_current_contact_form();
            }
            if(null === $contact_form){
                return '';
            }
            $type = $contact_form->pref('bc_type');
            if(null === $type){
                return '';
            }
            return $type;
        }

        // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

        private function sanitize_posted_data($value){
            if(is_array($value)){
    			$value = array_map([$this, 'sanitize_posted_data'], $value);
    		} elseif(is_string($value)){
    			$value = wp_check_invalid_utf8($value);
    			$value = wp_kses_no_null($value);
    		}
    		return $value;
        }

        // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

        /*private function setup_meta_data($contact_form, $submission){
            $meta_data = [
                'bc_container_post_id' => $submission->get_meta('container_post_id'),
                'bc_current_user_id' => $submission->get_meta('current_user_id'),
                'bc_id' => $contact_form->id(),
                'bc_locale' => $contact_form->locale(),
                'bc_name' => $contact_form->name(),
                'bc_remote_ip' => $submission->get_meta('remote_ip'),
                'bc_remote_port' => $submission->get_meta('remote_port'),
                'bc_response' => $submission->get_response(),
                'bc_status' => $submission->get_status(),
                'bc_timestamp' => $submission->get_meta('timestamp'),
                'bc_title' => $contact_form->title(),
                'bc_unit_tag' => $submission->get_meta('unit_tag'),
                'bc_url' => $submission->get_meta('url'),
                'bc_user_agent' => $submission->get_meta('user_agent'),
            ];
            $this->meta_data = $meta_data;
        }*/

        // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

        private function setup_posted_data(){
            $posted_data = array_filter((array) $_POST, function($key){
    			return in_array($key, $this->fields);
    		}, ARRAY_FILTER_USE_KEY);
    		$posted_data = wp_unslash($posted_data);
    		$posted_data = $this->sanitize_posted_data($posted_data);
            $this->posted_data = $posted_data;
        }

    	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    	//
    	// public
    	//
    	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

        public function init(){
            register_post_type('bc_payment_intent', [
                'labels' => bc_post_type_labels('Payment intent', 'Payment intents', false),
                'menu_icon' => 'dashicons-money-alt',
                'show_in_admin_bar' => false,
                'show_ui' => true,
                'supports' => ['custom-fields', 'title'],
            ]);
        }

        // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

        public function do_shortcode_tag($output, $tag, $attr, $m){
            if('contact-form-7' !== $tag){
                return $output;
            }
            $contact_form = wpcf7_get_current_contact_form();
			if('payment-intent' !== $this->get_type($contact_form)){
                return $output;
            }
            $tags = wp_list_pluck($contact_form->scan_form_tags(), 'type', 'name');
            $missing = [];
            foreach($this->fields as $field){
                if(!isset($tags[$field])){
                    $missing[] = $field;
                }
            }
            if($missing){
                $error = current_user_can('manage_options') ? sprintf(__('Missing parameter(s): %s'), implode(', ', $missing)) . '.' : __('Something went wrong.');
                return '<div class="alert alert-danger" role="alert">' . $error . '</div>';
            }
            $invalid = [];
            if($tags['cc-csc'] !== 'number*'){
                $invalid[] = 'cc-csc';
            }
            if($tags['cc-exp-mm'] !== 'select*'){
                $invalid[] = 'cc-exp-mm';
            }
            if($tags['cc-exp-yy'] !== 'select*'){
                $invalid[] = 'cc-exp-yy';
            }
            if($tags['cc-name'] !== 'text*'){
                $invalid[] = 'cc-name';
            }
            if($tags['cc-number'] !== 'number*'){
                $invalid[] = 'cc-number';
            }
            if($invalid){
                $error = current_user_can('manage_options') ? sprintf(__('Invalid parameter(s): %s'), implode(', ', $invalid)) . '.' : __('Something went wrong.');
                return '<div class="alert alert-danger" role="alert">' . $error . '</div>';
            }
            return $output;
        }

    	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

        public function plugins_loaded(){
        	if(!defined('BC_FUNCTIONS')){
        		return;
        	}
            if(!defined('WPCF7_VERSION')){
        		return;
        	}
            add_action('init', [$this, 'init']);
            add_action('wpcf7_before_send_mail', [$this, 'wpcf7_before_send_mail'], 10, 3);
			add_action('wpcf7_enqueue_scripts', [$this, 'wpcf7_enqueue_scripts']);
            add_filter('do_shortcode_tag', [$this, 'do_shortcode_tag'], 10, 4);
            add_filter('wpcf7_posted_data', [$this, 'wpcf7_posted_data']);
			if(!has_filter('wpcf7_verify_nonce', 'is_user_logged_in')){
                add_filter('wpcf7_verify_nonce', 'is_user_logged_in');
            }
            $this->fields = ['cc-csc', 'cc-exp-mm', 'cc-exp-yy', 'cc-name', 'cc-number'];
        }

        // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

        public function wpcf7_before_send_mail($contact_form, &$abort, $submission){
        	if('payment-intent' !== $this->get_type($contact_form)){
                return;
            }
			if(!$submission->is('init')){
                return; // prevent conflicts with other plugins
            }
            $abort = true; // prevent mail_sent and mail_failed actions
            $post_id = wp_insert_post([
				'post_status' => 'private',
				'post_title' => sprintf('[bc-payment-intent]'),
				'post_type' => 'bc_payment_intent',
			], true);
            if(is_wp_error($post_id)){
                $submission->set_response($post_id->get_error_message());
                $submission->set_status('aborted'); // try to prevent conflicts with other plugins
                return;
            }
			$this->post_id = $post_id;
            $posted_data = $submission->get_posted_data();
            if($posted_data){
            	foreach($posted_data as $key => $value){
	                if(is_array($value)){
						delete_post_meta($post_id, $key);
						foreach($value as $single){
							add_post_meta($post_id, $key, $single);
						}
					} else {
	                    update_post_meta($post_id, $key, $value);
					}
				}
			}
            $payment_intent = BC_Payment_Intent::get_instance($post_id);
            $this->setup_posted_data();
            $payment_intent = apply_filters('bc_payment_intent_object', $payment_intent, $this->posted_data, $contact_form, $submission);
            if($payment_intent instanceof BC_Payment_Intent){
                $data = $payment_intent->get_data();
                $message = $payment_intent->get_message();
                $status = $payment_intent->get_status();
            } else {
                $data = $payment_intent;
                $message = __('Invalid object type.');
                $status = false;
                update_post_meta($post_id, 'bc_payment_intent_data', $data);
                update_post_meta($post_id, 'bc_payment_intent_message', $message);
                update_post_meta($post_id, 'bc_payment_intent_status', $status);
            }
            if(false === $status){
            	$submission->set_response($message);
                $submission->set_status('aborted'); // try to prevent conflicts with other plugins
            } else {
                $response = $message;
                if($submission->mail()){
                    $submission->set_response($response . ' ' . $contact_form->message('mail_sent_ok'));
                    $submission->set_status('mail_sent');
    			} else {
                    $submission->set_response($response . ' ' . $contact_form->message('mail_sent_ng'));
    				$submission->set_status('mail_failed');
    			}
            }
            //$this->setup_meta_data($contact_form, $submission);
            // maybe update metadata
            do_action('bc_payment_intent', $post_id, $contact_form, $submission, $payment_intent);
        }

        // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

        public function wpcf7_enqueue_scripts(){
        	if(isset($_SERVER['HTTP_USER_AGENT']) and false !== strpos($_SERVER['HTTP_USER_AGENT'], 'Mobile')){
        		$src = plugin_dir_url($this->file) . 'assets/bc-cf7-payment-intent.js';
	            $ver = filemtime(plugin_dir_path($this->file) . 'assets/bc-cf7-payment-intent.js');
	            wp_enqueue_script('bc-cf7-payment-intent', $src, ['contact-form-7'], $ver, true);
        	}
        }

        // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

        public function wpcf7_posted_data($posted_data){
            $contact_form = wpcf7_get_current_contact_form();
            if(null === $contact_form){
                return $posted_data;
            }
            if('payment-intent' !== $this->get_type($contact_form)){
                return $posted_data;
            }
            foreach($this->fields as $field){
        		if(isset($posted_data[$field])){
        			unset($posted_data[$field]);
        		}
        	}
        	return $posted_data;
        }

        // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    }
}
