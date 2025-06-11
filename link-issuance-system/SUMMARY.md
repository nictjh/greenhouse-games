# Link Issuance System - System Overview

## Introduction

The Link Issuance System is a lightweight access control solution designed specifically for browser-based games. It allows game developers and publishers to generate unique access links with customizable parameters, track concurrent device connections, and manage access duration.

## Key Features

- **Unique Access Links**: Each link has a unique token that can be shared with clients
- **Concurrent Device Limiting**: Restrict the number of devices that can use a link simultaneously
- **Time-Based Access**: Access duration starts only when the link is first activated
- **Customizable Duration**: Set different time periods per link (hours/days)
- **No Database Required**: Uses simple JSON file storage for easy deployment
- **Admin Dashboard**: Comprehensive interface for link management and monitoring
- **Client Integration**: Simple JavaScript library for game integration
- **Security Focused**: JWT tokens, device fingerprinting, and CSRF protection

## System Architecture

The system follows a simple client-server architecture:

1. **Server-Side Components**:
   - PHP-based RESTful API
   - JSON file storage for data persistence
   - Admin dashboard for management

2. **Client-Side Components**:
   - JavaScript library for game integration
   - Access gateway for user entry

## Data Flow

1. **Link Generation**:
   - Admin creates a link via the dashboard
   - System generates a unique token
   - Link data is stored in JSON file

2. **Link Activation**:
   - User accesses the link
   - System validates the token
   - If valid and not expired, a session is created
   - User is redirected to the game selection page

3. **Session Management**:
   - Client library periodically validates the session
   - System tracks concurrent device usage
   - When session expires, user is redirected to expired page

## Security Measures

1. **Token Security**:
   - JWT tokens for authentication
   - Server-side validation of all tokens
   - Secure HTTP-only cookies

2. **Device Tracking**:
   - Device fingerprinting to identify unique devices
   - Concurrent device limiting
   - Session timeout for inactive users

3. **API Security**:
   - CSRF protection for admin actions
   - Rate limiting to prevent abuse
   - Input validation and sanitization

4. **File Security**:
   - .htaccess rules to prevent direct access to data files
   - Proper file permissions
   - Secure storage of sensitive information

## Integration Guide

### For Game Developers

1. **Include the Client Library**:
   ```html
   <script src="path/to/access-control.min.js"></script>
   ```

2. **Initialize the Library**:
   ```javascript
   AccessControl.init({
       onExpired: function() {
           // Handle session expiration
           alert('Your session has expired');
           window.location.href = 'path/to/access/';
       },
       checkInterval: 60 // Check session every 60 seconds
   });
   ```

3. **The library will automatically**:
   - Validate the user's session
   - Monitor session status
   - Handle session expiration

### For Administrators

1. **Generate Access Links**:
   - Log in to the admin dashboard
   - Select games to include in the link
   - Set duration and concurrent device limit
   - Generate and share the link with clients

2. **Monitor Usage**:
   - View active links and sessions
   - Track usage statistics
   - Revoke links if needed

## Technical Specifications

- **Server Requirements**:
  - PHP 7.4 or higher
  - Apache or Nginx web server
  - Write access to file system
  - No database required

- **Client Requirements**:
  - Modern web browser with JavaScript enabled
  - Cookies enabled

- **API Endpoints**:
  - `/api/generate` - Generate new access links
  - `/api/validate` - Validate links and create sessions
  - `/api/check-session` - Check if a session is valid
  - `/api/revoke` - Revoke links
  - `/api/list` - List all links
  - `/api/stats` - Get system statistics

## Customization Options

The system is designed to be easily customizable:

1. **Visual Customization**:
   - Edit CSS files to match your branding
   - Modify HTML templates

2. **Functional Customization**:
   - Adjust session check interval
   - Modify concurrent device limit
   - Change default session duration

3. **Integration Customization**:
   - Extend the client library
   - Add custom API endpoints
   - Implement additional security measures

## Limitations and Considerations

1. **Scalability**:
   - The file-based storage is suitable for moderate usage
   - For high-traffic applications, consider implementing a database backend

2. **Security**:
   - The system provides basic security measures
   - For high-value content, consider additional security layers

3. **Browser Compatibility**:
   - The system is designed for modern browsers
   - Some features may not work in older browsers

## Support and Maintenance

- Regular backups of data files are recommended
- Monitor system logs for errors or suspicious activity
- Keep PHP and web server software updated
- Check for system updates and security patches

## Conclusion

The Link Issuance System provides a lightweight, easy-to-deploy solution for access control in browser-based games. It balances security with user experience, offering flexible options for game developers and publishers.
