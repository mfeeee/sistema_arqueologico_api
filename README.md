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

### Artigos Científicos
Vincula publicações científicas a sítios arqueológicos do acervo. Pesquisadores submetem artigos (com ou sem DOI) pelo aplicativo móvel; cada submissão entra no fluxo de curadoria para aprovação antes de ser associada ao bem material correspondente. O módulo evita duplicidade de artigos já cadastrados: se o artigo já existir, apenas o vínculo `artigo_bem_material` é criado.

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

Os seeders são executados na seguinte ordem e cobrem os seguintes cenários:

| Seeder | Cenários gerados |
|---|---|
| `UserSeeder` | 3 usuários fixos: admin, curador, coletor |
| `BemMaterialSeeder` | 6 sítios base do Piauí (PI-BASE-0001 a 0006), com mídias e responsáveis |
| `ColetaECuradoriaSeeder` | **A** — 3 pendentes de criação de sítio · **B** — 3 aprovados `criarSitio` + auditoria Inserção · **C** — 3 aprovados `atualizarSitio` preenchendo campo null · **D** — 3 aprovados `atualizarSitio` modificando campo existente · **E** — 3 aprovados `atualizarSitio` múltiplos campos · **F** — 3 rejeitados |
| `AuditoriaManualSeeder` | Cenário G: auditorias com `meio = Manual` |
| `CuradoriaAtualizacaoPendenteSeeder` | 3 curadorias pendentes vinculadas a bens existentes para testar o fluxo de `atualizarSitio` interativamente |
| `ArtigoCientificoSeeder` | **A** — 1 submissão aprovada onde o artigo já existia (só cria `artigo_bem_material`) · **B** — 1 submissão pendente com artigo novo (aguarda curadoria) · **C** — 1 submissão pendente sem DOI (artigo só com título/autores) · **D** — 1 submissão rejeitada |

**Adicionar apenas as pendentes de atualização (sem recriar o banco):**

