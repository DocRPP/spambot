<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1><?php esc_html_e('Spambot Settings', 'wp-spambot'); ?></h1>
    <form method="post" action="options.php">
        <?php
        settings_fields('wp_spambot_settings_group');
        do_settings_sections('wp_spambot_settings');
        submit_button();
        ?>
    </form>
</div>
