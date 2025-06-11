# Local Testing Environment for Link Issuance System

This local testing environment allows you to preview and customize the front-facing pages of the Link Issuance System without having to upload files to Hostinger each time.

## Features

- Preview all front-facing pages (expired, error, success) in one place
- Test responsive design with desktop, tablet, and mobile views
- Make changes to the pages and see them in real-time
- No need for valid tokens or active links to test the pages

## Getting Started

### Starting the Local Server

1. Open a terminal in the project directory
2. Run the following command:

```bash
./start-server.sh
```

Or alternatively:

```bash
php start-local-server.php
```

3. The server will start at http://localhost:8000

### Accessing the Preview Tool

Once the server is running, open your browser and navigate to:

```
http://localhost:8000/preview.php
```

This will open the preview tool, which allows you to:

- Switch between different pages (expired, error, success)
- Test responsive design with desktop, tablet, and mobile views

### Direct Page URLs

You can also access the pages directly:

- Expired Page: http://localhost:8000/preview.php?page=expired
- Error Page: http://localhost:8000/preview.php?page=error
- Success Page: http://localhost:8000/preview.php?page=success

## Customizing Pages

### CSS Styles

The new design uses a separate CSS file:

```
access/new-styles.css
```

You can modify this file to change the appearance of the pages. The changes will be reflected immediately when you refresh the preview.

### Page Templates

The page templates are located in the `access` directory:

- `access/expired.php` - Shown when a link is expired
- `access/error.php` - Shown when an invalid token is provided
- `access/success.php` - Shown when a valid token is provided

You can modify these files to change the content and structure of the pages.

## Assets

The design uses several SVG assets located in the `assets/img` directory:

- `JalanJourneyLogo.svg` - Company logo
- `SadMascot.svg` - Sad mascot for error pages
- `Stars.svg` - Stars background for the header
- `Vines.svg` - Vines border for the header
- `Person1.svg`, `Person2.svg`, `Person3.svg` - Character images
- `JumpingMascot.svg` - Happy mascot character

## Deploying to Production

After you're satisfied with your changes, you can upload the modified files to your Hostinger server:

1. Upload the modified CSS file: `access/new-styles.css`
2. Upload the modified page templates: `access/expired.php`, `access/error.php`, etc.
3. Make sure all the required assets are also uploaded to the server

## Troubleshooting

If you encounter any issues with the local server:

1. Make sure PHP is installed and available in your PATH
2. Check that the port 8000 is not already in use by another application
3. If you get permission errors, make sure the script is executable: `chmod +x start-server.sh`
