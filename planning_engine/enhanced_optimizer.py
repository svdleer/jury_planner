#!/usr/bin/env python3
"""
Enhanced Python Optimization Engine with PHP Integration
Combines the power of OR-Tools optimization with PHP constraint management
"""

import json
import sys
import os
from datetime import datetime, timedelta
from typing import Dict, List, Any, Optional, Tuple
from dataclasses import dataclass
from enum import Enum

# Add the parent directory to the path to import our modules
sys.path.append(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

from ortools.linear_solver import pywraplp
from ortools.sat.python import cp_model
from planning_engine.rule_manager import RuleConfigurationManager, RuleTemplate
from backend.models import RuleType, DutyType

class SolverType(Enum):
    """Available solver types"""
    LINEAR = "linear"
    CONSTRAINT_SAT = "sat"
    AUTO = "auto"

@dataclass
class OptimizationResult:
    """Results from the optimization process"""
    success: bool
    assignments: List[Dict[str, Any]]
    optimization_score: float
    constraints_satisfied: int
    total_constraints: int
    solver_time: float
    metadata: Dict[str, Any]
    period: Dict[str, str]

class EnhancedJuryOptimizer:
    """
    Enhanced jury assignment optimizer with PHP integration
    Supports both template-based and PHP-imported constraints
    """
    
    def __init__(self, solver_type: SolverType = SolverType.AUTO):
        self.solver_type = solver_type
        self.rule_manager = RuleConfigurationManager()
        self.teams = []
        self.matches = []
        self.constraints = []
        self.weight_multipliers = {}
        
        # Optimization variables
        self.solver = None
        self.assignment_vars = {}
        self.objective_terms = []
        
    def load_from_php_export(self, php_config_path: str) -> bool:
        """Load configuration exported from PHP constraint editor"""
        try:
            with open(php_config_path, 'r') as f:
                config = json.load(f)
            
            self.teams = config.get('teams', [])
            self.matches = config.get('matches', [])
            self.constraints = config.get('constraints', [])
            self.weight_multipliers = config.get('weight_multipliers', {})
            
            print(f"Loaded {len(self.teams)} teams, {len(self.matches)} matches, {len(self.constraints)} constraints")
            return True
            
        except Exception as e:
            print(f"Error loading PHP configuration: {e}")
            return False
    
    def optimize_assignments(self) -> OptimizationResult:
        """Run the optimization with current configuration"""
        start_time = datetime.now()
        
        # Choose solver based on problem size and type
        if self.solver_type == SolverType.AUTO:
            effective_solver_type = self._choose_optimal_solver()
        else:
            effective_solver_type = self.solver_type
        
        print(f"Using {effective_solver_type.value} solver for optimization")
        
        if effective_solver_type == SolverType.CONSTRAINT_SAT:
            result = self._optimize_with_sat_solver()
        else:
            result = self._optimize_with_linear_solver()
        
        end_time = datetime.now()
        solver_time = (end_time - start_time).total_seconds()
        
        result.solver_time = solver_time
        result.metadata['solver_type'] = effective_solver_type.value
        result.metadata['optimization_start'] = start_time.isoformat()
        result.metadata['optimization_end'] = end_time.isoformat()
        
        return result
    
    def _choose_optimal_solver(self) -> SolverType:
        """Choose the best solver based on problem characteristics"""
        num_vars = len(self.teams) * len(self.matches) * 2  # Approximate
        hard_constraints = sum(1 for c in self.constraints if c.get('rule_type') == 'FORBIDDEN')
        
        # Use SAT solver for problems with many hard constraints or binary decisions
        if hard_constraints > 10 or num_vars > 1000:
            return SolverType.CONSTRAINT_SAT
        else:
            return SolverType.LINEAR
    
    def _optimize_with_linear_solver(self) -> OptimizationResult:
        """Optimize using linear programming solver"""
        solver = pywraplp.Solver.CreateSolver('SCIP')
        if not solver:
            return OptimizationResult(
                success=False, assignments=[], optimization_score=0,
                constraints_satisfied=0, total_constraints=0, solver_time=0,
                metadata={'error': 'Could not create linear solver'}, period={}
            )
        
        # Create decision variables
        assignment_vars = {}
        for team in self.teams:
            for match in self.matches:
                for duty_type in ['clock', 'score']:  # Main duty types
                    var_name = f"assign_{team['id']}_{match['id']}_{duty_type}"
                    assignment_vars[var_name] = solver.IntVar(0, 1, var_name)
        
        # Add constraint: each match needs required duties
        constraints_added = 0
        for match in self.matches:
            for duty in match.get('required_duties', []):
                if duty['required']:
                    constraint = solver.Constraint(duty['count'], duty['count'])
                    for team in self.teams:
                        var_name = f"assign_{team['id']}_{match['id']}_{duty['type']}"
                        if var_name in assignment_vars:
                            constraint.SetCoefficient(assignment_vars[var_name], 1)
                    constraints_added += 1
        
        # Add optimization-specific constraints
        constraints_satisfied = 0
        for constraint_def in self.constraints:
            if self._add_linear_constraint(solver, assignment_vars, constraint_def):
                constraints_satisfied += 1
            constraints_added += 1
        
        # Set objective function
        objective = solver.Objective()
        objective.SetMaximization()
        
        # Add base assignment values
        for var_name, var in assignment_vars.items():
            team_id, match_id, duty_type = self._parse_var_name(var_name)
            base_value = self._calculate_base_assignment_value(team_id, match_id, duty_type)
            objective.SetCoefficient(var, base_value)
        
        # Add constraint-based objective terms
        for constraint_def in self.constraints:
            self._add_linear_objective_terms(objective, assignment_vars, constraint_def)
        
        # Solve
        status = solver.Solve()
        
        if status == pywraplp.Solver.OPTIMAL or status == pywraplp.Solver.FEASIBLE:
            assignments = self._extract_linear_solution(assignment_vars)
            return OptimizationResult(
                success=True,
                assignments=assignments,
                optimization_score=solver.Objective().Value(),
                constraints_satisfied=constraints_satisfied,
                total_constraints=constraints_added,
                solver_time=0,  # Will be set by caller
                metadata={'solver_status': 'optimal' if status == pywraplp.Solver.OPTIMAL else 'feasible'},
                period=self._get_optimization_period()
            )
        else:
            return OptimizationResult(
                success=False,
                assignments=[],
                optimization_score=0,
                constraints_satisfied=0,
                total_constraints=constraints_added,
                solver_time=0,
                metadata={'solver_status': 'infeasible', 'error': 'No feasible solution found'},
                period={}
            )
    
    def _optimize_with_sat_solver(self) -> OptimizationResult:
        """Optimize using constraint satisfaction solver"""
        model = cp_model.CpModel()
        
        # Create decision variables
        assignment_vars = {}
        for team in self.teams:
            for match in self.matches:
                for duty_type in ['clock', 'score']:
                    var_name = f"assign_{team['id']}_{match['id']}_{duty_type}"
                    assignment_vars[var_name] = model.NewBoolVar(var_name)
        
        # Add constraints
        constraints_added = 0
        constraints_satisfied = 0
        
        # Match duty requirements
        for match in self.matches:
            for duty in match.get('required_duties', []):
                if duty['required']:
                    vars_for_duty = []
                    for team in self.teams:
                        var_name = f"assign_{team['id']}_{match['id']}_{duty['type']}"
                        if var_name in assignment_vars:
                            vars_for_duty.append(assignment_vars[var_name])
                    
                    if vars_for_duty:
                        model.Add(sum(vars_for_duty) == duty['count'])
                        constraints_added += 1
        
        # Add optimization-specific constraints
        for constraint_def in self.constraints:
            if self._add_sat_constraint(model, assignment_vars, constraint_def):
                constraints_satisfied += 1
            constraints_added += 1
        
        # Set objective
        objective_terms = []
        
        # Base assignment values
        for var_name, var in assignment_vars.items():
            team_id, match_id, duty_type = self._parse_var_name(var_name)
            base_value = self._calculate_base_assignment_value(team_id, match_id, duty_type)
            if base_value != 0:
                objective_terms.append(var * int(base_value * 100))  # Scale for integer math
        
        # Constraint-based objective terms
        for constraint_def in self.constraints:
            terms = self._get_sat_objective_terms(model, assignment_vars, constraint_def)
            objective_terms.extend(terms)
        
        if objective_terms:
            model.Maximize(sum(objective_terms))
        
        # Solve
        solver = cp_model.CpSolver()
        solver.parameters.max_time_in_seconds = 300  # 5 minute timeout
        
        status = solver.Solve(model)
        
        if status in [cp_model.OPTIMAL, cp_model.FEASIBLE]:
            assignments = self._extract_sat_solution(solver, assignment_vars)
            return OptimizationResult(
                success=True,
                assignments=assignments,
                optimization_score=solver.ObjectiveValue() / 100.0,  # Unscale
                constraints_satisfied=constraints_satisfied,
                total_constraints=constraints_added,
                solver_time=0,
                metadata={
                    'solver_status': 'optimal' if status == cp_model.OPTIMAL else 'feasible',
                    'solver_stats': {
                        'branches': solver.NumBranches(),
                        'conflicts': solver.NumConflicts(),
                        'bool_vars': solver.NumBooleans(),
                        'integer_vars': solver.NumIntegers()
                    }
                },
                period=self._get_optimization_period()
            )
        else:
            return OptimizationResult(
                success=False,
                assignments=[],
                optimization_score=0,
                constraints_satisfied=0,
                total_constraints=constraints_added,
                solver_time=0,
                metadata={'solver_status': 'infeasible', 'error': 'No feasible solution found'},
                period={}
            )
    
    def _parse_var_name(self, var_name: str) -> Tuple[int, int, str]:
        """Parse variable name to extract team_id, match_id, duty_type"""
        parts = var_name.split('_')
        return int(parts[1]), int(parts[2]), parts[3]
    
    def _calculate_base_assignment_value(self, team_id: int, match_id: int, duty_type: str) -> float:
        """Calculate base value for an assignment"""
        team = next((t for t in self.teams if t['id'] == team_id), None)
        match = next((m for m in self.matches if m['id'] == match_id), None)
        
        if not team or not match:
            return 0.0
        
        value = 10.0  # Base assignment value
        
        # Apply team capacity weight
        value *= team.get('capacity_weight', 1.0)
        
        # Apply match importance
        value *= match.get('importance_multiplier', 1.0)
        
        # Preference for clock vs score duties (could be team-specific)
        if duty_type == 'clock':
            value *= 1.1  # Slight preference for clock duties
        
        return value
    
    def _add_linear_constraint(self, solver, assignment_vars: Dict, constraint_def: Dict) -> bool:
        """Add a constraint to the linear solver"""
        template = constraint_def.get('template')
        parameters = constraint_def.get('parameters', {})
        
        try:
            if template == 'team_unavailable':
                return self._add_team_unavailable_linear(solver, assignment_vars, parameters)
            elif template == 'rest_between_matches':
                return self._add_rest_between_linear(solver, assignment_vars, parameters)
            elif template == 'max_duties_per_period':
                return self._add_max_duties_linear(solver, assignment_vars, parameters)
            elif template == 'dedicated_team_assignment':
                return self._add_dedicated_team_linear(solver, assignment_vars, parameters)
            # Add more constraint types as needed
            
        except Exception as e:
            print(f"Error adding linear constraint {template}: {e}")
            
        return False
    
    def _add_sat_constraint(self, model, assignment_vars: Dict, constraint_def: Dict) -> bool:
        """Add a constraint to the SAT solver"""
        template = constraint_def.get('template')
        parameters = constraint_def.get('parameters', {})
        
        try:
            if template == 'team_unavailable':
                return self._add_team_unavailable_sat(model, assignment_vars, parameters)
            elif template == 'rest_between_matches':
                return self._add_rest_between_sat(model, assignment_vars, parameters)
            elif template == 'max_duties_per_period':
                return self._add_max_duties_sat(model, assignment_vars, parameters)
            elif template == 'dedicated_team_assignment':
                return self._add_dedicated_team_sat(model, assignment_vars, parameters)
            # Add more constraint types as needed
            
        except Exception as e:
            print(f"Error adding SAT constraint {template}: {e}")
            
        return False
    
    def _add_team_unavailable_linear(self, solver, assignment_vars: Dict, parameters: Dict) -> bool:
        """Add team unavailable constraint to linear solver"""
        team_id = parameters.get('team_id')
        unavailable_date = parameters.get('date')
        
        if not team_id or not unavailable_date:
            return False
        
        # Find matches on the unavailable date
        unavailable_matches = [
            m for m in self.matches 
            if m['date_time'].startswith(unavailable_date)
        ]
        
        # Constrain team to 0 assignments on this date
        for match in unavailable_matches:
            for duty_type in ['clock', 'score']:
                var_name = f"assign_{team_id}_{match['id']}_{duty_type}"
                if var_name in assignment_vars:
                    constraint = solver.Constraint(0, 0)
                    constraint.SetCoefficient(assignment_vars[var_name], 1)
        
        return True
    
    def _add_team_unavailable_sat(self, model, assignment_vars: Dict, parameters: Dict) -> bool:
        """Add team unavailable constraint to SAT solver"""
        team_id = parameters.get('team_id')
        unavailable_date = parameters.get('date')
        
        if not team_id or not unavailable_date:
            return False
        
        # Find matches on the unavailable date
        unavailable_matches = [
            m for m in self.matches 
            if m['date_time'].startswith(unavailable_date)
        ]
        
        # Constrain team to 0 assignments on this date
        for match in unavailable_matches:
            for duty_type in ['clock', 'score']:
                var_name = f"assign_{team_id}_{match['id']}_{duty_type}"
                if var_name in assignment_vars:
                    model.Add(assignment_vars[var_name] == 0)
        
        return True
    
    def _add_linear_objective_terms(self, objective, assignment_vars: Dict, constraint_def: Dict):
        """Add objective terms for linear solver based on constraint"""
        weight = constraint_def.get('weight', 0)
        template = constraint_def.get('template')
        parameters = constraint_def.get('parameters', {})
        
        if template == 'preferred_duty_assignment':
            team_id = parameters.get('team_id')
            duty_type = parameters.get('duty_type')
            strength = parameters.get('strength', 1.0)
            
            if team_id and duty_type:
                for match in self.matches:
                    var_name = f"assign_{team_id}_{match['id']}_{duty_type}"
                    if var_name in assignment_vars:
                        objective.SetCoefficient(assignment_vars[var_name], weight * strength)
    
    def _get_sat_objective_terms(self, model, assignment_vars: Dict, constraint_def: Dict) -> List:
        """Get objective terms for SAT solver based on constraint"""
        terms = []
        weight = int(constraint_def.get('weight', 0) * 100)  # Scale for integer math
        template = constraint_def.get('template')
        parameters = constraint_def.get('parameters', {})
        
        if template == 'preferred_duty_assignment':
            team_id = parameters.get('team_id')
            duty_type = parameters.get('duty_type')
            strength = parameters.get('strength', 1.0)
            
            if team_id and duty_type:
                for match in self.matches:
                    var_name = f"assign_{team_id}_{match['id']}_{duty_type}"
                    if var_name in assignment_vars:
                        terms.append(assignment_vars[var_name] * int(weight * strength))
        
        return terms
    
    def _extract_linear_solution(self, assignment_vars: Dict) -> List[Dict[str, Any]]:
        """Extract solution from linear solver"""
        assignments = []
        
        for var_name, var in assignment_vars.items():
            if var.solution_value() > 0.5:  # Assigned
                team_id, match_id, duty_type = self._parse_var_name(var_name)
                
                team = next((t for t in self.teams if t['id'] == team_id), None)
                match = next((m for m in self.matches if m['id'] == match_id), None)
                
                if team and match:
                    assignments.append({
                        'match_id': match_id,
                        'team_name': team['name'],
                        'duty_type': duty_type,
                        'points': 10,  # Standard points
                        'assignment_score': var.solution_value()
                    })
        
        return assignments
    
    def _extract_sat_solution(self, solver, assignment_vars: Dict) -> List[Dict[str, Any]]:
        """Extract solution from SAT solver"""
        assignments = []
        
        for var_name, var in assignment_vars.items():
            if solver.Value(var):  # Assigned
                team_id, match_id, duty_type = self._parse_var_name(var_name)
                
                team = next((t for t in self.teams if t['id'] == team_id), None)
                match = next((m for m in self.matches if m['id'] == match_id), None)
                
                if team and match:
                    assignments.append({
                        'match_id': match_id,
                        'team_name': team['name'],
                        'duty_type': duty_type,
                        'points': 10,
                        'assignment_score': 1.0
                    })
        
        return assignments
    
    def _get_optimization_period(self) -> Dict[str, str]:
        """Get the optimization period from matches"""
        if not self.matches:
            return {}
        
        dates = [match['date_time'] for match in self.matches]
        return {
            'start_date': min(dates),
            'end_date': max(dates)
        }
    
    def save_solution(self, result: OptimizationResult, output_path: str):
        """Save optimization result to JSON file"""
        output_data = {
            'success': result.success,
            'assignments': result.assignments,
            'optimization_score': result.optimization_score,
            'constraints_satisfied': result.constraints_satisfied,
            'total_constraints': result.total_constraints,
            'solver_time': result.solver_time,
            'metadata': result.metadata,
            'period': result.period
        }
        
        with open(output_path, 'w') as f:
            json.dump(output_data, f, indent=2)

def main():
    """Main entry point for command-line usage"""
    if len(sys.argv) != 3:
        print("Usage: python enhanced_optimizer.py <php_config.json> <output_solution.json>")
        sys.exit(1)
    
    config_path = sys.argv[1]
    output_path = sys.argv[2]
    
    # Create optimizer
    optimizer = EnhancedJuryOptimizer(SolverType.AUTO)
    
    # Load configuration from PHP
    if not optimizer.load_from_php_export(config_path):
        print("Failed to load PHP configuration")
        sys.exit(1)
    
    print("Starting optimization...")
    result = optimizer.optimize_assignments()
    
    if result.success:
        print(f"✅ Optimization successful!")
        print(f"   Score: {result.optimization_score:.2f}")
        print(f"   Assignments: {len(result.assignments)}")
        print(f"   Constraints satisfied: {result.constraints_satisfied}/{result.total_constraints}")
        print(f"   Solver time: {result.solver_time:.2f}s")
        
        optimizer.save_solution(result, output_path)
        print(f"   Solution saved to: {output_path}")
    else:
        print(f"❌ Optimization failed: {result.metadata.get('error', 'Unknown error')}")
        sys.exit(1)

if __name__ == "__main__":
    main()
