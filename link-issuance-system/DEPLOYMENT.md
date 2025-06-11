# Deployment Guide for Link Issuance System

This guide provides detailed instructions for deploying the Link Issuance System on various hosting platforms.

## System Requirements

- PHP 7.4 or higher
- Write access to the file system
- Apache or Nginx web server
- HTTPS support (recommended for production)

## Deployment Options

### Option 1: Shared Hosting

Shared hosting is the simplest and most cost-effective option for deploying the Link Issuance System.

#### Steps:

1. **Upload Files**:
   - Download the Link Issuance System files
   - Upload them to your web hosting account using FTP or the hosting control panel
   - Place the files in a directory of your choice (e.g., `public_html/link-system/`)

2. **Set Permissions**:
   - Set the `data` directory to be writable by the web server:
     ```
     chmod -R 755 data/
     ```
   - If using cPanel, you can set permissions through the File Manager

3. **Configure Web Server**:
   - Most shared hosting environments are pre-configured for PHP applications
   - If needed, create an `.htaccess` file in the root directory with the following content:
     ```
     <IfModule mod_rewrite.c>
     RewriteEngine On
     RewriteBase /
     
     # Handle API requests
     RewriteRule ^api/(.*)$ api/index.php [L,QSA]
     
     # Prevent direct access to PHP files
     RewriteCond %{THE_REQUEST} ^.+?\ [^?]+\.php[?\ ]
     RewriteCond %{REQUEST_URI} !^/api/
     RewriteCond %{REQUEST_URI} !^/admin/
     RewriteCond %{REQUEST_URI} !^/access/
     RewriteRule \.php$ - [F]
     
     # Prevent direct access to data files
     RewriteRule ^data/ - [F]
     RewriteRule ^config/ - [F]
     </IfModule>
     ```

4. **Update Configuration**:
   - Edit `config/settings.json` to update the system settings
   - Change the default admin password and token secret
   - Update the system name and other settings as needed

5. **Test the Installation**:
   - Access the admin dashboard at `https://yourdomain.com/path/to/Link Issuance System/admin/`
   - Log in with the default password
   - Create a test link and verify that it works

### Option 2: VPS or Dedicated Server

For more control and better performance, you can deploy the Link Issuance System on a VPS or dedicated server.

#### Steps:

1. **Set Up the Server**:
   - Install a LAMP (Linux, Apache, MySQL, PHP) or LEMP (Linux, Nginx, MySQL, PHP) stack
   - For Ubuntu/Debian:
     ```
     sudo apt update
     sudo apt install apache2 php php-json php-mbstring
     ```
   - For CentOS/RHEL:
     ```
     sudo yum install httpd php php-json php-mbstring
     ```

2. **Configure the Web Server**:
   - For Apache, create a virtual host configuration:
     ```
     <VirtualHost *:80>
         ServerName yourdomain.com
         DocumentRoot /var/www/html/link-system
         
         <Directory /var/www/html/link-system>
             Options -Indexes +FollowSymLinks
             AllowOverride All
             Require all granted
         </Directory>
         
         ErrorLog ${APACHE_LOG_DIR}/link-system-error.log
         CustomLog ${APACHE_LOG_DIR}/link-system-access.log combined
     </VirtualHost>
     ```
   - For Nginx, create a server block:
     ```
     server {
         listen 80;
         server_name yourdomain.com;
         root /var/www/html/link-system;
         
         location / {
             try_files $uri $uri/ /index.php?$args;
         }
         
         location ~ \.php$ {
             include snippets/fastcgi-php.conf;
             fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
         }
         
         location ~ /\.ht {
             deny all;
         }
         
         location ~ ^/data/ {
             deny all;
         }
         
         location ~ ^/config/ {
             deny all;
         }
     }
     ```

3. **Upload Files**:
   - Clone the repository or upload the files to the server:
     ```
     git clone https://github.com/yourusername/link-issuance-system.git /var/www/html/link-system
     ```
   - Or upload via SFTP/SCP

4. **Set Permissions**:
   - Set the appropriate permissions:
     ```
     sudo chown -R www-data:www-data /var/www/html/link-system
     sudo chmod -R 755 /var/www/html/link-system
     sudo chmod -R 775 /var/www/html/link-system/data
     ```

5. **Configure HTTPS**:
   - Install Let's Encrypt certbot:
     ```
     sudo apt install certbot python3-certbot-apache
     ```
   - Or for Nginx:
     ```
     sudo apt install certbot python3-certbot-nginx
     ```
   - Obtain and configure SSL certificate:
     ```
     sudo certbot --apache -d yourdomain.com
     ```
   - Or for Nginx:
     ```
     sudo certbot --nginx -d yourdomain.com
     ```

6. **Update Configuration**:
   - Edit `config/settings.json` to update the system settings
   - Change the default admin password and token secret
   - Update the system name and other settings as needed

7. **Test the Installation**:
   - Access the admin dashboard at `https://yourdomain.com/admin/`
   - Log in with the default password
   - Create a test link and verify that it works

### Option 3: Cloud Hosting (AWS, Google Cloud, Azure)

For scalability and reliability, you can deploy the Link Issuance System on a cloud platform.

#### AWS Elastic Beanstalk:

