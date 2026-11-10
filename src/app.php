
<?php

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

$app = AppFactory::create();
$app->addBodyParsingMiddleware();

/*
|--------------------------------------------------------------------------
| ARRAYS
|--------------------------------------------------------------------------
| Os dados ficam armazenados em arrays durante a execução.
| Não utilizamos banco de dados.
*/

$categorias = [
    ['id' => 1, 'nome' => 'Salário', 'tipo' => 'receita'],
    ['id' => 2, 'nome' => 'Alimentação', 'tipo' => 'despesa'],
    ['id' => 3, 'nome' => 'Transporte', 'tipo' => 'despesa']
];

$lancamentos = [
    [
        'id' => 1,
        'descricao' => 'Salário mensal',
        'valor' => 2500.00,
        'data' => '2026-11-05',
        'tipo' => 'receita',
        'categoria_id' => 1,
        'categoria_nome' => 'Salário'
    ],
    [
        'id' => 2,
        'descricao' => 'Compras no mercado',
        'valor' => 350.00,
        'data' => '2026-11-06',
        'tipo' => 'despesa',
        'categoria_id' => 2,
        'categoria_nome' => 'Alimentação'
    ],
    [
        'id' => 3,
        'descricao' => 'Passagem de ônibus',
        'valor' => 100.00,
        'data' => '2026-11-07',
        'tipo' => 'despesa',
        'categoria_id' => 3,
        'categoria_nome' => 'Transporte'
    ]
];

/*
|--------------------------------------------------------------------------
| FUNÇÕES AUXILIARES
|--------------------------------------------------------------------------
*/

// Retorna uma resposta JSON.
$json = function (
    Response $response,
    $dados,
    int $status = 200
): Response {
    if ($status !== 204) {
        $response->getBody()->write(
            json_encode($dados, JSON_UNESCAPED_UNICODE)
        );
    }

    return $response
        ->withHeader('Content-Type', 'application/json; charset=utf-8')
        ->withStatus($status);
};

// Procura uma categoria pelo ID.
$buscarCategoria = function (int $id) use (&$categorias) {
    foreach ($categorias as $categoria) {
        if ($categoria['id'] === $id) {
            return $categoria;
        }
    }

    return null;
};

// Procura um lançamento pelo ID.
$buscarLancamento = function (int $id) use (&$lancamentos) {
    foreach ($lancamentos as $lancamento) {
        if ($lancamento['id'] === $id) {
            return $lancamento;
        }
    }

    return null;
};

// Verifica se o ID é positivo e inteiro.
$validarId = function ($id): bool {
    return ctype_digit((string) $id) && (int) $id > 0;
};

// Valida uma data no formato AAAA-MM-DD.
$validarData = function ($data): bool {
    if (!is_string($data)) {
        return false;
    }

    $dataFormatada = DateTimeImmutable::createFromFormat(
        '!Y-m-d',
        $data
    );

    return $dataFormatada !== false
        && $dataFormatada->format('Y-m-d') === $data;
};

// Verifica se uma categoria já existe pelo nome.
$categoriaDuplicada = function (
    string $nome,
    ?int $ignorarId = null
) use (&$categorias): bool {

    foreach ($categorias as $categoria) {
        if (
            strcasecmp($categoria['nome'], $nome) === 0
            && $categoria['id'] !== $ignorarId
        ) {
            return true;
        }
    }

    return false;
};

// Valida os dados de uma categoria.
$validarCategoria = function (
    array $dados,
    ?int $ignorarId = null
) use ($categoriaDuplicada): ?string {

    $nome = trim((string) ($dados['nome'] ?? ''));
    $tipo = $dados['tipo'] ?? '';

    if ($nome === '' || strlen($nome) > 100) {
        return 'O nome da categoria é obrigatório e deve ter até 100 caracteres.';
    }

    if (!in_array($tipo, ['receita', 'despesa'], true)) {
        return 'O tipo da categoria deve ser receita ou despesa.';
    }

    if ($categoriaDuplicada($nome, $ignorarId)) {
        return 'Já existe uma categoria com esse nome.';
    }

    return null;
};

