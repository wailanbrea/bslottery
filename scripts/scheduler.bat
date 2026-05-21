@echo off
cd /d C:\xampp\php\www\BSLotery
C:\xampp\php\php.exe artisan schedule:run >> C:\xampp\php\www\BSLotery\storage\logs\scheduler.log 2>&1
