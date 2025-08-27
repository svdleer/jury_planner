# 🚀 Comprehensive Constraint Integration Guide

## Overview

This integration bridges **PHP constraint management** with **Python mathematical optimization**, giving you the best of both worlds:

- 🎯 **User-friendly constraint editor** (PHP/Web UI)
- 🧠 **Powerful optimization engine** (Python/OR-Tools)
- 🔄 **Seamless data exchange** between systems
- 📊 **Real-time validation and preview**

## 🏗️ Architecture

```
┌─────────────────┐    ┌──────────────────┐    ┌─────────────────┐
│  PHP Web UI     │    │   Bridge Layer   │    │ Python Solver   │
│ ─────────────── │    │ ──────────────── │    │ ─────────────── │
│ • Constraint    │◄──►│ • Data Export    │◄──►│ • OR-Tools      │
│   Editor        │    │ • Format Convert │    │ • Linear/SAT    │
│ • Validation    │    │ • Result Import  │    │ • Template Eng. │
│ • UI Controls   │    │ • PHP↔Python     │    │ • Optimization  │
└─────────────────┘    └──────────────────┘    └─────────────────┘
```

## 🔧 Setup Instructions

### 1. Install Python Dependencies
```bash
chmod +x setup_optimization.sh
./setup_optimization.sh
```

### 2. Activate Environment
```bash
source activate_optimization.sh
```

### 3. Test Integration
```bash
# Test Python optimizer directly
python planning_engine/enhanced_optimizer.py sample_config.json output.json
```

## 📋 Available Constraint Types

### **PHP-Managed Constraints**
| Type | Description | Python Template |
|------|-------------|-----------------|
| `team_unavailable` | Team unavailable on specific date | `team_unavailable` |
| `rest_between_matches` | Minimum rest between assignments | `rest_between_matches` |
| `max_assignments_per_day` | Daily assignment limits | `max_duties_per_period` |
| `preferred_duty` | Team prefers specific duties | `preferred_duty_assignment` |
| `time_preference` | Preferred working hours | `preferred_match_dates` |
| `avoid_consecutive_matches` | Prevent consecutive assignments | `avoid_consecutive_matches` |

### **Legacy PHP Constraints (Auto-Imported)**
- `wrong_team_dedication` → `dedicated_team_assignment`
- `own_match` → `forbidden_self_assignment`
- `away_match_same_day` → `conflict_prevention`
- `consecutive_weekends` → `avoid_consecutive_matches`
- `recent_assignments` → `max_duties_per_period`
- `previous_week_assignment` → `rest_between_matches`

### **Python Template Engine**
10 advanced constraint templates with mathematical optimization:
- Team availability/unavailability
- Workload distribution and balancing
- Rest periods and spacing
- Dedicated team assignments
- Preference/avoidance patterns
- Date-based scheduling rules
- Opponent conflict handling
- Consecutive assignment limits

## 🚀 Usage Workflow

### **1. Constraint Management (PHP UI)**
```php
// Access constraint editor
http://your-domain/constraint_editor.php

// Create constraints via UI or API
$constraintManager = new ConstraintManager($db);
$result = $constraintManager->createConstraint([
    'name' => 'Team A Unavailable Dec 25',
    'constraint_type' => 'team_unavailable',
    'rule_type' => 'forbidden',
    'weight' => -1000,
    'parameters' => [
        'team_id' => 5,
        'date' => '2025-12-25',
        'reason' => 'Holiday'
    ]
]);
```

### **2. Optimization Execution**
```php
// Run full optimization
$optimizer = new OptimizationInterface($db);

// Validate constraints first
$validation = $optimizer->validateConstraints();
if (!$validation['valid']) {
    // Handle validation errors
}

// Preview optimization
$preview = $optimizer->previewOptimization([
    'solver_type' => 'auto',
    'timeout' => 120
]);

// Run full optimization
$result = $optimizer->runOptimization([
    'solver_type' => 'sat',  // linear, sat, auto
    'timeout' => 300
]);

if ($result['success']) {
    echo "Optimization Score: " . $result['optimization_score'];
    echo "Assignments Created: " . $result['imported_assignments'];
    echo "Constraints Satisfied: " . $result['constraints_satisfied'] . "/" . $result['total_constraints'];
}
```

### **3. Direct Python Usage**
```bash
# Export constraints from PHP
curl -X POST http://your-domain/api/export_constraints.php > config.json

# Run optimization
python planning_engine/enhanced_optimizer.py config.json solution.json

# Import results back to PHP
curl -X POST -d @solution.json http://your-domain/api/import_solution.php
```

## 🔄 Data Flow

### **PHP → Python Export Format**
```json
{
  "version": "1.0",
  "teams": [
    {"id": 1, "name": "Team A", "capacity_weight": 1.0, "dedicated_to_team": null}
  ],
  "matches": [
    {"id": 1, "date_time": "2025-01-15 19:00:00", "home_team": "Lions", "away_team": "Tigers"}
  ],
  "constraints": [
    {
      "id": 1,
      "name": "Team A Holiday Break",
      "template": "team_unavailable",
      "rule_type": "FORBIDDEN",
      "weight": -1000.0,
      "parameters": {"team_id": 1, "date": "2025-12-25"}
    }
  ],
  "weight_multipliers": {
    "hard_constraints": 1000.0,
    "soft_constraints": 1.0
  }
}
```

