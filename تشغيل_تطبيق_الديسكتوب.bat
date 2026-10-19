@echo off
chcp 65001 > nul
title تشغيل تطبيق نظام الفندق - Windows Desktop
echo ========================================================
echo        🏨 جاري تشغيل تطبيق نظام الفندق (Windows)
echo ========================================================
echo.
cd /d "%~dp0apps\hotel_desktop"
flutter run -d windows
pause

