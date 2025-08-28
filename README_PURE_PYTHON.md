# Pure Python Autoplanning System

## Overview

This system replaces the hybrid PHP/Python approach with a **pure Python OR-Tools optimization backend** while keeping the PHP frontend for user interface and data management.

## Architecture

### 🎯 Pure Python Backend (`planning_engine/pure_autoplanner.py`)
- **Complete OR-Tools optimization logic**
- **No PHP dependencies** - all constraint handling in Python
- **CP-SAT and Linear solvers** with automatic selection
- **Clean JSON API** for communication with PHP frontend
- **Comprehensive constraint support** (rest periods, max duties, team conflicts, etc.)

### 🌐 PHP Frontend Interface (`includes/PurePythonAutoplannerService.php`)
- **Thin API layer** between PHP and Python
- **Data marshalling** and format conversion
- **Error handling** and timeout management
- **Simple service calls** with clean result parsing

### 📱 User Interface (`pure_python_autoplanning.html`)
- **Modern web interface** for configuration and testing
- **Real-time optimization** with progress tracking
- **Service testing** and data preview
- **Results visualization** with solver statistics

## Key Benefits

✅ **Pure Python Logic**: All optimization logic in Python using OR-Tools  
✅ **No Mixed Processing**: Eliminates complex PHP/Python constraint translation  
✅ **Clean Separation**: PHP handles UI/data, Python handles optimization  
✅ **Better Performance**: Direct OR-Tools usage without PHP overhead  
✅ **Easier Maintenance**: Single source of truth for optimization logic  
✅ **Flexible Solvers**: CP-SAT for complex constraints, Linear for speed  

## Usage

### 1. Direct Python Usage
```bash
# Test the Python optimizer
python test_pure_autoplanner.py

# Run optimization from command line
python planning_engine/pure_autoplanner.py --input request.json --output result.json
```

### 2. PHP Web Interface
```php
// Use the service in PHP
$service = new PurePythonAutoplannerService();
$result = $service->generateAutoplan($teams, $matches, $constraints, $config);
```

### 3. Web Interface
- Open `pure_python_autoplanning.html` in your browser
- Test the Python service connectivity
- Configure optimization parameters
- Generate autoplan with real-time feedback

## Files Structure

```
jury_planner/
├── planning_engine/
│   └── pure_autoplanner.py          # Pure Python OR-Tools optimizer
├── includes/
│   └── PurePythonAutoplannerService.php  # PHP service interface
├── pure_python_optimization.php     # PHP API endpoint
├── pure_python_autoplanning.html    # Web interface
├── test_pure_autoplanner.py         # Test suite
└── README_PURE_PYTHON.md           # This file
```

## Configuration Options

### Solver Types
- **`auto`**: Automatic solver selection based on problem complexity
- **`sat`**: CP-SAT solver for complex constraint problems
- **`linear`**: Linear solver for simpler, faster optimization

### Constraint Types Supported
- **Maximum duties per period**: Limit assignments per team per time period
- **Rest between matches**: Ensure minimum rest time between assignments
- **Own match prohibition**: Teams cannot referee their own matches
- **Team availability**: Handle unavailable teams and time slots
- **Dedicated team assignments**: Special team-specific assignments

## Testing

The system includes comprehensive testing:

```bash
# Run full test suite
python test_pure_autoplanner.py

# Test specific solver
python planning_engine/pure_autoplanner.py --solver sat --input test_data.json

# Test PHP service
curl -X POST "pure_python_optimization.php" -d "action=test_python_service"
```

## Migration from Hybrid System

The pure Python system **replaces**:
- ❌ `optimization_interface.php` (hybrid approach)
- ❌ `SimplePhpOptimizer.php` (PHP optimization attempts)
- ❌ Complex constraint translation between PHP and Python

The pure Python system **keeps**:
- ✅ Database schema and data management
- ✅ Constraint editor and management UI
- ✅ Assignment import/export functionality
- ✅ Team and match management

## Performance

Typical optimization times:
- **Small problems** (5 teams, 10 matches): < 1 second
- **Medium problems** (20 teams, 50 matches): 5-30 seconds  
- **Large problems** (50+ teams, 100+ matches): 1-5 minutes

Performance can be tuned via:
- Solver selection (Linear vs CP-SAT)
- Time limits
- Constraint complexity
- Problem size

## Next Steps

1. **Replace existing autoplanning** calls with the pure Python service
2. **Update existing UI** to use `pure_python_autoplanning.html`
3. **Remove legacy hybrid** files once migration is complete
4. **Add more constraint types** as needed (all in Python)
5. **Optimize for larger problems** with advanced OR-Tools features
