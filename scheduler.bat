@echo off
cd /d C:\xampp\php\www\BSLotery
C:\xampp\php\php.exe artisan schedule:run >> storage\logs\scheduler.log 2>&1
