# Python Virtual Environment Setup for Remote Server

## Quick Setup Instructions

### Step 1: Upload Files to Remote Server
Upload these files to your remote server:
- `setup_python_venv.sh` - Virtual environment setup script
- `test_python_env.py` - Environment test script
- `requirements.txt` - Python package requirements
- `planning_engine/` - Python optimization engine folder

### Step 2: Connect to Remote Server
```bash
ssh your-username@your-server.com
cd /path/to/your/website/
```

### Step 3: Run Setup Script
```bash
# Make sure you're in the jury planner directory
chmod +x setup_python_venv.sh
./setup_python_venv.sh
```

### Step 4: Test the Environment
```bash
# Activate the virtual environment
source venv/bin/activate

# Run the test script
python3 test_python_env.py

# Deactivate when done
deactivate
```

### Step 5: Update PHP to Use Virtual Environment

Your PHP code should be updated to use the virtual environment Python. Here's what needs to be changed:

#### Option A: Update the Python path in optimization_interface.php
```php
// Instead of just 'python3', use the full path to venv python
$pythonPath = __DIR__ . '/../venv/bin/python3';
$command = "{$pythonPath} {$this->pythonScriptPath} {$dataFile} {$solutionFile}";
```

#### Option B: Create a wrapper script
Create `run_python_optimization.sh`:
```bash
#!/bin/bash
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/venv/bin/activate"
python3 "$@"
deactivate
```

Then use this wrapper in PHP:
```php
$command = __DIR__ . "/../run_python_optimization.sh {$this->pythonScriptPath} {$dataFile} {$solutionFile}";
```

## Troubleshooting

### Python3 Not Found
If you get "Python3 not found", install it:

**Ubuntu/Debian:**
```bash
sudo apt update
sudo apt install python3 python3-venv python3-pip
```

**CentOS/RHEL:**
```bash
sudo yum install python3 python3-venv python3-pip
```

**cPanel/Shared Hosting:**
- Check if Python 3 is available in your control panel
- Look for "Python App" or "Python Selector"
- Contact your hosting provider if Python 3 is not available

### Permission Issues
```bash
# Make sure directories are writable
chmod 755 /path/to/jury_planner/
chmod +x setup_python_venv.sh
```

### OR-Tools Installation Fails
If OR-Tools fails to install, try:
```bash
# Activate virtual environment first
source venv/bin/activate

# Try installing with specific version
pip install ortools==9.5.2237

# Or try a different version
pip install ortools==9.4.1874
```

### Memory Issues During Installation
If installation fails due to memory:
```bash
# Use pip with no cache
pip install --no-cache-dir ortools numpy pandas
```

### Verify Installation Success
```bash
# Activate environment
source venv/bin/activate

# Check Python path
which python3

# Check installed packages
pip list

# Test imports
python3 -c "import ortools; import numpy; import pandas; print('All packages working!')"
```

## Testing the Integration

### Test 1: Python Environment
```bash
cd /your/website/path/
source venv/bin/activate
python3 test_python_env.py
```

### Test 2: Shell Exec Availability
Upload and run `test_shell_exec.php`:
```
https://your-domain.com/test_shell_exec.php
```

### Test 3: Full Integration
1. Go to your constraint editor: `https://your-domain.com/constraint_editor.php`
2. Try running "Validate Constraints"
3. Try running "Preview Optimization"
4. Check for any error messages

## Common Hosting Provider Instructions

### cPanel Hosting
1. **File Manager**: Upload files through cPanel File Manager
2. **Terminal**: Use cPanel Terminal if available
3. **Python**: Check if Python 3 is available in "Software" section
4. **Shell Access**: Enable SSH if available in "Security" section

### Shared Hosting (Limited Access)
If you don't have SSH access:
1. Upload files via FTP/File Manager
2. Use cPanel Terminal or contact support
3. Some shared hosts don't allow custom Python environments
4. In this case, the PHP fallback optimizer will be used

### VPS/Dedicated Server
Full access - follow the standard instructions above.

## After Successful Setup

Once everything is working:
1. ✅ Python virtual environment is created
2. ✅ All required packages are installed  
3. ✅ OR-Tools optimization engine is working
4. ✅ PHP can execute Python scripts
5. ✅ Database connectivity is configured

Your jury planner will automatically:
- Use Python optimization when available
- Fall back to PHP optimization if Python fails
- Provide clear status messages to users
- Work reliably in both scenarios

## Support

If you encounter issues:
1. Check the error logs on your server
2. Run the test scripts to identify the problem
3. Verify PHP settings allow `shell_exec()`
4. Contact your hosting provider if needed

The system is designed to work with or without Python, so even if setup fails, the basic functionality will still work using the PHP fallback optimizer.
