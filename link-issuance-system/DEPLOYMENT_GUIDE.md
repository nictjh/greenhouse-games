# Link Issuance System - Deployment Guide

This guide provides step-by-step instructions for deploying the Link Issuance System to your Hostinger server.

## 1. Files to Upload

Upload all files to your Hostinger server in the `/Link-Issuance-System/` directory. Make sure to maintain the directory structure.

## 2. Critical Configuration

### .htaccess Configuration

The most important step is to properly configure the .htaccess file:

1. Upload the `hostinger.htaccess` file to your server
2. Rename it to `.htaccess` (replacing any existing .htaccess file)

This file contains critical configuration for:
- URL rewriting
- Access control
- Security settings
- PHP configuration

### Directory Permissions

Ensure these directories have write permissions (755 or 775):
- `/data/`
- `/config/`

## 3. Testing the Deployment

After uploading all files and configuring the .htaccess file, test the system:

1. Run the htaccess test:
   ```
   https://activities.jalanjourney.com/Link-Issuance-System/htaccess_test.php
   ```

2. Test direct access with a token:
   ```
   https://activities.jalanjourney.com/Link-Issuance-System/direct-access.php?token=test-token-123456
   ```

3. Access the admin dashboard:
   ```
   https://activities.jalanjourney.com/Link-Issuance-System/admin/
   ```

## 4. Troubleshooting

### 403 Forbidden Errors

If you see 403 Forbidden errors:
1. Check that you've renamed `hostinger.htaccess` to `.htaccess`
2. Verify that the .htaccess file has the correct permissions (644)
3. Make sure your Hostinger server has mod_rewrite enabled

### Link Not Found Errors

If links aren't working:
1. Verify that all URLs are using the format: `/Link-Issuance-System/direct-access.php?token=YOUR_TOKEN`
2. Check that the token exists in the `data/links.json` file
3. Run the access URL check tool: `check_access_url.php?token=YOUR_TOKEN`

### Other Issues

For other issues:
1. Check the PHP error logs on your Hostinger server
2. Verify file permissions (644 for files, 755 for directories)
3. Make sure all required files have been uploaded

## 5. Important Notes

- The system is configured to use the `/Link-Issuance-System/` subdirectory path
- All access links now use the direct-access.php script instead of URL rewriting
- The error pages have been updated with the new design

If you need to make any changes to the configuration, edit the `.htaccess` file on your server or update the source files and re-upload them.
