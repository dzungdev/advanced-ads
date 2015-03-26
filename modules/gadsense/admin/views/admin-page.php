<?php
if (!defined('WPINC')) {
	die;
}
$adsense_id = $this->data->get_adsense_id();
$limit_per_page = $this->data->get_limit_per_page();
?>
<h3>Google AdSense</h3>
<?php if (isset($_SESSION['gadsense']['admin_notice'])) : ?>
<?php
	$notice = $_SESSION['gadsense']['admin_notice'];
	unset($_SESSION['gadsense']['admin_notice']);
?>
	<div class="<?php echo $notice['class']; ?>">
		<p><?php echo $notice['msg'] ?></p>
	</div>
<?php endif; // END isset($_SESSION['gadsense']['admin_notice']) ?>
<form method="post" id="cred-form" class="gadsense-form">
	<input type="hidden" name="gadsense-form-name" value="cred-form" />
	<input type="hidden" name="gadsense-nonce" value="<?php echo $this->nonce; ?>" />
	<label><?php _e('Account ID', ADVADS_SLUG); ?><br />
	<input type="text" name="adsense-id" id="adsense-id" size="32" value="<?php echo $adsense_id; ?>" /></label>
	<p class="description"><?php _e('Your AdSense Publisher ID <em>(pub-xxxxxxxxxxxxxx)</em>', ADVADS_SLUG) ?></p>
	<br />
	<label>
		<input type="checkbox" name="limit-per-page" value="1" <?php checked($limit_per_page); ?> />
		<?php printf(__('Limit to %d AdSense ads', ADVADS_SLUG), 3); ?>
	</label>
	<p class="description">
		<?php
			printf(
			__('Currently, Google AdSense <a target="_blank" href="%s" title="Terms Of Service">TOS</a> imposes a limit of %d display ads per page. You can disable this limitation at your own risks.', ADVADS_SLUG),
			esc_url('https://www.google.com/adsense/terms'), 3
		); ?><br/><?php
                        _e('Notice: Advanced Ads only considers the AdSense ad type for this limit.', ADVADS_SLUG); ?>
	</p>
	<p>
		<input type="submit" class="button button-primary" value="<?php _e('Save AdSense Settings', ADVADS_SLUG); ?>" />
	</p>
</form>
<br />
<hr />
