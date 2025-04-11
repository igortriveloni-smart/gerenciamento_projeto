<?php
require_once 'config/database.php';
require_once 'includes/cache.php';
require_once 'includes/pagination.php';
require_once 'includes/charts.php';
require_once 'includes/auth.php';

// Verificar se o usuário está logado
verificarLogin();

// Verificar permissões
$podeVisualizarProjeto = verificarPermissao('visualizar_projetos');
$podeVisualizarCronograma = verificarPermissao('visualizar_cronograma');
$podeVisualizarTarefas = verificarPermissao('visualizar_tarefas');
$podeVisualizarStakeholders = verificarPermissao('visualizar_stakeholders');
$podeVisualizarReunioes = verificarPermissao('visualizar_reunioes');
$podeVisualizarRelatorios = verificarPermissao('visualizar_relatorios');
$podeCriarCronograma = verificarPermissao('criar_cronograma');
$podeCriarTarefas = verificarPermissao('criar_tarefas');
$podeCriarStakeholders = verificarPermissao('criar_stakeholders');
$podeCriarReunioes = verificarPermissao('criar_reunioes');
$podeEditar = verificarPermissao('editar_projetos');

if (!$podeVisualizarProjeto || !$podeVisualizarCronograma ||
    !$podeVisualizarTarefas || !$podeVisualizarStakeholders ||
    !$podeVisualizarReunioes || !$podeVisualizarRelatorios) {
    header('Location: index.php');
    exit;
}

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$id = $_GET['id'];
$cache = new Cache();

// Buscar dados do projeto com cache
$cacheKey = "projeto_{$id}";
$projeto = $cache->get($cacheKey);

if ($projeto === false) {
    $sql = "SELECT * FROM projetos WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $projeto = $stmt->fetch();
    
    if (!$projeto) {
        header('Location: index.php');
        exit;
    }
    
    $cache->set($cacheKey, $projeto);
}

// Configurar paginação
$itemsPerPage = 10;
$currentUrl = "detalhes_projeto.php?id=" . $id;

// Buscar etapas do cronograma com paginação
$sql = "SELECT COUNT(*) FROM etapas_cronograma WHERE projeto_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$totalEtapas = $stmt->fetchColumn();
$paginationEtapas = new Pagination($totalEtapas, $itemsPerPage, 'page_etapas');

$sql = "SELECT * FROM etapas_cronograma WHERE projeto_id = ? ORDER BY data_inicio ASC LIMIT " . 
       (int)$paginationEtapas->getLimit() . " OFFSET " . (int)$paginationEtapas->getOffset();
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$etapas = $stmt->fetchAll();

// Buscar tarefas com paginação
$sql = "SELECT COUNT(*) FROM tarefas WHERE projeto_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$totalTarefas = $stmt->fetchColumn();
$paginationTarefas = new Pagination($totalTarefas, $itemsPerPage, 'page_tarefas');

$sql = "SELECT t.*, p.nome as projeto_nome, ec.etapa as etapa_nome 
                FROM tarefas t 
                JOIN projetos p ON t.projeto_id = p.id                             
                JOIN etapas_cronograma ec ON t.etapa_id = ec.id 
                WHERE t.projeto_id = ? 
                ORDER BY etapa_nome DESC LIMIT " . 
       (int)$paginationTarefas->getLimit() . " OFFSET " . (int)$paginationTarefas->getOffset();
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$tarefas = $stmt->fetchAll();

// Buscar stakeholders com paginação
$sql = "SELECT COUNT(*) FROM stakeholders WHERE projeto_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$totalStakeholders = $stmt->fetchColumn();
$paginationStakeholders = new Pagination($totalStakeholders, $itemsPerPage, 'page_stakeholders');

$sql = "SELECT * FROM stakeholders WHERE projeto_id = ? ORDER BY nome ASC LIMIT " . 
       (int)$paginationStakeholders->getLimit() . " OFFSET " . (int)$paginationStakeholders->getOffset();
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$stakeholders = $stmt->fetchAll();

