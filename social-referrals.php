<?php
/*
Plugin Name: Social Referrals
Plugin URI: http://yourdomain.com/
Description: Logs and displays social site referrals
Version: 1.4
Author: Don Kukral
Author URI: http://yourdomain.com
License: GPL
*/

add_action('admin_menu', 'social_referrals_admin_menu');
add_action('wp', 'social_referrals_init', 0);

function social_referrals_init() {
    global $post;

     if (get_option('social_referrals_twitter', 0)) {
         $ref_url = parse_url($_SERVER['HTTP_REFERER']);
         if ($ref_url['host'] == 't.co') {
             social_referrals_log_entry($post->ID, $_SERVER['HTTP_REFERER'], 0, 1, 0);
             return;
         }
     }

    if (get_option('social_referrals_facebook', 0)) {
        $ref_url = parse_url($_SERVER['HTTP_REFERER']);
         if (($ref_url['host'] == 'www.facebook.com') || ($ref_url['host'] == 'm.facebook.com')) {
             social_referrals_log_entry($post->ID, $_SERVER['HTTP_REFERER'], 1, 0, 0);
             return;
         }
     }

    if (get_option('social_referrals_wordpress', 0)) {
        $ref_url = parse_url($_SERVER['HTTP_REFERER']);
         if (($ref_url['host'] == 'www.wordpress.com')) {
             social_referrals_log_entry($post->ID, $_SERVER['HTTP_REFERER'], 1, 0, 0);
             return;
         }
     }
}

function social_referrals_log_entry($post_id, $referral_url, $facebook, $twitter, $wordpress) {
    global $wpdb;
    $table_name = $wpdb->prefix . "social_referrals";
    $wpdb->insert(
        $table_name,
        array(
            'post_id' => $post_id,
            'referral_url' => $referral_url,
            'facebook' => $facebook,
            'twitter' => $twitter,
            'wordpress' => $wordpress
        ),
        array(
            '%d',
            '%s',
            '%d',
            '%d',
            '%d'
        )
    );
    $row = $wpdb->get_row("SELECT SUM(facebook) AS facebook, SUM(twitter) AS twitter, SUM(wordpress) as wordpress FROM " . $wpdb->prefix . "social_referrals WHERE ts > NOW() - INTERVAL " . get_option('social_referrals_hours', 24) . " HOUR");
    update_option('social_referrals_facebook_count', $row->facebook);
    update_option('social_referrals_twitter_count', $row->twitter);
    update_option('social_referrals_wordpress_count', $row->wordpress);

}

function social_referrals_admin_menu() {
    add_options_page(
        'Social Referrals',
        'Social Referrals',
        'administrator',
        'social_referrals',
        'social_referrals_settings_page');
}

function social_referrals_settings_page() {
    if ( isset($_POST['action']) && $_POST['action'] == 'update' ) {
        if ($_POST['social_referrals_facebook']) { update_option('social_referrals_facebook', 1); }
        else { delete_option('social_referrals_facebook'); }
        if ($_POST['social_referrals_twitter']) { update_option('social_referrals_twitter', 1); }
        else { delete_option('social_referrals_twitter'); }
        if ($_POST['social_referrals_wordpress']) { update_option('social_referrals_wordpress', 1); }
        else { delete_option('social_referrals_wordpress'); }
        if ($_POST['social_referrals_hours']) { update_option('social_referrals_hours', $_POST['social_referrals_hours']); }
        else { update_option('social_referrals_hours', '24'); }
        echo '<div class="updated"><p>Social Referrals Settings Updated</p></div>';
    }
?>
    <div id="content" class="narrowcolumn">

	    <div class="wrap">
	        <h2>Social Referrals</h2>
	        <form method="post" action="" id="email_on_publish_form">
	        <input type="hidden" name="action" value="update" />
	        <?php wp_nonce_field('update-options'); ?>

	        <table class="form-table">
	        <tr>
	        <td><input type="checkbox" name="social_referrals_facebook" <?php echo checked(get_option('social_referrals_facebook'), 1); ?>/> Track Facebook referrals.</td>
	        </tr>
	        <tr>
	        <td><input type="checkbox" name="social_referrals_twitter" <?php echo checked(get_option('social_referrals_twitter'), 1); ?>/> Track Twitter referrals.</td>
	        </tr>
            <tr>
            <td><input type="checkbox" name="social_referrals_wordpress" <?php echo checked(get_option('social_referrals_wordpress'), 1); ?>/> Track Wordpress.com referrals.</td>
            </tr>
	        <tr>
	        <tr>
	        <td>Display referrals for last <input type="text" name="social_referrals_hours" value="<?php echo get_option('social_referrals_hours', 24); ?>" size="3" style="text-align: center;"/> hours.</td>
	        </tr>
            <tr>
            <td><input type="submit" value="Update"/></td>
            </tr>
	        </table>
	        </form>
	    </div>
	</div>
<?php
}


function social_referrals_install() {
    global $wpdb;
    global $social_referrals_db_ver;

    $table_name = $wpdb->prefix . "social_referrals";

    $sql = "CREATE TABLE " . $table_name . " (
        id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
        post_id BIGINT(20) NOT NULL DEFAULT 0,
        referral_url TEXT NOT NULL DEFAULT '',
        facebook INT(11) NOT NULL DEFAULT 0,
        twitter INT(11) NOT NULL DEFAULT 0,
        ts TIMESTAMP NOT NULL,
        UNIQUE KEY id (id));";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    $social_referrals_db_ver = 1.0;
    add_option("social_referrals_db_ver", $social_referrals_db_ver);

}

register_activation_hook(__FILE__, 'social_referrals_install');

function social_referrals_db_update() {
    global $wpdb;

    $curr_social_referrals_ver = 1.1;
    $social_referrals_db_ver = get_option("social_referrals_db_ver", 0);

    if ($social_referrals_db_ver == 0) { social_referrals_install(); }
    $social_referrals_db_ver = get_option("social_referrals_db_ver", 0);

    if ($social_referrals_db_ver < 1.1) {
        $table_name = $wpdb->prefix . "social_referrals";

        $sql = "CREATE TABLE " . $table_name . " (
            id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
            post_id BIGINT(20) NOT NULL DEFAULT 0,
            referral_url TEXT NOT NULL DEFAULT '',
            facebook INT(11) NOT NULL DEFAULT 0,
            twitter INT(11) NOT NULL DEFAULT 0,
            wordpress INT(11) NOT NULL DEFAULT 0,
            ts TIMESTAMP NOT NULL,
            UNIQUE KEY id (id));";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        $social_referrals_db_ver = 1.1;
        add_option("social_referrals_db_ver", $social_referrals_db_ver);
    }
}

//add_action( 'plugins_loaded', 'social_referrals_db_update' );

?>
