# Conformidade com a LGPD

## Fundamento legal

A Lei Geral de Proteção de Dados (Lei nº 13.709/2018 — LGPD) estabelece regras sobre coleta, armazenamento, tratamento e compartilhamento de dados pessoais.

Os principais artigos que regem este sistema:

| Artigo | Descrição |
|--------|-----------|
| Art. 7 | Hipóteses legais de tratamento — os dados são tratados com base no consentimento do titular e para a execução de pesquisa científica |
| Art. 16, II | Dados tratados para fins de pesquisa podem ser conservados após o término do tratamento, desde que os dados sejam anonimizados |
| Art. 18, VI | O titular tem o direito de solicitar a eliminação dos dados pessoais tratados com base em seu consentimento |

---

## Dados pessoais coletados

| Campo | Finalidade |
|-------|-----------|
| `name` | Identificação do usuário no sistema |
| `email` | Autenticação e comunicações |
| `password` | Autenticação segura |
| `avatar_url` | Foto de perfil opcional |
| `perfil` | Controle de acesso (coletor / curador / admin) |
| `classificacao` | Contexto científico do usuário (estudante / professor / arqueólogo) |
| `ativo` | Estado da conta |

Dados não pessoais mantidos: contribuições científicas (coletas, curadorias, artigos), que são vinculadas pelo `id` do usuário sem identificar a pessoa.

---

## Direito de exclusão (art. 18, VI)

### Como o titular exerce o direito

1. O usuário logado acessa o menu de perfil (canto superior direito)
2. Clica em **"Excluir minha conta"**
3. Confirma digitando **EXCLUIR** no campo de confirmação
4. O sistema executa a anonimização

### Fluxo técnico

A exclusão é implementada pela `AnonimizarUsuarioAction`:

1. **Revogação de tokens** — todos os tokens Sanctum são deletados (`personal_access_tokens`)
2. **Anonimização in-place** — os campos pessoais são sobrescritos:
   - `name` → `"Usuário excluído"`
   - `email` → `"anonimizado-{uuid}@excluido.local"` (mantém unicidade da coluna)
   - `password` → hash bcrypt de string aleatória (login impossível)
   - `avatar_url`, `remember_token`, `two_factor_*`, `email_verified_at` → `null`
   - `ativo` → `false`
3. **Soft-delete** — `deleted_at` é preenchido; o registro permanece no banco para integridade referencial
4. **Auditoria** — um registro é criado em `auditorias` com `operacao = "Anonimização"` e o motivo LGPD

### O que é retido e por quê

Conforme LGPD art. 16 II (pesquisa científica), os seguintes dados são mantidos **sem identificação pessoal**:

- Registros de coletas (`coletas`) — o `usuario_id` permanece, mas o usuário correspondente não tem mais dados pessoais
- Curadorias (`curadorias`)
- Auditorias (`auditorias`)
- Artigos científicos (`artigos_cientificos`, `submissoes_artigos`)

Em todos esses registros, o `usuario_id` aponta para um registro anonimizado que exibe apenas `"Usuário excluído"`.

### O que é removido automaticamente (cascade)

- Notificações (`notificacoes`)
- Preferências de notificações (`preferencias_notificacoes`)
- Vínculos de responsabilidade por bem material (`bem_responsaveis`)
- Tokens de acesso Sanctum (`personal_access_tokens`)

---

## Restrições de acesso

- Somente o próprio titular pode solicitar a exclusão da própria conta
- Administradores **não podem** excluir contas de outros usuários por esta rota
- A operação é irreversível: uma vez anonimizado, os dados pessoais não podem ser recuperados

---

## Contato

Para exercer outros direitos previstos na LGPD (acesso, portabilidade, correção) ou para dúvidas sobre o tratamento de dados, entre em contato com o responsável pelo sistema.
