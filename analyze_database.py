#!/usr/bin/env python3
"""
Database Structure Analyzer
Analyzes the existing database structure and generates documentation
"""

import mysql.connector
import os
from dotenv import load_dotenv
import json
from datetime import datetime

# Load environment variables
load_dotenv()

def connect_to_database():
    """Connect to the production database"""
    try:
        connection = mysql.connector.connect(
            host=os.getenv('DB_HOST'),
            database=os.getenv('DB_NAME'),
            user=os.getenv('DB_USER'),
            password=os.getenv('DB_PASSWORD'),
            port=int(os.getenv('DB_PORT', 3306))
        )
        return connection
    except mysql.connector.Error as e:
        print(f"❌ Error connecting to database: {e}")
        return None

def analyze_database_structure(connection):
    """Analyze and document the database structure"""
    cursor = connection.cursor()
    
    print("🔍 Analyzing Database Structure...")
    print("=" * 60)
    
    # Get all tables
    cursor.execute("SHOW TABLES")
    tables = [table[0] for table in cursor.fetchall()]
    
    print(f"📊 Found {len(tables)} tables:")
    for table in tables:
        print(f"   - {table}")
    
    print("\n" + "=" * 60)
    
    database_info = {
        'analyzed_at': datetime.now().isoformat(),
        'database': os.getenv('DB_NAME'),
        'tables': {}
    }
    
    # Analyze each table
    for table in tables:
        print(f"\n📋 Table: {table}")
        print("-" * 40)
        
        # Get table structure
        cursor.execute(f"DESCRIBE {table}")
        columns = cursor.fetchall()
        
        # Get row count
        cursor.execute(f"SELECT COUNT(*) FROM {table}")
        row_count = cursor.fetchone()[0]
        
        table_info = {
            'row_count': row_count,
            'columns': []
        }
        
        print(f"📈 Rows: {row_count}")
        print("🏗️  Structure:")
        
        for column in columns:
            field, type_, null, key, default, extra = column
            column_info = {
                'name': field,
                'type': type_,
                'null': null,
                'key': key,
                'default': default,
                'extra': extra
            }
            table_info['columns'].append(column_info)
            
            # Format output
            null_str = "NULL" if null == "YES" else "NOT NULL"
            key_str = f" {key}" if key else ""
            default_str = f" DEFAULT {default}" if default else ""
            extra_str = f" {extra}" if extra else ""
            
            print(f"   {field:<20} {type_:<15} {null_str:<8}{key_str}{default_str}{extra_str}")
        
        database_info['tables'][table] = table_info
        
        # Show sample data if table has rows
        if row_count > 0:
            print("📄 Sample Data (first 3 rows):")
            cursor.execute(f"SELECT * FROM {table} LIMIT 3")
            sample_rows = cursor.fetchall()
            
            if sample_rows:
                # Get column names for header
                column_names = [col[0] for col in columns]
                
                # Print header
                header = " | ".join([name[:15] for name in column_names])
                print(f"   {header}")
                print("   " + "-" * len(header))
                
                # Print sample rows
                for row in sample_rows:
                    row_str = " | ".join([str(val)[:15] if val is not None else "NULL" for val in row])
                    print(f"   {row_str}")
    
    cursor.close()
    return database_info

def generate_schema_sql(database_info):
    """Generate SQL schema based on analyzed structure"""
    print("\n" + "=" * 60)
    print("🔧 Generating SQL Schema...")
    
    sql_content = f"""-- Database Schema for {database_info['database']}
-- Generated on {database_info['analyzed_at']}
-- 
-- This schema represents the existing database structure
-- analyzed from your production database

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

CREATE DATABASE IF NOT EXISTS `{database_info['database']}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `{database_info['database']}`;

"""
    
    for table_name, table_info in database_info['tables'].items():
        sql_content += f"\n-- Table: {table_name} (Current rows: {table_info['row_count']})\n"
        sql_content += f"CREATE TABLE `{table_name}` (\n"
        
        column_definitions = []
        for column in table_info['columns']:
            col_def = f"  `{column['name']}` {column['type']}"
            
            if column['null'] == 'NO':
                col_def += " NOT NULL"
            
            if column['default'] is not None:
                if column['default'] == 'CURRENT_TIMESTAMP':
                    col_def += " DEFAULT CURRENT_TIMESTAMP"
                else:
                    col_def += f" DEFAULT '{column['default']}'"
            
            if column['extra']:
                col_def += f" {column['extra']}"
            
            if column['key'] == 'PRI':
                col_def += " PRIMARY KEY"
            
            column_definitions.append(col_def)
        
        sql_content += ",\n".join(column_definitions)
        sql_content += f"\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n"
    
    sql_content += "\nCOMMIT;\n"
    
    return sql_content

def main():
    """Main analysis function"""
    print("🏊‍♂️ Jury Planner Database Analyzer")
    print("=" * 60)
    
    # Connect to database
    connection = connect_to_database()
    if not connection:
        return
    
    try:
        # Analyze structure
        database_info = analyze_database_structure(connection)
        
        # Generate documentation
        with open('database_analysis.json', 'w') as f:
            json.dump(database_info, f, indent=2, default=str)
        
        # Generate SQL schema
        schema_sql = generate_schema_sql(database_info)
        with open('existing_schema.sql', 'w') as f:
            f.write(schema_sql)
        
        print("\n" + "=" * 60)
        print("✅ Analysis Complete!")
        print("📄 Files generated:")
        print("   - database_analysis.json (detailed analysis)")
        print("   - existing_schema.sql (SQL schema)")
        print("\n🎯 Next: Review the structure and adapt the jury planner system")
        
    except Exception as e:
        print(f"❌ Error during analysis: {e}")
    
    finally:
        connection.close()

if __name__ == "__main__":
    main()
