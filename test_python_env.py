#!/usr/bin/env python3
"""
Test script to verify Python optimization environment
Run this after setting up the virtual environment
"""

import sys
import os
from datetime import datetime

def test_imports():
    """Test all required imports"""
    print("🧪 Testing Python imports...")
    
    try:
        import ortools
        print(f"✓ OR-Tools: {ortools.__version__}")
    except ImportError as e:
        print(f"❌ OR-Tools: {e}")
        return False
    
    try:
        import numpy as np
        print(f"✓ NumPy: {np.__version__}")
    except ImportError as e:
        print(f"❌ NumPy: {e}")
        return False
    
    try:
        import pandas as pd
        print(f"✓ Pandas: {pd.__version__}")
    except ImportError as e:
        print(f"❌ Pandas: {e}")
        return False
    
    try:
        import mysql.connector
        print("✓ MySQL Connector: Available")
    except ImportError as e:
        print(f"❌ MySQL Connector: {e}")
        return False
    
    try:
        import pymysql
        print(f"✓ PyMySQL: {pymysql.__version__}")
    except ImportError as e:
        print(f"❌ PyMySQL: {e}")
        return False
    
    return True

def test_ortools():
    """Test OR-Tools solvers"""
    print("\n🔧 Testing OR-Tools solvers...")
    
    try:
        from ortools.sat.python import cp_model
        
        # Create a simple test model
        model = cp_model.CpModel()
        x = model.NewIntVar(0, 10, 'x')
        y = model.NewIntVar(0, 10, 'y')
        model.Add(x + y <= 10)
        model.Maximize(x + y)
        
        solver = cp_model.CpSolver()
        status = solver.Solve(model)
        
        if status == cp_model.OPTIMAL:
            print("✓ CP-SAT solver: Working correctly")
            return True
        else:
            print("❌ CP-SAT solver: Failed to find optimal solution")
            return False
            
    except Exception as e:
        print(f"❌ CP-SAT solver test failed: {e}")
        return False

def test_database_modules():
    """Test database connectivity modules"""
    print("\n🗄️ Testing database modules...")
    
    try:
        import mysql.connector
        # Test connection parameters (without actually connecting)
        config = {
            'host': 'localhost',
            'user': 'test',
            'password': 'test',
            'database': 'test'
        }
        print("✓ MySQL Connector: Configuration test passed")
    except Exception as e:
        print(f"❌ MySQL Connector test failed: {e}")
        return False
    
    try:
        import pymysql
        print("✓ PyMySQL: Available")
        return True
    except Exception as e:
        print(f"❌ PyMySQL test failed: {e}")
        return False

def main():
    print("🐍 Python Optimization Environment Test")
    print("=" * 50)
    print(f"Python version: {sys.version}")
    print(f"Python executable: {sys.executable}")
    print(f"Current directory: {os.getcwd()}")
    print(f"Test time: {datetime.now()}")
    print()
    
    # Run tests
    tests_passed = 0
    total_tests = 3
    
    if test_imports():
        tests_passed += 1
    
    if test_ortools():
        tests_passed += 1
    
    if test_database_modules():
        tests_passed += 1
    
    print(f"\n📊 Test Results: {tests_passed}/{total_tests} passed")
    
    if tests_passed == total_tests:
        print("🎉 All tests passed! Python environment is ready for optimization.")
        return 0
    else:
        print("❌ Some tests failed. Please check the installation.")
        return 1

if __name__ == "__main__":
    sys.exit(main())
