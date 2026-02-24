# Deploy Staging: Google Cloud Run + Supabase (LookCrime)

Este repo está dividido en:

- `server/`: Laravel (web + API)
- `frontend/`: Vite/Tailwind (compila hacia `server/public/build`)

## 1) Requisitos

- Proyecto en Google Cloud
- `gcloud` instalado y logueado: `gcloud auth login`
- APIs habilitadas:
  - Cloud Run
  - Cloud Build
  - Artifact Registry
- Supabase Postgres (con PostGIS habilitado)
- Bucket de Google Cloud Storage (para imágenes)

## 2) Supabase (DB)

1. Crear un proyecto en Supabase.
2. Habilitar PostGIS en la DB (Extension `postgis`).
3. Guardar credenciales:
   - Host, puerto, database, username, password
4. Para staging se recomienda SSL:
   - Setear `DB_SSLMODE=require`

## 3) Google Cloud Storage (uploads)

Cloud Run no puede almacenar archivos localmente de forma persistente.
Para imágenes (registers) se usa GCS configurando el disco `public` como `gcs`.

- Crear bucket (ejemplo):
  - `gsutil mb -l us-central1 gs://TU_BUCKET`

Permisos:
- El servicio (Cloud Run) debe tener permiso para escribir en el bucket:
  - `roles/storage.objectAdmin`

Opcional (staging): si querés que las imágenes sean públicas:
- Configurá acceso público a objetos (o usá Signed URLs en el futuro).

## 4) Variables de entorno (Cloud Run)

### Laravel

- `APP_ENV=staging`
- `APP_DEBUG=false`
- `APP_KEY=base64:...` (copiar desde tu `.env` local)
- `APP_URL=https://TU_URL_DE_CLOUD_RUN`
- `LOG_CHANNEL=stderr`

### Supabase (Postgres)

- `DB_CONNECTION=pgsql`
- `DB_HOST=...`
- `DB_PORT=5432`
- `DB_DATABASE=...`
- `DB_USERNAME=...`
- `DB_PASSWORD=...`
- `DB_SSLMODE=require`

### Storage (GCS)

- `FILESYSTEM_PUBLIC_DRIVER=gcs`
- `GCS_BUCKET=TU_BUCKET`
- (Opcional) `GCS_PROJECT_ID=...` (normalmente no hace falta en Cloud Run)
- (Opcional) `GCS_PATH_PREFIX=staging` (para separar carpetas)
- (Opcional) `GCS_PUBLIC_URL=https://storage.googleapis.com/TU_BUCKET` (si los objetos son públicos)

## 5) Build local (verificación)

En tu PC:

```powershell
cd frontend
npm install
npm run build

cd ..\server
php artisan optimize:clear
php artisan route:list
```

## 6) Deploy a Cloud Run (usando Dockerfile)

Desde la raíz del repo (donde está `Dockerfile`):

```powershell
gcloud config set project TU_PROJECT_ID

# Construir y subir con Cloud Build
gcloud builds submit --tag gcr.io/TU_PROJECT_ID/lookcrime-staging

# Deploy
gcloud run deploy lookcrime-staging \
  --image gcr.io/TU_PROJECT_ID/lookcrime-staging \
  --region us-central1 \
  --platform managed \
  --allow-unauthenticated \
  --port 8080
```

Luego en Cloud Run → **Variables & Secrets** cargá las env vars de la sección 4.

## 7) Migraciones (recomendado: manual)

En staging, lo más seguro es correr migraciones manualmente contra Supabase:

- Opción A (local): ponés las env de staging en tu `.env` local temporalmente y corrés:

```powershell
cd server
php artisan migrate --force
```

- Opción B: crear un Cloud Run Job para `php artisan migrate --force` (si querés, lo armamos después).

## 8) Nota sobre imágenes

Con `FILESYSTEM_PUBLIC_DRIVER=gcs`, el código seguirá usando `Storage::disk('public')` y las URLs se van a generar con `GCS_PUBLIC_URL` (o el fallback `storage.googleapis.com`).

---

Si querés, el siguiente paso es: crear el bucket + service account, y dejar la lista exacta de comandos `gcloud` con tu `PROJECT_ID`, `REGION` y nombre de bucket.
