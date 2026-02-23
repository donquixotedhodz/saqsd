# Database Export and Import Guide

## Admin User Setup

Before using the system, you need an admin user. Use the "Seed Admin" button in Settings to create/update an admin user with these credentials:

- **Username:** admin
- **Password:** admin123
- **Email:** admin@saqsd.com
- **Role:** Administrator

**Important:** Change the password after first login for security!

## Export Database

1. Log in as an admin user
2. Go to Settings page
3. Click the "Export Database" button in the Maintenance section
4. A SQL file will be automatically downloaded with a timestamped filename

The exported file includes:

- Database creation statement
- All CREATE TABLE statements with indexes and constraints
- INSERT statements for all existing data

## Import Database

1. Log in as an admin user
2. Go to Settings page
3. Click the "Import Database" button
4. Choose one of two methods:

### Method 1: Upload SQL File

- Click "Choose File" and select your .sql file
- Choose import method:
  - **Delete entire database**: Nuclear option (drops and recreates the whole database)
  - **Drop existing tables**: Complete restore (deletes all data and recreates tables)
  - **Clear existing data**: Keeps table structure but removes current data
  - **Neither**: Merges data with existing tables (may cause conflicts)
- Click "Import from File"
- The system will execute all SQL statements in the file

### Method 2: Paste SQL Content

- Copy and paste SQL content into the text area
- Choose import method (same options as above)
- Click "Execute SQL"
- The system will execute the SQL statements

## Import Options

### Delete Entire Database (Nuclear Option)

- **When checked**: Drops the entire database and recreates it from scratch
- **Use for**: Complete system reset or when switching database versions
- **Most destructive**: Deletes everything including custom configurations
- **⚠️ EXTREME WARNING**: This will completely erase your database!

### Drop Existing Tables

- **When checked**: All existing tables will be dropped before creating new ones
- **Use for**: Complete database restoration from backup
- **Warning**: This will delete all existing data!

### Clear Existing Data

- **When checked**: Existing table structures are preserved, but all data is cleared
- **Use for**: Restoring data while keeping custom table modifications
- **Safer than**: Dropping tables

### Neither Option

- **Default behavior**: Existing tables and data are preserved
- **Use for**: Adding new data or updating existing records
- **May fail if**: Primary keys or unique constraints conflict

## Important Notes

- **Backup First**: Always backup your current database before importing
- **File Size Limit**: Maximum file size is 10MB
- **SQL Validation**: The system validates SQL syntax before execution
- **Transaction Safety**: Import operations use transactions - if any statement fails, all changes are rolled back
- **Admin Only**: Only admin users can access export/import functionality

## Supported SQL Operations

- CREATE DATABASE/TABLE
- INSERT statements
- ALTER TABLE
- DROP statements
- Other DDL and DML operations

## Troubleshooting

- If import fails, check the SQL file for syntax errors
- Ensure the SQL file is properly formatted with semicolons
- Comments (starting with --) are ignored
- Large files may take time to process
