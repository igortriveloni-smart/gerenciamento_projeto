<?php
require_once 'config/database.php';

// Verificar se o ID foi fornecido
if (!isset($_GET['id'])) {
    header('Location: stakeholders.php');
    exit;
}

$id = $_GET['id'];

// Processar formulário de edição
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $projeto_id = $_POST['projeto_id'];
    $nome = $_POST['nome'];
    $funcao_area = $_POST['funcao_area'];
    $telefone = $_POST['telefone'];
    $email = $_POST['email'];
    $observacao = $_POST['observacao'];

    $sql = "UPDATE stakeholders SET 
            projeto_id = ?,
            nome = ?, 
            funcao_area = ?, 
            telefone = ?, 
            email = ?, 
            observacao = ? 
            WHERE id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$projeto_id, $nome, $funcao_area, $telefone, $email, $observacao, $id]);
    
    header('Location: stakeholders.php');
    exit;
}

// Buscar dados do stakeholder
$sql = "SELECT * FROM stakeholders WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$stakeholder = $stmt->fetch();

if (!$stakeholder) {
    header('Location: stakeholders.php');
    exit;
}

// Buscar projetos para o select
$projetos = $pdo->query("SELECT id, nome FROM projetos ORDER BY nome")->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciador de Projetos - Editar Stakeholder</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/nav.php'; ?>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Editar Stakeholder</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="projeto_id" class="form-label">Projeto</label>
                                <select class="form-select" id="projeto_id" name="projeto_id" required>
                                    <option value="">Selecione um projeto...</option>
                                    <?php foreach ($projetos as $projeto): ?>
                                        <option value="<?php echo $projeto['id']; ?>" <?php echo $stakeholder['projeto_id'] == $projeto['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($projeto['nome']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="nome" class="form-label">Nome do Stakeholder</label>
                                <input type="text" class="form-control" id="nome" name="nome" value="<?php echo htmlspecialchars($stakeholder['nome']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="funcao_area" class="form-label">Função/Área</label>
                                <input type="text" class="form-control" id="funcao_area" name="funcao_area" value="<?php echo htmlspecialchars($stakeholder['funcao_area']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="telefone" class="form-label">Telefone</label>
                                <input type="tel" class="form-control" id="telefone" name="telefone" value="<?php echo htmlspecialchars($stakeholder['telefone']); ?>">
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">E-mail</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($stakeholder['email']); ?>">
                            </div>

                            <div class="mb-3">
                                <label for="observacao" class="form-label">Observação</label>
                                <textarea class="form-control" id="observacao" name="observacao" rows="3"><?php echo htmlspecialchars($stakeholder['observacao']); ?></textarea>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="stakeholders.php" class="btn btn-secondary">Voltar</a>
                                <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 