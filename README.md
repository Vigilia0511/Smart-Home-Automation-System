# Smart Home Automation System

A comprehensive IoT-based smart home automation platform integrating web applications, mobile applications, and Raspberry Pi hardware control for seamless home device management.

## Description

The Smart Home Automation System is a full-stack solution designed to provide remote control and monitoring of home appliances through multiple interfaces. This project combines web technologies, mobile applications, embedded systems, and database management to create a scalable and secure smart home ecosystem. It enables users to control lights, fans, security systems, and other home devices from anywhere with real-time feedback and energy monitoring capabilities.

## Features

- Real-time device control via web and mobile interfaces
- Raspberry Pi-based hardware integration for GPIO control
- Multi-user authentication and session management
- Energy consumption monitoring and analytics
- Push notifications for device state changes
- Secure user registration and login system
- Cross-platform mobile application (Android APK/AAB included)
- Remote access capabilities from anywhere with internet connection
- Database-driven device state persistence
- Responsive web interface for desktop and mobile browsers

## Technology Stack

### Backend
- PHP (primary backend language)
- MySQL/MariaDB (database management)

### Hardware Control
- Python 3.x (Raspberry Pi automation scripts)
- Raspberry Pi GPIO libraries

### Mobile Application
- Android application (APK and AAB formats provided)
- Java/Kotlin (assumed based on Android build artifacts)

### Frontend
- HTML5/CSS3/JavaScript
- Responsive web design

### Database
- SQL (structured data storage)

## Project Structure

```
Smart-Home-Automation-System/
├── database/                      # Database schemas and migration scripts
│   ├── schema.sql                # Database structure definitions
│   └── seed_data.sql             # Sample data for testing
│
├── imageSCMobileApp/             # Mobile application assets and resources
│   ├── screenshots/              # App screenshots for documentation
│   └── icons/                    # Application icons and graphics
│
├── raspberryPythonSC/            # Raspberry Pi control scripts
│   ├── gpio_control.py           # GPIO pin management
│   ├── device_manager.py         # Device state management
│   ├── sensor_reader.py          # Sensor data collection
│   └── main.py                   # Main automation controller
│
├── smarthomeSCWebApp/            # Web application directory
│   ├── assets/                   # Static files (CSS, JS, images)
│   ├── config/                   # Configuration files
│   ├── controllers/              # Business logic controllers
│   ├── models/                   # Data models
│   ├── views/                    # HTML templates
│   ├── api/                      # REST API endpoints
│   └── index.php                 # Application entry point
│
├── android.keystore              # Android signing keystore
├── finaltest.aab                 # Android App Bundle (for Play Store)
├── finaltest.apk                 # Android APK (direct installation)
└── README.md                     # Project documentation
```

## Requirements

### Hardware Requirements
- Raspberry Pi 3/4 (recommended)
- Relay modules (for device switching)
- GPIO-compatible sensors (temperature, motion, etc.)
- Power supply (5V 3A for Raspberry Pi)
- Home appliances to control (lights, fans, etc.)

### Software Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher / MariaDB 10.3+
- Python 3.7 or higher
- Composer (PHP dependency manager)
- Apache/Nginx web server
- Android 6.0+ (for mobile app)

### Python Libraries
- RPi.GPIO (Raspberry Pi GPIO control)
- requests (HTTP communication)
- mysql-connector-python (database connectivity)

## Installation

### 1. Clone the Repository

```bash
git clone https://github.com/Vigilia0511/Smart-Home-Automation-System.git
cd Smart-Home-Automation-System
```

### 2. Database Setup

```bash
# Login to MySQL
mysql -u root -p

# Create database
CREATE DATABASE smart_home_db;

# Import schema
mysql -u root -p smart_home_db < database/schema.sql

# (Optional) Import sample data
mysql -u root -p smart_home_db < database/seed_data.sql
```

### 3. Web Application Configuration

```bash
cd smarthomeSCWebApp

# Copy configuration template
cp config/config.example.php config/config.php

# Edit configuration file with your database credentials
nano config/config.php
```

Update the following in `config/config.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'smart_home_db');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
```

### 4. Web Server Setup

#### Apache Configuration

```bash
# Enable required modules
sudo a2enmod rewrite
sudo a2enmod ssl

# Create virtual host configuration
sudo nano /etc/apache2/sites-available/smarthome.conf
```

