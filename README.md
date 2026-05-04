<div align="center">

# Sistema Arqueológico — API

**Backend central do ecossistema de coleta e gestão de dados arqueológicos**

[![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?style=flat-square&logo=php&logoColor=white)](https://www.php.net/)
[![Laravel](https://img.shields.io/badge/Laravel-13.x-FF2D20?style=flat-square&logo=laravel&logoColor=white)](https://laravel.com/)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16%20+%20PostGIS-336791?style=flat-square&logo=postgresql&logoColor=white)](https://postgis.net/)
[![Docker](https://img.shields.io/badge/Docker-Compose-2496ED?style=flat-square&logo=docker&logoColor=white)](https://www.docker.com/)
[![License](https://img.shields.io/badge/Licença-MIT-green?style=flat-square)](LICENSE)

</div>

---

## Visão Geral

Esta API é o núcleo de processamento e armazenamento do ecossistema arqueológico. Ela recebe, valida, armazena e disponibiliza todos os dados coletados em campo pela aplicação móvel **[sistema_coleta_arqueologica](https://github.com/mfeeee/sistema_coleta_arqueologica)** (Flutter), além de oferecer uma interface administrativa integrada para curadoria, auditoria e gestão dos registros.

O projeto foi concebido para atender às exigências burocráticas e protocolos técnicos do registro e salvaguarda de materiais arqueológicos, garantindo rastreabilidade completa e integridade dos dados desde a coleta em campo até o arquivamento final.

---

## Módulos Principais

### Gestão de Coletas
Recebe e persiste os registros de coletas realizadas em campo, com suporte a coordenadas geoespaciais via PostGIS. Cada coleta é associada a um responsável, a um sítio e pode conter múltiplos bens materiais.

### Inventário de Bens Materiais
Gerencia o ciclo de vida dos artefatos coletados — desde o registro inicial com atributos descritivos até a consulta por proximidade geográfica (`GET /api/bens-materiais/nearby`), permitindo visualização espacial dos achados.

### Curadoria
Módulo de revisão técnica que permite que especialistas avaliem e validem os registros submetidos. O fluxo de curadoria garante que apenas dados aprovados componham o acervo oficial, respeitando os padrões científicos exigidos.

### Auditoria de Dados
Registro imutável de todas as operações críticas realizadas no sistema. Cada alteração relevante gera uma entrada de auditoria, assegurando o rastreamento completo das ações para fins de pesquisa, conformidade e integridade científica.

### Sincronização Offline (Mobile)
Endpoint dedicado (`POST /api/sync`) para recepção de lotes de dados enviados pelo aplicativo móvel após períodos sem conectividade, garantindo que trabalhos de campo em áreas remotas não sejam perdidos.

---

## Tecnologias

| Camada | Tecnologia |
|---|---|
| Backend | Laravel 13 · PHP 8.3 |
| Autenticação | Laravel Fortify · Laravel Sanctum (tokens API + 2FA) |
| Banco de Dados | PostgreSQL 16 com extensão PostGIS 3.4 |
| Cache / Filas | Redis 7.2 |
| Infraestrutura | Docker · Nginx 1.25 · Makefile |

---

## Requisitos do Sistema

- **Docker** 24+ e **Docker Compose** v2
- **PHP** 8.3+ (se rodar fora do Docker)
- **Composer** 2.x (se rodar fora do Docker)
- **Node.js** 20+ e **npm** (para assets do painel administrativo)

> O ambiente recomendado é o Docker. Todos os comandos abaixo assumem uso via container.

---

## Instalação e Configuração

### 1. Clone o repositório

```bash
git clone https://github.com/mfeeee/sistema_arqueologico_api.git
cd sistema_arqueologico_api
```

### 2. Configure as variáveis de ambiente

```bash
cp .env.example .env
```

Edite o `.env` e ajuste as variáveis obrigatórias:

```dotenv
APP_NAME="Sistema Arqueológico API"
APP_URL=http://localhost:8000

DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=sistema_arqueologico
DB_USERNAME=postgres
DB_PASSWORD=secret

REDIS_HOST=redis
```

### 3. Suba os containers

```bash
make up
```

### 4. Instale as dependências PHP

```bash
docker compose exec app composer install
```

### 5. Gere a chave da aplicação

```bash
make key
```

### 6. Execute as migrações (inclui ativação do PostGIS)

```bash
make migrate
```

> A migration `enable_postgis_extension` ativa automaticamente a extensão PostGIS no banco. Certifique-se de que a imagem `postgis/postgis:16-3.4-alpine` está sendo usada (já definida no `docker-compose.yml`).

### 7. (Opcional) Popule o banco com dados de exemplo

```bash
make seed
```

A API estará disponível em **http://localhost:8000/api**.

---

## Referência de Comandos (Makefile)

| Comando | Descrição |
|---|---|
| `make up` | Inicia todos os containers em background |
| `make down` | Para e remove os containers |
| `make restart` | Reinicia com rebuild da imagem |
| `make bash` | Abre shell dentro do container `app` |
| `make migrate` | Executa as migrações pendentes |
| `make fresh` | Recria o banco do zero com seeds |
| `make seed` | Executa apenas os seeders |
| `make test` | Roda a suíte de testes PHPUnit |
| `make logs` | Exibe logs em tempo real de todos os serviços |
| `make queue` | Inicia o worker de filas |

---

## Endpoints da API

Todos os endpoints (exceto autenticação) exigem o header `Authorization: Bearer {token}`.

```
POST   /api/auth/login
POST   /api/auth/logout
GET    /api/auth/me

GET    /api/coletas
POST   /api/coletas
GET    /api/coletas/{id}
PUT    /api/coletas/{id}
DELETE /api/coletas/{id}

POST   /api/sync                        # Sincronização batch (mobile offline)

GET    /api/bens-materiais/nearby       # Busca por proximidade geográfica
GET    /api/bens-materiais
POST   /api/bens-materiais
GET    /api/bens-materiais/{id}
PUT    /api/bens-materiais/{id}
DELETE /api/bens-materiais/{id}

GET    /api/curadorias
PATCH  /api/curadorias/{id}/avaliar

GET    /api/auditorias                  # Somente administradores
```

---

## Conexão com o Aplicativo Móvel

O repositório **[sistema_coleta_arqueologica](https://github.com/mfeeee/sistema_coleta_arqueologica)** (Flutter) depende diretamente desta API para funcionar. O aplicativo utiliza os tokens Sanctum para autenticação e o endpoint `/api/sync` para envio em lote dos dados coletados offline em campo.

**Configuração no app mobile:**

Defina a variável `API_BASE_URL` no arquivo de ambiente do Flutter apontando para o endereço desta API:

```
API_BASE_URL=http://<seu-ip-ou-dominio>:8000/api
```

> Em ambiente de desenvolvimento local, substitua `localhost` pelo IP da máquina na rede, pois emuladores Android não resolvem `localhost` para a máquina host.

---

## Segurança e Autenticação

- **Laravel Sanctum**: emissão de tokens de acesso pessoal para a autenticação da API mobile.
- **Laravel Fortify**: autenticação do painel administrativo web com suporte a **autenticação de dois fatores (2FA)** via TOTP.
- Todas as rotas de escrita são protegidas pelo middleware `auth:sanctum`.
- O módulo de auditoria registra automaticamente ações sensíveis para rastreabilidade.

---

## Testes

```bash
# Rodar toda a suíte
make test

# Ou diretamente via artisan (com filtro)
docker compose exec app php artisan test --compact
docker compose exec app php artisan test --compact tests/Feature/Coleta/
docker compose exec app php artisan test --compact --filter=testNomeDoTeste
```

Os testes cobrem os módulos de Coleta, Curadoria, Auditoria e Bens Materiais, incluindo os fluxos de autenticação e sincronização.

---

## Contexto Acadêmico

Este sistema foi desenvolvido no contexto de pesquisa arqueológica e atende aos protocolos técnicos e exigências institucionais para o registro formal de sítios e materiais arqueológicos.

A integridade dos dados é uma premissa inegociável: o módulo de auditoria garante que todo o histórico de criação, edição e validação dos registros seja preservado de forma imutável, assegurando a reprodutibilidade e a confiabilidade dos dados para fins de pesquisa científica, publicação e cumprimento das obrigações legais perante os órgãos competentes.

---

<div align="center">

Desenvolvido por **[Maria Fernanda Rodrigues Costa](https://github.com/mfeeee)**

</div>
