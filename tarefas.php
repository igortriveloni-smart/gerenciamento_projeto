<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

// Verificar se o usuário está logado
verificarLogin();

// Verificar permissões
$podeVisualizar = verificarPermissao('visualizar_tarefas');
$podeCriar = verificarPermissao('criar_tarefas');
$podeEditar = verificarPermissao('editar_tarefas');
$podeExcluir = verificarPermissao('excluir_tarefas');

if (!$podeVisualizar) {
    header('Location: index.php');
    exit;
}

$mensagem = '';

// Processar formulário de adição de tarefa
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'add' && $podeCriar) {
        $projeto_id = $_POST['projeto_id'];
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
            $sql = "INSERT INTO tarefas (projeto_id, etapa_id, titulo, descricao, sprint, responsavel, 
                    data_inicio, data_termino_planejada, data_termino_real, dias_uteis, status, comentarios) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$projeto_id, $etapa_id, $titulo, $descricao, $sprint, $responsavel, 
                           $data_inicio, $data_termino_planejada, $data_termino_real, $dias_uteis, 
                           $status, $comentarios]);
            
            $mensagem = '<div class="alert alert-success">
                Tarefa adicionada com sucesso!</div>';
            
            // Adicionar script para remover a mensagem após 3 segundos
            echo '<script>
                setTimeout(function() {
                    document.querySelector(".alert").remove();
                }, 3000);
            </script>';
        } catch(PDOException $e) {
            $mensagem = '<div class="alert alert-danger">
                Erro ao adicionar tarefa: ' . $e->getMessage() . '</div>';
        }
    }
}

