# SSH Access Setup for Plesk Server

This guide helps you set up SSH access to your Plesk server for manual checks and troubleshooting.

## 🔑 SSH Key Setup

### 1. Generate SSH Key Pair (if you don't have one)

```bash
# Generate a new SSH key pair
ssh-keygen -t ed25519 -C "your-email@example.com"

# Or use RSA if ed25519 isn't supported
ssh-keygen -t rsa -b 4096 -C "your-email@example.com"

# Save to default location: ~/.ssh/id_ed25519 (or id_rsa)
# Set a passphrase for security (optional but recommended)
```

### 2. Copy Your Public Key

```bash
# Display your public key
cat ~/.ssh/id_ed25519.pub
# or
cat ~/.ssh/id_rsa.pub

# Copy the entire output (starts with ssh-ed25519 or ssh-rsa)
```

## 🖥️ Server Configuration

### Option A: Via Plesk Panel

1. **Login to Plesk**: https://jury2025.useless.nl:8443
2. **Go to**: Websites & Domains → jury2025.useless.nl
3. **Web Hosting Access**: Click "Web Hosting Access"
4. **SSH Access**: Enable SSH access for the subscription
5. **SSH Keys**: Add your public key to authorized keys

### Option B: Via Command Line (if you have access)

```bash
# SSH to server (using password first time)
ssh jury2025@vps.serial.nl

# Create .ssh directory if it doesn't exist
mkdir -p ~/.ssh
chmod 700 ~/.ssh

# Add your public key to authorized_keys
echo "your-public-key-here" >> ~/.ssh/authorized_keys
chmod 600 ~/.ssh/authorized_keys

# Exit and test key-based login
exit
ssh jury2025@vps.serial.nl
```

## 🔧 Useful SSH Commands for Checks

### Quick Status Check
```bash
# Connect and check deployment status
ssh jury2025@vps.serial.nl "ls -la /home/httpd/vhosts/jury2025.useless.nl/httpdocs/"
```

### Environment Check
```bash
# Check environment file
ssh jury2025@vps.serial.nl "head -5 /home/httpd/vhosts/jury2025.useless.nl/httpdocs/.env"
```

### Error Log Check
```bash
# Check PHP error logs
ssh jury2025@vps.serial.nl "tail -n 20 /var/www/vhosts/jury2025.useless.nl/logs/error_log"
```

### Git Status Check
```bash
# Check git repository status
ssh jury2025@vps.serial.nl "cd /home/httpd/vhosts/jury2025.useless.nl/git/jury2025.git && git log --oneline -5"
```

### Permission Check
```bash
# Check file permissions
ssh jury2025@vps.serial.nl "ls -la /home/httpd/vhosts/jury2025.useless.nl/httpdocs/ | head -10"
```

## 🚀 SSH Deployment Helpers

### Create Handy Alias
Add to your `~/.bashrc` or `~/.zshrc`:

```bash
# Jury server alias
alias jury-ssh="ssh jury2025@vps.serial.nl"
alias jury-logs="ssh jury2025@vps.serial.nl 'tail -f /var/www/vhosts/jury2025.useless.nl/logs/error_log'"
alias jury-status="ssh jury2025@vps.serial.nl 'ls -la /home/httpd/vhosts/jury2025.useless.nl/httpdocs/'"
```

### Quick Check Script
```bash
#!/bin/bash
# save as check-jury-server.sh

echo "🔍 Checking Jury Planner Server Status..."

echo "📁 Files in httpdocs:"
ssh jury2025@vps.serial.nl "ls -la /home/httpd/vhosts/jury2025.useless.nl/httpdocs/ | head -10"

echo ""
echo "🔧 Environment status:"
ssh jury2025@vps.serial.nl "test -f /home/httpd/vhosts/jury2025.useless.nl/httpdocs/.env && echo '✅ .env exists' || echo '❌ .env missing'"

echo ""
echo "📊 Recent git commits:"
ssh jury2025@vps.serial.nl "cd /home/httpd/vhosts/jury2025.useless.nl/git/jury2025.git && git log --oneline -3"

echo ""
echo "🌐 Web test:"
curl -s -o /dev/null -w "%{http_code}" https://jury2025.useless.nl/test_connection.php
echo " - test_connection.php"
```

## 🛠️ Troubleshooting Commands

### If Database Connection Fails
```bash
# Check database connectivity from server
ssh jury2025@vps.serial.nl "php -r 'include \"/home/httpd/vhosts/jury2025.useless.nl/httpdocs/test_connection.php\";'"
```

### If Files Are Missing
```bash
# Re-run deployment manually
ssh jury2025@vps.serial.nl "cd /home/httpd/vhosts/jury2025.useless.nl/git/jury2025.git && ./hooks/post-receive"
```

### Check Disk Space
```bash
# Check available space
ssh jury2025@vps.serial.nl "df -h /home/httpd/vhosts/jury2025.useless.nl/"
```

## 🔐 Security Best Practices

1. **Use SSH Keys**: Never use password authentication for automated tasks
2. **Limit Access**: Only enable SSH for specific users/IPs if possible
3. **Monitor Logs**: Check SSH access logs regularly
4. **Use Passphrases**: Protect your private keys with passphrases
5. **Regular Updates**: Keep your SSH client and server updated

## 📞 Common Issues

### "Permission Denied"
- Check if SSH is enabled in Plesk
- Verify your public key is correctly added
- Check file permissions on server

### "Connection Refused"
- Verify SSH port (usually 22)
- Check firewall settings
- Confirm server is accessible

### "Host Key Verification Failed"
- Remove old host key: `ssh-keygen -R vps.serial.nl`
- Accept new host key on first connection

Remember: SSH is for manual checks and troubleshooting. Your normal deployment workflow should still use `./deploy.sh` for automatic git-based deployment! 🚀