Add the following configuration:

```apache
<VirtualHost *:80>
    ServerName smarthome.local
    DocumentRoot /path/to/Smart-Home-Automation-System/smarthomeSCWebApp
    
    <Directory /path/to/Smart-Home-Automation-System/smarthomeSCWebApp>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/smarthome-error.log
    CustomLog ${APACHE_LOG_DIR}/smarthome-access.log combined
</VirtualHost>
```

```bash
# Enable site and restart Apache
sudo a2ensite smarthome.conf
sudo systemctl restart apache2
```

### 5. Raspberry Pi Setup

```bash
cd raspberryPythonSC

# Install Python dependencies
pip3 install -r requirements.txt

# Configure GPIO permissions
sudo usermod -a -G gpio $USER

# Test GPIO access
python3 -c "import RPi.GPIO as GPIO; print('GPIO access successful')"
```

### 6. Mobile Application Installation

#### Option A: Install APK directly
```bash
# Transfer finaltest.apk to Android device
adb install finaltest.apk
```

#### Option B: Deploy AAB to Play Store
- Upload `finaltest.aab` to Google Play Console
- Follow Play Store submission guidelines

## Configuration

### Environment Variables

Create a `.env` file in the web application root:

```env
# Database Configuration
DB_HOST=localhost
DB_PORT=3306
DB_NAME=smart_home_db
DB_USER=smart_home_user
DB_PASSWORD=secure_password

# Application Settings
APP_ENV=production
APP_DEBUG=false
APP_URL=http://smarthome.local

# Raspberry Pi Settings
RPI_HOST=192.168.1.100
RPI_PORT=5000
RPI_API_KEY=your_secure_api_key

# Security
SESSION_LIFETIME=1440
ENCRYPTION_KEY=generate_32_character_key_here

# Email Configuration (for notifications)
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=your_email@gmail.com
SMTP_PASSWORD=your_app_password
```

### Raspberry Pi Configuration

Edit `raspberryPythonSC/config.json`:

```json
{
  "database": {
    "host": "192.168.1.50",
    "port": 3306,
    "username": "smart_home_user",
    "password": "secure_password",
    "database": "smart_home_db"
  },
  "gpio_pins": {
    "relay_1": 17,
    "relay_2": 27,
    "relay_3": 22,
    "relay_4": 23
  },
  "sensors": {
    "temperature": 4,
    "motion": 18
  },
  "api": {
    "port": 5000,
    "host": "0.0.0.0"
  }
}
```

## Usage

### Starting the System

#### 1. Start Web Server
```bash
# Apache is already running after setup
# For development with PHP built-in server:
cd smarthomeSCWebApp
php -S localhost:8000
```

#### 2. Start Raspberry Pi Controller
```bash
cd raspberryPythonSC
python3 main.py
```

#### 3. Access Web Interface
- Open browser and navigate to `http://localhost:8000` or your configured domain
- Register a new account or login with existing credentials

#### 4. Launch Mobile App
- Open the Smart Home app on your Android device
- Login with your web application credentials
- Grant necessary permissions (network access, notifications)

### Basic Operations

#### Controlling Devices via Web Interface
1. Login to the web application
2. Navigate to the "Devices" dashboard
3. Click on any device card to toggle ON/OFF
4. View real-time status updates

#### Controlling Devices via Mobile App
1. Open the mobile application
2. Tap on device icons to control
3. Use quick action buttons for common tasks
4. View energy consumption graphs

#### Setting Up Automation Rules
1. Go to "Automation" section
2. Click "Create New Rule"
3. Define trigger conditions (time, sensor data)
4. Select actions to execute
5. Save and activate the rule

## API Endpoints

### Authentication

#### Register User
```http
POST /api/auth/register
Content-Type: application/json

{
  "username": "john_doe",
  "email": "john@example.com",
  "password": "SecurePass123!",
  "password_confirm": "SecurePass123!"
}
```

#### Login
```http
POST /api/auth/login
Content-Type: application/json

{
  "email": "john@example.com",
  "password": "SecurePass123!"
}

Response:
{
  "success": true,
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "user": {
    "id": 1,
    "username": "john_doe",
    "email": "john@example.com"
  }
}
```

### Device Control

#### Get All Devices
```http
GET /api/devices
Authorization: Bearer {token}

Response:
{
  "success": true,
  "devices": [
    {
      "id": 1,
      "name": "Living Room Light",
      "type": "light",
      "status": "on",
      "gpio_pin": 17
    }
  ]
}
```