// Valida os dados de um lançamento.
$validarLancamento = function (
    array $dados
) use ($validarData, $buscarCategoria): ?string {

    $descricao = trim((string) ($dados['descricao'] ?? ''));
    $valor = $dados['valor'] ?? null;
    $data = $dados['data'] ?? null;
    $tipo = $dados['tipo'] ?? null;

    $categoriaId = filter_var(
        $dados['categoria_id'] ?? null,
        FILTER_VALIDATE_INT
    );

    if ($descricao === '' || strlen($descricao) > 200) {
        return 'A descrição é obrigatória e deve ter até 200 caracteres.';
    }

    if (!is_numeric($valor) || (float) $valor <= 0) {
        return 'O valor do lançamento deve ser numérico e maior que zero.';
    }

    if (!$validarData($data)) {
        return 'A data é obrigatória e deve ser válida no formato AAAA-MM-DD.';
    }

    if (!in_array($tipo, ['receita', 'despesa'], true)) {
        return 'O tipo deve ser receita ou despesa.';
    }

    if ($categoriaId === false || $categoriaId < 1) {
        return 'Informe um categoria_id válido.';
    }

    $categoria = $buscarCategoria($categoriaId);

    if ($categoria === null) {
        return 'Categoria não encontrada.';
    }

    if ($categoria['tipo'] !== $tipo) {
        return 'O tipo do lançamento deve ser compatível com o tipo da categoria.';
    }

    return null;
};

/*
|--------------------------------------------------------------------------
| CATEGORIAS
|--------------------------------------------------------------------------
*/

// GET /categorias
// Lista todas as categorias.
$app->get('/categorias', function (
    Request $request,
    Response $response
) use (&$categorias, $json) {

    return $json($response, $categorias);
});

// GET /categorias/{id}
// Consulta uma categoria específica.
$app->get('/categorias/{id}', function (
    Request $request,
    Response $response,
    array $args
) use (&$categorias, $buscarCategoria, $validarId, $json) {

    if (!$validarId($args['id'])) {
        return $json($response, [
            'erro' => 'O identificador deve ser um número inteiro positivo.'
        ], 400);
    }

    $categoria = $buscarCategoria((int) $args['id']);

    if ($categoria === null) {
        return $json($response, [
            'erro' => 'Categoria não encontrada.'
        ], 404);
    }

    return $json($response, $categoria);
});

// POST /categorias
// Cadastra uma categoria.
$app->post('/categorias', function (
    Request $request,
    Response $response
) use (&$categorias, $validarCategoria, $json) {

    $dados = $request->getParsedBody() ?? [];

    if (isset($dados['id'])) {
        return $json($response, [
            'erro' => 'O id não pode estar preenchido.'
        ], 400);
    }

    $erro = $validarCategoria($dados);

    if ($erro !== null) {
        return $json($response, ['erro' => $erro], 422);
    }

    $novoId = empty($categorias)
        ? 1
        : max(array_column($categorias, 'id')) + 1;

    $categoria = [
        'id' => $novoId,
        'nome' => trim($dados['nome']),
        'tipo' => $dados['tipo']
    ];

    $categorias[] = $categoria;

    return $json($response, $categoria, 201);
});

// PUT /categorias/{id}
// Atualiza uma categoria.
$app->put('/categorias/{id}', function (
    Request $request,
    Response $response,
    array $args
) use (
    &$categorias,
    &$lancamentos,
    $buscarCategoria,
    $validarCategoria,
    $validarId,
    $json
) {

    if (!$validarId($args['id'])) {
        return $json($response, [
            'erro' => 'O identificador deve ser um número inteiro positivo.'
        ], 400);
    }

    $id = (int) $args['id'];

    $categoria = $buscarCategoria($id);

    if ($categoria === null) {
        return $json($response, [
            'erro' => 'Categoria não encontrada.'
        ], 404);
    }

    $dados = $request->getParsedBody() ?? [];

    $erro = $validarCategoria($dados, $id);

    if ($erro !== null) {
        return $json($response, ['erro' => $erro], 422);
    }

    // Atualiza os dados da categoria.
    foreach ($categorias as &$item) {
        if ($item['id'] === $id) {
            $item['nome'] = trim($dados['nome']);
            $item['tipo'] = $dados['tipo'];
        }
    }

    unset($item);

    // Atualiza o nome da categoria nos lançamentos relacionados.
    foreach ($lancamentos as &$lancamento) {
        if ($lancamento['categoria_id'] === $id) {

            if ($lancamento['tipo'] !== $dados['tipo']) {
                return $json($response, [
                    'erro' => 'Não é possível alterar o tipo da categoria porque existem lançamentos incompatíveis.'
                ], 409);
            }

            $lancamento['categoria_nome'] = trim($dados['nome']);
        }
    }

    unset($lancamento);

    return $json($response, $buscarCategoria($id));
});

// DELETE /categorias/{id}
// Exclui uma categoria sem lançamentos vinculados.
$app->delete('/categorias/{id}', function (
    Request $request,
    Response $response,
    array $args
) use (&$categorias, &$lancamentos, $buscarCategoria, $validarId, $json) {

    if (!$validarId($args['id'])) {
        return $json($response, [
            'erro' => 'O identificador deve ser um número inteiro positivo.'
        ], 400);
    }

    $id = (int) $args['id'];

    if ($buscarCategoria($id) === null) {
        return $json($response, [
            'erro' => 'Categoria não encontrada.'
        ], 404);
    }

    foreach ($lancamentos as $lancamento) {
        if ($lancamento['categoria_id'] === $id) {
            return $json($response, [
                'erro' => 'Não é permitido excluir categoria com lançamentos vinculados.'
            ], 409);
        }
    }

    foreach ($categorias as $indice => $categoria) {
        if ($categoria['id'] === $id) {
            array_splice($categorias, $indice, 1);
            break;
        }
    }

    return $json($response, null, 204);
});