### **Python → PHP Solution Format**
```json
{
  "success": true,
  "assignments": [
    {"match_id": 1, "team_name": "Team B", "duty_type": "clock", "points": 10}
  ],
  "optimization_score": 847.5,
  "constraints_satisfied": 15,
  "total_constraints": 16,
  "solver_time": 2.34,
  "period": {"start_date": "2025-01-01", "end_date": "2025-01-31"}
}
```

## ⚙️ Solver Selection

### **Auto Selection Logic**
- **Linear Solver**: < 1000 variables, few hard constraints
- **SAT Solver**: > 1000 variables, many hard constraints, complex logic

### **Manual Selection**
- **Linear**: Best for weighted preferences, continuous optimization
- **SAT**: Best for hard constraints, boolean logic, feasibility problems

## 🎯 Best Practices

### **1. Constraint Design**
```php
// ✅ Good: Specific, well-weighted constraints
$constraint = [
    'name' => 'Team A Christmas Break',
    'constraint_type' => 'team_unavailable',
    'rule_type' => 'forbidden',      // Clear rule type
    'weight' => -1000,               // Strong negative weight
    'parameters' => [
        'team_id' => 5,
        'date' => '2025-12-25',
        'reason' => 'Holiday'        // Document reasoning
    ]
];

// ❌ Bad: Vague, conflicting constraints
$bad_constraint = [
    'name' => 'Something about teams',
    'weight' => -5,                  // Too weak for forbidden
    'rule_type' => 'forbidden'
];
```

### **2. Weight Guidelines**
| Rule Type | Weight Range | Purpose |
|-----------|--------------|---------|
| `forbidden` | -1000 to -500 | Hard constraints that must not be violated |
| `not_preferred` | -100 to -20 | Strong negative preferences |
| `less_preferred` | -50 to -5 | Mild negative preferences |
| `most_preferred` | +5 to +50 | Positive preferences and bonuses |

### **3. Performance Optimization**
- Use constraint validation before optimization
- Start with preview for large problem sets
- Set appropriate timeouts (30s for preview, 5min for full)
- Consider solver type based on problem characteristics

## 🐛 Troubleshooting

### **Common Issues**

**1. Python OR-Tools Not Found**
```bash
source activate_optimization.sh
pip install ortools
```

**2. Constraint Validation Fails**
- Check parameter formats (dates, team IDs, etc.)
- Verify constraint types match available templates
- Review weight values for appropriate ranges

**3. Optimization Returns No Solution**
- Too many conflicting hard constraints
- Infeasible constraint combinations
- Try relaxing some constraint weights

**4. Slow Optimization**
- Reduce timeout for testing
- Use linear solver for simpler problems
- Check for redundant constraints

### **Debug Tools**

**PHP Debug**
```php
// Enable constraint validation
$validation = $optimizer->validateConstraints();
var_dump($validation);

// Check constraint export
$bridge = new PythonConstraintBridge($db);
$export = $bridge->exportConstraintsToPython();
echo $export;
```

**Python Debug**
```python
# Add debug prints in enhanced_optimizer.py
print(f"Loaded {len(self.constraints)} constraints")
print(f"Problem size: {len(self.teams)} teams × {len(self.matches)} matches")
```

## 📊 Monitoring & Analytics

### **Optimization Statistics**
```php
$stats = $optimizer->getOptimizationHistory();
echo "Success Rate: " . $stats['avg_satisfaction_rate'] . "%";
echo "Average Time: " . $stats['avg_solver_time'] . "s";
```

### **Constraint Recommendations**
```php
$recommendations = $optimizer->getConstraintRecommendations();
foreach ($recommendations['missing_constraints'] as $rec) {
    echo "Consider adding: " . $rec['type'] . " - " . $rec['description'];
}
```

## 🔮 Advanced Features

### **Custom Constraint Templates**
Add new templates to `rule_manager.py`:
```python
templates["custom_rule"] = RuleTemplate(
    name="Custom Rule",
    description="Your custom constraint logic",
    rule_type=RuleType.NOT_PREFERRED,
    default_weight=-30.0,
    parameters_schema={
        "your_param": {"type": "integer", "required": True}
    },
    category="custom"
)
```

### **Multi-Objective Optimization**
```python
# Add multiple objectives with different priorities
objective_terms = []
objective_terms.extend(fairness_terms)     # Priority 1
objective_terms.extend(efficiency_terms)   # Priority 2
objective_terms.extend(preference_terms)   # Priority 3
model.Maximize(sum(objective_terms))
```

### **Real-Time Optimization**
```php
// Schedule automatic re-optimization
$cron_job = "0 2 * * * /path/to/run_optimization.php";
```

## 🚀 Next Steps

1. **Test the integration** with your existing constraints
2. **Add custom constraint types** specific to your needs
3. **Monitor optimization performance** and adjust weights
4. **Scale up** to handle larger problem sizes
5. **Integrate with calendar systems** for automated scheduling

The integration is now ready! You have a powerful, flexible constraint management system that combines the ease of PHP web interfaces with the mathematical precision of Python optimization engines. 🎉
