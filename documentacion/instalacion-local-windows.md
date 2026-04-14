# Instalacion local en Windows

Este proyecto necesita cuatro capas de herramientas:

- Flutter para la app mobile.
- PHP 8.1+ y Composer para el backend Laravel.
- Node.js y npm para compilar los assets del frontend.
- Google Cloud SDK si vas a desplegar en Cloud Run.

## Opcion rapida

1. Abre PowerShell como administrador.
2. Ejecuta:

```powershell
Set-ExecutionPolicy -Scope Process Bypass
.\setup_windows.ps1
```

3. Cierra y vuelve a abrir PowerShell.
4. Ejecuta:

```powershell
flutter doctor -v
composer --version
php --version
gcloud --version
```

## Que debe quedar instalado

- Git
- Node.js LTS
- Java 17
- Android Studio o Android SDK
- Flutter
- Composer
- PHP 8.1 o superior

## Si falta PHP

Si `php --version` sigue sin responder, instala PHP 8.1+ con una distribucion que lo exponga en PATH, por ejemplo XAMPP o Laragon, y vuelve a probar Composer.

## Setup del proyecto

Cuando las herramientas ya esten listas:



Para levantar Flutter contra otro backend, por ejemplo Cloud Run:

```powershell
flutter run 
```

