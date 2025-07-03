# Link Issuance System

A lightweight access control system for browser-based games that allows you to generate unique access links with customizable parameters.

## Features

- **Unique Access Links**: Generate secure, unique links for your clients
- **Concurrent Device Tracking**: Limit the number of devices that can use a link simultaneously
- **Time-Based Access**: Links start timing only when first activated
- **Customizable Duration**: Set different time periods per link (hours/days)
- **No Database Required**: Uses simple JSON file storage
- **Admin Dashboard**: Manage links, view statistics, and monitor usage
- **Client-Side Integration**: Easy to integrate with any browser-based game
- **Secure**: Built with security in mind, using JWT tokens and device fingerprinting
- **Lightweight**: Minimal server requirements, can run on free hosting tiers

## System Components

### Backend API

The system includes a RESTful API built with PHP that handles:

- Link generation
- Session validation
- Access control
- Statistics tracking

### Admin Dashboard

A comprehensive admin interface that allows you to:

- Generate new access links
- View and manage existing links
- Monitor active sessions
- View usage statistics
- Configure system settings

### Client Library

A lightweight JavaScript library that can be integrated into any browser-based game to:

- Validate access
- Monitor session status
- Handle session expiration

## Directory Structure

```
Link Issuance System/
├── api/                  # Backend API
│   ├── endpoints/        # API endpoints
│   │   ├── generate.php  # Generate new links
│   │   ├── validate.php  # Validate links and create sessions
│   │   ├── check_session.php # Check if a session is valid
│   │   ├── revoke.php    # Revoke links
│   │   ├── list.php      # List all links
│   │   └── stats.php     # Get system statistics
│   ├── utils/            # Utility functions
│   │   ├── storage.php   # File storage functions
│   │   ├── security.php  # Security functions
│   │   └── link_service.php # Link management functions
│   └── index.php         # API entry point
├── admin/                # Admin dashboard
│   ├── assets/           # Admin dashboard assets
│   │   ├── css/          # CSS styles
│   │   │   └── styles.css # Admin dashboard styles
│   │   └── js/           # JavaScript files
│   │       └── admin.js  # Admin dashboard functionality
│   └── index.php         # Admin dashboard entry point
├── assets/               # Shared assets
│   └── js/               # JavaScript files
│       ├── access-control.js     # Client library (unminified)
│       └── access-control.min.js # Client library (minified)
├── access/               # Access gateway
│   ├── index.php         # Access gateway entry point
│   ├── expired.php       # Expired session page
│   └── styles.css        # Access gateway styles
├── config/               # Configuration files
│   ├── settings.json     # System settings
│   └── games.json        # Games configuration
├── data/                 # Data storage
│   ├── links.json        # Access links data
│   └── sessions.json     # Active sessions data
└── games/                # Sample games
    └── sample-game.html  # Sample game with integration
```

## Installation

1. Upload the files to your web server
2. Make sure the `data` directory is writable by the web server
3. Access the admin dashboard at `https://yourdomain.com/Link Issuance System/admin/`
4. Log in with the default password (see `config/settings.json`)
5. Change the default password and token secret in the settings

## Usage

### Generating Access Links

1. Log in to the admin dashboard
2. Click "Create Link" button
3. Select the games to include in the link
4. Set the duration and concurrent device limit
5. Click "Create Link"
6. Copy the generated link and send it to your client

### Integrating with Your Games

1. Include the client library in your game's HTML:

```html
<script src="https://yourdomain.com/Link Issuance System/assets/js/access-control.min.js"></script>
```

2. Initialize the library:

```javascript
AccessControl.init({
    onExpired: function() {
        // Handle session expiration
        alert('Your session has expired');
        window.location.href = 'https://yourdomain.com/Link Issuance System/access/';
    },
    checkInterval: 60 // Check session every 60 seconds
});
```

3. The library will automatically validate the session and monitor it

## Security Considerations

- Change the default admin password immediately after installation
- Change the token secret in the settings
- Use HTTPS to protect data in transit
- Regularly monitor the system for suspicious activity
- Consider implementing additional security measures for high-value content

## Deployment

The system is designed to run on minimal hosting requirements:

- PHP 7.4 or higher
- Write access to the `data` directory
- No database required
- Works on shared hosting, VPS, or cloud platforms

For detailed deployment instructions, see [DEPLOYMENT.md](DEPLOYMENT.md).

## Customization

The system is designed to be easily customizable:

- Edit the CSS files to match your branding
- Modify the HTML templates to change the look and feel
- Extend the API with additional endpoints
- Add custom functionality to the client library

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

For support, feature requests, or bug reports, please open an issue on the GitHub repository.
