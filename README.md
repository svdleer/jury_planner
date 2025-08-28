# PHP Interface for Jury Planner

A modern, responsive web interface for managing water polo matches and jury team assignments, built with PHP, Tailwind CSS, and Alpine.js.

## Features

- **Dashboard**: Overview of teams, matches, and statistics
- **Team Management**: Create, edit, and manage jury teams with weights and contact information
- **Match Management**: Schedule matches, assign jury teams, and track assignments
- **Responsive Design**: Works seamlessly on desktop, tablet, and mobile devices
- **Real-time Feedback**: Toast notifications for user actions
- **Modern UI**: Clean, professional interface with Tailwind CSS

## Technology Stack

- **Backend**: PHP 8+, PDO for MySQL connectivity
- **Frontend**: Tailwind CSS 3.x, Alpine.js 3.x
- **Database**: MySQL 8.0+
- **Architecture**: MVC pattern with modular data access classes

## Installation

1. **Database Setup**: Ensure the MySQL database is set up using the schema from `../database/schema.sql`

2. **Environment Configuration**: Create a `.env` file in the project root:
   ```
   DB_HOST=vps.serial.nl
   DB_PORT=3306
   DB_NAME=mnc_jury
   DB_USER=mnc_jury
   DB_PASS=5j51_hE9r
   APP_ENV=production
   ```

3. **Web Server**: Configure your web server to serve files from the `php_interface` directory

4. **Permissions**: Ensure the web server has read access to all files

## File Structure

```
php_interface/
├── config/
│   ├── app.php          # Application configuration
│   └── database.php     # Database connection
├── includes/
│   ├── layout.php       # Main layout template
│   ├── TeamManager.php  # Team data access class
│   └── MatchManager.php # Match data access class
├── assets/
│   └── css/
│       └── custom.css   # Custom styles
├── index.php            # Dashboard page
├── teams.php            # Team management page
└── matches.php          # Match management page
```

## Usage

### Dashboard
Access the main dashboard at `index.php` to view:
- Team and match statistics
- Upcoming matches
- Quick action buttons

### Team Management
Navigate to `teams.php` to:
- Create new jury teams
- Edit team details (name, weight, contact info)
- Set team availability
- Manage team status (active/inactive)

### Match Management
Use `matches.php` to:
- Schedule new matches
- Filter matches by status, team, or date
- Assign jury teams to matches
- Update match details and status

## Configuration

### App Settings
Edit `config/app.php` to customize:
- Application name and version
- Database connection parameters
- Feature flags
- UI settings

### Database Connection
The application automatically loads database settings from the `.env` file. Alternatively, you can edit `config/database.php` directly.

## API Integration

This PHP interface works alongside the Python backend. While the PHP interface provides direct MySQL access for CRUD operations, you can integrate with the Python planning engine for automated jury assignments.

## Development

### Adding New Features
1. Create new PHP pages following the existing pattern
2. Use the layout template for consistent UI
3. Implement data access through manager classes
4. Follow the Alpine.js pattern for client-side interactivity

### Styling
- Use Tailwind CSS utility classes
- Custom styles go in `assets/css/custom.css`
- Water polo theme colors are predefined in the configuration

### JavaScript
- Minimal JavaScript using Alpine.js for interactivity
- Global utilities available through `window.JuryPlanner`
- Toast notifications for user feedback

## Security Considerations

- Input sanitization through the `h()` function
- Prepared statements for database queries
- CSRF protection should be added for production use
- Session security settings are configured in `config/app.php`

## Browser Support

- Chrome/Edge 90+
- Firefox 88+
- Safari 14+
- Mobile browsers (iOS Safari, Chrome Mobile)

## Contributing

1. Follow the existing code style and patterns
2. Test on multiple browsers and devices
3. Ensure responsive design principles
4. Add appropriate error handling
5. Update documentation as needed

## License

Part of the Water Polo Jury Planner project.