// Processar exclusão de tarefa
if (isset($_GET['delete']) && $podeExcluir) {
    $id = $_GET['delete'];
    try {
        $sql = "DELETE FROM tarefas WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);

        $mensagem = '<div class="alert alert-success">
            Tarefa excluída com sucesso!</div>';

        // Adicionar script para remover a mensagem após 3 segundos
        echo '<script>
            setTimeout(function() {
                document.querySelector(".alert").remove();
            }, 3000);
        </script>';
    } catch (PDOException $e) {
        $mensagem = '<div class="alert alert-danger">
            Erro ao excluir tarefa: ' . $e->getMessage() . '</div>';
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
    <title>Gerenciador de Projetos - Tarefas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/nav.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Tarefas</h2>
            <?php if ($podeCriar): ?>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#novaTarefaModal">
                <i class="bi bi-plus-circle"></i> Nova Tarefa
            </button>
            <?php endif; ?>
        </div>

        <?php echo $mensagem; ?>      

        <div class="card">
            <div class="card-body">
                 <!-- Filtros -->
                <form method="GET" class="mb-3">
                    <div class="row">
                        <!-- Filtro de Projetos -->
                        <div class="col-md-4">
                            <label for="filtro_projeto" class="form-label">Filtrar por Projeto</label>
                            <select class="form-select" id="filtro_projeto" name="projeto_id" onchange="this.form.submit()">
                                <option value="">Todos os Projetos</option>
                                <?php foreach ($projetos as $projeto): ?>
                                    <option value="<?php echo $projeto['id']; ?>" 
                                        <?php echo (isset($_GET['projeto_id']) && $_GET['projeto_id'] == $projeto['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($projeto['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Filtro de Sprints -->
                        <div class="col-md-4">
                            <label for="filtro_sprint" class="form-label">Filtrar por Sprint</label>
                            <select class="form-select" id="filtro_sprint" name="sprint" onchange="this.form.submit()">
                                <option value="">Todas as Sprints</option>
                                <?php for ($i = 1; $i <= 10; $i++): ?>
                                    <option value="<?php echo $i; ?>" 
                                        <?php echo (isset($_GET['sprint']) && $_GET['sprint'] == $i) ? 'selected' : ''; ?>>
                                        Sprint <?php echo $i; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Etapa</th>
                                <th>Descrição</th>
                                <th>Sprint</th>
                                <th>Responsável</th>                                
                                <th>Datas</th>
                                <th>Dias Úteis</th>
                                <th>Status</th>
                                <?php if ($podeEditar || $podeExcluir): ?>
                                <th>Ações</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Modificar a consulta SQL para aplicar os filtros
                            $sql = "SELECT t.*, p.nome as projeto_nome, ec.etapa as etapa_nome 
                            FROM tarefas t 
                            JOIN projetos p ON t.projeto_id = p.id 
                            JOIN etapas_cronograma ec ON t.etapa_id = ec.id";

                            $conditions = [];
                            $params = [];

                            if (isset($_GET['projeto_id']) && !empty($_GET['projeto_id'])) {
                                $conditions[] = "t.projeto_id = :projeto_id";
                                $params[':projeto_id'] = $_GET['projeto_id'];
                            }

                            if (isset($_GET['sprint']) && !empty($_GET['sprint'])) {
                                $conditions[] = "t.sprint = :sprint";
                                $params[':sprint'] = $_GET['sprint'];
                            }

                            if (!empty($conditions)) {
                                $sql .= " WHERE " . implode(" AND ", $conditions);
                            }

                            $sql .= " ORDER BY t.data_inicio DESC";
                            $stmt = $pdo->prepare($sql);

                            foreach ($params as $key => $value) {
                                $stmt->bindValue($key, $value, PDO::PARAM_STR);
                            }

                            $stmt->execute();

                            while ($row = $stmt->fetch()) {
                                $statusClass = '';
                                switch($row['status']) {
                                    case 'Não Iniciado':
                                        $statusClass = 'bg-secondary';
                                        break;
                                    case 'Em Andamento':
                                        $statusClass = 'bg-primary';
                                        break;
                                    case 'Concluído':
                                        $statusClass = 'bg-success';
                                        break;
                                    case 'Atrasado':
                                        $statusClass = 'bg-danger';
                                        break;
                                    case 'Cancelado':
                                        $statusClass = 'bg-dark';
                                        break;
                                    case 'Aguardando':
                                        $statusClass = 'bg-warning';
                                        break;
                                }
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['etapa_nome']); ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($row['titulo']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($row['projeto_nome']); ?></small>
                                    </td>
                                    <td>Sprint <?php echo $row['sprint']; ?></td>
                                    <td><?php echo htmlspecialchars($row['responsavel']); ?></td>                                    
                                    <td>
                                        <strong>Início:</strong> <?php echo date('d/m/Y', strtotime($row['data_inicio'])); ?><br>
                                        <strong>Planejado:</strong> <?php echo date('d/m/Y', strtotime($row['data_termino_planejada'])); ?><br>
                                        <?php if ($row['data_termino_real']): ?>
                                            <strong>Real:</strong> <?php echo date('d/m/Y', strtotime($row['data_termino_real'])); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo $row['dias_uteis'] ? htmlspecialchars($row['dias_uteis']) : 'N/A'; ?>
                                    </td>
                                    <td><span class="badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars($row['status']); ?></span></td>
                                    <?php if ($podeEditar || $podeExcluir): ?>
                                    <td>
                                        <div class="btn-group">
                                            <?php if ($podeEditar): ?>      
                                            <a href="editar_tarefa.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <?php endif; ?>
                                            <?php if ($podeExcluir): ?>
                                            <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Tem certeza que deseja excluir esta tarefa?')">
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
    <!-- Modal Nova Tarefa -->
    <div class="modal fade" id="novaTarefaModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nova Tarefa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="novaTarefaForm">
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
                            <label for="etapa_id" class="form-label">Etapa do Cronograma</label>
                            <select class="form-select" id="etapa_id" name="etapa_id" required>
                                <option value="">Selecione uma etapa...</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="titulo" class="form-label">Título da Tarefa</label>
                            <input type="text" class="form-control" id="titulo" name="titulo" required>
                        </div>

                        <div class="mb-3">
                            <label for="descricao" class="form-label">Descrição</label>
                            <textarea class="form-control" id="descricao" name="descricao" rows="3"></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="sprint" class="form-label">Sprint</label>
                            <select class="form-select" id="sprint" name="sprint" required>
                                <?php for($i = 1; $i <= 10; $i++): ?>
                                    <option value="<?php echo $i; ?>">Sprint <?php echo $i; ?></option>
                                <?php endfor; ?>
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
                            <label for="dias_uteis" class="form-label">Dias Úteis</label>
                            <input type="number" class="form-control" id="dias_uteis" name="dias_uteis" min="1">
                        </div>

                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="Não Iniciado">Não Iniciado</option>
                                <option value="Em Andamento">Em Andamento</option>
                                <option value="Concluído">Concluído</option>
                                <option value="Atrasado">Atrasado</option>
                                <option value="Cancelado">Cancelado</option>
                                <option value="Aguardando">Aguardando</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="comentarios" class="form-label">Comentários</label>
                            <textarea class="form-control" id="comentarios" name="comentarios" rows="3"></textarea>
                        </div>
                        
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Adicionar Tarefa</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

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