#### Control Device
```http
POST /api/devices/{id}/control
Authorization: Bearer {token}
Content-Type: application/json

{
  "action": "toggle",
  "value": "on"
}

Response:
{
  "success": true,
  "device_id": 1,
  "status": "on",
  "timestamp": "2025-01-05T10:30:00Z"
}
```

#### Get Device Status
```http
GET /api/devices/{id}/status
Authorization: Bearer {token}

Response:
{
  "success": true,
  "device": {
    "id": 1,
    "status": "on",
    "last_updated": "2025-01-05T10:30:00Z",
    "power_consumption": "45W"
  }
}
```

### Energy Monitoring

#### Get Energy Statistics
```http
GET /api/energy/stats?period=day
Authorization: Bearer {token}

Response:
{
  "success": true,
  "period": "day",
  "total_consumption": "12.5 kWh",
  "devices": [
    {
      "device_id": 1,
      "name": "Living Room Light",
      "consumption": "2.3 kWh"
    }
  ]
}
```

## Database Schema

### Users Table
```sql
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    INDEX idx_email (email),
    INDEX idx_username (username)
);
```

### Devices Table
```sql
CREATE TABLE devices (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    type ENUM('light', 'fan', 'ac', 'door', 'camera', 'sensor') NOT NULL,
    gpio_pin INT,
    status ENUM('on', 'off') DEFAULT 'off',
    room VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status)
);
```

### Device Logs Table
```sql
CREATE TABLE device_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    device_id INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    previous_state VARCHAR(20),
    new_state VARCHAR(20),
    triggered_by INT,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE CASCADE,
    FOREIGN KEY (triggered_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_device_id (device_id),
    INDEX idx_timestamp (timestamp)
);
```

### Energy Consumption Table
```sql
CREATE TABLE energy_consumption (
    id INT PRIMARY KEY AUTO_INCREMENT,
    device_id INT NOT NULL,
    consumption_kwh DECIMAL(10,3) NOT NULL,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE CASCADE,
    INDEX idx_device_id (device_id),
    INDEX idx_recorded_at (recorded_at)
);
```

## Security Considerations

### Authentication & Authorization
- Passwords are hashed using bcrypt with salt rounds
- Session tokens expire after configured timeout
- API endpoints require valid authentication tokens
- User roles and permissions are enforced at the application level

### Data Protection
- All sensitive configuration stored in `.env` files (excluded from version control)
- Database credentials should never be committed to repository
- Use HTTPS/TLS for all production deployments
- API keys for Raspberry Pi communication should be rotated regularly

### Network Security
- Configure firewall rules to restrict Raspberry Pi API access
- Use VPN for remote access in production environments
- Implement rate limiting on API endpoints
- Enable CSRF protection on web forms

### Raspberry Pi Hardening
```bash
# Change default password
passwd

# Disable SSH password authentication (use keys only)
sudo nano /etc/ssh/sshd_config
# Set: PasswordAuthentication no

# Enable firewall
sudo apt-get install ufw
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw allow 5000/tcp
sudo ufw enable

# Keep system updated
sudo apt-get update && sudo apt-get upgrade -y
```

## Screenshots

### Web Application Dashboard
![Dashboard Placeholder](./imageSCMobileApp/screenshots/web-dashboard.png)
*Main control panel showing all connected devices and their current status*

### Mobile Application Interface
![Mobile App Placeholder](./imageSCMobileApp/screenshots/mobile-home.png)
*Android app home screen with quick device controls*

### Energy Monitoring
![Energy Stats Placeholder](./imageSCMobileApp/screenshots/energy-stats.png)
*Real-time energy consumption analytics and historical data*

### Device Control Panel
![Control Panel Placeholder](./imageSCMobileApp/screenshots/device-control.png)
*Individual device management with scheduling options*

## Deployment

### Production Web Server (Ubuntu/Debian)

