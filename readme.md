# German Context Reader

A simple German reading app for saving texts and generating context-aware word explanations with AI.
## Frontend 
check another repo:
https://github.com/zhou98ch/AI-language-reader-frontend

## Features

- Upload and save German text documents
- View saved texts
- Click words in a text to generate short inline explanations
- Ask custom prompts for selected words
- Save explanation history
- Edit saved explanations
- Cache repeated explanations using document position, provider, explanation type, and prompt hash
- Gemini API integration through an AI provider registry

## Tech Stack
- PHP 8.4+
- Symfony 8
- MySQL
- Composer

## Setup

```bash
cd backend
composer install
```
Create backend/.env.local:
```
APP_ENV=dev
APP_DEBUG=1
DATABASE_URL="mysql://root:password@127.0.0.1:3306/deutsch_reader?serverVersion=8.0.32&charset=utf8mb4"
GEMINI_API_KEY="your-gemini-api-key"
GEMINI_MODEL="gemini-2.5-flash"
```
Run database migrations:
```
php bin/console doctrine:migrations:migrate
```
Start the backend server:
```
symfony serve
```

## Use Cases
<img width="3138" height="3155" alt="Text Explanation Generation-2026-05-17-152836" src="https://github.com/user-attachments/assets/02c5dea8-0227-4941-aaab-7eea4b92e7ba" />

## Component Diagram
### I tried DDD method & Clean Architecture in the project, for learning new design ideas
<img width="1272" height="527" alt="581f248794651748c9e2f586236e7be" src="https://github.com/user-attachments/assets/6921c7a9-0d9e-42f9-b5f5-58bf72db75e8" />

### Also ried on Registry Pattern, for better future extensibility of AI providers.
<img width="1175" height="908" alt="9b81b16b5699aded0e23723ad805b7a" src="https://github.com/user-attachments/assets/e7fd9129-6904-4b7d-b283-0369b8f8a185" />

## Sequence Diagram 
### for Word AI Explaination Use Case
<img width="8191" height="3680" alt="AI Explanation Workflow-2026-05-17-144142" src="https://github.com/user-attachments/assets/96eb2db5-9ae2-4eb6-82da-2cc5c5ea99ae" />
