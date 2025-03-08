<?php

/**
 * The class responsible for handling license REST API endpoints.
 */
class Angry_Bunny_License_API {

    /**
     * Register the REST API routes
     */
    public function register_routes() {
        register_rest_route('angry-bunny/v1', '/license/validate', array(
            'methods' => 'POST',
            'callback' => array($this, 'validate_license'),
            'permission_callback' => array($this, 'check_permission'),
            'args' => array(
                'license_key' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'site_url' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'esc_url_raw'
                )
            )
        ));

        register_rest_route('angry-bunny/v1', '/license/activate', array(
            'methods' => 'POST',
            'callback' => array($this, 'activate_license'),
            'permission_callback' => array($this, 'check_permission'),
            'args' => array(
                'license_key' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'site_url' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'esc_url_raw'
                )
            )
        ));

        register_rest_route('angry-bunny/v1', '/license/deactivate', array(
            'methods' => 'POST',
            'callback' => array($this, 'deactivate_license'),
            'permission_callback' => array($this, 'check_permission'),
            'args' => array(
                'license_key' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'site_url' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'esc_url_raw'
                )
            )
        ));
    }

    /**
     * Check if the request has permission to access the endpoints
     *
     * @param WP_REST_Request $request The request object
     * @return bool Whether the request has permission
     */
    public function check_permission($request) {
        // Add your own authentication logic here
        // For example, check for a specific API key in the headers
        $api_key = $request->get_header('X-Angry-Bunny-API-Key');
        return $api_key === get_option('angry_bunny_api_key');
    }

    /**
     * Handle license validation endpoint
     *
     * @param WP_REST_Request $request The request object
     * @return WP_REST_Response The response object
     */
    public function validate_license($request) {
        $license_key = $request->get_param('license_key');
        $site_url = $request->get_param('site_url');

        $is_valid = Angry_Bunny_License_Manager::is_license_valid($license_key, $site_url);
        
        if ($is_valid) {
            $license_data = Angry_Bunny_License_Manager::get_license_data($license_key);
            return new WP_REST_Response(array(
                'success' => true,
                'message' => 'License is valid',
                'data' => array(
                    'expires' => $license_data['expires'],
                    'site_limit' => $license_data['site_limit'],
                    'sites_active' => count($license_data['sites'])
                )
            ), 200);
        }

        return new WP_REST_Response(array(
            'success' => false,
            'message' => 'License is not valid'
        ), 403);
    }

    /**
     * Handle license activation endpoint
     *
     * @param WP_REST_Request $request The request object
     * @return WP_REST_Response The response object
     */
    public function activate_license($request) {
        $license_key = $request->get_param('license_key');
        $site_url = $request->get_param('site_url');

        $result = Angry_Bunny_License_Manager::activate_license($license_key, $site_url);
        
        return new WP_REST_Response($result, $result['success'] ? 200 : 400);
    }

    /**
     * Handle license deactivation endpoint
     *
     * @param WP_REST_Request $request The request object
     * @return WP_REST_Response The response object
     */
    public function deactivate_license($request) {
        $license_key = $request->get_param('license_key');
        $site_url = $request->get_param('site_url');

        $result = Angry_Bunny_License_Manager::deactivate_license($license_key, $site_url);
        
        return new WP_REST_Response($result, $result['success'] ? 200 : 400);
    }
} 