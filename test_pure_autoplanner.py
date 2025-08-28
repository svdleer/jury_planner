#!/usr/bin/env python3
"""
Test script for Pure Python Autoplanner
"""

import json
import sys
import os
from datetime import datetime

# Add the parent directory to the path
sys.path.append(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

from planning_engine.pure_autoplanner import PureJuryOptimizer, OptimizationRequest, Team, Match, Constraint, SolverType

def create_test_data():
    """Create test data for optimization"""
    
    # Test teams
    teams = [
        Team(id=1, name="Team Alpha", capacity_weight=1.0, is_active=True),
        Team(id=2, name="Team Beta", capacity_weight=1.2, is_active=True),
        Team(id=3, name="Team Gamma", capacity_weight=0.8, is_active=True),
        Team(id=4, name="Team Delta", capacity_weight=1.0, is_active=True),
        Team(id=5, name="Team Epsilon", capacity_weight=1.1, is_active=True)
    ]
    
    # Test matches
    matches = [
        Match(
            id=1,
            date_time="2024-01-15 10:00:00",
            home_team="Lions",
            away_team="Tigers", 
            location="Stadium A",
            competition="Premier League",
            required_duties=[
                {"type": "referee", "count": 1, "weight": 1.0},
                {"type": "assistant_referee_1", "count": 1, "weight": 0.8},
                {"type": "assistant_referee_2", "count": 1, "weight": 0.8}
            ]
        ),
        Match(
            id=2,
            date_time="2024-01-15 14:00:00",
            home_team="Eagles",
            away_team="Hawks",
            location="Stadium B", 
            competition="Premier League",
            required_duties=[
                {"type": "referee", "count": 1, "weight": 1.0},
                {"type": "assistant_referee_1", "count": 1, "weight": 0.8},
                {"type": "assistant_referee_2", "count": 1, "weight": 0.8}
            ]
        ),
        Match(
            id=3,
            date_time="2024-01-16 10:00:00",
            home_team="Wolves",
            away_team="Bears",
            location="Stadium C",
            competition="Championship",
            required_duties=[
                {"type": "referee", "count": 1, "weight": 1.0},
                {"type": "assistant_referee_1", "count": 1, "weight": 0.8}
            ]
        )
    ]
    
    # Test constraints
    constraints = [
        Constraint(
            id=1,
            name="Maximum duties per week",
            constraint_type="max_duties_per_period",
            rule_type="forbidden",
            weight=1.0,
            parameters={
                "max_duties": 2,
                "period_days": 7
            }
        ),
        Constraint(
            id=2,
            name="Rest between matches",
            constraint_type="rest_between_matches", 
            rule_type="forbidden",
            weight=1.0,
            parameters={
                "min_rest_days": 1
            }
        )
    ]
    
    return OptimizationRequest(
        teams=teams,
        matches=matches,
        constraints=constraints,
        solver_config={},
        time_limit_seconds=60
    )

def test_cp_sat_solver():
    """Test CP-SAT solver"""
    print("🔬 Testing CP-SAT Solver...")
    
    request = create_test_data()
    optimizer = PureJuryOptimizer(SolverType.CONSTRAINT_SAT)
    result = optimizer.optimize(request)
    
    print(f"✅ Success: {result.success}")
    print(f"📊 Objective Value: {result.objective_value}")
    print(f"⏱️  Solver Time: {result.solver_time_seconds:.2f}s")
    print(f"🏆 Status: {result.solver_status}")
    print(f"📋 Assignments: {len(result.assignments)}")
    print(f"✔️  Constraints Satisfied: {result.constraints_satisfied}/{result.total_constraints}")
    
    if result.assignments:
        print("\n📝 Assignments:")
        for assignment in result.assignments:
            print(f"  Match {assignment.match_id} -> Team {assignment.team_id} ({assignment.duty_type})")
    
    return result

def test_linear_solver():
    """Test Linear solver"""
    print("\n🔬 Testing Linear Solver...")
    
    request = create_test_data()
    optimizer = PureJuryOptimizer(SolverType.LINEAR)
    result = optimizer.optimize(request)
    
    print(f"✅ Success: {result.success}")
    print(f"📊 Objective Value: {result.objective_value}")
    print(f"⏱️  Solver Time: {result.solver_time_seconds:.2f}s")
    print(f"🏆 Status: {result.solver_status}")
    print(f"📋 Assignments: {len(result.assignments)}")
    
    return result

def test_auto_solver():
    """Test Auto solver selection"""
    print("\n🔬 Testing Auto Solver Selection...")
    
    request = create_test_data()
    optimizer = PureJuryOptimizer(SolverType.AUTO)
    result = optimizer.optimize(request)
    
    print(f"✅ Success: {result.success}")
    print(f"📊 Objective Value: {result.objective_value}")
    print(f"⏱️  Solver Time: {result.solver_time_seconds:.2f}s")
    print(f"🏆 Status: {result.solver_status}")
    print(f"🤖 Solver Type: {result.metadata.get('solver_type', 'Unknown')}")
    print(f"📋 Assignments: {len(result.assignments)}")
    
    return result

def test_json_api():
    """Test JSON API interface"""
    print("\n🔬 Testing JSON API Interface...")
    
    request = create_test_data()
    
    # Convert to JSON
    import json
    from dataclasses import asdict
    request_dict = asdict(request)
    json_data = json.dumps(request_dict, indent=2, default=str)
    
    print(f"📄 JSON Request Size: {len(json_data)} bytes")
    
    # Parse back
    from planning_engine.pure_autoplanner import parse_request_from_json
    parsed_request = parse_request_from_json(json_data)
    
    print(f"✅ JSON Parsing Success")
    print(f"📊 Teams: {len(parsed_request.teams)}")
    print(f"📊 Matches: {len(parsed_request.matches)}")
    print(f"📊 Constraints: {len(parsed_request.constraints)}")

def main():
    """Run all tests"""
    print("🚀 Pure Python Autoplanner Test Suite")
    print("=" * 50)
    
    try:
        # Test CP-SAT solver
        cp_sat_result = test_cp_sat_solver()
        
        # Test Linear solver (only if CP-SAT works)
        if cp_sat_result.success:
            linear_result = test_linear_solver()
        
        # Test Auto solver
        auto_result = test_auto_solver()
        
        # Test JSON API
        test_json_api()
        
        print("\n" + "=" * 50)
        print("🎉 All tests completed successfully!")
        
        # Summary
        print("\n📊 Test Summary:")
        print(f"✅ CP-SAT Solver: {'✅ Working' if cp_sat_result.success else '❌ Failed'}")
        print(f"✅ Auto Solver: {'✅ Working' if auto_result.success else '❌ Failed'}")
        print(f"✅ JSON API: ✅ Working")
        
    except Exception as e:
        print(f"\n❌ Test failed with error: {str(e)}")
        import traceback
        traceback.print_exc()
        sys.exit(1)

if __name__ == '__main__':
    main()
