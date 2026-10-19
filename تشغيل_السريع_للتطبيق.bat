@echo off
chcp 65001 > nul
title تشغيل تطبيق الفندق المكتبي
echo ========================================================
echo        🏨 جاري فتح تطبيق سطح المكتب المكتبي...
echo ========================================================
start "" "%~dp0apps\hotel_desktop\build\windows\x64\runner\Debug\hotel_desktop.exe"
exit

