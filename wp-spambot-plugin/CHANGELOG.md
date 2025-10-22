# Changelog

All notable changes to the WP Spambot plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2024-01-15

### Added
- Initial release of WP Spambot plugin
- User management interface with sortable columns
- Multi-select checkboxes for bulk operations
- StopForumSpam.org API integration
- Bulk operations:
  - Check for Spam: Verify users against spam databases
  - Flag as Spam: Manually mark users as spam
  - Delete Users: Bulk delete spam accounts
- Settings page with:
  - Enable/disable StopForumSpam service
  - API key configuration
  - Spam threshold settings
- Spam status tracking with user metadata
- Detailed spam information display:
  - Service used
  - Confidence score
  - Frequency count
  - Last checked timestamp
  - Per-field details (email and username)
- Pagination for large user lists (20 users per page)
- Admin notices for operation feedback
- Security features:
  - Nonce verification
  - Capability checks
  - Data sanitization and escaping
- WordPress coding standards compliance
- Comprehensive documentation:
  - README.md with technical details
  - USAGE-GUIDE.md with step-by-step instructions
  - DRUPAL-COMPARISON.md explaining how it emulates Drupal Spambot

### Security
- All user inputs sanitized
- CSRF protection with nonces
- Capability checks for admin actions
- Proper data escaping in templates

## [Unreleased]

### Planned for v2.0
- Registration protection (check new users on signup)
- IP address checking
- Automated actions (auto-block/delete)
- Additional spam services:
  - Project Honey Pot
  - Botscout
  - Custom database support
- Advanced reporting dashboard
- Email notifications for flagged users
- Activity logs
- Comment spam protection
- Scheduled background checks
- Export spam user list
- Import/export settings
- WP-CLI commands
- REST API endpoints

### Planned for v1.1
- Performance improvements
- Batch processing with progress indicators
- Caching for API responses
- Rate limiting protection
- Better error handling
- Enhanced UI with filters
- Search functionality
- Sort by spam status
- Bulk unflag action
