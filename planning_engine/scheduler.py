from typing import List, Dict, Tuple, Optional
from datetime import date, timedelta
from dataclasses import dataclass
from enum import Enum
import logging
from ortools.sat.python import cp_model

logger = logging.getLogger(__name__)

@dataclass
class PlanningConstraint:
    """Represents a planning constraint with weight"""
    name: str
    weight: float
    constraint_type: str
    parameters: Dict

class ConstraintType(Enum):
    TEAM_AVAILABILITY = "team_availability"
    TEAM_WORKLOAD = "team_workload"
    DEDICATED_TEAM = "dedicated_team"
    REST_BETWEEN_MATCHES = "rest_between_matches"
    PREFERRED_ASSIGNMENT = "preferred_assignment"
    AVOID_ASSIGNMENT = "avoid_assignment"

class JuryPlanningEngine:
    """
    Advanced constraint-based planning engine for jury team scheduling
    Uses Google OR-Tools CP-SAT solver for optimization
    """
    
    def __init__(self):
        self.model = None
        self.solver = None
        self.constraints = []
        self.variables = {}
        self.objective_terms = []
        
    def setup_problem(self, matches: List, teams: List, rules: List, 
                     start_date: date, end_date: date) -> bool:
        """Initialize the constraint programming model"""
        try:
            self.model = cp_model.CpModel()
            self.solver = cp_model.CpSolver()
            
            # Set solver parameters
            self.solver.parameters.max_time_in_seconds = 300.0
            self.solver.parameters.log_search_progress = True
            
            self._create_variables(matches, teams)
            self._add_basic_constraints(matches, teams)
            self._add_rule_constraints(rules, matches, teams)
            self._setup_objective(matches, teams, rules)
            
            return True
            
        except Exception as e:
            logger.error(f"Error setting up planning problem: {e}")
            return False
    
    def _create_variables(self, matches: List, teams: List):
        """Create decision variables for team assignments"""
        from backend.models import DutyType
        
        self.variables = {}
        
        # Binary variables: assignment[match_id][team_id][duty] = 1 if team is assigned to duty for match
        for match in matches:
            self.variables[match.id] = {}
            for team in teams:
                self.variables[match.id][team.id] = {}
                for duty in DutyType:
                    var_name = f"assign_m{match.id}_t{team.id}_d{duty.value}"
                    self.variables[match.id][team.id][duty] = self.model.NewBoolVar(var_name)
    
    def _add_basic_constraints(self, matches: List, teams: List):
        """Add fundamental constraints that must be satisfied"""
        from backend.models import DutyType
        
        # Constraint 1: Each duty for each match must be assigned to exactly one team
        for match in matches:
            for duty in DutyType:
                assignments = [
                    self.variables[match.id][team.id][duty] 
                    for team in teams if team.is_active
                ]
                self.model.Add(sum(assignments) == 1)
        
        # Constraint 2: A team cannot be assigned to multiple duties for the same match
        for match in matches:
            for team in teams:
                if not team.is_active:
                    continue
                team_assignments = [
                    self.variables[match.id][team.id][duty] 
                    for duty in DutyType
                ]
                self.model.Add(sum(team_assignments) <= 1)
    
    def _add_rule_constraints(self, rules: List, matches: List, teams: List):
        """Add constraints based on planning rules"""
        from backend.models import RuleType, DutyType
        
        for rule in rules:
            if not rule.is_active:
                continue
                
            try:
                if rule.rule_type == RuleType.FORBIDDEN:
                    self._add_forbidden_constraint(rule, matches, teams)
                elif rule.rule_type in [RuleType.NOT_PREFERRED, RuleType.LESS_PREFERRED]:
                    self._add_penalty_constraint(rule, matches, teams)
                elif rule.rule_type == RuleType.MOST_PREFERRED:
                    self._add_bonus_constraint(rule, matches, teams)
                    
            except Exception as e:
                logger.warning(f"Failed to add rule constraint {rule.name}: {e}")
    
    def _add_forbidden_constraint(self, rule, matches: List, teams: List):
        """Add hard constraints for forbidden assignments"""
        from backend.models import DutyType
        
        params = rule.parameters or {}
        
        if params.get('constraint_type') == 'team_unavailable':
            team_id = params.get('team_id')
            date_str = params.get('date')
            
            if team_id and date_str:
                target_date = date.fromisoformat(date_str)
                for match in matches:
                    if match.date == target_date:
                        for duty in DutyType:
                            self.model.Add(self.variables[match.id][team_id][duty] == 0)
        
        elif params.get('constraint_type') == 'dedicated_team_restriction':
            team_id = params.get('team_id')
            dedicated_to_team_id = params.get('dedicated_to_team_id')
            
            if team_id and dedicated_to_team_id:
                for match in matches:
                    # Dedicated teams can only work for their designated team's matches
                    # unless it's the last match of the day
                    if match.home_team_id != dedicated_to_team_id and match.away_team_id != dedicated_to_team_id:
                        same_day_matches = [m for m in matches if m.date == match.date]
                        if len(same_day_matches) > 1:  # Not the only match of the day
                            for duty in DutyType:
                                self.model.Add(self.variables[match.id][team_id][duty] == 0)
    
    def _add_penalty_constraint(self, rule, matches: List, teams: List):
        """Add soft constraints with penalties"""
        params = rule.parameters or {}
        weight = rule.weight
        
        # These will be handled in the objective function
        constraint_info = {
            'rule': rule,
            'type': 'penalty',
            'weight': weight,
            'parameters': params
        }
        self.constraints.append(constraint_info)
    
    def _add_bonus_constraint(self, rule, matches: List, teams: List):
        """Add soft constraints with bonuses"""
        params = rule.parameters or {}
        weight = rule.weight
        
        # These will be handled in the objective function
        constraint_info = {
            'rule': rule,
            'type': 'bonus',
            'weight': weight,
            'parameters': params
        }
        self.constraints.append(constraint_info)
    
    def _setup_objective(self, matches: List, teams: List, rules: List):
        """Setup the objective function to maximize satisfaction"""
        from backend.models import DutyType
        
        objective_terms = []
        
        # 1. Workload balancing: minimize deviation from ideal workload
        self._add_workload_balancing_objective(matches, teams, objective_terms)
        
        # 2. Process penalty and bonus constraints
        for constraint_info in self.constraints:
            if constraint_info['type'] == 'penalty':
                self._add_penalty_terms(constraint_info, matches, teams, objective_terms)
            elif constraint_info['type'] == 'bonus':
                self._add_bonus_terms(constraint_info, matches, teams, objective_terms)
        
        # Set the objective
        if objective_terms:
            self.model.Maximize(sum(objective_terms))
    
    def _add_workload_balancing_objective(self, matches: List, teams: List, objective_terms: List):
        """Add terms to balance workload according to team weights"""
        from backend.models import DutyType
        
        total_duties = len(matches) * len(DutyType)
        
        for team in teams:
            if not team.is_active:
                continue
                
            # Calculate expected workload based on team weight
            expected_duties = int(total_duties * team.weight / sum(t.weight for t in teams if t.is_active))
            
            # Count actual assigned duties
            team_duties = []
            for match in matches:
                for duty in DutyType:
                    team_duties.append(self.variables[match.id][team.id][duty])
            
            actual_duties = sum(team_duties)
            
            # Minimize deviation from expected workload
            deviation_var = self.model.NewIntVar(0, total_duties, f"deviation_team_{team.id}")
            self.model.Add(deviation_var >= actual_duties - expected_duties)
            self.model.Add(deviation_var >= expected_duties - actual_duties)
            
            # Add penalty for deviation (negative term to minimize)
            objective_terms.append(-10 * deviation_var)
    
    def _add_penalty_terms(self, constraint_info, matches: List, teams: List, objective_terms: List):
        """Add penalty terms to objective"""
        from backend.models import DutyType
        
        params = constraint_info['parameters']
        weight = constraint_info['weight']
        
        if params.get('constraint_type') == 'avoid_consecutive_matches':
            team_id = params.get('team_id')
            if team_id:
                # Penalize consecutive match assignments
                for i, match1 in enumerate(matches):
                    for j, match2 in enumerate(matches[i+1:], i+1):
                        if (match2.date - match1.date).days == 1:  # Consecutive days
                            penalty_var = self.model.NewBoolVar(f"consecutive_penalty_t{team_id}_m{match1.id}_m{match2.id}")
                            
                            # If team works both matches, activate penalty
                            duties1 = [self.variables[match1.id][team_id][duty] for duty in DutyType]
                            duties2 = [self.variables[match2.id][team_id][duty] for duty in DutyType]
                            
                            works_match1 = self.model.NewBoolVar(f"works_m{match1.id}_t{team_id}")
                            works_match2 = self.model.NewBoolVar(f"works_m{match2.id}_t{team_id}")
                            
                            self.model.Add(sum(duties1) >= works_match1)
                            self.model.Add(sum(duties2) >= works_match2)
                            
                            self.model.Add(penalty_var >= works_match1 + works_match2 - 1)
                            
                            objective_terms.append(weight * penalty_var)
    
    def _add_bonus_terms(self, constraint_info, matches: List, teams: List, objective_terms: List):
        """Add bonus terms to objective"""
        from backend.models import DutyType
        
        params = constraint_info['parameters']
        weight = constraint_info['weight']
        
        if params.get('constraint_type') == 'preferred_duty':
            team_id = params.get('team_id')
            preferred_duty = params.get('duty_type')
            
            if team_id and preferred_duty:
                duty_enum = DutyType(preferred_duty)
                for match in matches:
                    if team_id in self.variables[match.id]:
                        objective_terms.append(weight * self.variables[match.id][team_id][duty_enum])
    
    def solve(self) -> Tuple[bool, Dict]:
        """Solve the planning problem and return results"""
        if not self.model or not self.solver:
            return False, {"error": "Model not initialized"}
        
        try:
            status = self.solver.Solve(self.model)
            
            if status == cp_model.OPTIMAL or status == cp_model.FEASIBLE:
                solution = self._extract_solution()
                
                result = {
                    "status": "optimal" if status == cp_model.OPTIMAL else "feasible",
                    "objective_value": self.solver.ObjectiveValue(),
                    "solve_time": self.solver.WallTime(),
                    "assignments": solution,
                    "statistics": {
                        "num_variables": self.solver.NumVariables(),
                        "num_constraints": self.solver.NumConstraints(),
                        "num_branches": self.solver.NumBranches(),
                        "num_conflicts": self.solver.NumConflicts()
                    }
                }
                
                return True, result
            else:
                return False, {
                    "error": f"No solution found. Status: {self.solver.StatusName(status)}",
                    "solve_time": self.solver.WallTime()
                }
                
        except Exception as e:
            logger.error(f"Error solving planning problem: {e}")
            return False, {"error": str(e)}
    
    def _extract_solution(self) -> List[Dict]:
        """Extract the solution from the solved model"""
        from backend.models import DutyType
        
        assignments = []
        
        for match_id in self.variables:
            for team_id in self.variables[match_id]:
                for duty in DutyType:
                    if match_id in self.variables and team_id in self.variables[match_id]:
                        var = self.variables[match_id][team_id][duty]
                        if self.solver.Value(var) == 1:
                            assignments.append({
                                "match_id": match_id,
                                "team_id": team_id,
                                "duty_type": duty.value
                            })
        
        return assignments
