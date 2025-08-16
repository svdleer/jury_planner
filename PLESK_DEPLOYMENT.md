# Plesk Deployment Guide for Jury Planner

## 🏗️ Plesk Server Architecture

This deployment is specifically designed for Plesk hosting environments where:
- Web files must be in `/home/httpd/vhosts/jury2025.useless.nl/httpdocs/`
- Private files should be stored outside the web-accessible directory
- Git repositories are typically in domain-specific directories

## 📁 Directory Structure

```
/home/httpd/vhosts/jury2025.useless.nl/
├── httpdocs/                           # Symlink to php_interface (root web access)
├── jury_planner/                      # Full repository (private)
│   ├── php_interface/                 # PHP web interface source
│   │   ├── index.php                  # Dashboard
│   │   ├── teams.php                  # Team management
│   │   ├── matches.php                # Match management
│   │   └── test_connection.php        # Database test
│   ├── backend/                       # Python backend (private)
│   ├── planning_engine/               # Planning engine (private)
│   ├── database/                      # Database schema (private)
│   ├── .env                          # Environment config (secure)
│   └── README.md                     # Documentation
└── git/
    └── jury2025.git/                 # Bare git repository
        └── hooks/
            └── post-receive          # Auto-deployment hook
```

## 🚀 Deployment Process

### 1. Initial Server Setup

SSH into your Plesk server and run:

```bash
# Connect to your server
ssh jury2025@jury2025.useless.nl

# Download and run the Plesk setup script
cd ~
wget https://github.com/svdleer/jury_planner/raw/main/production-setup.sh
chmod +x production-setup.sh
./production-setup.sh
```

### 2. Local Deployment

From your local machine:

```bash
# Deploy to both GitHub and Plesk production
./deploy.sh "Your commit message"

# Or deploy just PHP interface changes
./deploy-php.sh "Updated interface"
```

### 3. Verification

Visit these URLs to verify deployment:
- **Main Interface**: https://jury2025.useless.nl/
- **Database Test**: https://jury2025.useless.nl/test_connection.php
- **Team Management**: https://jury2025.useless.nl/teams.php
- **Match Management**: https://jury2025.useless.nl/matches.php

## 🔧 How It Works

### Git Hook Automation

When you push to the production remote:

1. **Receives Push**: Git bare repository receives your code
2. **Triggers Hook**: `post-receive` hook automatically executes
3. **Updates Repository**: Pulls latest code to `/home/httpd/vhosts/jury2025.useless.nl/jury_planner/`
4. **Creates Symlink**: Links entire `php_interface/` directory as `httpdocs/` root
5. **Sets Permissions**: Applies Plesk-compatible permissions
6. **Updates Environment**: Manages `.env` configuration securely

### Security Features

- **Private Repository**: Full source code stored outside web directory
- **Selective Exposure**: Only PHP interface accessible via web
- **Secure Configuration**: `.env` files protected with 600 permissions
- **Symlink Strategy**: Web directory contains only necessary files
- **Plesk Integration**: Compatible with Plesk security model

## 🔒 Security Considerations

### File Permissions
```bash
# Repository permissions
/home/httpd/vhosts/jury2025.useless.nl/jury_planner/     755 (jury2025:psacln)

# Web interface permissions  
/home/httpd/vhosts/jury2025.useless.nl/httpdocs/jury/   755 (jury2025:psacln)

# Sensitive files
.env                                                    600 (jury2025:psacln)
```

### Protected Files
- Backend Python code (not web accessible)
- Database schemas and migrations (not web accessible)
- Environment configuration (secure permissions)
- Git repository (not web accessible)

## 🛠️ Maintenance

### Manual Deployment
```bash
# SSH to server
ssh jury2025@jury2025.useless.nl

# Navigate to repository
cd /home/httpd/vhosts/jury2025.useless.nl/jury_planner

# Pull latest changes
git pull origin main

# Update symlink if needed
ln -sf /home/httpd/vhosts/jury2025.useless.nl/jury_planner/php_interface /home/httpd/vhosts/jury2025.useless.nl/httpdocs
```

### Environment Updates
```bash
# Edit environment file
nano /home/httpd/vhosts/jury2025.useless.nl/jury_planner/.env

# Restart PHP-FPM if needed (Plesk manages this automatically)
```

### Rollback Procedure
```bash
# View git history
cd /home/httpd/vhosts/jury2025.useless.nl/jury_planner
git log --oneline

# Rollback to specific commit
git reset --hard COMMIT_HASH

# Update symlink permissions
chmod -R 755 /home/httpd/vhosts/jury2025.useless.nl/httpdocs/jury
```

## 📞 Troubleshooting

### Common Issues

1. **Permission Denied**
   ```bash
   sudo chown -R jury2025:psacln /home/httpd/vhosts/jury2025.useless.nl/jury_planner
   ```

2. **Symlink Not Working**
   ```bash
   rm -f /home/httpd/vhosts/jury2025.useless.nl/httpdocs
   ln -sf /home/httpd/vhosts/jury2025.useless.nl/jury_planner/php_interface /home/httpd/vhosts/jury2025.useless.nl/httpdocs
   ```

3. **Database Connection Issues**
   - Check `.env` file configuration
   - Verify database credentials
   - Test with: https://jury2025.useless.nl/test_connection.php

4. **Git Push Failures**
   - Verify SSH key authentication
   - Check repository permissions
   - Ensure git repository is properly initialized

## 🎯 Benefits of This Setup

- ✅ **Plesk Compatible**: Works seamlessly with Plesk hosting
- ✅ **Secure**: Private code outside web directory
- ✅ **Automated**: Zero-downtime deployments
- ✅ **Maintainable**: Easy rollbacks and updates
- ✅ **Professional**: Production-ready configuration
- ✅ **Efficient**: Only web assets exposed publicly
