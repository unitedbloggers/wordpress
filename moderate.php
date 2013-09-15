<?php
global $vicomi_api;

require(ABSPATH . 'wp-includes/version.php');

if ( !current_user_can('moderate_comments') ) {
    die();
}

if ( !function_exists('wp_nonce_field') ) {
    function wp_nonce_field() {}
}

// if reset request
if ( isset($_POST['reset']) ) {
	
	foreach ( array('vicomi_replace', 'vicomi_active', 'vicomi_api_key', 'vicomi_version') as $option ) {
		delete_option($option);
	}
    unset($_POST);

	?>
	<div class="wrap">
		<h2>Vicomi Reset</h2>
		<p>Vicomi has been reset successfully. You can <a href="?page=vicomi&amp;phase=1">reinstall</a> this plugin.</p>
	</div>
	<?php
	die();
}

// Clean params
if(isset($_POST['vc_un'])) {
    $_POST['vc_un'] = stripslashes($_POST['vc_un']);
	$_POST['vc_un'] = strip_tags($_POST['vc_un']);
}

if(isset($_POST['vc_ps'])) {
    $_POST['vc_ps'] = stripslashes($_POST['vc_ps']);
}

// set if vicomi plugin is active
if (isset($_GET['active'])) {
    update_option('vicomi_active', ($_GET['active'] == '1' ? '1' : '0'));
}

// init vicomi api key
$vicomi_api_key = isset($_POST['vicomi_api_key']) ? strip_tags($_POST['vicomi_api_key']) : null;

$phase = @intval($_GET['phase']);
if($phase == 2 && !isset($_POST['vc_un'])) $phase = 1;
$phase = (vicomi_is_installed()) ? 0 : ($phase ? $phase : 1);

if ($phase == 2 && isset($_POST['vc_un']) && isset($_POST['vc_ps']) ) {
    $vicomi_api_key = $vicomi_api->get_user_api_key($_POST['vc_un'], $_POST['vc_ps']);
    if ( $vicomi_api_key < 0 || !$vicomi_api_key ) {
        $phase = 1;
        vicomi_manage_dialog($vicomi_api->get_last_error(), true);
    }

    if ( $phase == 2 ) {
		update_option('vicomi_replace', 'all');
		update_option('vicomi_api_key', $vicomi_api_key);
    }
}

?>
<div class="wrap">
	<div class="vicomi-header">
		<div class="vicomi-menu">
			<span rel="vicomi-page" class="selected"><?php echo (vicomi_is_installed() ? 'Moderate' : 'Install'); ?></span>
			<span rel="vicomi-settings">Settings</span>
		</div>
	</div>
    <div class="vicomi-content">
    <?php
	if($phase == 0) {
		$mod_url = 'http://dashboard.vicomi.com/';
		?>
		<div class="vicomi-page">
            <h2>Vicomi Moderation</h2>
            <iframe src="<?php echo $mod_url ?>" style="width: 100%; height: 80%; min-height: 600px;"></iframe>
        </div>		
<?php } else if($phase == 1) { ?>
		<div class="vicomi-page">
            <h2>Install Vicomi</h2>
			<p>In order to activate Vicomi comment platform you need to have a Vicomi moderation account.</p>
			<p>Please fill in the fields below with your account credentials.</p>
			<p>If you don't have one, click <a href="http://vicomi.com" target="_blank">here</a> to create.</p>
			
            <form method="POST" action="?page=vicomi&amp;phase=2">
            <?php wp_nonce_field('vicomi-install-1'); ?>
			<div class="form-section">
				<label for="vc_un" style="display:block">Email</label>
				<input type="text" id="vc_un" name="vc_un" tabindex="1" />
			</div>
			<div class="form-section">
				<label for="vc_ps" style="display:block">Password</label>
				<input type="password" id="vc_ps" name="vc_ps" tabindex="2">
			</div>

            <input name="submit" type="submit" class="vicomi-btn" value="Next &raquo;" tabindex="3" style="margin-top:10px;">
            </form>
        </div>
<?php } else if($phase == 2) { ?>	
		 <div class="vicomi-page">
            <h2>Install Vicomi</h2>
            <p>Vicomi has been installed successfully. <a href="edit-comments.php?page=vicomi"> Continue to the moderation dashboard &gt</a></p>
        </div>
<?php	} ?>
        
    </div>

    <!-- Settings -->
    <div class="vicomi-content vicomi-settings" style="display:none">
        <h2>Settings</h2>
        <p>Version: <?php echo VICOMI_V; ?></p>
        <?php
        if (get_option('vicomi_active') === '0') {
            echo '<p class="status">Vicomi comments are currently disabled. (<a href="?page=vicomi&amp;active=1">Enable</a>)</p>';
        } else {
            echo '<p class="status">Vicomi comments are currently enabled. (<a href="?page=vicomi&amp;active=0">Disable</a>)</p>';
        }
        ?>
        <form method="POST" enctype="multipart/form-data">
        <?php wp_nonce_field('vicomi-settings'); ?>

        <form action="?page=vicomi" method="POST">
			<?php wp_nonce_field('vicomi-reset'); ?>
			<input type="submit" value="Reset Vicomi" name="reset" onclick="return confirm('Are you sure you want to reset the Vicomi plugin?')" class="button" /> This removes all Vicomi settings.
		</form>

    </div>
</div>
