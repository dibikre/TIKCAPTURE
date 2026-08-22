@echo off
setlocal EnableDelayedExpansion
title TikCapture Pro - Demarrage Local

cls
echo =====================================================================
echo                 TIKCAPTURE PRO - DEMARRAGE LOCAL                    
echo =====================================================================
echo.
echo [1/3] Verification des prerequis systeme...

REM Verification de Node.js
where node >nul 2>nul
if %errorlevel% neq 0 (
    echo [ERREUR] Node.js n'est pas installe ou n'est pas present dans le PATH.
    echo Veuillez installer Node.js depuis https://nodejs.org/
    echo.
    pause
    exit /b 1
)
echo [OK] Node.js detecte.

REM Verification de npm
where npm >nul 2>nul
if %errorlevel% neq 0 (
    echo [ERREUR] npm n'est pas detecte dans le PATH.
    echo.
    pause
    exit /b 1
)
echo [OK] npm detecte.

REM Verification et detection automatique de PHP
set "PHP_CMD="

REM Test 1 : PHP dans le PATH
where php >nul 2>nul
if %errorlevel% equ 0 (
    set "PHP_CMD=php"
    goto php_trouve
)

REM Test 2 : XAMPP
if exist "C:\xampp\php\php.exe" (
    set "PHP_CMD=C:\xampp\php\php.exe"
    goto php_trouve
)

REM Test 3 : WampServer (wamp64)
for /d %%D in ("C:\wamp64\bin\php\php*") do (
    if exist "%%~fD\php.exe" (
        set "PHP_CMD=%%~fD\php.exe"
        goto php_trouve
    )
)

REM Test 4 : Laragon
for /d %%D in ("C:\laragon\bin\php\php*") do (
    if exist "%%~fD\php.exe" (
        set "PHP_CMD=%%~fD\php.exe"
        goto php_trouve
    )
)

REM Test 5 : C:\php
if exist "C:\php\php.exe" (
    set "PHP_CMD=C:\php\php.exe"
    goto php_trouve
)

REM Test 6 : Program Files
if exist "C:\Program Files\PHP\php.exe" (
    set "PHP_CMD=C:\Program Files\PHP\php.exe"
    goto php_trouve
)

:demander_php
echo [ATTENTION] PHP CLI n'a pas ete detecte automatiquement.
echo Si vous utilisez XAMPP, WAMP ou Laragon, veuillez indiquer le chemin vers php.exe.
echo Exemple : C:\xampp\php\php.exe
echo.
set /p "CHEMIN_PHP=Chemin vers php.exe [ou Entree pour tester 'php'] : "

if "%CHEMIN_PHP%"=="" (
    set "PHP_CMD=php"
) else (
    set "PHP_CMD=%CHEMIN_PHP%"
)

:php_trouve
echo [OK] PHP configure : "%PHP_CMD%"

REM Verification des dependances Node
echo.
echo [2/3] Verification des modules Node.js...
if not exist "%~dp0node_modules\" (
    echo [*] Installation des dependances avec npm install...
    call npm install
    if %errorlevel% neq 0 (
        echo [ERREUR] Echec de npm install.
        pause
        exit /b 1
    )
) else (
    echo [OK] Dependances Node.js presentes.
)

echo.
echo [3/3] Lancement des serveurs en arriere-plan...

set "PORT_BACKEND=8000"
set "DOSSIER_PUBLIC=%~dp0public"

REM Demarrage du Backend PHP dans une invite dediee
echo [*] Demarrage Backend PHP sur http://127.0.0.1:%PORT_BACKEND%
start "TikCapture - Backend PHP" cmd /k "cd /d "%~dp0" && "%PHP_CMD%" -S 127.0.0.1:%PORT_BACKEND% -t public"

REM Attente courte pour laisser PHP se lier au port
ping 127.0.0.1 -n 3 >nul

REM Demarrage du Frontend Vite dans une invite dediee
echo [*] Demarrage Frontend React (Vite)...
start "TikCapture - Frontend Vite" cmd /k "cd /d "%~dp0" && npm run dev"

echo.
echo =====================================================================
echo                SERVEURS DEMARRES AVEC SUCCES                        
echo =====================================================================
echo.
echo  - Frontend React :  http://localhost:5173  (ou http://localhost:3000)
echo  - Backend PHP    :  http://127.0.0.1:%PORT_BACKEND%
echo  - Racine PHP     :  %DOSSIER_PUBLIC%
echo.
echo Pour arreter les serveurs, fermez les deux fenetres invite de commandes.
echo =====================================================================
echo.

set /p "REP_NAV=Ouvrir l'application dans votre navigateur maintenant ? (O/N) [Defaut: O] : "
if /i "%REP_NAV%"=="N" goto fin

start http://localhost:5173

:fin
echo.
echo Appuyez sur une touche pour fermer ce lanceur...
pause >nul
