<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

// Verificar se o usuário está logado e tem permissão
verificarLogin();
if (!verificarPermissao('editar_cronograma')) {
    header('Location: cronograma.php');
    exit;
}

$mensagem = '';

// Buscar dados da etapa
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $sql = "SELECT * FROM etapas_cronograma WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $etapa = $stmt->fetch();

    if (!$etapa) {
        header('Location: cronograma.php');
        exit;
    }
} else {
    header('Location: cronograma.php');
    exit;
}

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $etapa_nome = $_POST['etapa'];
    $descricao = $_POST['descricao'];
    $tipo = $_POST['tipo'];
    $responsavel = $_POST['responsavel'];
    $data_inicio = $_POST['data_inicio'];
    $data_termino_planejada = $_POST['data_termino_planejada'];
    $data_termino_real = $_POST['data_termino_real'] ?: null;
    $status = $_POST['status'];
    $observacoes = $_POST['observacoes'];

    try {
        $sql = "UPDATE etapas_cronograma SET 
                etapa = ?, descricao = ?, tipo = ?, responsavel = ?, 
                data_inicio = ?, data_termino_planejada = ?, data_termino_real = ?, 
                status = ?, observacoes = ? 
                WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$etapa_nome, $descricao, $tipo, $responsavel, 
                       $data_inicio, $data_termino_planejada, $data_termino_real, 
                       $status, $observacoes, $id]);
        
        $mensagem = '<div class="alert alert-success">Etapa atualizada com sucesso! Redirecionando em 3 segundos...</div>';
        // Adicionar script de redirecionamento
        echo '<script>
            setTimeout(function() {
                window.location.href = "cronograma.php";
            }, 3000);
        </script>';
    } catch(PDOException $e) {
        $mensagem = '<div class="alert alert-danger">Erro ao atualizar etapa: ' . $e->getMessage() . '</div>';
    }
}

// Buscar projetos para o select
$projetos = $pdo->query("SELECT id, nome FROM projetos ORDER BY nome")->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Etapa - Gerenciador de Projetos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/nav.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Editar Etapa</h2>
            <a href="cronograma.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Voltar
            </a>
        </div>

        <?php echo $mensagem; ?>

        <div class="card">
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label for="projeto_id" class="form-label">Projeto</label>
                        <select class="form-select" id="projeto_id" name="projeto_id" required disabled>
                            <?php foreach ($projetos as $projeto): ?>
                                <option value="<?php echo $projeto['id']; ?>" <?php echo $projeto['id'] == $etapa['projeto_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($projeto['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Não é possível alterar o projeto da etapa.</small>
                    </div>

                    <div class="mb-3">
                        <label for="etapa" class="form-label">Nome da Etapa</label>
                        <input type="text" class="form-control" id="etapa" name="etapa" value="<?php echo htmlspecialchars($etapa['etapa']); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="descricao" class="form-label">Descrição</label>
                        <textarea class="form-control" id="descricao" name="descricao" rows="3"><?php echo htmlspecialchars($etapa['descricao']); ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="tipo" class="form-label">Tipo</label>
                        <select class="form-select" id="tipo" name="tipo" required>
                            <option value="Infraestrutura" <?php echo $etapa['tipo'] == 'Infraestrutura' ? 'selected' : ''; ?>>Infraestrutura</option>
                            <option value="Desenvolvimento" <?php echo $etapa['tipo'] == 'Desenvolvimento' ? 'selected' : ''; ?>>Desenvolvimento</option>
                            <option value="Análise" <?php echo $etapa['tipo'] == 'Análise' ? 'selected' : ''; ?>>Análise</option>
                            <option value="Treinamento" <?php echo $etapa['tipo'] == 'Treinamento' ? 'selected' : ''; ?>>Treinamento</option>
                            <option value="Documentação" <?php echo $etapa['tipo'] == 'Documentação' ? 'selected' : ''; ?>>Documentação</option>
                            <option value="Testes e Validação" <?php echo $etapa['tipo'] == 'Testes e Validação' ? 'selected' : ''; ?>>Testes e Validação</option>
                            <option value="Configuração e Testes" <?php echo $etapa['tipo'] == 'Configuração e Testes' ? 'selected' : ''; ?>>Configuração e Testes</option>
                            <option value="Outros" <?php echo $etapa['tipo'] == 'Outros' ? 'selected' : ''; ?>>Outros</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="responsavel" class="form-label">Responsável</label>
                        <input type="text" class="form-control" id="responsavel" name="responsavel" value="<?php echo htmlspecialchars($etapa['responsavel']); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="data_inicio" class="form-label">Data de Início</label>
                        <input type="date" class="form-control" id="data_inicio" name="data_inicio" value="<?php echo $etapa['data_inicio']; ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="data_termino_planejada" class="form-label">Data de Término Planejada</label>
                        <input type="date" class="form-control" id="data_termino_planejada" name="data_termino_planejada" value="<?php echo $etapa['data_termino_planejada']; ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="data_termino_real" class="form-label">Data de Término Real</label>
                        <input type="date" class="form-control" id="data_termino_real" name="data_termino_real" value="<?php echo $etapa['data_termino_real']; ?>">
                    </div>

                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="Não Iniciado" <?php echo $etapa['status'] == 'Não Iniciado' ? 'selected' : ''; ?>>Não Iniciado</option>
                            <option value="Em Andamento" <?php echo $etapa['status'] == 'Em Andamento' ? 'selected' : ''; ?>>Em Andamento</option>
                            <option value="Concluído" <?php echo $etapa['status'] == 'Concluído' ? 'selected' : ''; ?>>Concluído</option>
                            <option value="Atrasado" <?php echo $etapa['status'] == 'Atrasado' ? 'selected' : ''; ?>>Atrasado</option>
                            <option value="Cancelado" <?php echo $etapa['status'] == 'Cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="observacoes" class="form-label">Observações</label>
                        <textarea class="form-control" id="observacoes" name="observacoes" rows="3"><?php echo htmlspecialchars($etapa['observacoes']); ?></textarea>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="cronograma.php" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 