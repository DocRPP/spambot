# WP Spambot Plugin - Usage Guide

## Quick Start

### Installation

1. Upload the `wp-spambot-plugin` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to **Spambot > Settings** to configure

### Initial Setup (5 minutes)

1. **Enable StopForumSpam Service**:
   - Navigate to **Spambot > Settings**
   - Check the "Enable StopForumSpam" checkbox
   - Click **Save Changes**

2. **(Optional) Add API Key**:
   - Get a free API key from https://www.stopforumspam.com/
   - Enter it in the "StopForumSpam API Key" field
   - This increases your daily request limit

3. **Set Spam Threshold**:
   - Default is 1 (flag if appears once in spam database)
   - Set to 0 to flag ANY positive match
   - Set higher (e.g., 5) to only flag frequent spammers

4. **You're Ready!**
   - Go to **Spambot > User Management** to start checking users

## User Management Interface

### Understanding the Table

The user management page displays all users with the following columns:

| Column | Description |
|--------|-------------|
| **Checkbox** | Select users for bulk operations |
| **Username** | The user's login name |
| **Email** | The user's email address |
| **Registered** | When the user account was created |
| **Posts** | Number of posts the user has published |
| **Role** | User's WordPress role (Administrator, Subscriber, etc.) |
| **Spam Status** | Current spam detection status |

### Spam Status Indicators

- **`-` (dash)**: User has not been checked yet
- **<span style="color:green">Clean</span> (stopforumspam)**: User checked and appears legitimate
- **<span style="color:red">Flagged</span> (stopforumspam)**: User appears in spam database
- **<span style="color:red">Flagged</span> (manual)**: User manually flagged by administrator

## Bulk Operations

### Check for Spam

**What it does**: Queries spam databases to verify selected users

**How to use**:
1. Select users using checkboxes
2. Choose "Check for Spam" from the bulk actions dropdown
3. Click **Apply**
4. Confirm the action
5. Wait for results (may take a few seconds)

**Result**: 
- Users found in spam databases are flagged in red
- Clean users are marked in green
- Results are stored and displayed in the "Spam Status" column

**Example Output**:
```
Spam check complete: 3 flagged, 15 clean, 0 errors.
```

### Flag as Spam

**What it does**: Manually marks users as spam without checking databases

**How to use**:
1. Select users you know are spam
2. Choose "Flag as Spam" from the bulk actions dropdown
3. Click **Apply**

**When to use**:
- You've verified spam through other means
- You want to mark users for later review
- Database check didn't catch obvious spam

**Result**: Selected users are marked as "Flagged (manual)"

### Delete Users

**What it does**: Permanently removes selected users and their content

**⚠️ WARNING**: This action cannot be undone!

**How to use**:
1. Select spam users to delete
2. Choose "Delete Users" from the bulk actions dropdown
3. Click **Apply**
4. Confirm the deletion

**Best Practice**: 
- Always check users for spam first
- Review flagged users before deleting
- Consider backing up your database before bulk deletions

## Settings Configuration

### StopForumSpam Settings

#### Enable StopForumSpam
- **Default**: Disabled
- **When to enable**: Always enable to use spam checking
- **Note**: Without this enabled, "Check for Spam" won't work

#### StopForumSpam API Key
- **Default**: Empty (anonymous requests)
- **Free Account Limits**: 500 requests per day
- **With API Key**: Higher limits (varies by account type)
- **How to get**: Register at https://www.stopforumspam.com/

#### Spam Threshold
- **Default**: 1
- **Range**: 0 to any positive number
- **Meaning**: How many times email/username must appear in spam reports

**Threshold Examples**:
- **0**: Flag ANY appearance in spam database (very strict)
- **1**: Flag if appears once or more (recommended)
- **5**: Flag only frequent spammers (more lenient)
- **10**: Flag only very active spammers (least strict)

**Recommendation**: Start with threshold 1, adjust based on results

## Workflow Examples

### Scenario 1: Cleaning Up Existing Users

**Goal**: Check all existing users for spam

**Steps**:
1. Go to **Spambot > User Management**
2. Click the checkbox at the top to select all users on the page
3. Choose "Check for Spam"
4. Click **Apply**
5. Review flagged users
6. Repeat for each page if you have many users
7. Once all checked, select flagged users
8. Choose "Delete Users" to remove confirmed spam
9. Click **Apply** and confirm

**Time**: ~2 minutes per 20 users (depends on API response)

### Scenario 2: Daily Spam Check

**Goal**: Check recent registrations for spam

