# LinusTodo

Each project is a vertical pipe. Items sit in order, and you work the one at the bottom. Finish it and it drops out; the item above becomes the one you work. Meetings and other dated tasks sit in the same pipe, so you can see what has to be finished before that time.

The screen is in Swedish. There are no accounts.

## Run locally

Needs PHP 8.3+, Composer, and Node.js 22+.

Backend (Laravel, SQLite):

```bash
cd backend
cp .env.example .env
composer install
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan serve
```

Frontend (React):

```bash
cd frontend
npm install
npm run dev
```

Open http://127.0.0.1:5173. The dev server proxies `/api` to http://127.0.0.1:8000. Port 8000 is the API host; the todo board is the React app.

`migrate --seed` loads two example pipes, Jobb and Hem. Times use Europe/Stockholm.

New items land at the top of a pipe. Move them down to place a deadline in the sequence. Only the bottom item can be marked done. A meeting or dated task at the bottom is passed the same way.
