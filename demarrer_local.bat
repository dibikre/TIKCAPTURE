@echo off
setlocal enabledelayedexpansion
title TikCapture Pro - Demarrage de l'Environnement Local

cls
echo =====================================================================
echo                 TIKCAPTURE PRO - DEMARRAGE LOCAL                    
echo =====================================================================
echo.
echo [1/3] Verification des prerequis systeme...

:: Verification de Node.js
where node >nul 2>nul
if %errorlevel% neq 0 (
    echo [ERREUR] Node.js n'est pas installe ou n'est pas present dans le PATH.
    echo Veuillez installer Node.js depuis https://nodejs.org/
    pause
    exit /b 1
) else (
    echo [OK] Node.js detecte.
)

:: Verification de npm
where npm >nul 2>nul
if %errorlevel% neq 0 (
    echo [ERREUR] npm n'est pas detecte.
    pause
    exit /b 1
) else (
    echo [OK] npm detecte.
)

:: Verification de PHP
where php >nul 2>nul
if %errorlevel% neq 0 (
    echo [ATTENTION] PHP n'a pas ete trouve dans le PATH systeme.
    echo Le serveur backend PHP necessite PHP 8.x (ex: XAMPP, Laragon, WAMP ou PHP CLI).
    echo.
    set /p rep_php="Voulez-vous specifier le chemin du binaire php.exe (ou Entree pour ignorer) : "
    if defined rep_php (
        set "PHP_BIN=!rep_php!"
    ) else (
        set "PHP_BIN=php"
    )
) else (
    set "PHP_BIN=php"
    echo [OK] PHP CLI detecte.
)

:: Verification de l'installation des dependances Node
if not exist "node_modules\" (
    echo.
    echo [2/3] Installation des dependances Node.js en cours...
    call npm install
    if %errorlevel% neq 0 (
        echo [ERREUR] Echec de l'installation des dependances avec npm install.
        pause
        exit /b 1
    )
) else (
    echo [OK] Dependances Node.js deja installees.
)

echo.
echo [3/3] Lancement des serveurs localement...
echo.

:: Port Backend PHP et Port Frontend
set "PORT_BACKEND=8000"
set "REPERTOIRE_PUBLIC=%~dp0public"

:: Demarrage du serveur Backend PHP dans une fenetre separee
echo [*] Lancement du Backend PHP sur le port %PORT_BACKEND% (Dossier: %REPERTOIRE_PUBLIC%)...
start "TikCapture - Backend PHP (Port %PORT_BACKEND%)" cmd /k "cd /d "%~dp0" && !PHP_BIN! -S 127.0.0.1:%PORT_BACKEND% -t public"

:: Petite temporisation pour initialiser le backend
timeout /t 2 /nobreak >nul

:: Demarrage du serveur Frontend Vite dans une fenetre separee
echo [*] Lancement du Frontend React (Vite)...
start "TikCapture - Frontend Vite" cmd /k "cd /d "%~dp0" && npm run dev"

echo.
echo =====================================================================
echo                SERVEURS DEMARRES AVEC SUCCES                        
echo =====================================================================
echo.
echo  - Frontend React :  http://localhost:5173 (ou http://localhost:3000)
echo  - Backend PHP    :  http://127.0.0.1:%PORT_BACKEND%
echo  - Racine Public  :  %REPERTOIRE_PUBLIC%
echo.
echo Pour arreter les serveurs, fermez simplement les fenetres d'invite de commandes.
echo.
echo =====================================================================
echo.

set /p ouvrir_navigateur="Souhaitez-vous ouvrir l'application dans votre navigateur ? (O/N) [Defaut: O] : "
if /i "%ouvrir_navigateur%"=="N" goto fin

start http://localhost:5173

:fin
echo.
echo Appuyez sur une touche pour quitter ce panneau de controle...
pause >nul
