# 🎯 Plesk Deployment Setup Summary

## ✅ What's Been Configured

### Repository Structure
```
Local Development → GitHub → Plesk Production
     ↓               ↓            ↓
./deploy.sh    Auto-sync    /home/httpd/vhosts/jury2025.useless.nl/
```

### Plesk Directory Layout
```
/home/httpd/vhosts/jury2025.useless.nl/
├── httpdocs/                   ← Direct symlink to php_interface (ROOT ACCESS)
├── jury_planner/               ← Full repository (PRIVATE)
│   └── php_interface/          ← PHP interface source
└── git/jury2025.git/           ← Bare repository + hooks (PRIVATE)
```

## 🚀 Next Steps for Server Setup

### 1. SSH to Your Plesk Server
```bash
ssh jury2025@jury2025.useless.nl
```

### 2. Run the Setup Script
```bash
# The script is now ready on GitHub
wget https://github.com/svdleer/jury_planner/raw/main/production-setup.sh
chmod +x production-setup.sh
./production-setup.sh
```

### 3. First Deployment
```bash
# From your local machine
./deploy.sh "Initial Plesk deployment"
```

## 🌍 Expected URLs After Setup

- **Main Interface**: https://jury2025.useless.nl/
- **Teams**: https://jury2025.useless.nl/teams.php
- **Matches**: https://jury2025.useless.nl/matches.php  
- **DB Test**: https://jury2025.useless.nl/test_connection.php

## 🔐 Security Features

✅ **Source Code Protection**: Full repository stored outside web directory  
✅ **Selective Exposure**: Only PHP interface accessible via web  
✅ **Environment Security**: `.env` files with restricted permissions  
✅ **Plesk Compatibility**: Proper ownership and permission structure  
✅ **Automatic Updates**: Git hooks handle deployment automatically  

## 📋 Deployment Commands

```bash
# Full deployment
./deploy.sh "Your commit message"

# PHP-only deployment  
./deploy-php.sh "Interface updates"

# Manual git push
git push origin main && git push production main
```

## 🛠️ Files Created/Updated

- `production-setup.sh` - Plesk server setup script
- `PLESK_DEPLOYMENT.md` - Detailed Plesk documentation  
- `.github/workflows/deploy.yml` - Updated for Plesk paths
- `README.md` - Updated with Plesk information
- Git remote updated to: `jury2025@jury2025.useless.nl:/home/httpd/vhosts/jury2025.useless.nl/git/jury2025.git`

## 📞 Support

If you encounter issues:
1. Check the detailed guide: `PLESK_DEPLOYMENT.md`
2. Verify SSH access to your server
3. Ensure Plesk permissions are correct
4. Test database connection via the web interface

Your jury planning system is now ready for professional Plesk hosting! 🏊‍♂️🚀