```bash
# Update system
sudo apt-get update && sudo apt-get upgrade -y

# Install LAMP stack
sudo apt-get install apache2 mysql-server php php-mysql php-curl php-json -y

# Secure MySQL installation
sudo mysql_secure_installation

# Clone repository to web root
cd /var/www/html
sudo git clone https://github.com/Vigilia0511/Smart-Home-Automation-System.git smarthome
sudo chown -R www-data:www-data smarthome

# Configure permissions
sudo chmod -R 755 smarthome
sudo chmod -R 775 smarthome/storage
sudo chmod -R 775 smarthome/cache

# Import database
mysql -u root -p < smarthome/database/schema.sql

# Configure Apache virtual host (as shown in Installation section)

# Enable SSL (recommended for production)
sudo apt-get install certbot python3-certbot-apache
sudo certbot --apache -d yourdomain.com
```

### Raspberry Pi Production Setup

```bash
# Create systemd service for auto-start
sudo nano /etc/systemd/system/smarthome-controller.service
```

Add the following content:

```ini
[Unit]
Description=Smart Home Controller Service
After=network.target mysql.service

[Service]
Type=simple
User=pi
WorkingDirectory=/home/pi/Smart-Home-Automation-System/raspberryPythonSC
ExecStart=/usr/bin/python3 /home/pi/Smart-Home-Automation-System/raspberryPythonSC/main.py
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

```bash
# Enable and start service
sudo systemctl daemon-reload
sudo systemctl enable smarthome-controller.service
sudo systemctl start smarthome-controller.service

# Check status
sudo systemctl status smarthome-controller.service
```

### Docker Deployment (Optional)

```bash
# Create Dockerfile for web application
cat > smarthomeSCWebApp/Dockerfile << EOF
FROM php:8.1-apache
RUN docker-php-ext-install mysqli pdo pdo_mysql
COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html
EXPOSE 80
EOF

# Build and run
docker build -t smarthome-web ./smarthomeSCWebApp
docker run -d -p 80:80 --name smarthome-web-container smarthome-web
```

## Troubleshooting

### Web Application Issues

#### Error: Database Connection Failed
```bash
# Verify MySQL service is running
sudo systemctl status mysql

# Check database credentials in config.php
# Test connection manually
mysql -h localhost -u smart_home_user -p smart_home_db
```

#### Error: Permission Denied (PHP)
```bash
# Fix file permissions
sudo chown -R www-data:www-data /var/www/html/smarthome
sudo chmod -R 755 /var/www/html/smarthome
```

### Raspberry Pi Issues

#### GPIO Permission Error
```bash
# Add user to gpio group
sudo usermod -a -G gpio $USER
# Logout and login again

# Verify permissions
groups $USER
```

#### Python Module Not Found
```bash
# Reinstall dependencies
pip3 install --upgrade -r requirements.txt

# If pip3 not found
sudo apt-get install python3-pip
```

#### Devices Not Responding
```bash
# Check GPIO pin assignments in config.json
# Test GPIO manually
python3 -c "import RPi.GPIO as GPIO; GPIO.setmode(GPIO.BCM); GPIO.setup(17, GPIO.OUT); GPIO.output(17, GPIO.HIGH)"

# Check relay module connections
# Verify power supply is adequate (3A minimum for Pi + relays)
```

### Mobile Application Issues

#### App Not Connecting to Server
- Verify the device and server are on the same network (or proper port forwarding is configured)
- Check API URL configuration in app settings
- Ensure firewall rules allow traffic on configured ports
- Verify SSL certificate if using HTTPS

#### Login Failed
- Clear app cache and data
- Verify credentials work on web interface
- Check server logs for authentication errors

### Network Issues

#### Cannot Access Web Interface Remotely
```bash
# Configure port forwarding on router
# Port 80 (HTTP) and 443 (HTTPS) to web server IP

# Use dynamic DNS service for changing public IPs
# Services: No-IP, DuckDNS, Cloudflare

# Verify firewall rules
sudo ufw status
```

### Debugging Tips

Enable debug mode in `config.php`:
```php
define('DEBUG_MODE', true);
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

View logs:
```bash
# Apache logs
sudo tail -f /var/log/apache2/error.log

# Raspberry Pi controller logs
tail -f /var/log/smarthome-controller.log

# MySQL query log
sudo tail -f /var/log/mysql/error.log
```

## Roadmap

### Phase 1 (Current)
- [x] Basic device control via web interface
- [x] User authentication and session management
- [x] Android mobile application
- [x] Raspberry Pi GPIO integration
- [x] Real-time device status updates

