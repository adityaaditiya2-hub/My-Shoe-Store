@echo off
echo Installing Node.js dependencies...
call npm install
if %errorlevel% neq 0 (
    echo Error: npm install failed. Please ensure Node.js is installed.
    pause
    exit /b %errorlevel%
)

echo Setting up Database...
echo Please enter your MySQL password (leave blank if none).
mysql -u root -p < database/schema.sql
if %errorlevel% neq 0 (
    echo Error: Database setup failed. Please ensure MySQL is running and in your PATH.
    pause
    exit /b %errorlevel%
)

echo Setup complete! You can now run 'start_server.bat' to start the server.
pause