```bash
docker compose exec app php artisan db:seed --class=CuradoriaAtualizacaoPendenteSeeder
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

Todos os endpoints protegidos exigem o header:

```
Authorization: Bearer {token}
```

### Auth (pública)

| Método | Endpoint | Autenticação | Descrição |
|---|---|---|---|
| `POST` | `/api/v1/auth/login` | — | Login e emissão de token Sanctum |
| `POST` | `/api/v1/auth/register` | — | Cadastro de novo usuário |
| `POST` | `/api/v1/auth/password-reset` | — | Solicita e-mail de recuperação de senha |
| `POST` | `/api/v1/auth/password-reset/confirm` | — | Confirma o reset com o token recebido por e-mail |
| `POST` | `/api/v1/auth/logout` | `auth:sanctum` | Revoga o token atual |
| `GET` | `/api/v1/auth/me` | `auth:sanctum` | Retorna dados do usuário autenticado |

**Body — `POST /api/v1/auth/login`**

```json
{
  "email": "usuario@exemplo.com",   // obrigatório
  "password": "senha"               // obrigatório
}
```

***

### Mobile — `v1/mobile`

Os endpoints mobile se dividem em dois grupos de autenticação:

| Grupo | Middleware | Comportamento |
|---|---|---|
| **Leitura pública** | `auth.optional:sanctum` | Sem token → acesso como guest. Com token válido → autenticado. Token inválido/expirado → `401`. Sujeito a rate limiting (`30 req/min` para guests, `120 req/min` para autenticados). |
| **Protegido** | `auth:sanctum` | Token obrigatório. Sem token → `401`. |

#### Coletas · `[auth:sanctum]`

| Método | Endpoint | Descrição |
|---|---|---|
| `GET` | `/api/v1/mobile/coletas` | Lista coletas do usuário autenticado (paginado) |
| `POST` | `/api/v1/mobile/coletas` | Registra nova coleta |
| `GET` | `/api/v1/mobile/coletas/{id}` | Detalha uma coleta |
| `PUT` | `/api/v1/mobile/coletas/{id}` | Atualiza uma coleta |
| `DELETE` | `/api/v1/mobile/coletas/{id}` | Remove uma coleta (soft delete) |

**Body — `POST /api/v1/mobile/coletas`**

```json
{
  "data_coleta": "2026-05-04",      // obrigatório
  "nome_bem": "Fragmento cerâmico", // obrigatório
  "latitude": -5.0921,              // obrigatório
  "longitude": -42.8016,            // obrigatório
  "natureza": "ceramica",           // opcional
  "tipo": "superficie",             // opcional
  "uf": "MA",                       // opcional
  "artefatos": [],                  // opcional (array)
  "dados_coletados": {},            // opcional (objeto)
  "versao": 1                       // opcional (padrão: 1)
}
```

#### Bens Materiais · `[auth.optional:sanctum]` · público

| Método | Endpoint | Descrição |
|---|---|---|
| `GET` | `/api/v1/mobile/bens-materiais` | Lista bens materiais (paginado), com filtro opcional de publicação |
| `GET` | `/api/v1/mobile/bens-materiais/nearby` | Busca por proximidade geográfica, com filtro opcional de publicação |
| `GET` | `/api/v1/mobile/bens-materiais/{id}` | Detalha um bem material |

**Query params — `GET /api/v1/mobile/bens-materiais/nearby`**

| Parâmetro | Tipo | Obrigatório | Descrição |
|---|---|---|---|
| `latitude` | `numeric` | ✅ | Latitude entre -90 e 90 |
| `longitude` | `numeric` | ✅ | Longitude entre -180 e 180 |
| `raio_km` | `numeric` | ❌ | Raio de busca em km (padrão: 5, máx: 100) |
| `publicado` | `string` | ❌ | Filtro de publicação: `true`, `false` ou `all` (padrão: `true`) |

**Query params — `GET /api/v1/mobile/bens-materiais`**

| Parâmetro | Tipo | Obrigatório | Descrição |
|---|---|---|---|
| `uf` | `string` | ❌ | Filtra por UF (ex: `MA`) |
| `tipo` | `string` | ❌ | Filtra por tipo de bem |
| `publicado` | `string` | ❌ | Filtro de publicação: `true`, `false` ou `all` (padrão: `true`) |

#### Artigos Científicos e Colaboradores · `[auth.optional:sanctum]` · público

| Método | Endpoint | Descrição |
|---|---|---|
| `GET` | `/api/v1/mobile/bens-materiais/{id}/artigos` | Lista artigos vinculados a um bem material |
| `GET` | `/api/v1/mobile/bens-materiais/{id}/colaboradores` | Lista colaboradores do bem material (coletores + autores de artigos aprovados) |

#### Artigos Científicos — Sugestão · `[auth:sanctum]`

| Método | Endpoint | Descrição |
|---|---|---|
| `GET` | `/api/v1/mobile/artigos-cientificos/buscar-doi` | Busca um artigo já cadastrado pelo DOI |
| `POST` | `/api/v1/mobile/submissoes-artigos` | Submete um artigo para curadoria, vinculando-o a um bem material |

**Resposta `200 OK` — `GET /api/v1/mobile/bens-materiais/{id}/colaboradores`:**

```json
{
  "colaboradores": [
    {
      "id": "uuid",
      "nome": "Maria Fernanda",
      "email": "mf@arqueologia.test",
      "classificacao": "arqueologo",
      "origem": "coleta",
      "total_contribuicoes": 3
    },
    {
      "id": "uuid",
      "nome": "Maria Fernanda",
      "email": "mf@arqueologia.test",
      "classificacao": "arqueologo",
      "origem": "artigo",
      "total_contribuicoes": 1
    }
  ]
}
```

> Retorna **uma linha por usuário por tipo de contribuição** (`origem: "coleta"` ou `"artigo"`). Um mesmo usuário pode aparecer duas vezes se contribuiu via coleta de campo E via artigo científico. `total_contribuicoes` indica quantas coletas aprovadas (ou vínculos de artigo ativos) esse usuário possui para o bem.

**Query params — `GET /api/v1/mobile/artigos-cientificos/buscar-doi`**

| Parâmetro | Tipo | Obrigatório | Descrição |
|---|---|---|---|
| `doi` | `string` | ✅ | DOI do artigo (ex: `10.1016/j.quaint.2021.01.001`) |

**Resposta `200 OK` — artigo encontrado:**

```json
{
  "data": {
    "id": "uuid",
    "doi": "10.1016/j.quaint.2021.01.001",
    "titulo": "...",
    "autores": "...",
    "ano_publicacao": 2021,
    "periodico": "...",
    "idioma": "pt",
    "resumo": "..."
  }
}
```

**Resposta `404 Not Found` — artigo não cadastrado:**

```json
{ "message": "Artigo não encontrado." }
```

**Body — `POST /api/v1/mobile/submissoes-artigos`**

```json
{
  "bem_material_id": "uuid-do-bem",   // obrigatório
  "tipo_mencao": "estudo_principal",  // obrigatório (ver TipoMencaoArtigo)
  "artigo_id": "uuid-do-artigo",      // opcional: se o artigo já existe no banco
  "doi": "10.1016/...",               // opcional: quando artigo_id não informado
  "titulo": "Título do artigo",       // opcional (mas recomendado sem DOI)
  "autores": "Silva, J.; Costa, M.",  // opcional
  "ano_publicacao": 2024,             // opcional (integer)
  "periodico": "Journal of ...",      // opcional
  "idioma": "pt",                     // opcional (padrão: "pt")
  "resumo": "Resumo do artigo...",    // opcional
  "link_acesso": "https://...",       // opcional
  "trecho_relevante": "..."           // opcional: trecho que menciona o bem
}
```

> **Valores válidos para `tipo_mencao` (`TipoMencaoArtigo`):**
> `estudo_principal` · `referencia_geografica` · `citacao_comparativa` · `dado_secundario` · `mencao_simples`

#### Sincronização · `[auth:sanctum]`

| Método | Endpoint | Descrição |
|---|---|---|
| `POST` | `/api/v1/mobile/sync` | Envia lote de coletas offline para processamento assíncrono |

**Body — `POST /api/v1/mobile/sync`**

```json
{
  "coletas": [                      // obrigatório (array)
    {
      "data_coleta": "2026-05-01",
      "nome_bem": "Lasca lítica",
      "latitude": -5.1002,
      "longitude": -42.8100
    }
  ]
}
```

**Resposta `202 Accepted`:**

```json
{
  "message": "Sincronização recebida e enfileirada.",
  "total_itens": 3
}
```

***

### Admin — `v1/admin` · `[auth:sanctum + perfil: admin ou curador]`

#### Bens Materiais

| Método | Endpoint | Descrição |
|---|---|---|
| `PATCH` | `/api/v1/admin/bens-materiais/{id}/publicar` | Altera o status de publicação de um bem material e registra auditoria |
| `PATCH` | `/api/v1/admin/bens-materiais/{id}/curador-responsavel` | Define ou altera o curador responsável pelo sítio |
| `DELETE` | `/api/v1/admin/bens-materiais/{id}` | Remove um bem material (soft delete) com registro de auditoria de exclusão |

**Body — `PATCH /api/v1/admin/bens-materiais/{id}/publicar`**

```json
{
  "publicado": true   // obrigatório (boolean)
}
```

**Body — `PATCH /api/v1/admin/bens-materiais/{id}/curador-responsavel`**

```json
{
  "curador_responsavel_id": "uuid-do-usuario"   // nullable — envie null para remover o responsável
}
```

> Qualquer usuário com perfil `curador` ou `admin` pode alterar o responsável. A mudança gera uma entrada de auditoria com `operacao = Alteração`, `meio = Manual`, contendo o **nome** do curador anterior e do novo (não apenas UUIDs) em `valor_anterior` e `valor_novo`, para legibilidade imediata no histórico.

#### Usuários

| Método | Endpoint | Descrição |
|---|---|---|
| `GET` | `/api/v1/admin/usuarios/curadores` | Lista usuários com perfil `curador` ou `admin` ativos (para seleção de responsável) |

**Resposta `200 OK`:**

```json
{
  "data": [
    { "id": "uuid", "name": "Nome", "email": "email@", "perfil": "curador" }
  ]
}
```

#### Artigos Científicos (admin)

| Método | Endpoint | Descrição |
|---|---|---|
| `DELETE` | `/api/v1/admin/artigos-bem-material/{id}` | Remove o vínculo entre um artigo e um bem material (sem excluir o artigo) |

#### Curadorias

| Método | Endpoint | Descrição |
|---|---|---|
| `GET` | `/api/v1/admin/curadorias` | Lista curadorias filtradas por status (paginado, 20/página) |
| `GET` | `/api/v1/admin/curadorias/{id}` | Detalha uma curadoria específica |
| `PATCH` | `/api/v1/admin/curadorias/{id}/avaliar` | Avalia uma curadoria e aplica os efeitos no BemMaterial |
| `GET` | `/api/v1/admin/bens-materiais/{id}/curadorias` | Lista o histórico de curadorias de um bem material (paginado, 20/página) |

**Query params — `GET /api/v1/admin/curadorias`**

| Parâmetro | Tipo | Obrigatório | Descrição |
|---|---|---|---|
| `status` | `string` | ❌ | Filtra por status: `pendente` \| `aprovado` \| `rejeitado` (padrão: `pendente`) |

**Body — `PATCH /api/v1/admin/curadorias/{id}/avaliar`**

```json
{
  "status": "aprovado",                      // obrigatório: aprovado | rejeitado
  "acao_resultante": "criarSitio",           // obrigatório: criarSitio | atualizarSitio | aprovar_artigo | rejeitar
  "bem_material_id": "uuid-do-bem",          // obrigatório se acao_resultante = atualizarSitio
  "observacao": "Registro validado.",        // opcional
  "publicado": false,                        // opcional (bool): define publicado no bem criado/atualizado
  "campos": {                                // opcional: campos específicos a aplicar no bem (atualizarSitio)
    "nome_bem": "Novo nome",
    "municipio": "São Raimundo Nonato",
    "meios_acesso": "Acesso pela PI-247..."
  }
}
```

> A curadoria é **polimórfica**: o campo `entidade_tipo` indica se a entrada é uma `coleta` ou uma `submissao_artigo`. O dispatcher no controller roteia automaticamente para o handler correto com base nesse tipo — ações de coleta (`criarSitio`, `atualizarSitio`) só são válidas para curadorias de coleta; `aprovar_artigo` só é válido para curadorias de submissão de artigo.

**Comportamento por `acao_resultante`**

| Valor | Entidade | Efeito no banco |
|---|---|---|
| `criarSitio` | `coleta` | Cria novo `BemMaterial` com os dados da coleta (incluindo campos de `dados_coletados`). Gera auditoria de **Inserção**. |
| `atualizarSitio` | `coleta` | Atualiza o `BemMaterial` referenciado por `bem_material_id`. Se `campos` for enviado, aplica somente esses campos; caso contrário, aplica todos os campos não-nulos da coleta. Atualiza o `geom` PostGIS se latitude ou longitude mudou. Gera auditoria de **Alteração** com snapshot completo em `valor_anterior` e apenas os campos alterados em `valor_novo`. |
| `aprovar_artigo` | `submissao_artigo` | Se `artigo_id` da submissão já estiver preenchido, cria apenas o registro `artigo_bem_material` (Cenário A). Caso contrário, cria primeiro o `artigo_cientifico` com os dados da submissão e depois cria o vínculo (Cenário B). |
| `rejeitar` | ambos | Nenhum `BemMaterial`, `artigo_cientifico` ou `artigo_bem_material` é criado ou alterado. Nenhuma auditoria de bem é gerada. |

> **Campos permitidos em `campos`:** `nome_bem`, `nomes_populares`, `natureza`, `tipo`, `artefatos`, `meios_acesso`, `uf`, `municipio`, `cep`, `endereco`, `latitude`, `longitude`, `ano_registro`, `descricao_atualizacao`, `publicado`. Chaves não reconhecidas são silenciosamente ignoradas.

#### Auditorias

| Método | Endpoint | Descrição |
|---|---|---|
| `GET` | `/api/v1/admin/auditorias` | Lista registros de auditoria (paginado, 50/página) |
| `GET` | `/api/v1/admin/auditorias/{id}` | Detalha um registro de auditoria específico |

**Query params — `GET /api/v1/admin/auditorias`**

| Parâmetro | Tipo | Obrigatório | Descrição |
|---|---|---|---|
| `entidade_tipo` | `string` | ❌ | Filtra por tipo de entidade auditada (ex: `App\Models\BemMaterial`) |
| `entidade_id` | `uuid` | ❌ | Filtra por ID da entidade auditada |
| `usuario_id` | `uuid` | ❌ | Filtra por usuário que realizou a ação |

**Estrutura de `valor_anterior` e `valor_novo`**

- **Inserção** (`criarSitio`): `valor_anterior = null`, `valor_novo` = snapshot completo do bem criado.
- **Alteração** (`atualizarSitio`): `valor_anterior` = snapshot completo do bem antes da mudança, `valor_novo` = apenas os campos que foram efetivamente alterados.

***

## Formato padrão de resposta

Todas as respostas seguem o envelope `data` / `meta`:

```json
// Lista paginada
{
  "data": [...],
  "meta": {
    "total": 45,
    "page": 1,
    "pages": 3
  }
}