### Phase 2 (In Progress)
- [ ] Voice control integration (Google Assistant, Alexa)
- [ ] Advanced scheduling and automation rules
- [ ] Energy consumption analytics dashboard
- [ ] Multi-room management
- [ ] iOS mobile application

### Phase 3 (Planned)
- [ ] Machine learning for usage pattern prediction
- [ ] Integration with third-party smart home devices
- [ ] Video surveillance module
- [ ] Weather-based automation triggers
- [ ] Multi-language support

### Phase 4 (Future)
- [ ] Mesh network support for extended range
- [ ] Offline mode with local processing
- [ ] Scene management (one-touch control multiple devices)
- [ ] Guest access with limited permissions
- [ ] Integration with home security systems

## Future Improvements

### Technical Enhancements
- Implement WebSocket for real-time bidirectional communication
- Migrate to REST API with JWT authentication
- Add Docker containerization for easier deployment
- Implement automated testing (PHPUnit, PyTest)
- Add CI/CD pipeline (GitHub Actions)

### Feature Additions
- Geofencing for location-based automation
- Voice command processing with natural language understanding
- Integration with smart meters for accurate energy tracking
- Maintenance alerts and diagnostics
- Backup and restore functionality

### User Experience
- Dark mode for mobile and web interfaces
- Customizable dashboard widgets
- Advanced data visualization with charts
- Push notifications for important events
- Widget support for quick device control

### Security Improvements
- Two-factor authentication (2FA)
- End-to-end encryption for communication
- Audit logs for all system actions
- Regular security vulnerability scanning
- Compliance with IoT security standards

## Contributing

Contributions are welcome and greatly appreciated. Please follow these guidelines:

### How to Contribute

1. Fork the repository
2. Create a feature branch
   ```bash
   git checkout -b feature/amazing-feature
   ```
3. Make your changes following the coding standards
4. Commit your changes
   ```bash
   git commit -m "Add amazing feature: description"
   ```
5. Push to your branch
   ```bash
   git push origin feature/amazing-feature
   ```
6. Open a Pull Request with detailed description

### Coding Standards

#### PHP
- Follow PSR-12 coding standard
- Use meaningful variable and function names
- Add PHPDoc comments for all classes and methods
- Keep functions small and focused

#### Python
- Follow PEP 8 style guide
- Use type hints where applicable
- Write docstrings for all functions and classes
- Maximum line length of 100 characters

#### General
- Write clear commit messages (present tense, imperative mood)
- Update documentation for new features
- Add tests for new functionality
- Keep pull requests focused on a single feature or fix

### Code Review Process

All submissions require review before merging:
1. Automated tests must pass
2. Code must meet quality standards
3. At least one maintainer approval required
4. No merge conflicts with main branch

### Bug Reports

When reporting bugs, please include:
- Operating system and version
- PHP/Python version
- Steps to reproduce the issue
- Expected vs actual behavior
- Error messages and logs
- Screenshots if applicable

### Feature Requests

For new features, please:
- Check if the feature is already planned in the Roadmap
- Describe the use case and benefits
- Consider implementation complexity
- Be open to discussion and feedback

## License

This project is licensed under the MIT License. See below for details:

```
MIT License

Copyright (c) 2025 Vigilia0511

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
```

## Author

**Vigilia0511**
- GitHub: [@Vigilia0511](https://github.com/Vigilia0511)
- Repository: [Smart-Home-Automation-System](https://github.com/Vigilia0511/Smart-Home-Automation-System)

## Acknowledgments

- Raspberry Pi Foundation for excellent hardware and documentation
- PHP community for robust web development tools
- Android development community for mobile app resources
- Contributors and testers who helped improve this project
- Open source libraries and frameworks used in this project

## Support

If you encounter any issues or have questions:

1. Check the [Troubleshooting](#troubleshooting) section
2. Search existing [GitHub Issues](https://github.com/Vigilia0511/Smart-Home-Automation-System/issues)
3. Create a new issue with detailed information
4. Join community discussions for general help

## Disclaimer

This software is provided as-is without warranty. Users are responsible for:
- Proper electrical safety when connecting hardware
- Securing their network and devices
- Compliance with local regulations regarding home automation
- Regular backups of configuration and data

The authors and contributors are not liable for any damage, injury, or loss resulting from the use of this software.

---

**Last Updated:** January 2025

**Version:** 1.0.0

For more information, visit the [repository](https://github.com/Vigilia0511/Smart-Home-Automation-System).
