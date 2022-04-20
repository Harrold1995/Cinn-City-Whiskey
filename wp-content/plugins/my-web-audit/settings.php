<?php 
/* 
 * My Web Audit Settings
 */

/** 
 * Function to add plugin option page 
 * 
 */
function mwa_register_options_page()
{
    add_options_page(
        'My Web Audit', 
        'My Web Audit', 
        'manage_options', 
        'mwa_options', 
        'mwa_options_page'
    );
}
add_action('admin_menu', 'mwa_register_options_page');

/** 
 * Function to show option page contents 
 * 
 */
function mwa_options_page()
{
    // Post mwa post api
    if (isset($_REQUEST['mwa_post_api']))
    {
        $mwa        = new MyWebAudit();
        $response   = $mwa->mwa_post_api();
        
        if ($response['status']=='success')
        {
            ?>
            <div class="notice notice-success is-dismissible"><p><?php _e($response['message']); ?></p></div>
            <?php
        }
        elseif ($response['status']=='error')
        {
            ?>
            <div class="notice notice-error is-dismissible"><p><?php _e($response['message']); ?></p></div>
            <?php
        }
    }

    // Post mwa project api
    if (isset($_REQUEST['mwa_post_project_api']))
    {
        $mwa        = new MyWebAudit();
        $response   = $mwa->mwa_post_project_api();

        if ($response['status']=='success')
        {
            ?>
            <div class="notice notice-success is-dismissible"><p><?php _e($response['message']); ?></p></div>
            <?php
        }
        elseif ($response['status']=='error')
        {
            ?>
            <div class="notice notice-error is-dismissible"><p><?php _e($response['message']); ?></p></div>
            <?php
        }
    }

    // Audit Token
    $mwaAuditToken = get_option('mwa_audit_token');

    // Project Token
    $mwaProjectToken = get_option('mwa_project_token');
    
    // Get MWA Plugin Setting Page Contents
    $apiUrl     = 'https://lp.mywebaudit.com/wp-json/mwa/v1/mwa-plugin/';
    $mwaKey     = '5dad8a167846513dcc50b4717aa3d509';
    $apiResponse    = wp_remote_post(
        $apiUrl,
        array(
            "body"  => array ( 
                "mwa_key"   => $mwaKey
            )
        )  
    );

    $settingContent = array();
    
    if ( is_array( $apiResponse ) ) 
    {
        $header         = $apiResponse['headers'];
        $body           = $apiResponse['body'];
        $settingContent = json_decode($body, true);
    }

    $style          = isset( $settingContent['data']['style'] ) ? $settingContent['data']['style'] : '';
    $auditTabTitle  = isset( $settingContent['data']['tabs']['audits'] ) ? $settingContent['data']['tabs']['audits'] : 'Audits';
    $title          = isset( $settingContent['data']['title'] ) ? $settingContent['data']['title'] : '';
    $topContent     = isset( $settingContent['data']['topContent'] ) ? $settingContent['data']['topContent'] : '';
    $bottomContent  = isset( $settingContent['data']['bottomContent'] ) ? $settingContent['data']['bottomContent'] : '';
    $bottomContent  = isset( $settingContent['data']['bottomContent'] ) ? $settingContent['data']['bottomContent'] : '';
    $tokenForm      = isset( $settingContent['data']['token_form'] ) ? $settingContent['data']['token_form'] : '';
    $placeholder    = isset( $tokenForm['placeholder'] ) ? $tokenForm['placeholder'] : 'Enter Token Here';
    $button         = isset( $tokenForm['button'] ) ? $tokenForm['button'] : 'Submit';
    $download       = isset( $settingContent['data']['download'] ) ? $settingContent['data']['download'] : 'Download';

    // Project token form
    $campaignsTabTitle      = isset( $settingContent['data']['tabs']['campaigns'] ) ? $settingContent['data']['tabs']['campaigns'] : 'Campaigns';
    $projectContent         = isset( $settingContent['data']['projectContent'] ) ? $settingContent['data']['projectContent'] : '';
    $projectBottomContent   = isset( $settingContent['data']['projectBottomContent'] ) ? $settingContent['data']['projectBottomContent'] : '';
    $pTokenForm             = isset( $settingContent['data']['project_token_form'] ) ? $settingContent['data']['project_token_form'] : array();
    $pTPlaceholder          = isset( $pTokenForm['placeholder'] ) ? $pTokenForm['placeholder'] : 'Enter Project Token Here';
    $pTButton               = isset( $pTokenForm['button'] ) ? $pTokenForm['button'] : 'Submit';

    // Set Style of contents 
    echo $style; 
  
    // Add Notice Error CSS 
    if( version_compare( get_bloginfo( 'version' ), '4.0.23', '<' ) )
    {
        ?>
        <style>        
            .notice-error {
                border-left-color: #dc3232 !important;
            }
            .notice-success {
                border-color: #7ad03a !important;
            }
            .notice.notice-error.is-dismissible, .notice.notice-success.is-dismissible {
                background: #fff;
                border-left: 4px solid #fff;
                -webkit-box-shadow: 0 1px 1px 0 rgba(0,0,0,.1);
                box-shadow: 0 1px 1px 0 rgba(0,0,0,.1);
                margin: 15px 18px 2px 3px;
                padding: 2px 9px;
            }
        </style>
        <?php
    }
    ?>
    <div class="wrap">
        <div class="mwa-heading-style">
        	<h1><?php echo $title; ?></h1>
        </div>

        <div class="">
	        <ul class="mwa-tabs">
				<li class="tab-link current" data-tab="audits-tab"><?php echo $auditTabTitle;?></li>
				<li class="tab-link" data-tab="campaigns-tab"><?php echo $campaignsTabTitle;?></li>
			</ul>

			<div id="audits-tab" class="mwa-tab-content current">
				<div class="mwa-container sidebars-column-1">
		            <?php echo $topContent; ?>
		            <form method="post" id="submit-mwa-api" action="options-general.php?page=mwa_options">
		                <input type="text" name="mwa_audit_token" value="<?php echo $mwaAuditToken;?>" placeholder="<?php echo $placeholder;?>" required="required" class="token-field">
		                <input type="submit" name="mwa_post_api" id="mwa_post_api" class="token-button" value="<?php echo $button;?>">
		            </form>
		        </div>
                <br>
                <div class="mwa-container sidebars-column-1">
                    <?php echo $bottomContent;?>
                    <form method="post" id="export-api-contents" action="options-general.php?page=mwa_options">
                        <input type="submit" name="mwa_export_api" id="mwa_export_api" class="token-button" value="<?php echo $download;?>">
                    </form>
                </div>
			</div>
			<div id="campaigns-tab" class="mwa-tab-content">
				 <div class="mwa-container sidebars-column-1">
		            <?php echo $projectContent;?>
		            <form method="post" id="submit-mwa-project-api" action="options-general.php?page=mwa_options">
		                <input type="text" name="mwa_project_token" value="<?php echo $mwaProjectToken;?>" placeholder="<?php echo $pTPlaceholder;?>" required="required" class="project-token-field token-field">
		                <input type="submit" name="mwa_post_project_api" id="mwa_post_project_api" class="token-button" value="<?php echo $pTButton;?>">
		            </form>
		        </div>
                <br>
                <div class="mwa-container sidebars-column-1">
                    <?php echo $projectBottomContent;?>
                    <form method="post" id="export-api-contents" action="options-general.php?page=mwa_options">
                        <input type="submit" name="mwa_export_api" id="mwa_export_api" class="token-button" value="<?php echo $download;?>">
                    </form>
                </div>
			</div>
		</div>
        
    </div>

	<script type="text/javascript">
		jQuery(document).ready(function(){
			jQuery('ul.mwa-tabs li').click(function(){
				var tab_id = jQuery(this).attr('data-tab');
				jQuery('ul.mwa-tabs li').removeClass('current');
				jQuery('.mwa-tab-content').removeClass('current');
				jQuery(this).addClass('current');
				jQuery("#"+tab_id).addClass('current');
			})
		})
	</script>
    <?php
}