1. **Prepare the Application**:
   - Create a `.ebextensions` directory in the root of your project
   - Create a configuration file `.ebextensions/01_permissions.config`:
     ```yaml
     files:
       "/tmp/set_permissions.sh":
         mode: "000755"
         owner: root
         group: root
         content: |
           #!/bin/bash
           chmod -R 755 /var/app/current/
           chmod -R 775 /var/app/current/data
           chown -R webapp:webapp /var/app/current/

     container_commands:
       01_set_permissions:
         command: "/tmp/set_permissions.sh"
     ```

2. **Create an Elastic Beanstalk Application**:
   - Use the AWS Management Console or AWS CLI
   - Choose the PHP platform
   - Upload your application as a ZIP file

3. **Configure Environment Variables**:
   - Set environment variables for sensitive information
   - Update the application to use environment variables for configuration

4. **Configure HTTPS**:
   - Use AWS Certificate Manager to create a certificate
   - Configure the load balancer to use HTTPS

#### Google Cloud App Engine:

1. **Prepare the Application**:
   - Create an `app.yaml` file in the root of your project:
     ```yaml
     runtime: php74
     
     handlers:
     - url: /data/.*
       script: forbidden.php
     
     - url: /config/.*
       script: forbidden.php
     
     - url: /api/.*
       script: api/index.php
     
     - url: /.*
       script: auto
     ```

2. **Deploy the Application**:
   - Install the Google Cloud SDK
   - Deploy the application:
     ```
     gcloud app deploy
     ```

3. **Configure HTTPS**:
   - App Engine automatically provides HTTPS

#### Azure App Service:

1. **Prepare the Application**:
   - Create a `web.config` file in the root of your project:
     ```xml
     <?xml version="1.0" encoding="UTF-8"?>
     <configuration>
       <system.webServer>
         <rewrite>
           <rules>
             <rule name="Block data directory" stopProcessing="true">
               <match url="^data/.*" />
               <action type="CustomResponse" statusCode="403" />
             </rule>
             <rule name="Block config directory" stopProcessing="true">
               <match url="^config/.*" />
               <action type="CustomResponse" statusCode="403" />
             </rule>
             <rule name="API Rewrite" stopProcessing="true">
               <match url="^api/(.*)$" />
               <action type="Rewrite" url="api/index.php" />
             </rule>
           </rules>
         </rewrite>
       </system.webServer>
     </configuration>
     ```

2. **Deploy the Application**:
   - Use the Azure Portal or Azure CLI
   - Create an App Service with PHP 7.4 or higher
   - Deploy your application

3. **Configure HTTPS**:
   - Azure App Service automatically provides HTTPS

## Post-Deployment Steps

Regardless of the deployment option you choose, complete these steps after deployment:

1. **Change Default Credentials**:
   - Log in to the admin dashboard
   - Go to the Settings page
   - Change the admin password
   - Update the token secret

2. **Configure Games**:
   - Edit `config/games.json` to add your games
   - Or use the admin dashboard to manage games

3. **Test the System**:
   - Create a test link
   - Access the link in a browser
   - Verify that the access control works as expected
   - Test concurrent device limits
   - Test session expiration

4. **Set Up Backup**:
   - Regularly back up the `data` and `config` directories
   - For shared hosting, use the hosting control panel or FTP
   - For VPS or cloud hosting, set up automated backups

## Troubleshooting

### Common Issues:

1. **Permission Errors**:
   - Ensure the `data` directory is writable by the web server
   - Check the web server error logs for permission issues

2. **API Errors**:
   - Verify that the `.htaccess` or web server configuration is correct
   - Check that PHP is properly configured

3. **CORS Issues**:
   - If integrating with games on different domains, configure CORS headers
   - Edit `api/index.php` to add appropriate CORS headers

4. **Session Validation Fails**:
   - Check that the token secret is consistent
   - Verify that the server time is correct
   - Ensure cookies are being properly set and sent

## Security Recommendations

1. **Use HTTPS**:
   - Always use HTTPS in production
   - Obtain a valid SSL certificate
   - Configure the web server to redirect HTTP to HTTPS

2. **Secure File Permissions**:
   - Restrict access to sensitive files and directories
   - Use the principle of least privilege

3. **Regular Updates**:
   - Keep PHP and the web server up to date
   - Apply security patches promptly

4. **Firewall Configuration**:
   - Configure a firewall to restrict access to the server
   - Allow only necessary ports (80, 443)

5. **Monitoring**:
   - Set up monitoring to detect unusual activity
   - Regularly review logs for security issues

## Performance Optimization

1. **PHP Optimization**:
   - Enable PHP OPcache
   - Increase PHP memory limit if needed

2. **Web Server Optimization**:
   - Enable compression
   - Configure caching
   - Use a CDN for static assets

3. **File Storage**:
   - For high-traffic sites, consider using a more robust storage solution
   - Implement a caching layer

## Scaling

The Link Issuance System is designed to be lightweight and can handle moderate traffic with minimal resources. For higher traffic:

1. **Horizontal Scaling**:
   - Deploy multiple instances behind a load balancer
   - Use a shared file system or database for data storage

2. **Vertical Scaling**:
   - Increase server resources (CPU, RAM)
   - Optimize PHP and web server configuration

3. **Database Integration**:
   - For very high traffic, consider modifying the system to use a database
   - Implement a caching layer

## Support

If you encounter issues during deployment, please refer to the documentation or open an issue on the GitHub repository.