**Steps**:
1. Go to **Spambot > User Management**
2. Recent users appear at the top (sorted by registration date)
3. Select recent users (e.g., last week's registrations)
4. Choose "Check for Spam"
5. Click **Apply**
6. Review and delete any flagged accounts

**Frequency**: Daily or weekly, depending on registration volume

### Scenario 3: Manual Spam Flagging

**Goal**: Mark obvious spam without API check

**Steps**:
1. Go to **Spambot > User Management**
2. Review user list for obvious spam patterns:
   - Generic usernames (user12345, randomname99)
   - Suspicious email domains
   - Zero posts but registered months ago
   - Strange characters in username
3. Select suspicious users
4. Choose "Flag as Spam"
5. Click **Apply**
6. Later, verify flagged users and delete if confirmed

### Scenario 4: Verifying False Positives

**Goal**: Ensure legitimate users aren't flagged incorrectly

**Steps**:
1. Review users flagged as spam
2. Check their profile:
   - Do they have legitimate posts?
   - Is the email address real?
   - Have they been active?
3. If legitimate, leave them (status won't affect their access)
4. If definitely spam, select and delete

**Note**: False positives are rare but can happen with common names/emails

## Understanding Spam Check Results

### What Gets Checked

When you run "Check for Spam", the plugin checks:
- **Email address**: Is it in spam reports?
- **Username**: Has it been used by spammers?

### What the API Returns

**Example Response for Spam Account**:
```
Email: appears 42 times, last seen 2024-01-15
Username: appears 15 times, last seen 2024-01-10
Confidence: 89.5%
Result: FLAGGED
```

**Example Response for Clean Account**:
```
Email: not found
Username: not found
Result: CLEAN
```

### Confidence Scores

Higher confidence = more likely to be spam
- **0-30%**: Low confidence, possibly false positive
- **30-70%**: Medium confidence, likely spam
- **70-100%**: High confidence, almost certainly spam

## Troubleshooting

### "No spam services are enabled" Warning

**Problem**: You tried to check users but no services are configured

**Solution**:
1. Go to **Spambot > Settings**
2. Enable "StopForumSpam"
3. Save settings
4. Try again

### API Rate Limit Exceeded

**Problem**: "Rate limit exceeded" or checks stop working

**Cause**: Free API limited to 500 requests/day

**Solutions**:
1. Get a free API key from StopForumSpam (increases limit)
2. Check users in smaller batches
3. Wait 24 hours for limit to reset
4. Prioritize checking suspicious users

### False Positives

**Problem**: Legitimate user flagged as spam

**Cause**: User has common name/email that spammers also use

**Solution**:
1. Review the user's activity
2. If they're legitimate, leave them
3. Consider increasing spam threshold to reduce false positives
4. Flagging doesn't block users, it just marks them

### Slow Performance

**Problem**: Checking users takes a long time

**Cause**: API requests take time (each user ~1-2 seconds)

**Solutions**:
1. Check users in smaller batches (5-10 at a time)
2. Ensure good internet connection
3. Check during off-peak hours
4. Be patient - thorough checking takes time

## Best Practices

### 1. Start Conservative
- Begin with threshold 1
- Review flagged users before deleting
- Adjust threshold based on results

### 2. Regular Maintenance
- Check new users weekly
- Review flagged users monthly
- Clean up obvious spam immediately

### 3. Don't Auto-Delete
- Always review flagged users first
- Look for false positives
- Check if user has any legitimate activity

### 4. Use Multiple Indicators
- Combine spam check with manual review
- Look at registration date vs. activity
- Check for patterns (burst registrations, similar emails)

### 5. Document Your Process
- Note which threshold works for your site
- Track false positive rate
- Adjust strategy as needed

## API Key Benefits

### Without API Key (Anonymous)
- 500 requests per day
- Adequate for small sites
- No registration required

### With API Key (Free Account)
- Higher request limits
- More reliable service
- Access to additional features
- Recommended for medium-large sites

### How to Get API Key
1. Visit https://www.stopforumspam.com/
2. Click "Sign Up" (free)
3. Verify email
4. Go to API settings
5. Copy your API key
6. Paste in plugin settings

## Security & Privacy

### Data Sent to StopForumSpam
- Username
- Email address
- That's it - no other personal data

### Data Stored Locally
- Spam check results
- Confidence scores
- Last check timestamp
- Service used (stopforumspam or manual)

### User Privacy
- Checking users doesn't affect their experience
- No notifications sent to users
- Results only visible to administrators

## Frequently Asked Questions

### Q: Will flagged users be blocked from my site?
**A**: No, this plugin only detects and marks spam users. It doesn't block access. You must manually delete users to remove them.

### Q: Can I unflag a user?
**A**: Yes, just check them again. If they're clean in the database, they'll be marked as clean. Or delete the user meta manually.

### Q: How accurate is spam detection?
**A**: StopForumSpam has a very large database and high accuracy, but false positives can occur. Always review before deleting.

### Q: Does this prevent new spam registrations?
**A**: Not in v1.0. This version focuses on checking existing users. Registration protection planned for v2.0.

### Q: Can I add other spam databases?
**A**: Not in v1.0, but the plugin is designed to support multiple services. Additional services planned for future versions.

### Q: What if a legitimate user is flagged?
**A**: Don't delete them! The flag is just a warning. Review their profile and activity to confirm they're legitimate.

### Q: How often should I check users?
**A**: Weekly for new users is good. Monthly for full database checks on smaller sites.

## Support & Feedback

If you encounter issues or have suggestions:
1. Check this usage guide first
2. Review the README.md for technical details
3. Check the DRUPAL-COMPARISON.md to understand how it works
4. Submit issues with detailed information

## Next Steps

Now that you understand how to use WP Spambot:

1. ✅ Configure your settings
2. ✅ Run your first spam check
3. ✅ Review the results
4. ✅ Set up a regular maintenance schedule
5. ✅ Keep your site spam-free!

Happy spam hunting! 🛡️
