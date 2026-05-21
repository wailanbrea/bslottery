@echo off
cd /d C:\xampp\php\www\BSLotery
C:\xampp\php\php.exe artisan queue:work --sleep=3 --tries=3 --max-time=3600 --queue=default >> C:\xampp\php\www\BSLotery\storage\logs\queue.log 2>&1
