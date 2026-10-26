
<?php
declare(strict_types=1);

date_default_timezone_set('America/Sao_Paulo');

function escapar(string $valor): string {
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}

function dinheiro(float $valor): string {
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

function falhar(string $mensagem): never {
    http_response_code(400);
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Erro na matrícula</title>
        <link rel="stylesheet" href="style.css">
    </head>
    <body>
        <header class="topbar">
            <a class="logo" href="index.html">FITNESS MANAGER</a>
        </header>
        <main class="container">
            <h1>Não foi possível processar</h1>
            <div class="panel">
                <p><?= escapar($mensagem) ?></p>
                <a class="btn" href="cadastro.html">Voltar ao formulário</a>
            </div>
        </main>
    </body>
    </html>
    <?php
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: cadastro.html');
    exit;
}

$nome = trim((string) ($_POST['nome'] ?? ''));
$nascimento = (string) ($_POST['nascimento'] ?? '');
$telefone = trim((string) ($_POST['telefone'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$data = (string) ($_POST['data'] ?? '');
$plano = (string) ($_POST['plano'] ?? '');
$pagamento = (string) ($_POST['pagamento'] ?? '');

$precos = [
    'Basico' => 89.90,
    'Plus' => 129.90,
    'Premium' => 179.90
];

$nomesPlanos = [
    'Basico' => 'Básico',
    'Plus' => 'Plus',
    'Premium' => 'Premium'
];

function dataValida(string $valor): bool {
    $data = DateTimeImmutable::createFromFormat('!Y-m-d', $valor);

    return $data !== false && $data->format('Y-m-d') === $valor;
}

if (
    $nome === '' ||
    mb_strlen($nome) < 3 ||
    mb_strlen($nome) > 120 ||
    $telefone === '' ||
    strlen($telefone) > 30 ||
    !filter_var($email, FILTER_VALIDATE_EMAIL) ||
    strlen($email) > 254
) {
    falhar('Preencha corretamente todos os dados pessoais.');
}

if (
    !dataValida($nascimento) ||
    !dataValida($data)
) {
    falhar('Informe datas válidas.');
}

$hoje = new DateTimeImmutable('today');
$dataNascimento = new DateTimeImmutable($nascimento);
$dataMatricula = new DateTimeImmutable($data);

if ($dataNascimento > $hoje || $dataMatricula > $hoje) {
    falhar('As datas não podem ser futuras.');
}

$idade = $dataNascimento->diff($hoje)->y;

if ($idade < 18) {
    falhar('A matrícula é permitida somente para maiores de idade (18 anos ou mais).');
}

if (!array_key_exists($plano, $precos)) {
    falhar('Selecione um plano válido.');
}

if (!in_array($pagamento, ['PIX', 'Cartao'], true)) {
    falhar('Selecione uma forma de pagamento válida.');
}

// Valores calculados exclusivamente no servidor.
$valorOriginal = $precos[$plano];

// Não acumular descontos: vale o maior percentual.
if ($idade >= 60) {
    $percentualDesconto = 15;
} elseif ($pagamento === 'PIX') {
    $percentualDesconto = 10;
} else {
    $percentualDesconto = 0;
}

// Arredondamento para centavos.
$valorDesconto = round(
    $valorOriginal * $percentualDesconto / 100,
    2
);

$valorFinal = round($valorOriginal - $valorDesconto, 2);

$formaPagamento = $pagamento === 'PIX' ? 'PIX' : 'Cartão';
$nomePlano = $nomesPlanos[$plano];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprovante | Fitness Manager</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="topbar">
        <a class="logo" href="index.html">FITNESS MANAGER</a>
        <nav>
            <a href="index.html">Painel</a>
            <a href="cadastro.html">Matrículas</a>
            <a href="planos.html">Planos</a>
        </nav>
    </header>

    <main class="container">
        <h1>Comprovante de matrícula</h1>

        <div class="success">
            Matrícula processada com sucesso!
        </div>

        <section class="receipt">
            <h2>Resumo da matrícula</h2>

            <table>
                <tr>
                    <td>Aluno</td>
                    <td><strong><?= escapar($nome) ?></strong></td>
                </tr>
                <tr>
                    <td>Data de nascimento</td>
                    <td><?= escapar($dataNascimento->format('d/m/Y')) ?></td>
                </tr>
                <tr>
                    <td>Idade</td>
                    <td><?= $idade ?> anos</td>
                </tr>
                <tr>
                    <td>Telefone</td>
                    <td><?= escapar($telefone) ?></td>
                </tr>
                <tr>
                    <td>E-mail</td>
                    <td><?= escapar($email) ?></td>
                </tr>
                <tr>
                    <td>Data da matrícula</td>
                    <td><?= escapar($dataMatricula->format('d/m/Y')) ?></td>
                </tr>
                <tr>
                    <td>Plano</td>
                    <td><?= escapar($nomePlano) ?></td>
                </tr>
                <tr>
                    <td>Forma de pagamento</td>
                    <td><?= escapar($formaPagamento) ?></td>
                </tr>
                <tr>
                    <td>Valor original</td>
                    <td><?= dinheiro($valorOriginal) ?></td>
                </tr>
                <tr>
                    <td>Desconto aplicado</td>
                    <td>
                        <?= $percentualDesconto ?>%
                        (<?= dinheiro($valorDesconto) ?>)
                    </td>
                </tr>
                <tr>
                    <td>Valor final</td>
                    <td class="total"><?= dinheiro($valorFinal) ?></td>
                </tr>
            </table>

            <p class="subtitle">
                Comprovante demonstrativo. Os dados não são gravados
                permanentemente em um banco de dados.
            </p>

            <div class="actions">
                <button class="btn" type="button"
                        onclick="window.print()">
                    Imprimir comprovante
                </button>

                <a class="btn btn-secondary" href="cadastro.html">
                    Nova matrícula
                </a>

                <a class="btn btn-secondary" href="index.html">
                    Voltar ao painel
                </a>
            </div>
        </section>
    </main>
</body>
</html>
