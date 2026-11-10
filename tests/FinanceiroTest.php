
<?php

use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;

class FinanceiroTest extends TestCase
{
    // Cada teste recebe uma aplicação nova.
    private function criarApp()
    {
        return require __DIR__ . '/../src/app.php';
    }

    // Cria uma requisição JSON.
    private function criarRequisicaoJson($metodo, $url, $dados = null)
    {
        $request = (new ServerRequestFactory())
            ->createServerRequest($metodo, $url)
            ->withHeader('Content-Type', 'application/json');

        if ($dados !== null) {
            $request->getBody()->write(json_encode($dados));
            $request->getBody()->rewind();
        }

        return $request;
    }

    // Testa se a listagem de categorias funciona.
    public function testGetCategorias(): void
    {
        $app = $this->criarApp();

        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', '/categorias');

        $response = $app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());

        $corpo = json_decode((string) $response->getBody(), true);

        $this->assertCount(3, $corpo);
        $this->assertEquals('Salário', $corpo[0]['nome']);
    }

    // Testa a consulta de uma categoria pelo ID.
    public function testGetCategoriaPorId(): void
    {
        $app = $this->criarApp();

        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', '/categorias/1');

        $response = $app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());

        $corpo = json_decode((string) $response->getBody(), true);

        $this->assertEquals('Salário', $corpo['nome']);
    }

    // Testa a consulta de uma categoria inexistente.
    public function testGetCategoriaInexistente(): void
    {
        $app = $this->criarApp();

        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', '/categorias/999');

        $response = $app->handle($request);

        $this->assertEquals(404, $response->getStatusCode());

        $corpo = json_decode((string) $response->getBody(), true);

        $this->assertEquals('Categoria não encontrada.', $corpo['erro']);
    }

    // Testa o cadastro de uma categoria.
    public function testPostCategoria(): void
    {
        $app = $this->criarApp();

        $request = $this->criarRequisicaoJson(
            'POST',
            '/categorias',
            [
                'nome' => 'Moradia',
                'tipo' => 'despesa'
            ]
        );

        $response = $app->handle($request);

        $this->assertEquals(201, $response->getStatusCode());

        $corpo = json_decode((string) $response->getBody(), true);

        $this->assertEquals(4, $corpo['id']);
        $this->assertEquals('Moradia', $corpo['nome']);
    }

    // Testa a rejeição de uma categoria duplicada.
    public function testPostCategoriaDuplicada(): void
    {
        $app = $this->criarApp();

        $request = $this->criarRequisicaoJson(
            'POST',
            '/categorias',
            [
                'nome' => 'salário',
                'tipo' => 'receita'
            ]
        );

        $response = $app->handle($request);

        $this->assertEquals(422, $response->getStatusCode());
    }

    // Testa a atualização de uma categoria.
    public function testPutCategoria(): void
    {
        $app = $this->criarApp();

        $request = $this->criarRequisicaoJson(
            'PUT',
            '/categorias/1',
            [
                'nome' => 'Salário atualizado',
                'tipo' => 'receita'
            ]
        );

        $response = $app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());

        $corpo = json_decode((string) $response->getBody(), true);

        $this->assertEquals('Salário atualizado', $corpo['nome']);
    }

    // Não permite excluir uma categoria que possui lançamentos.
    public function testDeleteCategoriaComLancamentos(): void
    {
        $app = $this->criarApp();

        $request = (new ServerRequestFactory())
            ->createServerRequest('DELETE', '/categorias/1');

        $response = $app->handle($request);

        $this->assertEquals(409, $response->getStatusCode());

        $corpo = json_decode((string) $response->getBody(), true);

        $this->assertArrayHasKey('erro', $corpo);
    }

    // Testa a exclusão de uma categoria sem lançamentos.
    public function testDeleteCategoria(): void
    {
        $app = $this->criarApp();

        // A categoria 1 possui lançamento, então excluímos primeiro
        // o lançamento 1.
        $request = (new ServerRequestFactory())
            ->createServerRequest('DELETE', '/lancamentos/1');

        $app->handle($request);

        $request = (new ServerRequestFactory())
            ->createServerRequest('DELETE', '/categorias/1');

        $response = $app->handle($request);

        $this->assertEquals(204, $response->getStatusCode());
    }

    // Testa a listagem de lançamentos.
    public function testGetLancamentos(): void
    {
        $app = $this->criarApp();

        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', '/lancamentos');

        $response = $app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());

        $corpo = json_decode((string) $response->getBody(), true);

        $this->assertCount(3, $corpo);
    }

    // Testa a consulta de um lançamento pelo ID.
    public function testGetLancamentoPorId(): void
    {
        $app = $this->criarApp();

        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', '/lancamentos/1');

        $response = $app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());

        $corpo = json_decode((string) $response->getBody(), true);

        $this->assertEquals('Salário mensal', $corpo['descricao']);
    }

    // Testa o cadastro de um lançamento.
    public function testPostLancamento(): void
    {
        $app = $this->criarApp();

        $request = $this->criarRequisicaoJson(
            'POST',
            '/lancamentos',
            [
                'descricao' => 'Conta de luz',
                'valor' => 150,
                'data' => '2026-11-10',
                'tipo' => 'despesa',
                'categoria_id' => 2
            ]
        );

        $response = $app->handle($request);

        $this->assertEquals(201, $response->getStatusCode());

        $corpo = json_decode((string) $response->getBody(), true);

        $this->assertEquals('Conta de luz', $corpo['descricao']);
        $this->assertEquals(150, $corpo['valor']);
    }

    // Testa a atualização de um lançamento.
    public function testPutLancamento(): void
    {
        $app = $this->criarApp();

        $request = $this->criarRequisicaoJson(
            'PUT',
            '/lancamentos/1',
            [
                'descricao' => 'Salário atualizado',
                'valor' => 3000,
                'data' => '2026-11-10',
                'tipo' => 'receita',
                'categoria_id' => 1
            ]
        );

        $response = $app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());

        $corpo = json_decode((string) $response->getBody(), true);

        $this->assertEquals('Salário atualizado', $corpo['descricao']);
        $this->assertEquals(3000, $corpo['valor']);
    }

    // Testa a exclusão de um lançamento.
    public function testDeleteLancamento(): void
    {
        $app = $this->criarApp();

        $request = (new ServerRequestFactory())
            ->createServerRequest('DELETE', '/lancamentos/1');

        $response = $app->handle($request);

        $this->assertEquals(204, $response->getStatusCode());
    }

    // Testa o filtro de lançamentos por tipo.
    public function testFiltroPorTipo(): void
    {
        $app = $this->criarApp();

        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', '/lancamentos?tipo=despesa');

        $response = $app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());

        $corpo = json_decode((string) $response->getBody(), true);

        $this->assertCount(2, $corpo);
        $this->assertEquals('despesa', $corpo[0]['tipo']);
    }

    // Testa o filtro por categoria.
    public function testFiltroPorCategoria(): void
    {
        $app = $this->criarApp();

        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', '/lancamentos?categoria_id=2');

        $response = $app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());

        $corpo = json_decode((string) $response->getBody(), true);

        $this->assertCount(1, $corpo);
        $this->assertEquals('Alimentação', $corpo[0]['categoria_nome']);
    }

    // Testa o filtro por período.
    public function testFiltroPorPeriodo(): void
    {
        $app = $this->criarApp();

        $request = (new ServerRequestFactory())
            ->createServerRequest(
                'GET',
                '/lancamentos?data_inicial=2026-11-05&data_final=2026-11-06'
            );

        $response = $app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());

        $corpo = json_decode((string) $response->getBody(), true);

        $this->assertCount(2, $corpo);
    }

    // Testa a validação do valor do lançamento.
    public function testLancamentoComValorInvalido(): void
    {
        $app = $this->criarApp();

        $request = $this->criarRequisicaoJson(
            'POST',
            '/lancamentos',
            [
                'descricao' => 'Teste',
                'valor' => 0,
                'data' => '2026-11-10',
                'tipo' => 'despesa',
                'categoria_id' => 2
            ]
        );

        $response = $app->handle($request);

        $this->assertEquals(422, $response->getStatusCode());
    }

    // Testa a incompatibilidade entre categoria e lançamento.
    public function testTipoIncompativel(): void
    {
        $app = $this->criarApp();

        $request = $this->criarRequisicaoJson(
            'POST',
            '/lancamentos',
            [
                'descricao' => 'Teste',
                'valor' => 100,
                'data' => '2026-11-10',
                'tipo' => 'despesa',
                'categoria_id' => 1
            ]
        );

        $response = $app->handle($request);

        $this->assertEquals(422, $response->getStatusCode());
    }

    // Testa o resumo financeiro geral.
    public function testResumoSaldo(): void
    {
        $app = $this->criarApp();

        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', '/resumo/saldo');

        $response = $app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());

        $corpo = json_decode((string) $response->getBody(), true);

        $this->assertEquals(2500, $corpo['total_receitas']);
        $this->assertEquals(450, $corpo['total_despesas']);
        $this->assertEquals(2050, $corpo['saldo']);
    }

    // Testa o resumo financeiro por período.
    public function testResumoPeriodo(): void
    {
        $app = $this->criarApp();

        $request = (new ServerRequestFactory())
            ->createServerRequest(
                'GET',
                '/resumo/periodo?data_inicial=2026-11-05&data_final=2026-11-07'
            );

        $response = $app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());

        $corpo = json_decode((string) $response->getBody(), true);

        $this->assertEquals(2500, $corpo['total_receitas']);
        $this->assertEquals(450, $corpo['total_despesas']);
        $this->assertEquals(2050, $corpo['saldo']);
    }
}