// Item único
{
  "data": { ... }
}

// Erro
{
  "message": "Recurso não encontrado."
}
```

***

## Perfis de Usuário

| Perfil | Acesso Mobile (`v1/mobile`) | Acesso Admin (`v1/admin`) |
|---|---|---|
| `coletor` | ✅ Total | ❌ Bloqueado |
| `curador` | ✅ Total | ✅ Curadorias, Auditorias, publicar/excluir bem, alterar responsável |
| `admin` | ✅ Total | ✅ Total |

***

## Usuários de Teste (Seeders)

Após rodar `make seed`, os seguintes usuários estarão disponíveis:

| E-mail | Senha | Perfil |
|---|---|---|
| `admin@arqueologia.test` | `password` | admin |
| `curador@arqueologia.test` | `password` | curador |
| `coletor@arqueologia.test` | `password` | coletor |

---

## Conexão com o Aplicativo Móvel

O repositório **[sistema_coleta_arqueologica](https://github.com/mfeeee/sistema_coleta_arqueologica)** (Flutter) depende diretamente desta API para funcionar. O aplicativo utiliza os tokens Sanctum para autenticação e o endpoint `/api/v1/mobile/sync` para envio em lote dos dados coletados offline em campo.

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
- Middleware `CheckRole` verifica o campo `perfil` do usuário antes de acessar rotas admin.
- Middleware `OptionalAuthenticate` (`auth.optional:sanctum`): permite acesso público aos endpoints de leitura. Se um token for enviado e for **válido**, a requisição é autenticada normalmente. Se o token for **inválido ou expirado**, retorna `401` — apenas a **ausência** de token resulta em acesso como guest.
- **Rate limiting** nos endpoints públicos via limiter `public-api`: `30 req/min` por IP para guests e `120 req/min` por ID de usuário para autenticados. Limite excedido retorna `429 Too Many Requests` com header `Retry-After`.
- Todas as rotas protegidas retornam `401 Unauthorized` sem token e `403 Forbidden` sem perfil adequado.
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

## Futuras Implementações

Funcionalidades identificadas como necessárias mas ainda não implementadas, ordenadas por impacto estimado.

### Média prioridade

| # | Funcionalidade | Motivação |
|---|---|---|
| 1 | **Paginação com cursor em `/admin/auditorias`** | Paginação por offset degrada com tabelas grandes (OFFSET 5000 escaneia 5000 linhas antes de retornar). Cursor-based pagination (ex.: `?after=uuid`) é O(log n) com índice. |
| 2 | **Filtros adicionais em `/admin/auditorias`** | Suporte a `operacao` (Inserção, Alteração) e `data_inicio`/`data_fim` para facilitar investigações de auditoria sem precisar baixar todas as páginas. |
| 3 | **Endpoint de exportação de auditoria** | `GET /admin/auditorias/export?format=csv` para geração de relatórios formais exigidos por processos de conformidade e publicação científica. |
| 4 | **Upload de mídias na API** | Hoje `dados_coletados.midias` armazena apenas URLs externas. Um endpoint de upload (`POST /mobile/coletas/{id}/midias`) com armazenamento em S3 ou disco local centralizaria a gestão de evidências fotográficas. |
| 5 | **Busca de artigos por bem material com paginação** | `GET /mobile/bens-materiais/{id}/artigos` retorna todos os artigos sem paginação. Com volumes maiores, adicionar `?page=` e metadados de paginação segue o padrão do restante da API. |

### Baixa prioridade / exploratória

| # | Funcionalidade | Motivação |
|---|---|---|
| 5 | **Evento/webhook na aprovação de curadoria** | Ao aprovar uma curadoria, o `web_coletum` invalida o cache manualmente via `invalidate_bens_cache()`. Um evento (`CuradoriaAprovada`) que dispara um webhook ou SSE eliminaria o acoplamento e funcionaria para qualquer cliente. |
| 6 | **Versionamento de BemMaterial** | Guardar um snapshot completo a cada aprovação de curadoria permitiria consultar o estado exato do sítio em qualquer ponto do tempo, não apenas o anterior imediato. |
| 7 | **Busca full-text em bens materiais** | `GET /mobile/bens-materiais?q=pedra+furada` usando `tsvector`/`tsquery` do PostgreSQL para buscas textuais eficientes em `nome_bem`, `nomes_populares` e `descricao_atualizacao`. |
| 8 | **Rate limiting nas rotas admin** | As rotas `admin` não têm throttle configurado. As rotas públicas já possuem o limiter `public-api` (30/120 req/min). Adicionar limites equivalentes por perfil nas rotas admin previne uso indevido e sobrecarga acidental. |

---

## Contexto Acadêmico

Este sistema foi desenvolvido no contexto de pesquisa arqueológica e atende aos protocolos técnicos e exigências institucionais para o registro formal de sítios e materiais arqueológicos.

A integridade dos dados é uma premissa inegociável: o módulo de auditoria garante que todo o histórico de criação, edição e validação dos registros seja preservado de forma imutável, assegurando a reprodutibilidade e a confiabilidade dos dados para fins de pesquisa científica, publicação e cumprimento das obrigações legais perante os órgãos competentes.

---

<div align="center">

Desenvolvido por **[Maria Fernanda Rodrigues Costa](https://github.com/mfeeee)** e **[Ryan Rodrigues Silva](https://github.com/Ryan-auchi)**

</div>
