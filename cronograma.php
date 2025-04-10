<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

// Verificar se o usuário está logado
verificarLogin();

// Verificar permissões
$podeVisualizar = verificarPermissao('visualizar_cronograma');
$podeCriar = verificarPermissao('criar_cronograma');
$podeEditar = verificarPermissao('editar_cronograma');
$podeExcluir = verificarPermissao('excluir_cronograma');

if (!$podeVisualizar) {
    header('Location: index.php');
    exit;
}

$mensagem = '';

// Processar formulário de adição de etapa
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'add' && $podeCriar) {
        $projeto_id = $_POST['projeto_id'];
        $etapa = $_POST['etapa'];
        $descricao = $_POST['descricao'];
        $tipo = $_POST['tipo'];
        $responsavel = $_POST['responsavel'];
        $data_inicio = $_POST['data_inicio'];
        $data_termino_planejada = $_POST['data_termino_planejada'];
        $data_termino_real = $_POST['data_termino_real'] ?: null;
        $status = $_POST['status'];
        $observacoes = $_POST['observacoes'];

        try {
            $sql = "INSERT INTO etapas_cronograma (projeto_id, etapa, descricao, tipo, responsavel, 
                    data_inicio, data_termino_planejada, data_termino_real, status, observacoes) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$projeto_id, $etapa, $descricao, $tipo, $responsavel, 
                           $data_inicio, $data_termino_planejada, $data_termino_real, 
                           $status, $observacoes]);
            
            $mensagem = '<div class="alert alert-success">Etapa adicionada com sucesso!</div>';
        } catch(PDOException $e) {
            $mensagem = '<div class="alert alert-danger">Erro ao adicionar etapa: ' . $e->getMessage() . '</div>';
        }
    }
}

// Processar exclusão de etapa
if (isset($_GET['delete']) && $podeExcluir) {
    $id = $_GET['delete'];
    try {
        $sql = "DELETE FROM etapas_cronograma WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        
        $mensagem = '<div class="alert alert-success alert-dismissible fade show" role="alert">
            Etapa excluída com sucesso!
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>';
        
        // Adicionar script para remover a mensagem após 3 segundos
        echo '<script>
            setTimeout(function() {
                document.querySelector(".alert").remove();
            }, 3000);
        </script>';
    } catch(PDOException $e) {
        $mensagem = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
            Erro ao excluir etapa: ' . $e->getMessage() . '
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>';
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
    <title>Gerenciador de Projetos - Cronograma</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/nav.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Cronograma</h2>
            <?php if ($podeCriar): ?>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#novaEtapaModal">
                <i class="bi bi-plus-circle"></i> Nova Etapa
            </button>
            <?php endif; ?>
        </div>

        <?php echo $mensagem; ?>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Etapa</th>
                                <th>Projeto</th>
                                <th>Tipo</th>
                                <th>Responsável</th>
                                <th>Datas</th>
                                <th>Status</th>
                                <?php if ($podeEditar || $podeExcluir): ?>
                                <th>Ações</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT ec.*, p.nome as projeto_nome 
                                   FROM etapas_cronograma ec 
                                   JOIN projetos p ON ec.projeto_id = p.id 
                                   ORDER BY ec.data_inicio ASC";
                            $stmt = $pdo->query($sql);
                            while ($row = $stmt->fetch()) {
                                ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($row['etapa']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($row['descricao']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['projeto_nome']); ?></td>
                                    <td><?php echo htmlspecialchars($row['tipo']); ?></td>
                                    <td><?php echo htmlspecialchars($row['responsavel']); ?></td>
                                    <td>
                                        Início: <?php echo date('d/m/Y', strtotime($row['data_inicio'])); ?><br>
                                        Planejado: <?php echo date('d/m/Y', strtotime($row['data_termino_planejada'])); ?><br>
                                        <?php if ($row['data_termino_real']): ?>
                                            Real: <?php echo date('d/m/Y', strtotime($row['data_termino_real'])); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            switch($row['status']) {
                                                case 'Não Iniciado':
                                                    echo 'secondary';
                                                    break;
                                                case 'Em Andamento':
                                                    echo 'primary';
                                                    break;
                                                case 'Concluído':
                                                    echo 'success';
                                                    break;
                                                case 'Atrasado':
                                                    echo 'danger';
                                                    break;
                                                case 'Cancelado':
                                                    echo 'dark';
                                                    break;
                                                case 'Aguardando':
                                                    echo 'warning';
                                                    break;
                                                default:
                                                    echo 'secondary';
                                            }
                                        ?>">
                                            <?php echo htmlspecialchars($row['status']); ?>
                                        </span>
                                    </td>
                                    <?php if ($podeEditar || $podeExcluir): ?>
                                    <td>
                                        <div class="btn-group">
                                            <?php if ($podeEditar): ?>
                                            <a href="editar_etapa.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <?php endif; ?>
                                            <?php if ($podeExcluir): ?>
                                            <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Tem certeza que deseja excluir esta etapa?')">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                                <?php
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?php if ($podeCriar): ?>
    <!-- Modal Nova Etapa -->
    <div class="modal fade" id="novaEtapaModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nova Etapa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="novaEtapaForm">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="mb-3">
                            <label for="projeto_id" class="form-label">Projeto</label>
                            <select class="form-select" id="projeto_id" name="projeto_id" required>
                                <option value="">Selecione um projeto...</option>
                                <?php foreach ($projetos as $projeto): ?>
                                    <option value="<?php echo $projeto['id']; ?>"><?php echo htmlspecialchars($projeto['nome']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="etapa" class="form-label">Nome da Etapa</label>
                            <input type="text" class="form-control" id="etapa" name="etapa" required>
                        </div>

                        <div class="mb-3">
                            <label for="descricao" class="form-label">Descrição</label>
                            <textarea class="form-control" id="descricao" name="descricao" rows="3"></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="tipo" class="form-label">Tipo</label>
                            <select class="form-select" id="tipo" name="tipo" required>
                                <option value="Infraestrutura">Infraestrutura</option>
                                <option value="Desenvolvimento">Desenvolvimento</option>
                                <option value="Análise">Análise</option>
                                <option value="Treinamento">Treinamento</option>
                                <option value="Documentação">Documentação</option>
                                <option value="Testes e Validação">Testes e Validação</option>
                                <option value="Configuração e Testes">Configuração e Testes</option>
                                <option value="Outros">Outros</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="responsavel" class="form-label">Responsável</label>
                            <input type="text" class="form-control" id="responsavel" name="responsavel" required>
                        </div>

                        <div class="mb-3">
                            <label for="data_inicio" class="form-label">Data de Início</label>
                            <input type="date" class="form-control" id="data_inicio" name="data_inicio" required>
                        </div>

                        <div class="mb-3">
                            <label for="data_termino_planejada" class="form-label">Data de Término Planejada</label>
                            <input type="date" class="form-control" id="data_termino_planejada" name="data_termino_planejada" required>
                        </div>

                        <div class="mb-3">
                            <label for="data_termino_real" class="form-label">Data de Término Real</label>
                            <input type="date" class="form-control" id="data_termino_real" name="data_termino_real">
                        </div>

                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="Não Iniciado">Não Iniciado</option>
                                <option value="Em Andamento">Em Andamento</option>
                                <option value="Concluído">Concluído</option>
                                <option value="Atrasado">Atrasado</option>
                                <option value="Cancelado">Cancelado</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="observacoes" class="form-label">Observações</label>
                            <textarea class="form-control" id="observacoes" name="observacoes" rows="3"></textarea>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Adicionar Etapa</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 