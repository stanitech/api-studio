# 📡 API Studio — Laravel + AI

> Dynamic API Documentation Generator with AI-multi-model-powered summaries and realtime developer collaboration, built on Laravel 13.

---

## ✨ Features

- **Import Postman Collections** — Upload any Postman Collection v2.1 JSON and get instant docs
- **Dynamic Endpoint CRUD** — Add, edit, and delete endpoints directly in the UI
- **Ollama AI Summaries** — Generate Postman-style AI documentation per endpoint or for the full collection
- **SSE Streaming** — AI summaries stream token-by-token in real time
- **Search & Filter** — Live search across all endpoints, names, URLs, and AI summaries
- **Persistent Storage** — Collections saved as JSON files, AI summaries cached

---

## 🚀 Quick Setup

### 1. Prerequisites

| Tool | Version |
|------|---------|
| PHP  | ≥ 8.3   |
| Composer | latest |
| Ollama | latest |

### 2. Install Ollama & Pull a Model

```bash
# Install Ollama — https://ollama.com/download
curl -fsSL https://ollama.com/install.sh | sh

# Start Ollama server
ollama serve

# Pull a model (pick one)
ollama pull llama3       # recommended — best docs quality
ollama pull mistral      # faster, good quality
ollama pull phi3         # very fast, smaller
ollama pull gemma2       # Google's model
```

### 3. Clone & Install

```bash
git clone <your-repo>
cd api-docs-laravel

composer install

cp .env.example .env
php artisan key:generate
```

### 4. Configure `.env`

```env
# Ollama
OLLAMA_HOST=http://localhost:11434
OLLAMA_MODEL=llama3          # must match what you pulled
OLLAMA_TEMPERATURE=0.3
OLLAMA_MAX_TOKENS=1024

# App
APP_URL=http://localhost:8000
```

### 5. Create Storage Directory

```bash
mkdir -p storage/app/collections
php artisan storage:link
```

### 6. Run

```bash
php artisan serve
# → http://localhost:8000
```

---

## 🔌 API Reference

### Collections

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET`    | `/api/collections` | List all collections |
| `POST`   | `/api/collections/upload` | Upload Postman JSON (`multipart/form-data`) |
| `GET`    | `/api/collections/{id}` | Get collection + all endpoints |
| `PUT`    | `/api/collections/{id}` | Update name/description |
| `DELETE` | `/api/collections/{id}` | Delete collection |

### Endpoints (within a collection)

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET`    | `/api/collections/{id}/endpoints` | List endpoints (supports `?group=`, `?method=`, `?search=`) |
| `POST`   | `/api/collections/{id}/endpoints` | Create new endpoint |
| `GET`    | `/api/collections/{id}/endpoints/{epId}` | Get single endpoint |
| `PUT`    | `/api/collections/{id}/endpoints/{epId}` | Update endpoint |
| `DELETE` | `/api/collections/{id}/endpoints/{epId}` | Delete endpoint |

### AI (Ollama)

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET`    | `/api/ai/status` | Check Ollama server status |
| `GET`    | `/api/ai/models` | List available local models |
| `POST`   | `/api/ai/summarize` | Stream AI summary for one endpoint (SSE) |
| `POST`   | `/api/ai/summarize-collection` | Batch stream AI docs for whole collection (SSE) |

#### POST `/api/ai/summarize` Body

```json
{
  "endpoint": {
    "name": "Get User",
    "method": "GET",
    "url": "{{base_url}}/api/users/:id",
    "group": "Users",
    "auth": { "type": "bearer" },
    "query": [],
    "body": {},
    "responses": []
  },
  "model": "llama3",
  "collection_id": "uuid-here",
  "endpoint_id": "uuid-here"
}
```

---

## 🏗️ Project Structure

```
api-docs-laravel/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── CollectionController.php   # Upload & manage collections
│   │   │   ├── EndpointController.php     # CRUD for endpoints
│   │   │   └── OllamaController.php       # AI summary (SSE streaming)
│   │   └── Middleware/
│   │       └── CorsMiddleware.php
│   └── Services/
│       ├── OllamaService.php              # Ollama API client (curl streaming)
│       └── PostmanParserService.php       # Postman JSON → flat endpoints
├── bootstrap/
│   └── app.php                            # Laravel 11 app config
├── config/
│   └── ollama.php                         # Ollama config
├── resources/
│   └── views/
│       └── app.blade.php                  # Full SPA frontend
├── routes/
│   ├── api.php                            # API routes
│   └── web.php                            # SPA catch-all
├── storage/
│   └── app/
│       └── collections/                   # JSON storage for collections
└── .env.example
```

---

## 🤖 How the AI Works

1. User clicks **"Generate AI Documentation"** on any endpoint
2. Frontend sends a `POST /api/ai/summarize` with the full endpoint data
3. `OllamaController` builds a structured prompt (similar to Postman AI docs)
4. `OllamaService` sends to `POST http://localhost:11434/api/generate` with `stream: true`
5. cURL reads the NDJSON stream and writes SSE events (`data: {"token":"..."}`)
6. Frontend's `EventSource` / `ReadableStream` receives tokens and renders them live
7. When complete, the summary is persisted to the collection's JSON file

### Prompt Structure

The AI prompt includes:
- HTTP method, URL, group/folder
- Auth type
- All query parameters and path variables
- Request body fields (form-data, urlencoded, or raw JSON)
- Example response body (first 600 chars)
- Any existing description

Output format mirrors Postman's AI docs: Overview → Use Case → Auth → Parameters → Request Body → Response → Notes.

---

## 🔧 Tips

- **Model choice**: `llama3` gives the best documentation quality. `mistral` is faster. `phi3` for low-RAM machines.
- **Temperature**: Keep at `0.3` for factual, structured output. Raise to `0.7` for more creative prose.
---






