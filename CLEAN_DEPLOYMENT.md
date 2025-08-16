# Clean Plesk Deployment Guide

This guide covers deploying the Jury Planner to a Plesk-managed server with a clean, direct approach.

## Prerequisites

- Plesk panel access with file manager or SSH
- Git repository access
- Domain configured in Plesk
- MySQL database with proper credentials

## Clean Deploy to Production (jury2025.useless.nl)

### 1. Clean Setup on Production Server

```bash
# SSH to your server as the domain user
ssh jury2025@vps.serial.nl

# Upload the clean setup script
# (Or use Plesk file manager to upload clean-plesk-setup.sh)

# Run the clean setup (removes all previous attempts)
chmod +x clean-plesk-setup.sh
./clean-plesk-setup.sh
```

This script will:
- ✅ Remove ALL existing deployments (including symlinks)
- ✅ Create a fresh git repository
- ✅ Set up direct file deployment to httpdocs root
- ✅ Configure proper permissions
- ✅ Clean up non-web files automatically

### 2. Local Deployment

From your local machine:

```bash
# Deploy to the clean production setup
./deploy.sh "Clean deployment to httpdocs root"
```

### 3. Verification

Visit these URLs to verify deployment:
- **Main Interface**: https://jury2025.useless.nl/
- **Database Test**: https://jury2025.useless.nl/test_connection.php
- **Dashboard**: https://jury2025.useless.nl/mnc_dashboard.php

## 🏗️ Clean Architecture (No Symlinks!)

```
/home/httpd/vhosts/jury2025.useless.nl/
├── httpdocs/                           # Direct PHP files (no symlinks!)
│   ├── index.php                       # Main entry point
│   ├── mnc_dashboard.php               # Dashboard
│   ├── test_connection.php             # Database test
│   ├── config/                         # Configuration
│   ├── includes/                       # PHP classes
│   └── .env                           # Environment (secure)
└── git/
    └── jury2025.git/                   # Git repository
        └── hooks/
            └── post-receive            # Clean deployment hook
```

## 🔧 How Clean Deployment Works

When you push to production:

1. **Direct Checkout**: Files are checked out directly to httpdocs
2. **PHP Interface Movement**: Files from php_interface/ are moved to httpdocs root
3. **Cleanup**: Non-web files (Python backend, etc.) are automatically removed
4. **Permissions**: Proper Plesk permissions are set
5. **Environment**: .env file is created from template if needed

### Benefits of Clean Approach

- ✅ **No Symlinks**: Direct files in httpdocs - no confusion
- ✅ **Simple**: Easy to understand and maintain
- ✅ **Plesk Native**: Works perfectly with Plesk hosting
- ✅ **Fast**: Direct file access, no link resolution
- ✅ **Clean**: Only web files in web directory

## 🛠️ Maintenance

### Force Clean Redeployment

If you need to start completely fresh:

```bash
# SSH to server
ssh jury2025@vps.serial.nl

# Run clean setup again
./clean-plesk-setup.sh

# Deploy from local
./deploy.sh "Fresh clean deployment"
```

### Manual File Updates

```bash
# SSH to server
ssh jury2025@vps.serial.nl

# Edit files directly in httpdocs
cd /home/httpd/vhosts/jury2025.useless.nl/httpdocs
nano index.php
```

## 📞 Troubleshooting

### 1. Permission Issues
```bash
# Fix permissions on server
cd /home/httpd/vhosts/jury2025.useless.nl
chown -R jury2025:psacln httpdocs
chmod -R 755 httpdocs
chmod 600 httpdocs/.env
```

### 2. Git Push Issues
```bash
# Reinitialize git repository
rm -rf git
mkdir -p git
cd git
git init --bare jury2025.git
# Re-run clean setup to restore hook
```

### 3. Database Connection
- Check `.env` file in httpdocs root
- Test at: https://jury2025.useless.nl/test_connection.php

## 🎯 Why This Approach?

- **Simplicity**: No complex symlink management
- **Reliability**: Direct files are more predictable
- **Plesk Compatible**: Works naturally with Plesk
- **Maintainable**: Easy to understand and debug
- **Production Ready**: Clean, professional deployment
