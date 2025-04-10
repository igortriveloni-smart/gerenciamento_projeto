<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

// Verificar se o usuário está logado e tem permissão
verificarLogin();
if (!verificarPermissao('editar_tarefas')) {
    header('Location: tarefas.php');
    exit;
}

$mensagem = '';

// Buscar dados da tarefa
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $sql = "SELECT * FROM tarefas WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $tarefa = $stmt->fetch();

    if (!$tarefa) {
        header('Location: tarefas.php');
        exit;
    }
} else {
    header('Location: tarefas.php');
    exit;
}

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    //$projeto_id = $_POST['projeto_id'];
    $etapa_id = $_POST['etapa_id'];
    $titulo = $_POST['titulo'];
    $descricao = $_POST['descricao'];
    $sprint = $_POST['sprint'];
    $responsavel = $_POST['responsavel'];
    $data_inicio = $_POST['data_inicio'];
    $data_termino_planejada = $_POST['data_termino_planejada'];
    $data_termino_real = $_POST['data_termino_real'] ?: null;
    $dias_uteis = $_POST['dias_uteis'];
    $status = $_POST['status'];
    $comentarios = $_POST['comentarios'];

    try {
        // Verificar se o projeto ainda existe
        $sql_check = "SELECT id FROM projetos WHERE id = ?";
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->execute([$tarefa['projeto_id']]);
        
        if (!$stmt_check->fetch()) {
            throw new Exception("O projeto associado a esta tarefa não existe mais.");
        }

        $sql = "UPDATE tarefas SET 
                etapa_id = ?, titulo = ?, descricao = ?, 
                sprint = ?, responsavel = ?, data_inicio = ?, 
                data_termino_planejada = ?, data_termino_real = ?, 
                dias_uteis = ?, status = ?, comentarios = ? 
                WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$etapa_id, $titulo, $descricao, $sprint, $responsavel, 
                       $data_inicio, $data_termino_planejada, $data_termino_real, $dias_uteis, $status, $comentarios, $id]);
        
        $mensagem = '<div class="alert alert-success">Tarefa atualizada com sucesso! Redirecionando em 3 segundos...</div>';
        echo '<script>
                setTimeout(function() {
                    window.location.href = "tarefas.php";
                }, 3000);
            </script>';
    } catch(PDOException $e) {
        $mensagem = '<div class="alert alert-danger">Erro ao atualizar tarefa: ' . $e->getMessage() . '</div>';
    } catch(Exception $e) {
        $mensagem = '<div class="alert alert-danger">' . $e->getMessage() . '</div>';
    }
}

// Buscar projetos para o select
$projetos = $pdo->query("SELECT id, nome FROM projetos ORDER BY nome")->fetchAll();

// Buscar etapas do projeto atual
$etapas = $pdo->prepare("SELECT id, etapa FROM etapas_cronograma WHERE projeto_id = ? ORDER BY etapa");
$etapas->execute([$tarefa['projeto_id']]);
$etapas = $etapas->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciador de Projetos - Editar Tarefa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/nav.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Editar Tarefa</h2>
            <a href="tarefas.php" class="btn btn-secondary">
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
                                <option value="<?php echo $projeto['id']; ?>" <?php echo $projeto['id'] == $tarefa['projeto_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($projeto['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Não é possível alterar o projeto da tarefa.</small>
                    </div>

                            <div class="mb-3">
                                <label for="etapa_id" class="form-label">Etapa do Cronograma</label>
                                <select class="form-select" id="etapa_id" name="etapa_id" required>
                                    <option value="">Selecione uma etapa...</option>
                                    <?php foreach ($etapas as $etapa): ?>
                                        <option value="<?php echo $etapa['id']; ?>" <?php echo $tarefa['etapa_id'] == $etapa['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($etapa['etapa']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="titulo" class="form-label">Título da Tarefa</label>
                                <input type="text" class="form-control" id="titulo" name="titulo" value="<?php echo htmlspecialchars($tarefa['titulo']); ?>" required>
                            </div>

                    <div class="mb-3">
                        <label for="descricao" class="form-label">Descrição</label>
                        <textarea class="form-control" id="descricao" name="descricao" rows="3"><?php echo htmlspecialchars($tarefa['descricao']); ?></textarea>
                    </div>

                            <div class="mb-3">
                                <label for="sprint" class="form-label">Sprint</label>
                                <select class="form-select" id="sprint" name="sprint" required>
                                    <?php for($i = 1; $i <= 10; $i++): ?>
                                        <option value="<?php echo $i; ?>" <?php echo $tarefa['sprint'] == $i ? 'selected' : ''; ?>>
                                            Sprint <?php echo $i; ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="responsavel" class="form-label">Responsável</label>
                                <input type="text" class="form-control" id="responsavel" name="responsavel" value="<?php echo htmlspecialchars($tarefa['responsavel']); ?>" required>
                            </div>

                    <div class="mb-3">
                        <label for="data_inicio" class="form-label">Data de Início</label>
                        <input type="date" class="form-control" id="data_inicio" name="data_inicio" value="<?php echo $tarefa['data_inicio']; ?>" required>
                    </div>

                            <div class="mb-3">
                                <label for="data_termino_planejada" class="form-label">Data de Término Planejada</label>
                                <input type="date" class="form-control" id="data_termino_planejada" name="data_termino_planejada" value="<?php echo $tarefa['data_termino_planejada']; ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="data_termino_real" class="form-label">Data de Término Real</label>
                                <input type="date" class="form-control" id="data_termino_real" name="data_termino_real" value="<?php echo $tarefa['data_termino_real']; ?>">
                            </div>

                            <div class="mb-3">
                                <label for="dias_uteis" class="form-label">Dias Úteis</label>
                                <input type="number" class="form-control" id="dias_uteis" name="dias_uteis" min="1" value="<?php echo $tarefa['dias_uteis']; ?>">
                            </div>

                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="Não Iniciado" <?php echo $tarefa['status'] == 'Não Iniciado' ? 'selected' : ''; ?>>Não Iniciado</option>
                                    <option value="Em Andamento" <?php echo $tarefa['status'] == 'Em Andamento' ? 'selected' : ''; ?>>Em Andamento</option>
                                    <option value="Concluído" <?php echo $tarefa['status'] == 'Concluído' ? 'selected' : ''; ?>>Concluído</option>
                                    <option value="Atrasado" <?php echo $tarefa['status'] == 'Atrasado' ? 'selected' : ''; ?>>Atrasado</option>
                                    <option value="Cancelado" <?php echo $tarefa['status'] == 'Cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="comentarios" class="form-label">Comentários</label>
                                <textarea class="form-control" id="comentarios" name="comentarios" rows="3"><?php echo htmlspecialchars($tarefa['comentarios']); ?></textarea>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="tarefas.php" class="btn btn-secondary">Cancelar</a>
                                <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Carregar etapas do projeto selecionado
        document.getElementById('projeto_id').addEventListener('change', function() {
            const projetoId = this.value;
            const etapaSelect = document.getElementById('etapa_id');
            
            // Limpar opções atuais
            etapaSelect.innerHTML = '<option value="">Selecione uma etapa...</option>';
            
            if (projetoId) {
                // Buscar etapas do projeto
                fetch(`get_etapas.php?projeto_id=${projetoId}`)
                    .then(response => response.json())
                    .then(etapas => {
                        etapas.forEach(etapa => {
                            const option = document.createElement('option');
                            option.value = etapa.id;
                            option.textContent = etapa.etapa;
                            etapaSelect.appendChild(option);
                        });
                    });
            }
        });
    </script>
</body>
</html> 