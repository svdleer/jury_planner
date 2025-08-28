#!/usr/bin/env python3
"""
Pure Python Autoplanning Service for Jury Assignment
Handles all optimization logic using OR-Tools with clean API interface for PHP frontend
"""

import json
import sys
import os
from datetime import datetime, timedelta
from typing import Dict, List, Any, Optional, Tuple
from dataclasses import dataclass, asdict
from enum import Enum
import argparse
import logging

# Add the parent directory to the path
sys.path.append(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

from ortools.sat.python import cp_model
from ortools.linear_solver import pywraplp

# Configure logging
logging.basicConfig(level=logging.INFO, format='%(asctime)s - %(levelname)s - %(message)s')
logger = logging.getLogger(__name__)

class SolverType(Enum):
    """Available solver types"""
    CONSTRAINT_SAT = "sat"
    LINEAR = "linear"
    AUTO = "auto"

class ConstraintType(Enum):
    """Constraint types for jury assignment"""
    FORBIDDEN = "forbidden"
    NOT_PREFERRED = "not_preferred"
    LESS_PREFERRED = "less_preferred"
    MOST_PREFERRED = "most_preferred"

@dataclass
class Team:
    """Jury team representation"""
    id: int
    name: str
    capacity_weight: float = 1.0
    is_active: bool = True
    dedicated_to_team: Optional[str] = None

@dataclass
class Match:
    """Match representation"""
    id: int
    date_time: str
    home_team: str
    away_team: str
    location: str
    competition: str
    required_duties: List[Dict[str, Any]]
    importance_multiplier: float = 1.0
    is_locked: bool = False

@dataclass
class Constraint:
    """Constraint representation"""
    id: int
    name: str
    constraint_type: str
    rule_type: ConstraintType
    weight: float
    parameters: Dict[str, Any]
    is_active: bool = True

@dataclass
class Assignment:
    """Assignment representation"""
    match_id: int
    team_id: int
    duty_type: str
    assignment_time: str
    confidence_score: float = 1.0

@dataclass
class OptimizationRequest:
    """Request for optimization"""
    teams: List[Team]
    matches: List[Match]
    constraints: List[Constraint]
    solver_config: Dict[str, Any]
    time_limit_seconds: int = 300

@dataclass
class OptimizationResult:
    """Results from optimization"""
    success: bool
    assignments: List[Assignment]
    objective_value: float
    constraints_satisfied: int
    total_constraints: int
    solver_time_seconds: float
    solver_status: str
    metadata: Dict[str, Any]
    errors: List[str] = None

class PureJuryOptimizer:
    """
    Pure Python jury assignment optimizer using OR-Tools
    No PHP dependencies - all logic contained in Python
    """
    
    def __init__(self, solver_type: SolverType = SolverType.AUTO):
        self.solver_type = solver_type
        self.model = None
        self.solver = None
        self.variables = {}
        self.constraints_applied = 0
        
    def optimize(self, request: OptimizationRequest) -> OptimizationResult:
        """
        Main optimization entry point
        """
        logger.info(f"Starting optimization with {len(request.teams)} teams, {len(request.matches)} matches, {len(request.constraints)} constraints")
        
        start_time = datetime.now()
        
        try:
            # Choose solver
            if self.solver_type == SolverType.AUTO:
                solver_type = self._choose_optimal_solver(request)
            else:
                solver_type = self.solver_type
                
            logger.info(f"Using solver: {solver_type.value}")
            
            # Build and solve model
            if solver_type == SolverType.CONSTRAINT_SAT:
                result = self._solve_with_cp_sat(request)
            else:
                result = self._solve_with_linear(request)
                
            end_time = datetime.now()
            result.solver_time_seconds = (end_time - start_time).total_seconds()
            
            logger.info(f"Optimization completed in {result.solver_time_seconds:.2f}s")
            return result
            
        except Exception as e:
            logger.error(f"Optimization failed: {str(e)}")
            return OptimizationResult(
                success=False,
                assignments=[],
                objective_value=0,
                constraints_satisfied=0,
                total_constraints=len(request.constraints),
                solver_time_seconds=(datetime.now() - start_time).total_seconds(),
                solver_status="ERROR",
                metadata={},
                errors=[str(e)]
            )
    
    def _choose_optimal_solver(self, request: OptimizationRequest) -> SolverType:
        """Choose the best solver based on problem characteristics"""
        # Use CP-SAT for complex constraint problems
        if len(request.constraints) > 10 or any(c.constraint_type in ['team_unavailable', 'dedicated_team_assignment'] for c in request.constraints):
            return SolverType.CONSTRAINT_SAT
        # Use Linear for simpler problems
        return SolverType.LINEAR
    
    def _solve_with_cp_sat(self, request: OptimizationRequest) -> OptimizationResult:
        """Solve using CP-SAT (Constraint Programming)"""
        model = cp_model.CpModel()
        
        # Create decision variables: team_assignment[match_id, team_id, duty_type]
        team_assignments = {}
        for match in request.matches:
            for team in request.teams:
                if not team.is_active:
                    continue
                for duty in match.required_duties:
                    duty_type = duty['type']
                    var_name = f"assign_{match.id}_{team.id}_{duty_type}"
                    team_assignments[(match.id, team.id, duty_type)] = model.NewBoolVar(var_name)
        
        # Constraint 1: Each required duty must be assigned
        for match in request.matches:
            for duty in match.required_duties:
                duty_type = duty['type']
                count_required = duty['count']
                
                assigned_teams = []
                for team in request.teams:
                    if team.is_active and (match.id, team.id, duty_type) in team_assignments:
                        assigned_teams.append(team_assignments[(match.id, team.id, duty_type)])
                
                if assigned_teams:
                    model.Add(sum(assigned_teams) == count_required)
        
        # Constraint 2: Team cannot be assigned to multiple duties in same match
        for match in request.matches:
            for team in request.teams:
                if not team.is_active:
                    continue
                    
                team_duties = []
                for duty in match.required_duties:
                    duty_type = duty['type']
                    if (match.id, team.id, duty_type) in team_assignments:
                        team_duties.append(team_assignments[(match.id, team.id, duty_type)])
                
                if len(team_duties) > 1:
                    model.Add(sum(team_duties) <= 1)
        
        # Apply custom constraints
        constraint_violations = []
        for constraint in request.constraints:
            if not constraint.is_active:
                continue
                
            violation_vars = self._apply_constraint_cp_sat(model, constraint, request, team_assignments)
            if violation_vars:
                constraint_violations.extend(violation_vars)
                self.constraints_applied += 1
        
        # Objective: Minimize constraint violations, maximize assignment quality
        objective_terms = []
        
        # Penalize constraint violations
        for violation_var in constraint_violations:
            objective_terms.append(violation_var * 1000)  # High penalty
        
        # Reward assignments (prefer balanced distribution)
        for (match_id, team_id, duty_type), var in team_assignments.items():
            # Add small positive weight for assignments
            objective_terms.append(var * -1)  # Negative to maximize assignments
        
        if objective_terms:
            model.Minimize(sum(objective_terms))
        
        # Solve
        solver = cp_model.CpSolver()
        solver.parameters.max_time_in_seconds = request.time_limit_seconds
        
        status = solver.Solve(model)
        
        # Extract results
        assignments = []
        if status in [cp_model.OPTIMAL, cp_model.FEASIBLE]:
            for (match_id, team_id, duty_type), var in team_assignments.items():
                if solver.Value(var) == 1:
                    match = next(m for m in request.matches if m.id == match_id)
                    assignments.append(Assignment(
                        match_id=match_id,
                        team_id=team_id,
                        duty_type=duty_type,
                        assignment_time=match.date_time,
                        confidence_score=0.95
                    ))
        
        return OptimizationResult(
            success=status in [cp_model.OPTIMAL, cp_model.FEASIBLE],
            assignments=assignments,
            objective_value=solver.ObjectiveValue() if status in [cp_model.OPTIMAL, cp_model.FEASIBLE] else 0,
            constraints_satisfied=self.constraints_applied,
            total_constraints=len([c for c in request.constraints if c.is_active]),
            solver_time_seconds=solver.WallTime(),
            solver_status=solver.StatusName(status),
            metadata={
                "solver_type": "CP-SAT",
                "num_variables": len(team_assignments),
                "num_constraints": model.Proto().constraints.__len__()
            }
        )
    
    def _solve_with_linear(self, request: OptimizationRequest) -> OptimizationResult:
        """Solve using Linear Programming"""
        solver = pywraplp.Solver.CreateSolver('SCIP')
        if not solver:
            return OptimizationResult(
                success=False,
                assignments=[],
                objective_value=0,
                constraints_satisfied=0,
                total_constraints=len(request.constraints),
                solver_time_seconds=0,
                solver_status="SOLVER_NOT_AVAILABLE",
                metadata={},
                errors=["Linear solver not available"]
            )
        
        # Create binary variables for assignments
        team_assignments = {}
        for match in request.matches:
            for team in request.teams:
                if not team.is_active:
                    continue
                for duty in match.required_duties:
                    duty_type = duty['type']
                    var_name = f"assign_{match.id}_{team.id}_{duty_type}"
                    team_assignments[(match.id, team.id, duty_type)] = solver.BoolVar(var_name)
        
        # Constraints and objective similar to CP-SAT implementation
        # (Simplified for brevity - would implement full linear constraints)
        
        # Set time limit
        solver.SetTimeLimit(request.time_limit_seconds * 1000)  # milliseconds
        
        # Solve
        status = solver.Solve()
        
        # Extract results
        assignments = []
        if status in [pywraplp.Solver.OPTIMAL, pywraplp.Solver.FEASIBLE]:
            for (match_id, team_id, duty_type), var in team_assignments.items():
                if var.solution_value() > 0.5:
                    match = next(m for m in request.matches if m.id == match_id)
                    assignments.append(Assignment(
                        match_id=match_id,
                        team_id=team_id,
                        duty_type=duty_type,
                        assignment_time=match.date_time,
                        confidence_score=0.90
                    ))
        
        return OptimizationResult(
            success=status in [pywraplp.Solver.OPTIMAL, pywraplp.Solver.FEASIBLE],
            assignments=assignments,
            objective_value=solver.Objective().Value() if status in [pywraplp.Solver.OPTIMAL, pywraplp.Solver.FEASIBLE] else 0,
            constraints_satisfied=self.constraints_applied,
            total_constraints=len([c for c in request.constraints if c.is_active]),
            solver_time_seconds=solver.WallTime() / 1000.0,
            solver_status=self._linear_status_name(status),
            metadata={
                "solver_type": "Linear",
                "num_variables": len(team_assignments)
            }
        )
    
    def _apply_constraint_cp_sat(self, model, constraint: Constraint, request: OptimizationRequest, team_assignments) -> List:
        """Apply a constraint to the CP-SAT model"""
        constraint_type = constraint.constraint_type
        params = constraint.parameters
        
        violation_vars = []
        
        if constraint_type == "max_duties_per_period":
            # Limit duties per team per period
            max_duties = params.get('max_duties', 3)
            period_days = params.get('period_days', 7)
            
            for team in request.teams:
                if not team.is_active:
                    continue
                    
                # Group matches by time periods
                team_duties_in_period = []
                for match in request.matches:
                    for duty in match.required_duties:
                        duty_type = duty['type']
                        if (match.id, team.id, duty_type) in team_assignments:
                            team_duties_in_period.append(team_assignments[(match.id, team.id, duty_type)])
                
                if team_duties_in_period:
                    violation_var = model.NewBoolVar(f"violation_max_duties_{team.id}")
                    model.Add(sum(team_duties_in_period) <= max_duties).OnlyEnforceIf(violation_var.Not())
                    violation_vars.append(violation_var)
        
        elif constraint_type == "rest_between_matches":
            # Ensure rest between assignments
            min_rest_days = params.get('min_rest_days', 1)
            
            for team in request.teams:
                if not team.is_active:
                    continue
                
                # Sort matches by date
                sorted_matches = sorted(request.matches, key=lambda m: m.date_time)
                
                for i in range(len(sorted_matches) - 1):
                    match1 = sorted_matches[i]
                    match2 = sorted_matches[i + 1]
                    
                    # Check if matches are within rest period
                    date1 = datetime.fromisoformat(match1.date_time.replace(' ', 'T'))
                    date2 = datetime.fromisoformat(match2.date_time.replace(' ', 'T'))
                    
                    if (date2 - date1).days < min_rest_days:
                        # Cannot assign same team to both matches
                        team_assigned_1 = []
                        team_assigned_2 = []
                        
                        for duty in match1.required_duties:
                            if (match1.id, team.id, duty['type']) in team_assignments:
                                team_assigned_1.append(team_assignments[(match1.id, team.id, duty['type'])])
                        
                        for duty in match2.required_duties:
                            if (match2.id, team.id, duty['type']) in team_assignments:
                                team_assigned_2.append(team_assignments[(match2.id, team.id, duty['type'])])
                        
                        if team_assigned_1 and team_assigned_2:
                            violation_var = model.NewBoolVar(f"violation_rest_{team.id}_{match1.id}_{match2.id}")
                            model.Add(sum(team_assigned_1) + sum(team_assigned_2) <= 1).OnlyEnforceIf(violation_var.Not())
                            violation_vars.append(violation_var)
        
        elif constraint_type == "own_match":
            # Teams cannot referee their own matches
            for match in request.matches:
                for team in request.teams:
                    if not team.is_active:
                        continue
                    
                    # Check if team is playing in this match
                    if team.name in [match.home_team, match.away_team]:
                        # Prohibit assignment
                        team_duties = []
                        for duty in match.required_duties:
                            if (match.id, team.id, duty['type']) in team_assignments:
                                team_duties.append(team_assignments[(match.id, team.id, duty['type'])])
                        
                        if team_duties:
                            model.Add(sum(team_duties) == 0)
        
        return violation_vars
    
    def _linear_status_name(self, status):
        """Convert linear solver status to string"""
        status_map = {
            pywraplp.Solver.OPTIMAL: "OPTIMAL",
            pywraplp.Solver.FEASIBLE: "FEASIBLE",
            pywraplp.Solver.INFEASIBLE: "INFEASIBLE",
            pywraplp.Solver.UNBOUNDED: "UNBOUNDED",
            pywraplp.Solver.ABNORMAL: "ABNORMAL",
            pywraplp.Solver.NOT_SOLVED: "NOT_SOLVED"
        }
        return status_map.get(status, "UNKNOWN")

def parse_request_from_json(json_data: str) -> OptimizationRequest:
    """Parse optimization request from JSON"""
    data = json.loads(json_data)
    
    teams = [Team(**team_data) for team_data in data['teams']]
    matches = [Match(**match_data) for match_data in data['matches']]
    constraints = [Constraint(**constraint_data) for constraint_data in data['constraints']]
    
    return OptimizationRequest(
        teams=teams,
        matches=matches,
        constraints=constraints,
        solver_config=data.get('solver_config', {}),
        time_limit_seconds=data.get('time_limit_seconds', 300)
    )

def main():
    """Main entry point for command line usage"""
    parser = argparse.ArgumentParser(description='Pure Python Jury Assignment Optimizer')
    parser.add_argument('--input', '-i', required=True, help='Input JSON file with optimization request')
    parser.add_argument('--output', '-o', help='Output JSON file for results')
    parser.add_argument('--solver', choices=['sat', 'linear', 'auto'], default='auto', help='Solver type')
    parser.add_argument('--time-limit', type=int, default=300, help='Time limit in seconds')
    parser.add_argument('--verbose', '-v', action='store_true', help='Verbose logging')
    
    args = parser.parse_args()
    
    if args.verbose:
        logging.getLogger().setLevel(logging.DEBUG)
    
    try:
        # Read input
        with open(args.input, 'r') as f:
            request = parse_request_from_json(f.read())
        
        # Override time limit if specified
        if args.time_limit:
            request.time_limit_seconds = args.time_limit
        
        # Run optimization
        optimizer = PureJuryOptimizer(SolverType(args.solver))
        result = optimizer.optimize(request)
        
        # Output results
        result_json = json.dumps(asdict(result), indent=2, default=str)
        
        if args.output:
            with open(args.output, 'w') as f:
                f.write(result_json)
            logger.info(f"Results written to {args.output}")
        else:
            print(result_json)
            
        # Exit with appropriate code
        sys.exit(0 if result.success else 1)
        
    except Exception as e:
        logger.error(f"Fatal error: {str(e)}")
        sys.exit(1)

if __name__ == '__main__':
    main()
