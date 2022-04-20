<?php
/**
 * Plugin Name: My Web Audit
 * Description: My Web Audit - Supporting plugin.
 * Version: 1.1.9
 * Author: My Web Audit
 * Author URI: https://www.mywebaudit.com
 * License: GPL3
 * License URI:  https://www.gnu.org/licenses/gpl-3.0.html
 */

# domain/mwa-json (With Permalink Enabled)
# domain/index.php/mwa-json (With Permalink Disabled)
# domain/?feed=mwa-json (With Permalink Disabled)

define('MWA_CURRENT_VERSION', '1.1.9');

/**
 * Include the library to establish secure connect and encrypt the data
 */
require_once(dirname(__FILE__).'/src/PHPSecLib/Crypt/RSA.php');

/** 
 * Check if get_plugins() function exists.
 */
if ( ! function_exists( 'get_plugins' ) ) 
{
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

/** 
 * Check if get_site_transient() function exists.
 */
if ( ! function_exists( 'get_site_transient' ) ) 
{
    require_once( ABSPATH . 'wp-includes/update.php' );
}

/**
 * Include the plugin settings functions
 */
require_once(dirname(__FILE__).'/settings.php');
           
/**
 * Define class MyWebAudit 
 */
class MyWebAudit 
{
    /**
     * The plugin current version
     * @var string
     */
    public $current_version = MWA_CURRENT_VERSION;
    
    /**
     * The plugin remote update path
     * @var string
     */
    public $update_path = 'https://lp.mywebaudit.com/mwapluginupdate';

    /**
     * The plugin remote zip path
     * @var string
     */
    public $package_path = 'https://lp.mywebaudit.com/wp-content/uploads/my-web-audit.zip';

    /**
     * Plugin Slug (my-web-audit/my-web-audit.php)
     * @var string
     */
    public $plugin_slug = 'my-web-audit/my-web-audit.php';

    /**
     * Plugin name (plugin_file)
     * @var string
     */
    public $slug = 'my-web-audit';
    
    /**
     * API URL
     * @var string
     */
    public $api_url = 'https://www.mywebaudit.com/api/get-wp-audit-data';

    /**
     * Project API URL
     * @var string
     */
    public $project_api_url = 'https://campaigns.mywebaudit.com/post-run-wp-assessment';

    /**
     * Constructor function
     * @private
     */
    function __construct()
    {
        /**
         * Add MWA JSON routes 
         * Flush rewrite rules if needed
         */ 
        add_action('init', array($this, 'mwa_register_routes'));
        add_action('init', array($this, 'mwa_api_maybe_flush_rewrites'), 999 );

        // Register the API for automatic plugin updates
        add_filter('pre_set_site_transient_update_plugins', array(&$this, 'check_update'));
        add_filter('plugins_api', array(&$this, 'check_info'), 10, 3);
        
        // Add settings page link on the plugin page 
        add_filter('plugin_action_links', array($this, 'mwa_add_action_plugin'), 10, 5 );

        // Export encrypted MWA API response  
        add_action('init', array($this,'mwa_export_api_response'));
    }
    
    /** 
     * Function to add settings page link on the plugin page
     * 
     * @param array $actions
     * @param string $plugin_file
     * @return array                 
     */
    public function mwa_add_action_plugin( $actions, $plugin_file ) 
    {
        static $plugin;

        if (!isset($plugin))
        {
            $plugin     = plugin_basename(__FILE__);
        }
        
        if ($plugin == $plugin_file) 
        {   
            $settings   = array(
                'settings'  => '<a href="options-general.php?page=mwa_options">' . __('Settings', 'General') . '</a>'
            );
            
            $actions    = array_merge($settings, $actions);         
        }       
        
        return $actions;
    }
    
    /**
     * Function to register the rewrite rule
     * @return void
     */
    public function mwa_register_routes() 
    {
        add_feed( 'mwa-json', array($this, 'mwa_api_responce'));
    }
    
    /**
     * Determine if the rewrite rules should be flushed.
     */
    public function mwa_api_maybe_flush_rewrites() 
    {
        $version = get_option( 'mwa_plugin_version', null );

        if ( empty( $version ) ||  $version !== MWA_CURRENT_VERSION ) 
        {
            //Ensure the $wp_rewrite global is loaded
            global $wp_rewrite;
            
            //Call flush_rules() as a method of the $wp_rewrite object
            $wp_rewrite->flush_rules();

            update_option( 'mwa_plugin_version', MWA_CURRENT_VERSION );
        }        
    }
    
    /** 
     * Function to return the details of the 
     * active theme in array format (WP > 3.4.0)
     * 
     * @param object $theme 
     * @return array 
     */
    private function get_theme_array($theme)
    {
        $return = array(
            'Name'          => $theme->get('Name'),
            'Description'   => $theme->get('Description'),
            'Author'        => $theme->get('Author'),
            'AuthorURI'     => $theme->get('AuthorURI'),
            'Version'       => $theme->get('Version'),
            'Template'      => $theme->get('Template'),
            'Status'        => $theme->get('Status'),
            'Tags'          => $theme->get('Tags'),
            'TextDomain'    => $theme->get('TextDomain'),
            'DomainPath'    => $theme->get('DomainPath'),   
        ); 
        return $return;
    }

    /** 
     * Function to get the list of the active
     * and inactive themes for WP version > 3.4.0
     * 
     * @return array 
     */
    private function list_the_themes()
    {   
        // If WP version is less than 3.4.0 then use different function
        if(version_compare( get_bloginfo('version'), '3.4.0', '<' ))
        {
           return $this->list_the_themes_less_3_4();
        }     
        
        $upgradeThemes  = get_site_transient( 'update_themes' ); 
        $themes         = wp_get_themes();
        $current_theme  = get_option('stylesheet');     
        $all_themes     = array();
        
        foreach ($themes as $theme => $theme_data) 
        {           
            // Active/inactive theme
            $theme_status   = ($theme == $current_theme) ? 'active' : 'inactive';

            // Collect theme details
            $all_themes[$theme_status][$theme]  = $this->get_theme_array($theme_data);
            
            // If the active theme is a child theme, then collect parent theme details
            if (is_object($theme_data->parent())) 
            {
                $all_themes[$theme_status][$theme]['Parent'] = $this->get_theme_array($theme_data->parent());     
            }      

            // Collect theme's latest version
            $versionLatest = $all_themes[$theme_status][$theme]['Version'];
            if ( isset($upgradeThemes->response[$theme]['new_version']) ) 
            {
                $versionLatest = $upgradeThemes->response[$theme]['new_version'];
            }
            
            $all_themes[$theme_status][$theme]['VersionLatest'] = $versionLatest;            
        }

        return $all_themes;
    }

    /** 
     * Function to return the details of the 
     * active theme in array format (WP < 3.4.0)
     * 
     * @param object $theme 
     * @return array 
     */
    private function get_theme_array_less_3_4($theme)
    {        
        $return = array(
            'Name'          => $theme['Name'],
            'Description'   => $theme['Description'],
            'Author'        => $theme['Author'],
            'AuthorURI'     => $theme['Author URI'],
            'Version'       => $theme['Version'],
            'Template'      => $theme['Template'],
            'Status'        => $theme['Status'],
            'Tags'          => $theme['Tags'],
            'TextDomain'    => '',
            'DomainPath'    => '',   
        ); 
        return $return;
    }

    /** 
     * Function to get the list of the active
     * and inactive themes for WP version > 3.4.0
     * 
     * @return array 
     */
    public function list_the_themes_less_3_4()
    {    
        $upgradeThemes  = get_site_transient( 'update_themes' );          
        $themes         = get_themes();
        $current_theme  = get_option('stylesheet');     
        $all_themes     = array();
        
        foreach ($themes as $theme => $theme_data) 
        {           
            // Active/inactive theme
            $theme_status   = ($theme_data["Stylesheet"] == $current_theme) ? 'active' : 'inactive';

            // Collect theme details
            $all_themes[$theme_status][$theme] = $this->get_theme_array_less_3_4($theme_data);
            
            // If the active theme is a child theme, then collect parent theme details
            if(isset($theme_data['Parent Theme']) && (!empty($theme_data['Parent Theme'])))
            {
                $all_themes[$theme_status][$theme]['Parent'] = $this->get_theme_array_less_3_4($themes[$theme_data['Parent Theme']]); 
            }
             
            // Collect theme's latest version
            $versionLatest = $all_themes[$theme_status][$theme]['Version'];
            if ( isset($upgradeThemes->response[$theme_data['Template']]['new_version']) ) 
            {
                $versionLatest = $upgradeThemes->response[$theme_data['Template']]['new_version'];
            }
            
            $all_themes[$theme_status][$theme]['VersionLatest'] = $versionLatest;            
        }

        return $all_themes;
    }

    /** 
     * Function to get the list of the active
     * and inactive plugins 
     * 
     * @return array 
     */
    private function list_the_plugins() 
    {  
        $plugins        = get_plugins();
        $upgradePlugins = get_site_transient( 'update_plugins' );  
        $all_plugins    = array();

        foreach ( $plugins as $plugin => $plugin_data ) 
        {
            // Active/inactive plugin
            $plugin_status = is_plugin_active( $plugin ) ? 'active' : 'inactive';             
            
            // Collect plugin's latest version
            $versionLatest = $plugin_data['Version'];
            if ( isset($upgradePlugins->response[$plugin]->new_version) ) 
            {
                $versionLatest = $upgradePlugins->response[$plugin]->new_version;
            }

            // explode $plugin to "/" for get plugin dir name 
            $pluginSlug                             = explode('/',$plugin);
            $plugin_data['VersionLatest']           = $versionLatest;
            $plugin_data['PluginSlug']              = $pluginSlug[0];
            $all_plugins[$plugin_status][$plugin]   = $plugin_data;        
        }

        return $all_plugins;
    }

    /** 
     * Function to check for the availability 
     * of the sample content
     * 
     * @return array 
     */
    private function get_wp_default_contents() 
    {       
        // Set default value of sample contents
        $sample_page = $sample_post = $sample_comment = false;

        // Find the WP default 'Sample Page'
        $defaultPage   = get_page_by_title( 'Sample Page' );    
        if ($defaultPage) 
        {
            $sample_page  = true;    
        } 
        
        // Find the WP default 'Hello world!' post
        $defaultPost   = get_posts( array( 'title' => 'Hello world!' ) );        
        if ($defaultPost) 
        {
            $sample_post    = true;
            
            // Find the WP default comments
            if ( get_comments( array( 'post_id' => $defaultPost[0]->ID, 'count' => 1 ) ) ) 
            {
                $sample_comment = true;    
            }   
        }

        return array(
            'samplePage'       => $sample_page,
            'samplePost'       => $sample_post,
            'sampleComment'    => $sample_comment
        );
    }

    /**
     * Function to identify the availability of the username 'admin'
     * 
     * @return array
     */
    function getUsersAdmin() 
    {
        global $wpdb;
        
        $sql = 'SELECT ID, display_name FROM '
            .$wpdb->users.' WHERE ' 
            .$wpdb->users.'.user_login = "admin"';
        
        $userIDs = $wpdb->get_col( $sql );
        return $userIDs;
    }
    
    /**
     * Function to prepare collect, encrypt and 
     * send the data to the request made by 
     * My Web Audit worker application
     * 
     * @return array
     */
    protected function mwa_responce_json()
    {
        if ( ! function_exists( 'get_core_updates' ) ) 
        {
            require_once( ABSPATH . 'wp-admin/includes/update.php' );
        }  

        $update_wordpress   = get_core_updates( array('dismissed' => false) );
        
        $adminCounts        = count($this->getUsersAdmin());

        $blogPublic = get_option('blog_public');
        $timezone   = get_option('timezone_string');
        $offset     = get_option( 'gmt_offset' );
        
        if($timezone == '' && $offset!= 0)
        {
            $timezone = $offset;
        }
        
        // Check the availability of the theme/plugin files editing from the admin editor
        $themePluginEditor      = (defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT) ? true : false;

        // Check the status of the debug mode 
        $debugMode              = (defined('WP_DEBUG') && WP_DEBUG) ? false : true;

        // Check the status of WordPress automatic updates 
        $automaticUpdate        = (defined('AUTOMATIC_UPDATER_DISABLED') && AUTOMATIC_UPDATER_DISABLED) ? true : false;

        // Check the status of post revisions 
        $postRevisionControl    = defined('WP_POST_REVISIONS') ? WP_POST_REVISIONS : '';                 
        
        // Check the status of the permalink structure 
        $checkPermalink         = get_option( 'permalink_structure' ) ? true : false;

        $wcArray = array();
        if ( class_exists( 'WooCommerce' ) ) 
        {
            $system_status      = new WC_REST_System_Status_Controller();
            
            $database           = method_exists( $system_status, 'get_database_info' ) ? $system_status->get_database_info() : '';
            $environment        = method_exists( $system_status, 'get_environment_info' ) ? $system_status->get_environment_info() : '';
            $post_type_counts   = method_exists( $system_status, 'get_post_type_counts' ) ? $system_status->get_post_type_counts() : '';  
            $security           = method_exists( $system_status, 'get_security_info' ) ? $system_status->get_security_info() : '';
            $active_plugins     = method_exists( $system_status, 'get_active_plugins' ) ? $system_status->get_active_plugins() : '';
            $inactive_plugins   = method_exists( $system_status, 'get_inactive_plugins' ) ? $system_status->get_inactive_plugins() : '';
            $settings           = method_exists( $system_status, 'get_settings' ) ? $system_status->get_settings() : '';
            $wp_pages           = method_exists( $system_status, 'get_pages' ) ? $system_status->get_pages() : '';
            $theme              = method_exists( $system_status, 'get_theme_info' ) ? $system_status->get_theme_info() : '';
            
            $environment['wp_latest_version']   = get_transient( 'woocommerce_system_status_wp_version_check' );            
            
            // Add WC Pages slug
            $wc_pages = array();
            if ( is_array( $wp_pages ) ) 
            {
                foreach ( $wp_pages as $wp_page ) 
                {
                    $wp_page['slug'] = ( isset($wp_page['page_id']) && !empty($wp_page['page_id']) ) ? str_replace( home_url(), '', get_permalink( $wp_page['page_id'] ) ) : '' ; 
                    
                    $wc_pages[]  = $wp_page; 
                }
            }

            $wcArray = array( 
                'wordPress_environment' => $environment,
                'database'              => $database,
                'post_type_counts'      => $post_type_counts,
                'security'              => $security,
                'active_plugins'        => $active_plugins,
                'inactive_plugins'      => $inactive_plugins,
                'settings'              => $settings,
                'wooCommerce_pages'     => $wc_pages,
                'theme'                 => $theme
            );
        }

        // Check URLs Accessibility
        $readmeStatus       = $this->check_url_accessible(site_url('readme.html'));
        $installStatus      = $this->check_url_accessible(site_url('wp-admin/install.php'));
        $wpConfigStatus     = $this->check_url_accessible(site_url('wp-config.php'));
        $upgradeStatus      = $this->check_url_accessible(site_url('wp-admin/upgrade.php')); 

        return  array(
            'wordpress'                 => get_bloginfo('version'),
            'VersionLatestWordPress'    => (!empty($update_wordpress) ? $update_wordpress[0]->current : ""),
            'timezone'                  => $timezone,
            'themes'                    => $this->list_the_themes(),
            'plugins'                   => $this->list_the_plugins(),
            'users'                     => count_users(),
            'admin'                     => $adminCounts,
            'searchEngineVisibility'    => $blogPublic,
            'sampleContents'            => $this->get_wp_default_contents(),
            'themePluginEditor'         => $themePluginEditor,
            'debugMode'                 => $debugMode,
            'automaticUpdate'           => $automaticUpdate,
            'postRevisionControl'       => $postRevisionControl,
            'checkPermalink'            => $checkPermalink,
            'readmeStatus'              => $readmeStatus,
            'installStatus'             => $installStatus,
            'wpConfigStatus'            => $wpConfigStatus,
            'upgradeStatus'             => $upgradeStatus,
            'woocommerce'               => $wcArray
        );
    }
    
    /** 
     * Function to post MWA API 
     * @return array
     */
    public function mwa_post_api() 
    {
        if ($_REQUEST['mwa_audit_token'] != '') 
        {
            // Post My Web Audit API
            $mwaAuditToken = $_REQUEST['mwa_audit_token'];

            // check audit token and save
            update_option( 'mwa_audit_token', $mwaAuditToken );

            $url        = $this->api_url;
            $params     = array(
                'mwa_audit_token'           => $mwaAuditToken,
                'mwa_audit_api_response'    => $this->mwa_encrypt_response()  
            );
            
            $response   = wp_remote_post( 
                $url, 
                array(
                    'method'        => 'POST',
                    'headers'       => array(),
                    'body'          => $params,
                    'cookies'       => array()
                )
            );
            
            if ( is_wp_error( $response ) ) 
            {
                $messages = $response->get_error_message(); 
                $response = array("status"=> "error", "message" => $messages); 
            } 
            else 
            {
                $emptyBody  = array("status"=> "error", "message" => 'No response from API.');
                $response   = (isset($response['body'])) ? json_decode($response['body'], true) : $emptyBody;
            }

            return $response;
        }
    }

    /** 
     * Function to post MWA Project API 
     * @return array
     */
    public function mwa_post_project_api() 
    {
        if ($_REQUEST['mwa_project_token'] != '') 
        {
            // Post My Web Audit Project API
            $mwaProjectToken = $_REQUEST['mwa_project_token'];

            // Check project token and save
            update_option( 'mwa_project_token', $mwaProjectToken );

            $url        = $this->project_api_url;
            $params     = array(
                'projectToken'  => $mwaProjectToken,
                'wpFileContent' => $this->mwa_encrypt_response()  
            );

            $response   = wp_remote_post( 
                $url, 
                array(
                    'method'        => 'POST',
                    'headers'       => array(),
                    'body'          => $params,
                    'cookies'       => array()
                )
            );

            if ( is_wp_error( $response ) ) 
            {
                $messages = $response->get_error_message(); 
                $response = array("status"=> "error", "message" => $messages); 
            } 
            else 
            {
                $emptyBody  = array("status"=> "error", "message" => 'No response from API.');
                $response   = (isset($response['body'])) ? json_decode($response['body'], true) : $emptyBody;
            }

            return $response;
        }
    }

    /**
     * Function to authorize and return the API response
     * 
     * @param object $request 
     * @return string JSON
     */
    public function mwa_api_responce()
    {
        header('Content-Type: application/json');
        
        status_header(200);

        // Private key
        $mwa_key  = '5dad8a167846513dcc50b4717aa3d509';

        // Set allowed sites
        $valid_sites = array(
            'mywebsitequote.com',
            'mywebaudit.com',
            'mywebinsights.io'
        );

        // Get key and site values
        $key = isset($_SERVER['HTTP_KEY']) ? $_SERVER['HTTP_KEY'] : false;
        
        // Check for authorization
        if (!$key || ($mwa_key!==$key)) 
        {
            $this->mwa_wp_error();
        }

        // Check for the website referer
        $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : false;
        $refData = parse_url($referer);
        if (!$referer || !in_array($refData['host'],$valid_sites)) 
        {
            $this->mwa_wp_error();
        }

        echo $this->mwa_encrypt_response();
        exit;
    }
    
    /**
     * Generates the WP error in JSON format
     * @return JSON
     */
    public function mwa_wp_error()
    {
        $wperror = array(
            "errors" => array(
                "Invalid Request" => "Authorization failed."
            ),
            "error_data" => array(
                "Invalid Request" => array(
                    "status" => 403
                )
            )            
        );
        
        echo json_encode($wperror);
        
        exit;
    }
    
    /**
     * Callback function for the API encrypt response 
     *
     * @return encrypt string $response
     */
    function mwa_encrypt_response()
    {
        $response_arr = json_encode(
            array(
                'status'    => 'ok',
                'data'      => $this->mwa_responce_json()
            )
        );
        // Encrypt the data using the public/private keys
        $rsa        = new Crypt_RSA();
        $rsa->loadKey( file_get_contents( dirname(__FILE__).'/public/my-web-audit.pub') );
        $ciphertext = $rsa->encrypt($response_arr);
        $response   = base64_encode($ciphertext);
        
        return $response;
    }

    /**
     * Function to add the self-hosted plugin to autoupdate filter transient
     *
     * @param $transient
     * @return object $transient
     */
    public function check_update($transient)
    {
        if (empty($transient->checked)) 
        {
            return $transient;
        }
 
        // Get the remote version
        $remote_version = $this->getRemote_version();
 
        // If a newer version is available, add the update
        if (version_compare($this->current_version, $remote_version, '<')) 
        {
            $obj                = new stdClass();
            $obj->slug          = $this->slug;
            $obj->new_version   = $remote_version;
            $obj->url           = $this->update_path;
            $obj->package       = $this->package_path;
            
            $transient->response[$this->plugin_slug] = $obj;
        }
        return $transient;
    }
 
    /**
     * Function to check the status of the self-hosted plugin
     *
     * @param boolean $false
     * @param array $action
     * @param object $arg
     * @return bool|object
     */
    public function check_info($false, $action, $arg)
    {
        if ($arg->slug === $this->slug) 
        {
            $information = $this->getRemote_information();
            return $information;
        }
        return false;
    }
 
    /**
     * Function to get the plugin version from the remote server
     * @return string $remote_version
     */
    public function getRemote_version()
    {
        $request = wp_remote_post($this->update_path, array('body' => array('action' => 'version')));
        if (!is_wp_error($request) || wp_remote_retrieve_response_code($request) === 200) 
        {
            return $request['body'];
        }
        return false;
    }
 
    /**
     * Function to get the plugin information
     * @return bool|object
     */
    public function getRemote_information()
    {
        $request = wp_remote_post($this->update_path, array('body' => array('action' => 'info')));
        if (!is_wp_error($request) || wp_remote_retrieve_response_code($request) === 200) 
        {
            return unserialize($request['body']);
        }
        return false;
    }

    /**
     * Function to check url accessible information
     * @return bool
     */
    function check_url_accessible( $url )
    {
        $response       = wp_remote_get($url);
        $responseCode   = wp_remote_retrieve_response_code( $response );

        // If http Status 200 on wp-config.php   
        if ( site_url('wp-config.php') == $url && $responseCode == 200 ) 
        {
            return false;
        }
        
        // If http Status 200
        if ( $responseCode == 200 && !empty( $response['body'] ) ) 
        {
            $bodyContent = strip_tags($response['body']);               

            if ( strpos( $bodyContent, 'request rejected' ) !== false ) 
            {
                return true;
            }
            return false;
        }
        return true;    
    }

    /**
     * Function to Download Encrypted Data as .txt format
     */
    function mwa_export_api_response()
    {
        if (isset($_REQUEST['mwa_export_api'])) 
        {           
            $response   = $this->mwa_encrypt_response();
            $fileName   = 'mywebaudit-wp-audit-'.time().'.txt';  
            header('Content-disposition: attachment; filename='.$fileName);
            header('Content-type: application/txt');
            echo $response;
            exit; 
        }
    }
}

$myWebAudit = new MyWebAudit();