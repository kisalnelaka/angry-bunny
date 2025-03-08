# Angry Bunny Security Scanner

A comprehensive WordPress security scanner that actively looks for vulnerabilities and provides fix recommendations.

## Features

### Free Version
- File Permission Scanner
- Core File Integrity Check
- Basic Malware Detection
- Security Score Dashboard
- Email Notifications
- Scheduled Scans
- Basic Reporting

### Pro Version
- Real-time File Monitoring
- Advanced Malware Detection
- Two-Factor Authentication (2FA)
- Advanced Firewall Protection
- Automated Malware Removal
- Backup Management
- System Restore Points
- Emergency Recovery Mode
- Advanced PDF Reports
- Custom Scan Scheduling
- Historical Trend Analysis
- White-label Options
- Third-party Integrations
  - Slack Notifications
  - Discord Alerts
  - Cloud Backup Services
  - Status Page Integration

## Requirements

- PHP 7.2 or higher
- WordPress 5.0 or higher
- MySQL 5.6 or higher
- Write permissions on wp-content directory

## Installation

1. Download the plugin zip file
2. Go to WordPress admin > Plugins > Add New
3. Click "Upload Plugin" and select the zip file
4. Click "Install Now"
5. Activate the plugin

## Configuration

1. Navigate to "Angry Bunny Security" in your WordPress admin menu
2. Configure scan frequency and email notifications
3. Run your first security scan
4. Review security score and recommendations

## Pro Version Setup

1. Purchase a license from [our website](https://yourdomain.com/angry-bunny-pro)
2. Navigate to Angry Bunny Security > License Management
3. Enter your license key and activate
4. Access pro features from the main dashboard

## Usage

### Running a Security Scan

1. Go to Angry Bunny Security dashboard
2. Click "Run Security Scan"
3. Wait for the scan to complete
4. Review findings and follow recommended fixes

### Managing Features

1. Navigate to Features page
2. Review available features
3. Upgrade to pro for advanced features
4. Configure feature settings as needed

### License Management

1. Go to License Management page
2. Enter your license key
3. Click Activate
4. Monitor license status and renewal date

## Development

### File Structure
```
angry-bunny/
├── admin/
│   ├── css/
│   ├── js/
│   └── class-angry-bunny-admin.php
├── includes/
│   ├── pro/
│   ├── class-angry-bunny.php
│   ├── class-angry-bunny-loader.php
│   └── ...
├── languages/
├── README.md
└── angry-bunny.php
```

### Actions and Filters

```php
// Run before security scan
do_action('angry_bunny_before_scan');

// Filter scan results
apply_filters('angry_bunny_scan_results', $results);

// After security fix
do_action('angry_bunny_after_fix', $fix_type);
```

## Troubleshooting

### Common Issues

1. **Fatal Error on Activation**
   - Check PHP version requirements
   - Verify WordPress version
   - Ensure all files are properly uploaded

2. **Scan Fails to Complete**
   - Check server timeout settings
   - Verify file permissions
   - Review error logs

3. **License Activation Issues**
   - Verify license key
   - Check site URL matches registration
   - Contact support if persistent

## Support

- Documentation: [docs.yourdomain.com/angry-bunny](https://docs.yourdomain.com/angry-bunny)
- Support Email: support@yourdomain.com
- Pro Support: [support.yourdomain.com](https://support.yourdomain.com)

## Changelog

### 1.0.0
- Initial release
- Basic security scanning
- File permission checks
- Core file integrity
- Security score dashboard

## License

GPL v2 or later
