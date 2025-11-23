# Order Processing System – Laravel, Horizon, Redis, PostgreSQL, Docker

![Laravel 12](https://img.shields.io/badge/Laravel-10-ff2d20?logo=laravel&logoColor=white) ![PHP 8.2+](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)
![PostgreSQL 17](https://img.shields.io/badge/PostgreSQL-17-336791?logo=postgresql&logoColor=white)
![Redis](https://img.shields.io/badge/Redis-Latest-dc382d?logo=redis&logoColor=white)
![Horizon](https://img.shields.io/badge/Horizon-Queue%20Dashboard-e9573f?logo=laravel&logoColor=white)
![Docker](https://img.shields.io/badge/Docker-Ready-2496ED?logo=docker&logoColor=white)
![Deadlock Safe](https://img.shields.io/badge/Stock-Deadlock%20Safe-blueviolet)
![Idempotent Refunds](https://img.shields.io/badge/Refunds-Idempotent-brightgreen)

A highly scalable and production-ready order processing system built with **Laravel 10+**, **Laravel Horizon**, **Redis**, **PostgreSQL**, and fully containerized using **Docker**.  

This project demonstrates real-world challenges such as:
- Large CSV order imports (thousands of rows)
- Atomic stock reservation with concurrency safety
- Two-phase order fulfillment (reserve → payment → finalize/rollback)
- Payment simulation with callback handling
- KPI tracking & leaderboards using Redis
- Refund processing
- Queue monitoring via Horizon
- Deadlock & race condition testing

Perfect for interviews, architecture discussions, or as a boilerplate for e-commerce backends.

## 📁 Project Structure
~~~sh
docs/
├── erd.png                     # Entity Relationship Diagram
├── api_collection.json         # Postman / Insomnia collection
├── sample_csv_generator.php    # Generates large test CSV


docker/
├── nginx/default.conf
└── supervisor
    └── horizon.conf
    └── php-fpm.conf
    
└── README.md                   # You are here
~~~

## How to Run

Follow the steps below to set up and run the full system.

You need to have installed Docker on you system

---

### **1. Start Docker Containers**

Build and start all services (PHP-FPM, Nginx, Redis, Postgres, Horizon workers):

```sh
docker-compose up --build
```

###  **2. Run Migrations and Seeders**

Run database migrations and seed data inside the app container:

```sh
docker exec -it app php artisan migrate --seed
```
### **3. Generate Sample CSV File**

Generate a large random CSV dataset for testing:

```sh
docker exec -it app php docs/dummy/generate_orders_csv.php
```

The file will be created at:

docs/dummy/large_orders.csv

### **4. Import Orders from CSV**

Use the queued import command. Horizon will automatically process jobs:

```sh
docker exec -it app php artisan orders:import docs/dummy/large_orders.csv
```

## 📊 Horizon Dashboard
Accessible at:
```sh
http://localhost:8000/horizon
```
To clear dashboard data:
```sh
docker exec -it app php artisan horizon:clear
docker exec -it app php artisan queue:flush
```

## 📦 Redis Access
Redis Insight (recommended)
Download from: https://redis.com/redis-enterprise/redis-insight/

Connect to Redis using:

~~~sh
Host: localhost
Port: 6379
~~~

## Refund API & Health Check API
API collection is available in:

~~~sh
docs/api_collection.json
~~~

Includes:

- Refund API (POST)

- Health Check API (GET)


## 🏗 Workflow Overview

~~~sh
CSV Import
   ↓
Stream → Group by order_code → Dispatch ProcessOrderJob (per order)
   ↓
ProcessOrderJob
   → Create draft order + items
   → OrderFulfillmentService::reserve()  (pessimistic locking)
       ↓
   Success → Dispatch SimulatePaymentJob
   Failed  → Mark order as failed
       ↓
SimulatePaymentJob → Random success/failure
       ↓
PaymentCallbackJob
   → On success: finalize()  → commit stock, update KPIs
   → On failure: rollback() → release reservation, update KPIs

~~~

# Enjoy The Project. Thank you..!