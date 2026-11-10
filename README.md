# Web Service de Controle Financeiro Pessoal

API REST desenvolvida em PHP 8.1+ com PDO e SQLite. Os dados são enviados e recebidos em JSON.

## 1. Requisitos

- PHP 8.1 ou superior
- Extensões `pdo` e `pdo_sqlite`
- Composer (para instalar e executar os testes)

Confira a instalação:

```bash
php -v
php -m
composer --version
```

## 2. Instalação e execução

Na pasta do projeto:

```bash
composer install
mkdir -p data
php -S localhost:8000 -t public
```

A API ficará disponível em `http://localhost:8000`. O banco SQLite é criado automaticamente em `data/database.sqlite`.

## 3. Formato das respostas

Todas as respostas são JSON. Os erros seguem o formato:

```json
{
  "erro": "Descrição do problema"
}
```

Códigos HTTP usados: `200` (consulta/alteração), `201` (criação), `204` (exclusão), `400` (JSON ou filtro inválido), `404` (recurso inexistente), `409` (conflito/regra de negócio) e `422` (dados inválidos).

## 4. Endpoints

### Categorias

| Método | Rota | Descrição |
|---|---|---|
| POST | `/categorias` | Cadastrar categoria |
| GET | `/categorias` | Listar categorias |
| GET | `/categorias/{id}` | Consultar categoria |
| PUT | `/categorias/{id}` | Atualizar categoria |
| DELETE | `/categorias/{id}` | Excluir categoria (somente sem lançamentos vinculados) |

Exemplo de criação:

```json
{
  "nome": "Salário",
  "tipo": "receita"
}
```

Valores aceitos para `tipo`: `receita` ou `despesa`.

### Lançamentos

| Método | Rota | Descrição |
|---|---|---|
| POST | `/lancamentos` | Cadastrar lançamento |
| GET | `/lancamentos` | Listar lançamentos |
| GET | `/lancamentos/{id}` | Consultar lançamento |
| PUT | `/lancamentos/{id}` | Atualizar lançamento |
| DELETE | `/lancamentos/{id}` | Excluir lançamento |

Exemplo de criação:

```json
{
  "descricao": "Pagamento",
  "valor": 2500.00,
  "data": "2026-11-10",
  "tipo": "receita",
  "categoria_id": 1
}
```

Filtros combináveis em `GET /lancamentos`:

- `data_inicial=2026-11-01`
- `data_final=2026-11-30`
- `categoria_id=1`
- `tipo=despesa`

### Resumo financeiro

| Método | Rota | Descrição |
|---|---|---|
| GET | `/resumo/saldo` | Saldo atual (receitas - despesas) |
| GET | `/resumo/periodo?data_inicial=AAAA-MM-DD&data_final=AAAA-MM-DD` | Totais de receitas, despesas e saldo do período |

## 5. Regras de negócio implementadas

- O valor do lançamento deve ser numérico e maior que zero.
- O tipo do lançamento deve ser igual ao tipo da categoria.
- Não é permitido cadastrar duas categorias com o mesmo nome (comparação sem diferenciar maiúsculas/minúsculas).
- Não é permitido cadastrar lançamento com categoria inexistente.
- A data é obrigatória e deve existir no calendário no formato `AAAA-MM-DD`.
- Uma categoria com lançamentos vinculados não pode ser excluída.
- Os dados recebidos são validados e erros retornam mensagens JSON.

## 6. Testes automatizados

```bash
composer install
composer test
```

Os testes usam um banco SQLite temporário, separado dos dados reais. Eles verificam criação e consulta de categorias, validações, lançamentos, filtros, resumo financeiro, exclusões e recursos inexistentes.

## 7. Exemplos com cURL

Criar categoria:

```bash
curl -X POST http://localhost:8000/categorias \
  -H "Content-Type: application/json" \
  -d '{"nome":"Mercado","tipo":"despesa"}'
```

Criar lançamento:

```bash
curl -X POST http://localhost:8000/lancamentos \
  -H "Content-Type: application/json" \
  -d '{"descricao":"Compras","valor":180.50,"data":"2026-11-10","tipo":"despesa","categoria_id":1}'
```

Consultar lançamentos:

```bash
curl "http://localhost:8000/lancamentos?tipo=despesa&data_inicial=2026-11-01&data_final=2026-11-30"
```

Consultar saldo:

```bash
curl http://localhost:8000/resumo/saldo
```

## 8. Estrutura do projeto

controle-financeiro-php/
│
├── public/
│   └── index.php
│
├── src/
│   └── app.php
│
├── tests/
│   └── FinanceiroTest.php
│
├── composer.json
├── composer.lock