/*
|--------------------------------------------------------------------------
| LANÇAMENTOS
|--------------------------------------------------------------------------
*/

// GET /lancamentos
// Lista os lançamentos e permite utilizar filtros.
$app->get('/lancamentos', function (
    Request $request,
    Response $response
) use (&$lancamentos, $validarData, $validarId, $json) {

    $filtros = $request->getQueryParams();

    $resultado = $lancamentos;

    // Filtro por período.
    if (
        isset($filtros['data_inicial'])
        || isset($filtros['data_final'])
    ) {

        $inicio = $filtros['data_inicial'] ?? null;
        $fim = $filtros['data_final'] ?? null;

        if (
            !$validarData($inicio)
            || !$validarData($fim)
            || $inicio > $fim
        ) {
            return $json($response, [
                'erro' => 'Informe data_inicial e data_final válidas, com início anterior ao fim.'
            ], 400);
        }

        $resultado = array_filter(
            $resultado,
            function ($lancamento) use ($inicio, $fim) {
                return $lancamento['data'] >= $inicio
                    && $lancamento['data'] <= $fim;
            }
        );
    }

    // Filtro por categoria.
    if (isset($filtros['categoria_id'])) {

        if (!$validarId((string) $filtros['categoria_id'])) {
            return $json($response, [
                'erro' => 'Informe um categoria_id válido.'
            ], 400);
        }

        $categoriaId = (int) $filtros['categoria_id'];

        $resultado = array_filter(
            $resultado,
            function ($lancamento) use ($categoriaId) {
                return $lancamento['categoria_id'] === $categoriaId;
            }
        );
    }

    // Filtro por tipo.
    if (isset($filtros['tipo'])) {

        if (!in_array($filtros['tipo'], ['receita', 'despesa'], true)) {
            return $json($response, [
                'erro' => 'O tipo deve ser receita ou despesa.'
            ], 422);
        }

        $tipo = $filtros['tipo'];

        $resultado = array_filter(
            $resultado,
            function ($lancamento) use ($tipo) {
                return $lancamento['tipo'] === $tipo;
            }
        );
    }

    // Ordena os lançamentos pela data mais recente.
    usort($resultado, function ($a, $b) {
        return strcmp($b['data'], $a['data'])
            ?: ($b['id'] <=> $a['id']);
    });

    return $json($response, array_values($resultado));
});

// GET /lancamentos/{id}
// Consulta um lançamento específico.
$app->get('/lancamentos/{id}', function (
    Request $request,
    Response $response,
    array $args
) use ($buscarLancamento, $validarId, $json) {

    if (!$validarId($args['id'])) {
        return $json($response, [
            'erro' => 'O identificador deve ser um número inteiro positivo.'
        ], 400);
    }

    $lancamento = $buscarLancamento((int) $args['id']);

    if ($lancamento === null) {
        return $json($response, [
            'erro' => 'Lançamento não encontrado.'
        ], 404);
    }

    return $json($response, $lancamento);
});

// POST /lancamentos
// Cadastra um lançamento.
$app->post('/lancamentos', function (
    Request $request,
    Response $response
) use (&$lancamentos, &$categorias, $validarLancamento, $json) {

    $dados = $request->getParsedBody() ?? [];

    if (isset($dados['id'])) {
        return $json($response, [
            'erro' => 'O id não pode estar preenchido.'
        ], 400);
    }

    $erro = $validarLancamento($dados);

    if ($erro !== null) {
        $status = $erro === 'Categoria não encontrada.' ? 404 : 422;
        return $json($response, ['erro' => $erro], $status);
    }

    $categoriaId = (int) $dados['categoria_id'];

    $categoria = null;

    foreach ($categorias as $item) {
        if ($item['id'] === $categoriaId) {
            $categoria = $item;
            break;
        }
    }

    $novoId = empty($lancamentos)
        ? 1
        : max(array_column($lancamentos, 'id')) + 1;

    $lancamento = [
        'id' => $novoId,
        'descricao' => trim($dados['descricao']),
        'valor' => round((float) $dados['valor'], 2),
        'data' => $dados['data'],
        'tipo' => $dados['tipo'],
        'categoria_id' => $categoriaId,
        'categoria_nome' => $categoria['nome']
    ];

    $lancamentos[] = $lancamento;

    return $json($response, $lancamento, 201);
});

