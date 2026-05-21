# API Docs — Laravel + team collerboration

A lightweight API documentation tool built with Laravel 13 and powered by Multi model AI-assisted endpoint documentation from any json API collections.

The goal of the project was to keep things simple:

- upload a Postman collection
- browse and edit endpoints
- generate readable API documentation using local AI models

---

## Features

- Import Postman Collection v2.1 files
- Create, edit, and delete endpoints from the UI
- Generate AI-written endpoint summaries using Ollama
- Stream AI responses live with SSE
- Search endpoints by name, URL, method, or summary
- Store collections locally as JSON files
- Cache generated summaries

---

# Getting Started

## Requirements

| Dependency | Version |
| --- | --- |
| PHP | 8.3+ |
| Composer | Latest |
| Ollama | Latest |

---

## Install Ollama

Download Ollama:

https://ollama.com

Start the Ollama server:

```bash
ollama serve
```

Pull a model:

```bash
# Recommended
ollama pull llama3

# Alternatives
ollama pull mistral
ollama pull phi3
ollama pull gemma2
```

### Suggested Models

| Model | Notes |
| --- | --- |
| `llama3` | Best overall documentation quality |
| `mistral` | Faster responses |
| `phi3` | Lightweight and fast |
| `gemma2` | Good alternative option |

---

# Installation

Clone the repository and install dependencies:

```bash
git clone <your-repo>
cd api-docs-laravel

composer install
```

Copy the environment file:

```bash
cp .env.example .env
php artisan key:generate
```

---

# Environment Configuration

Update your `.env` file:

```env
OLLAMA_HOST=http://localhost:11434
OLLAMA_MODEL=llama3
OLLAMA_TEMPERATURE=0.3
OLLAMA_MAX_TOKENS=1024

APP_URL=http://localhost:8000
```

---

# Storage Setup

Create the collections directory and link storage:

```bash
mkdir -p storage/app/collections

php artisan storage:link
```

---

# Run the Application

```bash
php artisan serve
```

Application URL:

```txt
http://localhost:8000
```

---

# API Endpoints

## Collections

| Method | Endpoint | Description |
| --- | --- | --- |
| GET | `/api/collections` | Fetch all collections |
| POST | `/api/collections/upload` | Upload a Postman collection |
| GET | `/api/collections/{id}` | Fetch a collection with endpoints |
| PUT | `/api/collections/{id}` | Update collection details |
| DELETE | `/api/collections/{id}` | Remove a collection |

---

## Endpoints

| Method | Endpoint | Description |
| --- | --- | --- |
| GET | `/api/collections/{id}/endpoints` | List endpoints |
| POST | `/api/collections/{id}/endpoints` | Create endpoint |
| GET | `/api/collections/{id}/endpoints/{epId}` | Get endpoint |
| PUT | `/api/collections/{id}/endpoints/{epId}` | Update endpoint |
| DELETE | `/api/collections/{id}/endpoints/{epId}` | Delete endpoint |

Supported filters:

```txt
?group=
?method=
?search=
```

---

## AI Routes

| Method | Endpoint | Description |
| --- | --- | --- |
| GET | `/api/ai/status` | Check Ollama connection |
| GET | `/api/ai/models` | List installed models |
| POST | `/api/ai/summarize` | Generate docs for one endpoint |
| POST | `/api/ai/summarize-collection` | Generate docs for entire collection |

---

## Example AI Request

`POST /api/ai/summarize`

```json
{
  "endpoint": {
    "name": "Get User",
    "method": "GET",
    "url": "{{base_url}}/api/users/:id",
    "group": "Users",
    "auth": {
      "type": "bearer"
    },
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

# Project Structure

```txt
api-docs-laravel/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── CollectionController.php
│   │   │   ├── EndpointController.php
│   │   │   └── OllamaController.php
│   │   └── Middleware/
│   │       └── CorsMiddleware.php
│   │
│   └── Services/
│       ├── OllamaService.php
│       └── PostmanParserService.php
│
├── bootstrap/
│   └── app.php
│
├── config/
│   └── ollama.php
│
├── resources/
│   └── views/
│       └── app.blade.php
│
├── routes/
│   ├── api.php
│   └── web.php
│
├── storage/
│   └── app/
│       └── collections/
│
└── .env.example
```

---

# How the AI Flow Works

1. User clicks **Generate AI Documentation**
2. Frontend sends endpoint data to the AI route
3. Laravel builds a structured prompt
4. Ollama receives the request with streaming enabled
5. The response streams token-by-token through SSE
6. The frontend renders the response live
7. Generated summaries are saved back to the collection JSON

---

# Prompt Design

The prompt includes:

- HTTP method
- Endpoint URL
- Group/folder name
- Authentication type
- Query parameters
- Path variables
- Request body
- Example responses
- Existing endpoint descriptions

Generated output follows a structure similar to Postman AI documentation:

- Overview
- Use Case
- Authentication
- Parameters
- Request Body
- Response
- Notes

---

# Notes

- `llama3` generally produces the cleanest documentation
- Lower temperature values work better for structured API docs
- Streaming works through Server-Sent Events (SSE)
- Collections are stored locally as JSON for simplicity

---

# Future Improvements

A few things planned for later versions:

- Markdown export
- OpenAPI/Swagger generation

---

# License

MIT License.
