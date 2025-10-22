# WP Spambot Plugin

A WordPress plugin inspired by the Drupal Spambot module that helps detect and manage spam user accounts using external spam databases.

## Features

### Version 1.0.0

- **User Management Interface**: View all registered users with detailed information
  - Username
  - Email address
  - Registration date
  - Number of posts
  - User role/level
  - Spam status
  - Multi-select checkboxes

- **Bulk Operations**:
  - **Check for Spam**: Verify user accounts against spam databases
  - **Flag as Spam**: Manually flag users as spam
  - **Delete Users**: Bulk delete selected users

- **Spam Detection Services**:
  - **StopForumSpam.org**: Check email addresses and usernames against their database
  - Configurable spam threshold
  - Optional API key support

- **Settings Page**:
  - Enable/disable spam services
  - Configure API keys
  - Set spam detection threshold

## Installation

1. Upload the `wp-spambot-plugin` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure the plugin settings at **Spambot > Settings**

## Usage

### Configuring Spam Services

1. Navigate to **Spambot > Settings** in your WordPress admin
2. Enable **StopForumSpam** service
3. (Optional) Add your StopForumSpam API key for authenticated requests
4. Set the spam threshold (number of occurrences required to flag a user)
5. Click **Save Changes**

### Managing Users

1. Navigate to **Spambot > User Management**
2. Review the list of users with their information
3. Select users using the checkboxes
4. Choose a bulk action:
   - **Check for Spam**: Queries spam databases and updates status
   - **Flag as Spam**: Manually marks users as spam
   - **Delete Users**: Permanently removes selected users
5. Click **Apply**

### Understanding Spam Status

- **Flagged (red)**: User has been identified as potential spam
- **Clean (green)**: User has been checked and appears legitimate
- **- (dash)**: User has not been checked yet

## How It Works

### StopForumSpam Integration

The plugin uses the [StopForumSpam.org API](https://www.stopforumspam.com/usage) to check:
- Email addresses
- Usernames

When a user is checked:
1. Their username and email are sent to the StopForumSpam API
2. The API returns the number of times each has appeared in spam reports
3. If the appearance count meets or exceeds your threshold, the user is flagged as spam
4. Results are stored in user metadata for future reference

### Similar to Drupal Spambot Module

This plugin is inspired by the [Drupal Spambot module](https://www.drupal.org/project/spambot) and provides similar functionality:

- **User verification**: Check existing users against spam databases
- **Bulk operations**: Process multiple users at once
- **Multiple service support**: Extendable architecture for adding more spam detection services
- **Configurable settings**: Control how aggressive spam detection should be

## Technical Details

### Database

The plugin stores spam status in WordPress user metadata:
- Meta key: `wp_spambot_status`
- Meta value structure:
  ```php
  array(
      'status' => 'flagged|clean',
      'service' => 'stopforumspam|manual',
      'confidence' => (int) frequency score,
      'last_checked' => 'Y-m-d H:i:s'
  )
  ```

### Plugin Structure

```
wp-spambot-plugin/
├── wp-spambot.php (main plugin file)
├── includes/
│   ├── class-wp-spambot.php (core plugin class)
│   ├── class-wp-spambot-admin.php (admin interface)
│   ├── class-wp-spambot-spam-service.php (spam detection service)
│   └── class-wp-spambot-settings.php (settings management)
├── templates/
│   ├── admin-user-management.php (user management page)
│   └── admin-settings.php (settings page)
└── assets/
    └── css/
        └── admin.css (admin styling)
```

### Code Principles

This plugin follows WordPress coding standards and best practices:
- **Simple and clean code**: Easy to understand and maintain
- **WordPress APIs**: Uses native WordPress functions and hooks
- **Security**: Nonce verification, capability checks, and data sanitization
- **Extendable**: Easy to add new spam detection services

## API Reference

### StopForumSpam API

No API key is required for basic usage (500 requests/day).
For higher limits, you can obtain a free API key from [StopForumSpam](https://www.stopforumspam.com/).

API Endpoint: `https://api.stopforumspam.org/api`

Parameters used:
- `email`: Email address to check
- `username`: Username to check
- `json`: Return JSON format

Response includes:
- `appears`: Number of times the email/username appears in spam reports
- `frequency`: Spam frequency score

## Future Enhancements

Potential features for future versions:
- Additional spam detection services (Project Honey Pot, etc.)
- Automatic spam detection on user registration
- Scheduled background checks for existing users
- Email notifications for flagged users
- Export spam user list
- Integration with comment spam detection
- IP address checking

## Requirements

- WordPress 5.0 or higher
- PHP 7.0 or higher
- Active internet connection for API requests

## License

This plugin is licensed under the GPL v2 or later.

## Support

For issues, questions, or contributions, please visit the plugin repository.

## Credits

Inspired by the [Drupal Spambot module](https://www.drupal.org/project/spambot) and powered by [StopForumSpam.org](https://www.stopforumspam.com/).
