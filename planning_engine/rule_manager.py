from typing import Dict, List, Any
from dataclasses import dataclass, asdict
from datetime import date
from backend.models import RuleType, DutyType

@dataclass
class RuleTemplate:
    """Template for creating planning rules"""
    name: str
    description: str
    rule_type: RuleType
    default_weight: float
    parameters_schema: Dict[str, Any]
    category: str

class RuleConfigurationManager:
    """
    Manages the configuration and creation of planning rules
    Provides a modular system for different constraint types
    """
    
    def __init__(self):
        self.rule_templates = self._initialize_rule_templates()
    
    def _initialize_rule_templates(self) -> Dict[str, RuleTemplate]:
        """Initialize predefined rule templates"""
        templates = {}
        
        # Team Availability Rules
        templates["team_unavailable"] = RuleTemplate(
            name="Team Unavailable",
            description="Mark a team as unavailable for a specific date",
            rule_type=RuleType.FORBIDDEN,
            default_weight=-1000.0,
            parameters_schema={
                "constraint_type": "team_unavailable",
                "team_id": {"type": "integer", "required": True, "description": "ID of the unavailable team"},
                "date": {"type": "date", "required": True, "description": "Date when team is unavailable"},
                "reason": {"type": "string", "required": False, "description": "Reason for unavailability"}
            },
            category="availability"
        )
        
        # Workload Distribution Rules
        templates["max_duties_per_period"] = RuleTemplate(
            name="Maximum Duties Per Period",
            description="Limit the number of duties a team can have in a time period",
            rule_type=RuleType.NOT_PREFERRED,
            default_weight=-50.0,
            parameters_schema={
                "constraint_type": "max_duties_per_period",
                "team_id": {"type": "integer", "required": True, "description": "ID of the team"},
                "max_duties": {"type": "integer", "required": True, "description": "Maximum number of duties"},
                "period_days": {"type": "integer", "required": True, "description": "Period in days"},
                "start_date": {"type": "date", "required": False, "description": "Start date of period"},
                "end_date": {"type": "date", "required": False, "description": "End date of period"}
            },
            category="workload"
        )
        
        # Rest Period Rules
        templates["rest_between_matches"] = RuleTemplate(
            name="Rest Between Matches",
            description="Ensure teams have rest time between consecutive assignments",
            rule_type=RuleType.NOT_PREFERRED,
            default_weight=-30.0,
            parameters_schema={
                "constraint_type": "rest_between_matches",
                "team_id": {"type": "integer", "required": False, "description": "Specific team (optional)"},
                "min_rest_days": {"type": "integer", "required": True, "description": "Minimum rest days between matches"},
                "applies_to_all_teams": {"type": "boolean", "required": True, "description": "Apply to all teams"}
            },
            category="rest"
        )
        
        # Dedicated Team Rules
        templates["dedicated_team_assignment"] = RuleTemplate(
            name="Dedicated Team Assignment",
            description="Assign a team only to matches of a specific team",
            rule_type=RuleType.FORBIDDEN,
            default_weight=-1000.0,
            parameters_schema={
                "constraint_type": "dedicated_team_restriction",
                "team_id": {"type": "integer", "required": True, "description": "ID of the jury team"},
                "dedicated_to_team_id": {"type": "integer", "required": True, "description": "ID of the team they serve"},
                "allow_last_match_exception": {"type": "boolean", "required": True, "description": "Allow assignment if only match of day"}
            },
            category="dedication"
        )
        
        # Preference Rules
        templates["preferred_duty_assignment"] = RuleTemplate(
            name="Preferred Duty Assignment",
            description="Team has preference for specific duty types",
            rule_type=RuleType.MOST_PREFERRED,
            default_weight=20.0,
            parameters_schema={
                "constraint_type": "preferred_duty",
                "team_id": {"type": "integer", "required": True, "description": "ID of the team"},
                "duty_type": {"type": "enum", "enum_values": [d.value for d in DutyType], "required": True, "description": "Preferred duty type"},
                "strength": {"type": "float", "required": False, "description": "Preference strength multiplier"}
            },
            category="preferences"
        )
        
        templates["avoid_duty_assignment"] = RuleTemplate(
            name="Avoid Duty Assignment",
            description="Team should avoid specific duty types",
            rule_type=RuleType.LESS_PREFERRED,
            default_weight=-15.0,
            parameters_schema={
                "constraint_type": "avoid_duty",
                "team_id": {"type": "integer", "required": True, "description": "ID of the team"},
                "duty_type": {"type": "enum", "enum_values": [d.value for d in DutyType], "required": True, "description": "Duty type to avoid"},
                "strength": {"type": "float", "required": False, "description": "Avoidance strength multiplier"}
            },
            category="preferences"
        )
        
        # Date-based Rules
        templates["preferred_match_dates"] = RuleTemplate(
            name="Preferred Match Dates",
            description="Team prefers to work on specific dates",
            rule_type=RuleType.MOST_PREFERRED,
            default_weight=10.0,
            parameters_schema={
                "constraint_type": "preferred_dates",
                "team_id": {"type": "integer", "required": True, "description": "ID of the team"},
                "dates": {"type": "array", "items": {"type": "date"}, "required": True, "description": "List of preferred dates"},
                "reason": {"type": "string", "required": False, "description": "Reason for preference"}
            },
            category="scheduling"
        )
        
        templates["avoid_match_dates"] = RuleTemplate(
            name="Avoid Match Dates",
            description="Team should avoid working on specific dates",
            rule_type=RuleType.LESS_PREFERRED,
            default_weight=-25.0,
            parameters_schema={
                "constraint_type": "avoid_dates",
                "team_id": {"type": "integer", "required": True, "description": "ID of the team"},
                "dates": {"type": "array", "items": {"type": "date"}, "required": True, "description": "List of dates to avoid"},
                "reason": {"type": "string", "required": False, "description": "Reason for avoidance"}
            },
            category="scheduling"
        )
        
        # Opponent-based Rules
        templates["avoid_opponent_team"] = RuleTemplate(
            name="Avoid Opponent Team",
            description="Team should avoid working matches against specific opponents",
            rule_type=RuleType.LESS_PREFERRED,
            default_weight=-20.0,
            parameters_schema={
                "constraint_type": "avoid_opponent",
                "team_id": {"type": "integer", "required": True, "description": "ID of the jury team"},
                "opponent_team_id": {"type": "integer", "required": True, "description": "ID of the opponent team to avoid"},
                "reason": {"type": "string", "required": False, "description": "Reason for avoidance"}
            },
            category="opponents"
        )
        
        # Consecutive Match Rules
        templates["avoid_consecutive_matches"] = RuleTemplate(
            name="Avoid Consecutive Matches",
            description="Team should avoid working consecutive matches",
            rule_type=RuleType.NOT_PREFERRED,
            default_weight=-40.0,
            parameters_schema={
                "constraint_type": "avoid_consecutive_matches",
                "team_id": {"type": "integer", "required": False, "description": "Specific team (optional)"},
                "max_consecutive": {"type": "integer", "required": True, "description": "Maximum consecutive matches"},
                "applies_to_all_teams": {"type": "boolean", "required": True, "description": "Apply to all teams"}
            },
            category="scheduling"
        )
        
        return templates
    
    def get_rule_templates(self) -> Dict[str, RuleTemplate]:
        """Get all available rule templates"""
        return self.rule_templates
    
    def get_rule_template(self, template_name: str) -> RuleTemplate:
        """Get a specific rule template"""
        return self.rule_templates.get(template_name)
    
    def get_templates_by_category(self, category: str) -> Dict[str, RuleTemplate]:
        """Get rule templates filtered by category"""
        return {
            name: template for name, template in self.rule_templates.items()
            if template.category == category
        }
    
    def validate_rule_parameters(self, template_name: str, parameters: Dict[str, Any]) -> tuple[bool, List[str]]:
        """Validate rule parameters against template schema"""
        template = self.get_rule_template(template_name)
        if not template:
            return False, [f"Unknown rule template: {template_name}"]
        
        errors = []
        schema = template.parameters_schema
        
        # Check required parameters
        for param_name, param_def in schema.items():
            if param_name == "constraint_type":
                continue  # Skip meta parameter
                
            if isinstance(param_def, dict) and param_def.get("required", False):
                if param_name not in parameters:
                    errors.append(f"Missing required parameter: {param_name}")
                    continue
            
            # Type validation
            if param_name in parameters:
                value = parameters[param_name]
                if isinstance(param_def, dict):
                    param_type = param_def.get("type")
                    
                    if param_type == "integer" and not isinstance(value, int):
                        errors.append(f"Parameter {param_name} must be an integer")
                    elif param_type == "float" and not isinstance(value, (int, float)):
                        errors.append(f"Parameter {param_name} must be a number")
                    elif param_type == "string" and not isinstance(value, str):
                        errors.append(f"Parameter {param_name} must be a string")
                    elif param_type == "boolean" and not isinstance(value, bool):
                        errors.append(f"Parameter {param_name} must be a boolean")
                    elif param_type == "date":
                        try:
                            if isinstance(value, str):
                                date.fromisoformat(value)
                            elif not isinstance(value, date):
                                errors.append(f"Parameter {param_name} must be a valid date")
                        except ValueError:
                            errors.append(f"Parameter {param_name} must be a valid date")
                    elif param_type == "enum":
                        enum_values = param_def.get("enum_values", [])
                        if value not in enum_values:
                            errors.append(f"Parameter {param_name} must be one of: {enum_values}")
        
        return len(errors) == 0, errors
    
    def create_rule_from_template(self, template_name: str, rule_name: str, 
                                parameters: Dict[str, Any], custom_weight: float = None) -> Dict[str, Any]:
        """Create a rule configuration from a template"""
        template = self.get_rule_template(template_name)
        if not template:
            raise ValueError(f"Unknown rule template: {template_name}")
        
        # Validate parameters
        is_valid, errors = self.validate_rule_parameters(template_name, parameters)
        if not is_valid:
            raise ValueError(f"Invalid parameters: {', '.join(errors)}")
        
        # Add constraint type to parameters
        final_parameters = dict(parameters)
        if "constraint_type" in template.parameters_schema:
            final_parameters["constraint_type"] = template.parameters_schema["constraint_type"]
        
        # Create rule configuration
        rule_config = {
            "name": rule_name,
            "description": template.description,
            "rule_type": template.rule_type,
            "weight": custom_weight if custom_weight is not None else template.default_weight,
            "parameters": final_parameters,
            "template": template_name,
            "is_active": True
        }
        
        return rule_config
    
    def get_weight_recommendations(self) -> Dict[str, Dict[str, float]]:
        """Get recommended weight ranges for different rule types"""
        return {
            "forbidden": {
                "min": -1000.0,
                "max": -500.0,
                "default": -1000.0,
                "description": "Hard constraints that must not be violated"
            },
            "not_preferred": {
                "min": -100.0,
                "max": -20.0,
                "default": -50.0,
                "description": "Strong negative preferences"
            },
            "less_preferred": {
                "min": -50.0,
                "max": -5.0,
                "default": -15.0,
                "description": "Mild negative preferences"
            },
            "most_preferred": {
                "min": 5.0,
                "max": 50.0,
                "default": 20.0,
                "description": "Positive preferences and bonuses"
            }
        }
    
    def export_rules_config(self, rules: List) -> Dict[str, Any]:
        """Export rules configuration for backup/import"""
        config = {
            "version": "1.0",
            "exported_at": date.today().isoformat(),
            "rules": []
        }
        
        for rule in rules:
            rule_config = {
                "name": rule.name,
                "description": rule.description,
                "rule_type": rule.rule_type.value,
                "weight": rule.weight,
                "parameters": rule.parameters,
                "is_active": rule.is_active
            }
            config["rules"].append(rule_config)
        
        return config
    
    def import_rules_config(self, config: Dict[str, Any]) -> List[Dict[str, Any]]:
        """Import rules configuration from backup"""
        if config.get("version") != "1.0":
            raise ValueError("Unsupported configuration version")
        
        imported_rules = []
        for rule_data in config.get("rules", []):
            try:
                rule_config = {
                    "name": rule_data["name"],
                    "description": rule_data["description"],
                    "rule_type": RuleType(rule_data["rule_type"]),
                    "weight": rule_data["weight"],
                    "parameters": rule_data["parameters"],
                    "is_active": rule_data.get("is_active", True)
                }
                imported_rules.append(rule_config)
            except Exception as e:
                # Log error but continue with other rules
                continue
        
        return imported_rules
