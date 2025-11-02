<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('wp_spambot_sortable_column')) {
    function wp_spambot_sortable_column($column, $label, $current_orderby, $current_order) {
        $new_order = 'ASC';
        $arrow = '';
        
        if ($column === $current_orderby) {
            $new_order = ($current_order === 'ASC') ? 'DESC' : 'ASC';
            $arrow = ($current_order === 'ASC') ? ' ▲' : ' ▼';
        }
        
        $url = add_query_arg(array(
            'orderby' => $column,
            'order' => $new_order,
        ));
        
        return sprintf(
            '<a href="%s">%s%s</a>',
            esc_url($url),
            esc_html($label),
            esc_html($arrow)
        );
    }
}
?>
<div class="wrap">
    <h1><?php esc_html_e('User Management - Spam Detection', 'wp-spambot'); ?></h1>
    <p><?php esc_html_e('Select users and perform bulk operations to check for spam, flag, or delete spam accounts.', 'wp-spambot'); ?></p>
    
    <form method="get" action="">
        <input type="hidden" name="page" value="wp_spambot" />
        <div class="wp-spambot-filters">
            <div class="filter-group">
                <label for="filter-role"><?php esc_html_e('Role', 'wp-spambot'); ?></label>
                <select name="filter_role" id="filter-role">
                    <option value=""><?php esc_html_e('All Roles', 'wp-spambot'); ?></option>
                    <?php
                    global $wp_roles;
                    if (!isset($wp_roles)) {
                        $wp_roles = wp_roles();
                    }
                    foreach ($wp_roles->roles as $role_key => $role_data) :
                        $selected = selected($filter_role, $role_key, false);
                        ?>
                        <option value="<?php echo esc_attr($role_key); ?>" <?php echo $selected; ?>><?php echo esc_html($role_data['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label for="filter-posts-min"><?php esc_html_e('Min Posts', 'wp-spambot'); ?></label>
                <input type="number" name="filter_posts_min" id="filter-posts-min" value="<?php echo esc_attr($filter_posts_min); ?>" min="0" />
            </div>
            <div class="filter-group">
                <label for="filter-posts-max"><?php esc_html_e('Max Posts', 'wp-spambot'); ?></label>
                <input type="number" name="filter_posts_max" id="filter-posts-max" value="<?php echo esc_attr($filter_posts_max); ?>" min="0" />
            </div>
            <div class="filter-group">
                <label for="filter-spam-status"><?php esc_html_e('Spam Status', 'wp-spambot'); ?></label>
                <select name="filter_spam_status" id="filter-spam-status">
                    <option value=""><?php esc_html_e('All', 'wp-spambot'); ?></option>
                    <option value="flagged" <?php selected($filter_spam_status, 'flagged'); ?>><?php esc_html_e('Flagged', 'wp-spambot'); ?></option>
                    <option value="clean" <?php selected($filter_spam_status, 'clean'); ?>><?php esc_html_e('Clean', 'wp-spambot'); ?></option>
                    <option value="unchecked" <?php selected($filter_spam_status, 'unchecked'); ?>><?php esc_html_e('Not Checked', 'wp-spambot'); ?></option>
                </select>
            </div>
            <div class="filter-group">
                <label for="filter-spam-reason"><?php esc_html_e('Spam Reason', 'wp-spambot'); ?></label>
                <select name="filter_spam_reason" id="filter-spam-reason">
                    <option value=""><?php esc_html_e('All', 'wp-spambot'); ?></option>
                    <option value="email_only" <?php selected($filter_spam_reason, 'email_only'); ?>><?php esc_html_e('Email Only', 'wp-spambot'); ?></option>
                    <option value="username_only" <?php selected($filter_spam_reason, 'username_only'); ?>><?php esc_html_e('Username Only', 'wp-spambot'); ?></option>
                    <option value="both" <?php selected($filter_spam_reason, 'both'); ?>><?php esc_html_e('Email & Username', 'wp-spambot'); ?></option>
                </select>
            </div>
            <div class="filter-group">
                <label for="per-page"><?php esc_html_e('Users Per Page', 'wp-spambot'); ?></label>
                <input type="number" name="per_page" id="per-page" value="<?php echo esc_attr($users_per_page); ?>" min="1" max="500" />
            </div>
            <div class="filter-actions">
                <button type="submit" class="button button-primary"><?php esc_html_e('Apply Filters', 'wp-spambot'); ?></button>
                <a href="<?php echo esc_url(admin_url('admin.php?page=wp_spambot')); ?>" class="button"><?php esc_html_e('Reset', 'wp-spambot'); ?></a>
            </div>
        </div>
    </form>
    
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="wp-spambot-user-management">
        <?php wp_nonce_field('wp_spambot_bulk_action'); ?>
        <input type="hidden" name="action" value="wp_spambot_bulk_action" />
        
        <div class="tablenav top">
            <div class="alignleft actions bulkactions">
                <label for="bulk-action-selector-top" class="screen-reader-text"><?php esc_html_e('Select bulk action', 'wp-spambot'); ?></label>
                <select name="bulk_action" id="bulk-action-selector-top">
                    <option value=""><?php esc_html_e('Bulk Actions', 'wp-spambot'); ?></option>
                    <option value="check_spam"><?php esc_html_e('Check for Spam', 'wp-spambot'); ?></option>
                    <option value="flag_spam"><?php esc_html_e('Flag as Spam', 'wp-spambot'); ?></option>
                    <option value="mark_not_spam"><?php esc_html_e('Mark as Not Spam', 'wp-spambot'); ?></option>
                    <option value="delete_users"><?php esc_html_e('Delete Users', 'wp-spambot'); ?></option>
                </select>
                <input type="submit" class="button action" value="<?php esc_attr_e('Apply', 'wp-spambot'); ?>" />
            </div>
        </div>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <td class="manage-column column-cb check-column">
                        <input type="checkbox" id="select-all-users" />
                    </td>
                    <th scope="col" class="manage-column column-username"><?php echo wp_spambot_sortable_column('username', __('Username', 'wp-spambot'), $orderby, $order); ?></th>
                    <th scope="col" class="manage-column column-email"><?php echo wp_spambot_sortable_column('email', __('Email', 'wp-spambot'), $orderby, $order); ?></th>
                    <th scope="col" class="manage-column column-registered"><?php echo wp_spambot_sortable_column('registered', __('Registered', 'wp-spambot'), $orderby, $order); ?></th>
                    <th scope="col" class="manage-column column-posts"><?php echo wp_spambot_sortable_column('posts', __('Posts', 'wp-spambot'), $orderby, $order); ?></th>
                    <th scope="col" class="manage-column column-role"><?php echo wp_spambot_sortable_column('role', __('Role', 'wp-spambot'), $orderby, $order); ?></th>
                    <th scope="col" class="manage-column column-spam-status"><?php echo wp_spambot_sortable_column('spam_status', __('Spam Status', 'wp-spambot'), $orderby, $order); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($users)) : ?>
                    <?php foreach ($users as $user) : 
                        $user_roles = implode(', ', $user->roles);
                        $post_count = count_user_posts($user->ID);
                        $spam_status = get_user_meta($user->ID, 'wp_spambot_status', true);
                        $spam_display = '<span class="wp-spambot-status-unknown">' . esc_html__('Not checked', 'wp-spambot') . '</span>';
                        
                        if (is_array($spam_status) && !empty($spam_status['status'])) {
                            $status_text = $spam_status['status'];
                            $service = !empty($spam_status['service']) ? $spam_status['service'] : 'unknown';
                            
                            if ($status_text === 'flagged') {
                                $status_label = '<span class="wp-spambot-status-flagged">' . esc_html__('Flagged', 'wp-spambot') . '</span>';
                            } elseif ($status_text === 'clean') {
                                $status_label = '<span class="wp-spambot-status-clean">' . esc_html__('Clean', 'wp-spambot') . '</span>';
                            } else {
                                $status_label = '<span class="wp-spambot-status-unknown">' . esc_html(ucfirst($status_text)) . '</span>';
                            }
                            
                            $meta_info = array();
                            $meta_info[] = sprintf(
                                esc_html__('Service: %s', 'wp-spambot'),
                                esc_html(ucwords(str_replace('_', ' ', $service)))
                            );
                            
                            if (array_key_exists('confidence', $spam_status) && $spam_status['confidence'] !== null && $spam_status['confidence'] !== '') {
                                $meta_info[] = sprintf(
                                    esc_html__('Confidence: %s%%', 'wp-spambot'),
                                    esc_html(round((float) $spam_status['confidence'], 2))
                                );
                            }
                            
                            if (array_key_exists('frequency', $spam_status) && $spam_status['frequency'] !== null && $spam_status['frequency'] !== '') {
                                $meta_info[] = sprintf(
                                    esc_html__('Frequency: %s', 'wp-spambot'),
                                    esc_html((int) $spam_status['frequency'])
                                );
                            }
                            
                            if (!empty($spam_status['last_checked'])) {
                                $meta_info[] = sprintf(
                                    esc_html__('Last checked: %s', 'wp-spambot'),
                                    esc_html(mysql2date(get_option('date_format') . ' ' . get_option('time_format'), $spam_status['last_checked']))
                                );
                            }
                            
                            $detail_info = array();
                            if (!empty($spam_status['details']) && is_array($spam_status['details'])) {
                                foreach ($spam_status['details'] as $key => $detail) {
                                    $detail_label = sprintf(
                                        esc_html__('%1$s frequency: %2$s, confidence: %3$s%%', 'wp-spambot'),
                                        esc_html(ucwords(str_replace('_', ' ', $key))),
                                        esc_html(isset($detail['frequency']) ? (int) $detail['frequency'] : 0),
                                        esc_html(isset($detail['confidence']) ? round((float) $detail['confidence'], 2) : 0)
                                    );
                                    
                                    if (!empty($detail['lastseen'])) {
                                        $detail_label .= ' ' . sprintf(
                                            esc_html__('(last seen: %s)', 'wp-spambot'),
                                            esc_html(mysql2date(get_option('date_format'), $detail['lastseen']))
                                        );
                                    }
                                    
                                    $detail_info[] = $detail_label;
                                }
                            }
                            
                            $spam_display = $status_label;
                            if (!empty($meta_info)) {
                                $spam_display .= '<br /><small>' . implode(' | ', $meta_info) . '</small>';
                            }
                            if (!empty($detail_info)) {
                                $spam_display .= '<br /><small>' . implode(' | ', $detail_info) . '</small>';
                            }
                        }
                    ?>
                    <tr>
                        <th scope="row" class="check-column">
                            <input type="checkbox" name="user_ids[]" value="<?php echo esc_attr($user->ID); ?>" class="user-checkbox" />
                        </th>
                        <td class="username column-username">
                            <strong><?php echo esc_html($user->user_login); ?></strong>
                        </td>
                        <td class="email column-email">
                            <?php echo esc_html($user->user_email); ?>
                        </td>
                        <td class="registered column-registered">
                            <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($user->user_registered))); ?>
                        </td>
                        <td class="posts column-posts">
                            <?php echo esc_html($post_count); ?>
                        </td>
                        <td class="role column-role">
                            <?php echo esc_html($user_roles); ?>
                        </td>
                        <td class="spam-status column-spam-status">
                            <?php echo wp_kses_post($spam_display); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="7"><?php esc_html_e('No users found.', 'wp-spambot'); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td class="manage-column column-cb check-column">
                        <input type="checkbox" id="select-all-users-bottom" />
                    </td>
                    <th scope="col" class="manage-column column-username"><?php echo wp_spambot_sortable_column('username', __('Username', 'wp-spambot'), $orderby, $order); ?></th>
                    <th scope="col" class="manage-column column-email"><?php echo wp_spambot_sortable_column('email', __('Email', 'wp-spambot'), $orderby, $order); ?></th>
                    <th scope="col" class="manage-column column-registered"><?php echo wp_spambot_sortable_column('registered', __('Registered', 'wp-spambot'), $orderby, $order); ?></th>
                    <th scope="col" class="manage-column column-posts"><?php echo wp_spambot_sortable_column('posts', __('Posts', 'wp-spambot'), $orderby, $order); ?></th>
                    <th scope="col" class="manage-column column-role"><?php echo wp_spambot_sortable_column('role', __('Role', 'wp-spambot'), $orderby, $order); ?></th>
                    <th scope="col" class="manage-column column-spam-status"><?php echo wp_spambot_sortable_column('spam_status', __('Spam Status', 'wp-spambot'), $orderby, $order); ?></th>
                </tr>
            </tfoot>
        </table>
        
        <div class="tablenav bottom">
            <div class="alignleft actions bulkactions">
                <select name="bulk_action_bottom" id="bulk-action-selector-bottom">
                    <option value=""><?php esc_html_e('Bulk Actions', 'wp-spambot'); ?></option>
                    <option value="check_spam"><?php esc_html_e('Check for Spam', 'wp-spambot'); ?></option>
                    <option value="flag_spam"><?php esc_html_e('Flag as Spam', 'wp-spambot'); ?></option>
                    <option value="mark_not_spam"><?php esc_html_e('Mark as Not Spam', 'wp-spambot'); ?></option>
                    <option value="delete_users"><?php esc_html_e('Delete Users', 'wp-spambot'); ?></option>
                </select>
                <input type="submit" class="button action" value="<?php esc_attr_e('Apply', 'wp-spambot'); ?>" />
            </div>
            
            <?php if ($total_pages > 1) : ?>
            <div class="tablenav-pages">
                <span class="displaying-num"><?php printf(esc_html__('%s users', 'wp-spambot'), number_format_i18n($total_users)); ?></span>
                <?php
                $page_links = paginate_links(array(
                    'base' => add_query_arg('paged', '%#%'),
                    'format' => '',
                    'prev_text' => __('&laquo;'),
                    'next_text' => __('&raquo;'),
                    'total' => $total_pages,
                    'current' => $paged
                ));
                
                if ($page_links) {
                    echo '<span class="pagination-links">' . $page_links . '</span>';
                }
                ?>
            </div>
            <?php endif; ?>
        </div>
    </form>
    
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#select-all-users, #select-all-users-bottom').on('click', function() {
                var checked = $(this).prop('checked');
                $('.user-checkbox').prop('checked', checked);
            });
            
            var $topAction = $('select[name="bulk_action"]');
            var $bottomAction = $('select[name="bulk_action_bottom"]');
            
            $topAction.add($bottomAction).on('change', function() {
                var value = $(this).val();
                $topAction.val(value);
                $bottomAction.val(value);
            });
            
            $('#wp-spambot-user-management').on('submit', function(e) {
                var action = $topAction.val();
                if (!action) {
                    action = $bottomAction.val();
                    $topAction.val(action);
                }
                if (!action) {
                    alert('<?php esc_html_e('Please select a bulk action.', 'wp-spambot'); ?>');
                    e.preventDefault();
                    return false;
                }
                
                var selected = $('.user-checkbox:checked').length;
                if (selected === 0) {
                    alert('<?php esc_html_e('Please select at least one user.', 'wp-spambot'); ?>');
                    e.preventDefault();
                    return false;
                }
                
                if (action === 'delete_users') {
                    if (!confirm('<?php esc_html_e('Are you sure you want to delete the selected users? This action cannot be undone.', 'wp-spambot'); ?>')) {
                        e.preventDefault();
                        return false;
                    }
                }
                
                if (action === 'check_spam') {
                    if (!confirm('<?php esc_html_e('This will check the selected users against spam databases. Continue?', 'wp-spambot'); ?>')) {
                        e.preventDefault();
                        return false;
                    }
                }
                
                return true;
            });
        });
    </script>
</div>