// PUT /lancamentos/{id}
// Atualiza um lançamento.
$app->put('/lancamentos/{id}', function (
    Request $request,
    Response $response,
    array $args
) use (
    &$lancamentos,
    &$categorias,
    $buscarLancamento,
    $validarLancamento,
    $validarId,
    $json
) {

    if (!$validarId($args['id'])) {
        return $json($response, [
            'erro' => 'O identificador deve ser um número inteiro positivo.'
        ], 400);
    }

    $id = (int) $args['id'];

    if ($buscarLancamento($id) === null) {
        return $json($response, [
            'erro' => 'Lançamento não encontrado.'
        ], 404);
    }

    $dados = $request->getParsedBody() ?? [];

    $erro = $validarLancamento($dados);

    if ($erro !== null) {
        $status = $erro === 'Categoria não encontrada.' ? 404 : 422;
        return $json($response, ['erro' => $erro], $status);
    }

    $categoriaId = (int) $dados['categoria_id'];

    $categoria = null;

    foreach ($categorias as $item) {
        if ($item['id'] === $categoriaId) {
            $categoria = $item;
            break;
        }
    }

    $atualizado = [
        'id' => $id,
        'descricao' => trim($dados['descricao']),
        'valor' => round((float) $dados['valor'], 2),
        'data' => $dados['data'],
        'tipo' => $dados['tipo'],
        'categoria_id' => $categoriaId,
        'categoria_nome' => $categoria['nome']
    ];

    foreach ($lancamentos as &$lancamento) {
        if ($lancamento['id'] === $id) {
            $lancamento = $atualizado;
            break;
        }
    }

    unset($lancamento);

    return $json($response, $atualizado);
});

// DELETE /lancamentos/{id}
// Exclui um lançamento.
$app->delete('/lancamentos/{id}', function (
    Request $request,
    Response $response,
    array $args
) use (&$lancamentos, $buscarLancamento, $validarId, $json) {

    if (!$validarId($args['id'])) {
        return $json($response, [
            'erro' => 'O identificador deve ser um número inteiro positivo.'
        ], 400);
    }

    $id = (int) $args['id'];

    if ($buscarLancamento($id) === null) {
        return $json($response, [
            'erro' => 'Lançamento não encontrado.'
        ], 404);
    }

    foreach ($lancamentos as $indice => $lancamento) {
        if ($lancamento['id'] === $id) {
            array_splice($lancamentos, $indice, 1);
            break;
        }
    }

    return $json($response, null, 204);
});

/*
|--------------------------------------------------------------------------
| RESUMOS FINANCEIROS
|--------------------------------------------------------------------------
*/

// GET /resumo/saldo
// Calcula todas as receitas, despesas e o saldo.
$app->get('/resumo/saldo', function (
    Request $request,
    Response $response
) use (&$lancamentos, $json) {

    $receitas = 0;
    $despesas = 0;

    foreach ($lancamentos as $lancamento) {

        if ($lancamento['tipo'] === 'receita') {
            $receitas += $lancamento['valor'];
        }

        if ($lancamento['tipo'] === 'despesa') {
            $despesas += $lancamento['valor'];
        }
    }

    return $json($response, [
        'total_receitas' => round($receitas, 2),
        'total_despesas' => round($despesas, 2),
        'saldo' => round($receitas - $despesas, 2)
    ]);
});

// GET /resumo/periodo
// Calcula os totais dentro de um período específico.
$app->get('/resumo/periodo', function (
    Request $request,
    Response $response
) use (&$lancamentos, $validarData, $json) {

    $filtros = $request->getQueryParams();

    $inicio = $filtros['data_inicial'] ?? null;
    $fim = $filtros['data_final'] ?? null;

    if (
        !$validarData($inicio)
        || !$validarData($fim)
        || $inicio > $fim
    ) {
        return $json($response, [
            'erro' => 'Informe data_inicial e data_final válidas, com início anterior ao fim.'
        ], 400);
    }

    $receitas = 0;
    $despesas = 0;

    foreach ($lancamentos as $lancamento) {

        if (
            $lancamento['data'] < $inicio
            || $lancamento['data'] > $fim
        ) {
            continue;
        }

        if ($lancamento['tipo'] === 'receita') {
            $receitas += $lancamento['valor'];
        }

        if ($lancamento['tipo'] === 'despesa') {
            $despesas += $lancamento['valor'];
        }
    }

    return $json($response, [
        'total_receitas' => round($receitas, 2),
        'total_despesas' => round($despesas, 2),
        'saldo' => round($receitas - $despesas, 2),
        'data_inicial' => $inicio,
        'data_final' => $fim
    ]);
});

// Retorna a aplicação para o PHPUnit.
// Não colocar $app->run() aqui!
return $app;
