@echo off
REM ==========================================================================
REM  Pemicu Laravel scheduler untuk Windows/Laragon (pengganti crontab).
REM  Daftarkan di Windows Task Scheduler: Trigger "Daily", ulang tiap 1 menit
REM  selama 1 hari; Action jalankan file ini. Centang "Run whether user is
REM  logged on or not". Sesuaikan path php.exe Laragon di bawah bila berbeda
REM  (Task Scheduler tidak mewarisi PATH Laragon).
REM ==========================================================================
cd /d "%~dp0"
"C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe" artisan schedule:run
