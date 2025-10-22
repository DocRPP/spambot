# Drupal Spambot Module Comparison

This document explains how the WP Spambot plugin emulates the Drupal Spambot module functionality.

## Drupal Spambot Module Overview

The Drupal Spambot module (https://www.drupal.org/project/spambot) is designed to detect and block spam user registrations and check existing users against spam databases.

### Key Features of Drupal Spambot Module:

1. **Registration Protection**: Automatically checks new user registrations
2. **Existing User Scanning**: Bulk check existing users for spam
3. **StopForumSpam Integration**: Primary spam database integration
4. **User Management**: List and manage flagged spam accounts
5. **Configurable Threshold**: Adjust spam detection sensitivity
6. **IP Address Checking**: Check IP addresses against spam databases
7. **Automated Actions**: Block or delete spam users automatically

## WP Spambot Implementation (v1.0)

### What We've Implemented:

#### 1. User Management Interface
**Drupal equivalent**: Admin interface at `/admin/people`

Our implementation provides:
- User listing with key information (username, email, registration date, posts, role)
- Multi-select checkboxes for bulk operations
- Pagination for large user lists
- Spam status display for each user

```php
Location: templates/admin-user-management.php
Access: WordPress Admin > Spambot > User Management
```

#### 2. StopForumSpam Integration
**Drupal equivalent**: StopForumSpam service configuration

Our implementation:
- Full API integration with StopForumSpam.org
- Checks both username and email address
- Supports optional API key for higher limits
- Configurable spam threshold
- Confidence scoring

```php
Location: includes/class-wp-spambot-spam-service.php
API: https://api.stopforumspam.org/api
```

#### 3. Bulk Operations
**Drupal equivalent**: Bulk operations on user list

Available actions:
- **Check for Spam**: Query spam databases for selected users
- **Flag as Spam**: Manually mark users as spam
- **Delete Users**: Remove spam users from the system

```php
Location: includes/class-wp-spambot-admin.php
Methods: bulk_check_spam(), bulk_flag_spam(), bulk_delete_users()
```

#### 4. Settings Configuration
**Drupal equivalent**: Admin settings at `/admin/config/people/spambot`

Configuration options:
- Enable/disable StopForumSpam service
- API key input for authenticated requests
- Spam threshold setting (frequency required to flag)

```php
Location: includes/class-wp-spambot-settings.php
Access: WordPress Admin > Spambot > Settings
```

#### 5. Spam Status Tracking
**Drupal equivalent**: User metadata storage

We store spam check results in user metadata:
```php
Meta key: wp_spambot_status
Meta value: {
    status: 'flagged' | 'clean',
    service: 'stopforumspam' | 'manual',
    confidence: float,
    frequency: int,
    details: {
        email: { frequency, confidence, lastseen },
        username: { frequency, confidence, lastseen }
    },
    last_checked: 'Y-m-d H:i:s'
}
```

## Key Differences from Drupal Module

### What's Different in v1.0:

1. **No Real-time Registration Protection**: 
   - Drupal version checks users during registration
   - WP version focuses on checking existing users
   - Future enhancement planned

2. **No IP Address Checking**:
   - Drupal checks IP addresses
   - WP v1.0 checks username and email only
   - Can be added in future versions

3. **No Automatic Actions**:
   - Drupal can automatically block/delete spam users
   - WP v1.0 requires manual review and action
   - Safer approach for initial version

4. **Different Admin Interface**:
   - Drupal integrates with native people management
   - WP has dedicated menu section
   - WordPress-style UI and UX

### WordPress-Specific Adaptations:

1. **WordPress Hooks & Filters**:
   - Uses WordPress admin_menu, admin_init, admin_post hooks
   - Native WordPress Settings API
   - WordPress nonce verification

2. **WordPress User Queries**:
   - WP_User_Query for user retrieval
   - count_user_posts() for post counts
   - WordPress user roles system

3. **WordPress Admin Notices**:
   - Transient-based notice system
   - WordPress notice styling
   - Auto-dismiss after display

4. **WordPress Security**:
   - current_user_can() capability checks
   - wp_nonce_field() and check_admin_referer()
   - sanitize_text_field() and esc_html()

## API Integration Details

### StopForumSpam API

The plugin uses the same API as Drupal Spambot:

**Endpoint**: `https://api.stopforumspam.org/api`

**Query Parameters**:
```
email: user email address
username: user login name
json: 1 (return JSON format)
confidence: 1 (include confidence scores)
api_key: optional authenticated requests
```

**Response Structure**:
```json
{
  "success": 1,
  "email": {
    "appears": 1,
    "frequency": 42,
    "confidence": 89.5,
    "lastseen": "2024-01-15"
  },
  "username": {
    "appears": 1,
    "frequency": 15,
    "confidence": 75.2,
    "lastseen": "2024-01-10"
  }
}
```

### Spam Detection Logic

Similar to Drupal, we use a threshold-based approach:

```php
// Default threshold: 1 occurrence
// Threshold 0: Flag any positive match
// Threshold 5: Flag only if appears 5+ times

if (email_frequency >= threshold || username_frequency >= threshold) {
    mark_as_spam();
}
```

## Code Structure Comparison

### Drupal Module Structure:
```
spambot/
├── spambot.module
├── spambot.admin.inc
├── spambot.install
└── src/
    └── Service/
        └── SpambotService.php
```

### WP Plugin Structure:
```
wp-spambot-plugin/
├── wp-spambot.php
├── includes/
│   ├── class-wp-spambot.php
│   ├── class-wp-spambot-admin.php
│   ├── class-wp-spambot-spam-service.php
│   └── class-wp-spambot-settings.php
├── templates/
│   ├── admin-user-management.php
│   └── admin-settings.php
└── assets/
    └── css/
        └── admin.css
```

## How to Use (Similar to Drupal)

### Step 1: Configure Service (like Drupal setup)
1. Go to **Spambot > Settings**
2. Enable StopForumSpam service
3. (Optional) Add API key
4. Set spam threshold
5. Save settings

### Step 2: Check Existing Users (like Drupal bulk operation)
1. Go to **Spambot > User Management**
2. Select users to check
3. Choose "Check for Spam" bulk action
4. Click Apply
5. Review results

### Step 3: Handle Spam Users (like Drupal user management)
1. Review flagged users (shown in red)
2. Select confirmed spam accounts
3. Choose action:
   - Flag as Spam (manual marking)
   - Delete Users (permanent removal)
4. Click Apply

## Future Enhancements to Match Drupal

### Planned for v2.0:

1. **Registration Protection**:
   ```php
   add_action('user_register', 'wp_spambot_check_registration');
   ```

2. **IP Address Checking**:
   ```php
   $params['ip'] = $_SERVER['REMOTE_ADDR'];
   ```

3. **Automated Actions**:
   - Auto-block spam registrations
   - Auto-delete spam accounts
   - Scheduled cleanup tasks

4. **Additional Services**:
   - Project Honey Pot integration
   - Botscout integration
   - Custom spam database support

5. **Advanced Reporting**:
   - Spam statistics dashboard
   - Email notifications
   - Activity logs

6. **Comment Spam Protection**:
   - Check comment authors
   - Integration with Akismet

## Performance Considerations

Both plugins handle performance similarly:

1. **Rate Limiting**: 
   - Free API: 500 requests/day
   - With API key: Higher limits
   - Consider caching results

2. **Batch Processing**:
   - Process users in chunks
   - Avoid timeout on large batches
   - Show progress indicators

3. **Caching**:
   - Store check results in user meta
   - Don't re-check recently verified users
   - Configurable cache duration

## Conclusion

The WP Spambot plugin successfully emulates the core functionality of the Drupal Spambot module while adapting to WordPress conventions and best practices. Version 1.0 focuses on simplicity and core features, with a clear path for future enhancements to achieve feature parity with the Drupal version.

The plugin provides a solid foundation for spam user management in WordPress, using the same proven APIs and detection methods as the Drupal community has relied on.
