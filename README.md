# SMART RECYCLEBOT - Web Application

Laravel-based web application for waste classification monitoring and bin status management.

## Features
- Real-time waste classification detection
- Bin fill level monitoring
- Automated notifications
- Analytics and reporting

## Local Setup

### Prerequisites
- PHP 8.1+
- Composer
- MySQL/MariaDB
- Node.js & NPM

### Installation
```bash
# Clone repository
git clone <your-repo-url>
cd smartrecyclebot

# Install dependencies
composer install
npm install

# Environment setup
cp .env.example .env
php artisan key:generate

# Database
php artisan migrate
php artisan db:seed

# Run application
php artisan serve
npm run dev
```

## Arduino Integration (Local Demo Only)
```bash
# Start serial reader (requires Arduino connected)
php scripts/serial_reader.php
```

## Environment Variables
See `.env.example` for required configuration.

## Deployment
See [DEPLOYMENT.md](DEPLOYMENT.md) for Render deployment instructions.

## Related system components
- Backend Detection Service: https://github.com/Geloyot/smartrecyclebot-python
- Robotic Arm Integration: https://github.com/Geloyot/smartrecyclebot-robotic-arm-integration