// Buscar reuniões com paginação
$sql = "SELECT COUNT(*) FROM reunioes WHERE projeto_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$totalReunioes = $stmt->fetchColumn();
$paginationReunioes = new Pagination($totalReunioes, $itemsPerPage, 'page_reunioes');

$sql = "SELECT * FROM reunioes WHERE projeto_id = ? ORDER BY data_reuniao DESC LIMIT " . 
       (int)$paginationReunioes->getLimit() . " OFFSET " . (int)$paginationReunioes->getOffset();
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$reunioes = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciador de Projetos - Detalhes do Projeto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.bootstrap5.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include 'includes/nav.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><?php echo htmlspecialchars($projeto['nome']); ?></h2>
            <div>
                <?php if ($podeEditar): ?>
                <a href="editar_projeto.php?id=<?php echo $id; ?>" class="btn btn-warning">
                    <i class="bi bi-pencil"></i> Editar Projeto
                </a>
                <?php endif; ?>
                <a href="index.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Voltar
                </a>
            </div>
        </div>

        <!-- Informações Básicas -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Informações Básicas</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-2">
                        <?php if ($projeto['imagem']): ?>
                            <img src="<?php echo htmlspecialchars($projeto['imagem']); ?>" 
                                 alt="Logo" class="img-fluid mb-3">
                        <?php else: ?>
                            <i class="bi bi-building" style="font-size: 4rem;"></i>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-10">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Objetivo:</strong><br><?php echo htmlspecialchars($projeto['objetivo']); ?></p>
                                <p><strong>Escopo:</strong><br><?php echo htmlspecialchars($projeto['escopo']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Gestor:</strong> <?php echo htmlspecialchars($projeto['gestor']); ?></p>
                                <p><strong>Ponto Focal:</strong> <?php echo htmlspecialchars($projeto['ponto_focal']); ?></p>
                                <p><strong>Status:</strong> 
                                    <span class="badge bg-<?php echo $projeto['status'] == 'Concluído' ? 'success' : 
                                        ($projeto['status'] == 'Em Andamento' ? 'primary' : 
                                        ($projeto['status'] == 'Atrasado' ? 'danger' : 'secondary')); ?>">
                                        <?php echo htmlspecialchars($projeto['status']); ?>
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráficos de Progresso -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Progresso do Projeto</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $charts = new ProjectCharts($projeto, $etapas, $tarefas);
                        $progressData = $charts->getProgressData();
                        ?>
                        <div style="height: 300px;">
                            <canvas id="progressChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Distribuição de Status</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $statusData = $charts->getStatusDistribution();
                        ?>
                        <div style="height: 300px;">
                            <canvas id="statusChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sistema de Abas -->
        <ul class="nav nav-tabs mb-4" id="projectTabs" role="tablist">
            <?php if ($podeVisualizarCronograma): ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="cronograma-tab" data-bs-toggle="tab" data-bs-target="#cronograma" type="button" role="tab">
                    <i class="bi bi-calendar"></i> Cronograma
                </button>
            </li>
            <?php endif; ?>
            <?php if ($podeVisualizarTarefas): ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tarefas-tab" data-bs-toggle="tab" data-bs-target="#tarefas" type="button" role="tab">
                    <i class="bi bi-list-task"></i> Tarefas
                </button>
            </li>
            <?php endif; ?>
            <?php if ($podeVisualizarStakeholders): ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="stakeholders-tab" data-bs-toggle="tab" data-bs-target="#stakeholders" type="button" role="tab">
                    <i class="bi bi-people"></i> Stakeholders
                </button>
            </li>
            <?php endif; ?>
            <?php if ($podeVisualizarReunioes): ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="reunioes-tab" data-bs-toggle="tab" data-bs-target="#reunioes" type="button" role="tab">
                    <i class="bi bi-camera-video"></i> Reuniões
                </button>
            </li>
            <?php endif; ?>
        </ul>

        <div class="tab-content" id="projectTabsContent">
            <!-- Cronograma -->
            <div class="tab-pane fade show active" id="cronograma" role="tabpanel">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Cronograma</h5>
                        <?php if ($podeCriarCronograma): ?>
                        <a href="cronograma.php?projeto_id=<?php echo $id; ?>" class="btn btn-sm btn-primary">
                            <i class="bi bi-plus-circle"></i> Nova Etapa
                        </a>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped" <?php if($podeVisualizarRelatorios) { echo 'id="cronogramaTable"'; } ?>>
                                <thead>
                                    <tr>
                                        <th>Etapa</th>
                                        <th>Descrição</th>
                                        <th>Tipo</th>
                                        <th>Responsável</th>
                                        <th>Início</th>
                                        <th>Término Planejada</th>
                                        <th>Término Real</th>
                                        <th>Status</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($etapas as $etapa): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($etapa['etapa']); ?></td>
                                            <td><?php echo htmlspecialchars($etapa['descricao']); ?></td>
                                            <td><?php echo htmlspecialchars($etapa['tipo']); ?></td>
                                            <td><?php echo htmlspecialchars($etapa['responsavel']); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($etapa['data_inicio'])); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($etapa['data_termino_planejada'])); ?></td>
                                            <td><?php echo $etapa['data_termino_real'] ? date('d/m/Y', strtotime($etapa['data_termino_real'])) : '-'; ?></td>
                                            <td>
                                            <span class="badge bg-<?php 
                                                switch($etapa['status']) {
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
                                            <?php echo htmlspecialchars($etapa['status']); ?>
                                        </span>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="editar_etapa.php?id=<?php echo $etapa['id']; ?>" class="btn btn-sm btn-warning">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <a href="cronograma.php?delete=<?php echo $etapa['id']; ?>" class="btn btn-sm btn-danger" 
                                                       onclick="return confirm('Tem certeza que deseja excluir esta etapa?')">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php echo $paginationEtapas->render($currentUrl . "#cronograma"); ?>
                    </div>
                </div>
            </div>

            <!-- Tarefas -->
            <div class="tab-pane fade" id="tarefas" role="tabpanel">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Tarefas</h5>
                        <?php if ($podeCriarTarefas): ?>
                        <a href="tarefas.php?projeto_id=<?php echo $id; ?>" class="btn btn-sm btn-primary">
                            <i class="bi bi-plus-circle"></i> Nova Tarefa
                        </a>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped"  <?php if($podeVisualizarRelatorios) { echo 'id="tarefasTable"'; } ?>>
                                <thead>
                                    <tr>
                                        <th>Tarefa</th>
                                        <th>Descrição</th>
                                        <th>Sprint</th>
                                        <th>Responsável</th>
                                        <th>Início</th>
                                        <th>Término Planejada</th>
                                        <th>Término Real</th>
                                        <th>Dias Úteis</th>
                                        <th>Status</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tarefas as $tarefa): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($tarefa['etapa_nome']); ?></td>
                                            <td><?php echo htmlspecialchars($tarefa['descricao']); ?></td>
                                            <td><?php echo htmlspecialchars($tarefa['sprint']); ?></td>
                                            <td><?php echo htmlspecialchars($tarefa['responsavel']); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($tarefa['data_inicio'])); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($tarefa['data_termino_planejada'])); ?></td>
                                            <td><?php echo $tarefa['data_termino_real'] ? date('d/m/Y', strtotime($tarefa['data_termino_real'])) : '-'; ?></td>
                                            <td><?php echo htmlspecialchars($tarefa['dias_uteis']); ?></td>
                                            <td>
                                            <span class="badge bg-<?php 
                                                switch($tarefa['status']) {
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
                                                <?php echo htmlspecialchars($tarefa['status']); ?>
                                            </span>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="editar_tarefa.php?id=<?php echo $tarefa['id']; ?>" class="btn btn-sm btn-warning">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <a href="tarefas.php?delete=<?php echo $tarefa['id']; ?>" class="btn btn-sm btn-danger" 
                                                       onclick="return confirm('Tem certeza que deseja excluir esta tarefa?')">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php echo $paginationTarefas->render($currentUrl . "#tarefas"); ?>
                    </div>
                </div>
            </div>

            <!-- Stakeholders -->
            <div class="tab-pane fade" id="stakeholders" role="tabpanel">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Stakeholders</h5>
                        <?php if ($podeCriarStakeholders): ?>
                        <a href="stakeholders.php?projeto_id=<?php echo $id; ?>" class="btn btn-sm btn-primary">
                            <i class="bi bi-plus-circle"></i> Novo Stakeholder
                        </a>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped" <?php if($podeVisualizarRelatorios) { echo 'id="stakeholdersTable"'; } ?>>
                                <thead>
                                    <tr>
                                        <th>Nome</th>
                                        <th>Função/Área</th>
                                        <th>Telefone</th>
                                        <th>Email</th>
                                        <th>Observações</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($stakeholders as $stakeholder): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($stakeholder['nome']); ?></td>
                                            <td><?php echo htmlspecialchars($stakeholder['funcao_area']); ?></td>
                                            <td><?php echo htmlspecialchars($stakeholder['telefone']); ?></td>
                                            <td><?php echo htmlspecialchars($stakeholder['email']); ?></td>
                                            <td><?php echo htmlspecialchars($stakeholder['observacao']); ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="editar_stakeholder.php?id=<?php echo $stakeholder['id']; ?>" class="btn btn-sm btn-warning">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <a href="stakeholders.php?delete=<?php echo $stakeholder['id']; ?>" class="btn btn-sm btn-danger" 
                                                       onclick="return confirm('Tem certeza que deseja excluir este stakeholder?')">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php echo $paginationStakeholders->render($currentUrl . "#stakeholders"); ?>
                    </div>
                </div>
            </div>

            <!-- Reuniões -->
            <div class="tab-pane fade" id="reunioes" role="tabpanel">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Reuniões</h5>
                        <?php if ($podeCriarReunioes): ?>
                        <a href="reunioes.php?projeto_id=<?php echo $id; ?>" class="btn btn-sm btn-primary">
                            <i class="bi bi-plus-circle"></i> Nova Reunião
                        </a>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped" <?php if($podeVisualizarRelatorios) { echo 'id="reunioesTable"'; } ?>>
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Responsável</th>
                                        <th>Participantes</th>
                                        <th>Principais Decisões</th>
                                        <th>Próximas Ações</th>
                                        <th>Link do Vídeo</th>
                                        <th>Observações</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reunioes as $reuniao): ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y', strtotime($reuniao['data_reuniao'])); ?></td>
                                            <td><?php echo htmlspecialchars($reuniao['responsavel']); ?></td>
                                            <td><?php echo htmlspecialchars($reuniao['participantes']); ?></td>
                                            <td><?php echo htmlspecialchars($reuniao['principais_decisoes']); ?></td>
                                            <td><?php echo htmlspecialchars($reuniao['proximas_acoes']); ?></td>
                                            <td>
                                                <?php if ($reuniao['link_video']): ?>
                                                    <a href="<?php echo htmlspecialchars($reuniao['link_video']); ?>" target="_blank">
                                                        <i class="bi bi-camera-video"></i> Assistir
                                                    </a>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($reuniao['observacoes']); ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="editar_reuniao.php?id=<?php echo $reuniao['id']; ?>" class="btn btn-sm btn-warning">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <a href="reunioes.php?delete=<?php echo $reuniao['id']; ?>" class="btn btn-sm btn-danger" 
                                                       onclick="return confirm('Tem certeza que deseja excluir esta reunião?')">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php echo $paginationReunioes->render($currentUrl . "#reunioes"); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.print.min.js"></script>

    <script>
        // Dados dos gráficos
        const progressData = {
            etapas: <?php echo $progressData['etapas']['percentual']; ?>,
            tarefas: <?php echo $progressData['tarefas']['percentual']; ?>
        };

        const statusData = <?php echo json_encode($statusData); ?>;

        // Inicialização do DataTables
        $(document).ready(function() {
            // Configuração comum para todas as tabelas
            const tableConfig = {
                language: {
                    sEmptyTable: "Nenhum registro encontrado",
                    sInfo: "Mostrando de _START_ até _END_ de _TOTAL_ registros",
                    sInfoEmpty: "Mostrando 0 até 0 de 0 registros",
                    sInfoFiltered: "(Filtrados de _MAX_ registros)",
                    sLengthMenu: "Mostrar _MENU_ registros",
                    sLoadingRecords: "Carregando...",
                    sProcessing: "Processando...",
                    sSearch: "Pesquisar",
                    sZeroRecords: "Nenhum registro encontrado",
                    oPaginate: {
                        sFirst: "Primeiro",
                        sLast: "Último",
                        sNext: "Próximo",
                        sPrevious: "Anterior"
                    },
                    oAria: {
                        sSortAscending: ": Ordenar colunas de forma ascendente",
                        sSortDescending: ": Ordenar colunas de forma descendente"
                    }
                },
                dom: 'Bfrtip',
                buttons: [
                    'copy', 'excel', 'pdf', 'print'
                ],
                order: [[0, 'asc']],
                pageLength: 10,
                responsive: true
            };

            // Inicialização das tabelas
            $('#cronogramaTable').DataTable(tableConfig);
            $('#tarefasTable').DataTable(tableConfig);
            $('#stakeholdersTable').DataTable(tableConfig);
            $('#reunioesTable').DataTable(tableConfig);

            // Gráfico de Progresso
            const progressCtx = document.getElementById('progressChart');
            if (progressCtx) {
                new Chart(progressCtx, {
                    type: 'bar',
                    data: {
                        labels: ['Etapas', 'Tarefas'],
                        datasets: [{
                            label: 'Progresso (%)',
                            data: [progressData.etapas, progressData.tarefas],
                            backgroundColor: [
                                'rgba(54, 162, 235, 0.5)',
                                'rgba(75, 192, 192, 0.5)',
                                
                            ],
                            borderColor: [
                                'rgba(54, 162, 235, 1)',
                                'rgba(75, 192, 192, 1)'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 100
                            }
                        }
                    }
                });
            }

            // Gráfico de Distribuição de Status
            const statusCtx = document.getElementById('statusChart');
            if (statusCtx) {
                new Chart(statusCtx, {
                    type: 'doughnut',
                    data: {
                        labels: Object.keys(statusData),
                        datasets: [{
                            data: Object.values(statusData),
                            backgroundColor: [
                                'rgba(75, 192, 192, 0.5)',
                                'rgba(54, 162, 235, 0.5)',
                                'rgba(255, 99, 132, 0.5)',
                                'rgba(255, 206, 86, 0.5)',
                                'rgba(153, 102, 255, 0.5)',
                                'rgba(255, 159, 64, 0.5)',
                                'rgba(199, 199, 199, 0.5)'
                            ],
                            borderColor: [
                                'rgba(75, 192, 192, 1)',
                                'rgba(54, 162, 235, 1)',
                                'rgba(255, 99, 132, 1)',
                                'rgba(255, 206, 86, 1)',
                                'rgba(153, 102, 255, 1)',
                                'rgba(255, 159, 64, 1)',
                                'rgba(199, 199, 199, 1)'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false
                    }
                });
            }
        });
    </script>
</body>
</html> 