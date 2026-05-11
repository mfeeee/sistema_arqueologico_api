# Contributing Guide

## Objetivo

Este documento define o fluxo de colaboração para o repositório da API. A ideia é evitar alterações diretas na branch principal, manter o histórico limpo e garantir revisões antes de qualquer merge.

## Fluxo de trabalho

1. Crie uma branch a partir da `main`.
2. Faça suas alterações nessa branch.
3. Rode testes e validações localmente.
4. Abra um Pull Request para a `main`.
5. Aguarde revisão e aprovação.
6. Após aprovação, faça o merge.

## Regras de branch

- `main`: branch protegida, nunca recebe push direto.
- `feature/nome-da-feature`: novas funcionalidades.
- `fix/nome-do-bug`: correções.
- `hotfix/nome`: correções urgentes.

## Padrão de commits

Use mensagens curtas e objetivas.

Exemplos:

- `:sparkles: feat: adiciona rota para disparar TestarFilaJob e retornar status`
- `:zap: refactor: reorganiza a configuração de rotas no bootstrap/app.php`

## Antes de abrir PR

Verifique:

- se o código compila;
- se os testes passam;
- se não há secrets no código;
- se a branch está atualizada com a `main`;
- se a descrição do PR explica o que foi feito.

## Revisão

Nenhuma mudança entra direto na `main` sem revisão. A revisão deve verificar:

- aderência à arquitetura;
- segurança;
- impacto nas rotas existentes;
- compatibilidade com o banco;
- padronização de código.

## Acesso ao repositório

Colaboradores podem receber acesso de escrita, mas a branch `main` deve continuar protegida. Mesmo com permissão no repositório, ninguém deve conseguir dar push direto na `main`.

## Configuração local

1. Clone o repositório.
2. Instale dependências.
3. Configure o `.env`.
4. Rode migrations.
5. Rode os testes.

## Exemplo de fluxo

```bash
git checkout -b feature/minha-feature
# editar código
git add .
git commit -m "feat: adiciona minha feature"
git push origin feature/minha-feature
```

Depois, abra o Pull Request no GitHub.

## O que não fazer

- Não fazer push direto na `main`.
- Não subir credenciais reais.
- Não misturar alterações grandes em um único PR.
- Não ignorar falhas de teste.
- Não quebrar a organização de pastas.
