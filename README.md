# Upwork BD Automation System

Internal agency tool: **Laravel 12 API** + **Next.js dashboard** + **Upwork official MCP** + **OpenAI** + **Slack alerts** + **Reverb**.

AI does matching and proposal drafts. **Humans approve before any Connects spend / MCP submit.**

## Important — Upwork Support first

Scheduled polling, storing MCP output, and combining tools for custom workflows should be cleared with **Upwork MCP Support** before production use.

Describe it as:

> Internal agency tool for periodically retrieving relevant jobs, storing results, matching them against our portfolio, creating proposal drafts, and requiring human confirmation before any proposal submission.

Until then keep:

```env
UPWORK_POLLING_ENABLED=false
```

## Stack

| Layer | Tech |
|-------|------|
| API | Laravel 12, Sanctum, Horizon (Linux), Reverb |
| Web | Next.js (App Router) |
| DB | PostgreSQL 16 + pgvector |
| Queue/cache | Redis |
| AI | OpenAI |
| Upwork | `https://mcp.upwork.com/mcp` |
| Alerts | Slack Incoming Webhook (no Slack Pro) |

## Quick start

### 1. Infrastructure

```bash
docker compose up -d
```

Postgres: `localhost:5433` · Redis: `localhost:6380`

### 2. API

```bash
cd apps/api
cp ../../.env.example .env   # or use existing .env
composer install --ignore-platform-reqs
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

In another terminal:

```bash
php artisan queue:work
php artisan reverb:start
# On Linux/Docker also: php artisan horizon
```

Seeded users:

- `manager@hmstech.local` / `password` (can approve)
- `bd@hmstech.local` / `password`

### 3. Web

```bash
cd apps/web
npm install
npm run dev
```

Open http://localhost:3000

### 4. Configure

1. **Settings** → Slack webhook URL  
2. **Accounts** → add account → paste MCP OAuth access token after logging into Upwork MCP  
3. Set `OPENAI_API_KEY` in `apps/api/.env`  
4. Only after Support OK: `UPWORK_POLLING_ENABLED=true`

## Approval gate (hard rule)

`POST /api/proposals/{id}/submit` refuses unless:

- `status === APPROVED`
- `approved_by` is set

AI never calls MCP submit directly.

## Main flows

1. Watcher (when enabled) → MCP search → dedupe → AI match → Slack if ≥85%  
2. BD opens job → generate 3 strategies → edit → submit for approval  
3. Manager approves → gated MCP draft/confirm submit  
4. Auto AI critique on submitted proposal  
5. Outcomes (reply/hire) feed strategy analytics  

## Monorepo layout

```
apps/api   Laravel API
apps/web   Next.js dashboard
docker-compose.yml
.env.example
README.md
```

## Notes

- Horizon needs `pcntl` (Linux/Docker). On Windows use `queue:work`.
- pgvector embeddings power portfolio semantic search; OpenAI embeddings required for that path.
- MCP tool names vary; client tries common names and logs attempts in `mcp_tool_logs`.
