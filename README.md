## Crypto Exchange Project

This repository contains a Laravel 12 API and a Vue 3 + Tailwind SPA that together simulate a tiny spot-exchange. It demonstrates atomic balance updates, asset locking, simple limit order matching, and Pusher-powered real-time updates that fan out to both counterparties.

### Features

- **Accounts & Auth** — token-based authentication via Laravel Sanctum with demo seeding for quick starts.
- **Wallet Model** — USD balance tracked on the `users` table plus per-symbol assets with locked amounts for open sell orders.
- **Orders & Matching** — limit orders with full-match semantics, FIFO priority, automatic matching against the first valid counter order, and a 1.5 % commission charged to the buyer.
- **Real-time** — OrderMatched events broadcast over Pusher to `private-user.{id}` channels; the Vue client listens through Laravel Echo and patches balances/orders instantly.
- **Safety** — critical updates run inside DB transactions with row-level locking (and SQLite-safe fallbacks) to prevent race conditions.
- **Tests** — feature tests cover wallet reads, locking logic, and an end-to-end match between buy/sell orders.

---

## Local Development

### Prerequisites

- PHP 8.2+, Composer, and a database (SQLite/MySQL/PostgreSQL).
- Node 20+ and npm.
- A Pusher account (or a self-hosted compatible server such as laravel-websockets).

### Backend

```bash
cd backend
cp .env.example .env            # tailor DB + Pusher credentials
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve               # http://localhost:8000
```

If your are using MySQL, create a database and adjust `.env`:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=crypto_exchange
DB_USERNAME=your_user
DB_PASSWORD=your_password
```

Important environment variables:

```
BROADCAST_CONNECTION=pusher
PUSHER_APP_ID=your-app-id
PUSHER_APP_KEY=your-key
PUSHER_APP_SECRET=your-secret
PUSHER_APP_CLUSTER=mt1
PUSHER_HOST=                # optional for self-hosted
PUSHER_PORT=443
PUSHER_SCHEME=https
```

Sanctum uses API tokens for every request, so the Vue client simply stores the bearer token locally.

Run tests with:

```bash
php artisan test
```

### Frontend

```bash
cd frontend
cp .env.example .env
npm install
npm run dev                   # http://localhost:5173
```

Match the Pusher settings from the backend:

```
VITE_API_BASE_URL=http://localhost:8000/api
VITE_PUSHER_APP_KEY=your-key
VITE_PUSHER_APP_CLUSTER=mt1
VITE_PUSHER_HOST=
VITE_PUSHER_PORT=443
VITE_PUSHER_SCHEME=https
```

The SPA offers:

1. Authentication (register/login/logout).
2. A limit order form supporting buy & sell sides with USD notional previews.
3. Wallet/Orders/Orderbook panels that auto-refresh when broadcasts arrive.

---

## API Surface

| Method | Endpoint                  | Notes                                               |
| ------ | ------------------------- | --------------------------------------------------- |
| POST   | `/api/register`           | Creates user, returns token + profile.              |
| POST   | `/api/login`              | Returns Sanctum token + profile.                    |
| POST   | `/api/logout`             | Revokes the current token.                          |
| GET    | `/api/profile`            | Wallet overview (USD + assets).                     |
| GET    | `/api/orders?mine=1`      | Authenticated user history (open/filled/cancelled). |
| GET    | `/api/orders?symbol=BTC`  | Public orderbook snapshot for the symbol.           |
| POST   | `/api/orders`             | Places a limit order (buy/sell).                    |
| POST   | `/api/orders/{id}/cancel` | Cancels an open order and releases locked funds.    |

Matching rules:

- A buy order locks `amount * price` plus the 1.5 % commission buffer.
- A sell order moves the requested amount into `assets.locked_amount`.
- Only full matches are executed (no partial fills). The newest order matches against the first valid counter order that satisfies the price condition.
- When a trade executes, USD is transferred to the seller, assets to the buyer, the fee remains with the platform, and an `OrderMatched` event is broadcast to both users.

---

## Demo Data

`php artisan migrate --seed` creates a `demo@example.com / password` account with USD funds and BTC/ETH holdings so you can immediately place sell orders and see buy orders match in the UI.
