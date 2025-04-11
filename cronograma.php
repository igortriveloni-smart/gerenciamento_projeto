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
        
        $mensagem = '<div class="alert alert-success" role="alert">Etapa excluída com sucesso!<div>';
        
        // Adicionar script para remover a mensagem após 3 segundos
        echo '<script>
            setTimeout(function() {
                document.querySelector(".alert").remove();
            }, 3000);
        </script>';
    } catch(PDOException $e) {
        $mensagem = '<div class="alert alert-danger" role="alert">
            Erro ao excluir etapa: ' . $e->getMessage() . '           
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
                <!-- Filtros -->
                <form method="GET" class="mb-3">
                    <div class="row g-3 align-items-end">
                        <!-- Filtro por Projeto -->
                        <div class="col-md-4">
                            <label for="filtro_projeto" class="form-label">Filtrar por Projeto</label>
                            <select class="form-select" id="filtro_projeto" name="projeto_id">
                                <option value="">Todos os Projetos</option>
                                <?php foreach ($projetos as $projeto): ?>
                                    <option value="<?php echo $projeto['id']; ?>" 
                                        <?php echo (isset($_GET['projeto_id']) && $_GET['projeto_id'] == $projeto['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($projeto['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Filtro por Responsável -->
                        <div class="col-md-4">
                            <label for="filtro_responsavel" class="form-label">Filtrar por Responsável</label>
                            <select class="form-select" id="filtro_responsavel" name="responsavel">
                                <option value="">Todos os Responsáveis</option>
                                <?php
                                // Buscar responsáveis únicos
                                $responsaveis = $pdo->query("SELECT DISTINCT responsavel FROM etapas_cronograma WHERE responsavel IS NOT NULL ORDER BY responsavel")->fetchAll();
                                foreach ($responsaveis as $responsavel):
                                ?>
                                    <option value="<?php echo htmlspecialchars($responsavel['responsavel']); ?>" 
                                        <?php echo (isset($_GET['responsavel']) && $_GET['responsavel'] == $responsavel['responsavel']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($responsavel['responsavel']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Filtro por Status -->
                        <div class="col-md-4">
                            <label for="filtro_status" class="form-label">Filtrar por Status</label>
                            <select class="form-select" id="filtro_status" name="status">
                                <option value="">Todos os Status</option>
                                <option value="Não Iniciado" <?php echo (isset($_GET['status']) && $_GET['status'] == 'Não Iniciado') ? 'selected' : ''; ?>>Não Iniciado</option>
                                <option value="Em Andamento" <?php echo (isset($_GET['status']) && $_GET['status'] == 'Em Andamento') ? 'selected' : ''; ?>>Em Andamento</option>
                                <option value="Concluído" <?php echo (isset($_GET['status']) && $_GET['status'] == 'Concluído') ? 'selected' : ''; ?>>Concluído</option>
                                <option value="Atrasado" <?php echo (isset($_GET['status']) && $_GET['status'] == 'Atrasado') ? 'selected' : ''; ?>>Atrasado</option>
                                <option value="Cancelado" <?php echo (isset($_GET['status']) && $_GET['status'] == 'Cancelado') ? 'selected' : ''; ?>>Cancelado</option>
                                <option value="Aguardando" <?php echo (isset($_GET['status']) && $_GET['status'] == 'Aguardando') ? 'selected' : ''; ?>>Aguardando</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col text-end">
                            <!-- Botão de Aplicar Filtros -->
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-funnel"></i> Aplicar Filtros
                            </button>
                            <!-- Botão de Limpar Filtros -->
                            <a href="cronograma.php" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Limpar Filtros
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <div class="card-body">
                <!-- Tabela de Etapas -->
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Projeto</th>
                                <th>Etapa</th>                                
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
                                    JOIN projetos p ON ec.projeto_id = p.id";

                            $conditions = [];
                            $params = [];

                            if (isset($_GET['projeto_id']) && !empty($_GET['projeto_id'])) {
                                $conditions[] = "ec.projeto_id = :projeto_id";
                                $params[':projeto_id'] = $_GET['projeto_id'];
                            }

                            if (isset($_GET['responsavel']) && !empty($_GET['responsavel'])) {
                                $conditions[] = "ec.responsavel = :responsavel";
                                $params[':responsavel'] = $_GET['responsavel'];
                            }

                            if (isset($_GET['status']) && !empty($_GET['status'])) {
                                $conditions[] = "ec.status = :status";
                                $params[':status'] = $_GET['status'];
                            }

                            if (!empty($conditions)) {
                                $sql .= " WHERE " . implode(" AND ", $conditions);
                            }

                            $sql .= " ORDER BY ec.data_inicio ASC";
                            $stmt = $pdo->prepare($sql);

                            foreach ($params as $key => $value) {
                                $stmt->bindValue($key, $value);
                            }

                            $stmt->execute();

                            while ($row = $stmt->fetch()) {
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['projeto_nome']); ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($row['etapa']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($row['descricao']); ?></small>
                                    </td>                                    
                                    <td><?php echo htmlspecialchars($row['tipo']); ?></td>
                                    <td><?php echo htmlspecialchars($row['responsavel']); ?></td>
                                    <td>
                                        <strong>Início:</strong> <?php echo date('d/m/Y', strtotime($row['data_inicio'])); ?><br>
                                        <strong>Planejado:</strong> <?php echo date('d/m/Y', strtotime($row['data_termino_planejada'])); ?><br>
                                        <?php if ($row['data_termino_real']): ?>
                                            <strong>Real:</strong> <?php echo date('d/m/Y', strtotime($row['data_termino_real'])); ?>
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
                            <label for="data_inicio" class="form-label">Início</label>
                            <input type="date" class="form-control" id="data_inicio" name="data_inicio" required>
                        </div>

                        <div class="mb-3">
                            <label for="data_termino_planejada" class="form-label">Término Planejada</label>
                            <input type="date" class="form-control" id="data_termino_planejada" name="data_termino_planejada" required>
                        </div>

                        <div class="mb-3">
                            <label for="data_termino_real" class="form-label">Término Real</label>
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