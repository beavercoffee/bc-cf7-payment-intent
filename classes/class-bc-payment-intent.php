<?php

if(!class_exists('BC_Payment_Intent')){
    final class BC_Payment_Intent {

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

        public static function get_instance($post_id = 0){
            if(null !== self::$instance){
                return self::$instance;
            }
            $post = get_post($post_id);
            if(null === $post){
                wp_die(__('Invalid post ID.'));
            }
            if('bc_payment_intent' !== get_post_type($post)){
                wp_die(__('Invalid post type.'));
            }
            if('trash' === get_post_status($post)){
                wp_die(__('Invalid post ID.'));
            }
            self::$instance = new self($post_id);
            return self::$instance;
    	}

    	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    	//
    	// private
    	//
    	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

        private $data = null, $message = '', $post_id = 0, $status = false;

    	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    	private function __clone(){}

    	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    	private function __construct($post_id = 0){
            if(metadata_exists('post', $post_id, 'bc_payment_intent_status')){
                $status = (bool) get_post_meta($post_id, 'bc_payment_intent_status', true);
                $this->status = $status;
            } else {
                $status = false;
                $this->set_status($status);
            }
            if(metadata_exists('post', $post_id, 'bc_payment_intent_message')){
                $message = (string) get_post_meta($post_id, 'bc_payment_intent_message', true);
                $this->message = $message;
            } else {
                $message = sprintf(__("Method '%s' not implemented. Must be overridden in subclass."), 'bc_payment_intent');
                $this->set_message($message);
            }
            if(metadata_exists('post', $post_id, 'bc_payment_intent_data')){
                $status = get_post_meta($post_id, 'bc_payment_intent_data', true);
                $this->data = $data;
            } else {
                $data = '';
                $this->set_data($data);
            }
            $this->post_id = $post_id;
        }

        // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    	//
    	// public
    	//
    	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

        public function get_data(){
            return $this->data;
        }

    	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

        public function get_message(){
            return $this->message;
        }

    	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

        public function get_post_id(){
            return $this->post_id;
        }

    	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

        public function get_status(){
            return $this->status;
        }

    	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

        public function set_data($data = null){
            if(null === $data){
                delete_post_meta($this->post_id, 'bc_payment_intent_data');
            } else {
                update_post_meta($this->post_id, 'bc_payment_intent_data', $data);
            }
            $this->data = $data;
        }

    	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

        public function set_message($message = ''){
            $message = (string) $message;
            update_post_meta($this->post_id, 'bc_payment_intent_message', $message);
            $this->message = $message;
        }

    	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

        public function set_status($status = false){
            $status = (bool) $status;
            update_post_meta($this->post_id, 'bc_payment_intent_status', $status);
            $this->status = $status;
        }

    	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    }
}
