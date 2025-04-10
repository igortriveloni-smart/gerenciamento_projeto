<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

// Verificar se o usuário está logado
verificarLogin();

// Verificar permissões
$podeVisualizar = verificarPermissao('visualizar_projetos');
$podeCriar = verificarPermissao('criar_projetos');
$podeEditar = verificarPermissao('editar_projetos');
$podeExcluir = verificarPermissao('excluir_projetos');

if (!$podeVisualizar) {
    header('Location: login.php');
    exit;
}

// Processar formulário de adição de projeto
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'add' && $podeCriar) {
        $nome = $_POST['nome'];
        $objetivo = $_POST['objetivo'];
        $escopo = $_POST['escopo'];
        $gestor = $_POST['gestor'];
        $ponto_focal = $_POST['ponto_focal'];
        $data_inicio = $_POST['data_inicio'];
        $data_conclusao = $_POST['data_conclusao'];
        $status = $_POST['status'];

        // Processar upload do logo
        $logo_path = null;
        if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] == 0) {
            $upload_dir = 'uploads/logos/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_extension = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));
            $file_name = uniqid() . '.' . $file_extension;
            $target_file = $upload_dir . $file_name;

            if (move_uploaded_file($_FILES['imagem']['tmp_name'], $target_file)) {
                $logo_path = $target_file;
            }
        }

        $sql = "INSERT INTO projetos (nome, imagem, objetivo, escopo, gestor, ponto_focal, 
                data_inicio, data_conclusao, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nome, $logo_path, $objetivo, $escopo, $gestor, $ponto_focal, 
                       $data_inicio, $data_conclusao, $status]);
        
        header('Location: index.php');
        exit;
    }
}

// Processar exclusão de projeto
if (isset($_GET['delete']) && $podeExcluir) {
    $id = $_GET['delete'];
    
    // Buscar o logo do projeto antes de excluir
    $sql = "SELECT imagem FROM projetos WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $projeto = $stmt->fetch();
    
    // Excluir o arquivo do logo se existir
    if ($projeto && $projeto['imagem'] && file_exists($projeto['imagem'])) {
        unlink($projeto['imagem']);
    }
    
    // Excluir o projeto
    $sql = "DELETE FROM projetos WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciador de Projetos - Visão Geral</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/nav.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Visão Geral dos Projetos</h2>
            <?php if ($podeCriar): ?>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#novoProjetoModal">
                <i class="bi bi-plus-circle"></i> Novo Projeto
            </button>
            <?php endif; ?>
        </div>

        <div class="row">
            <?php
            $sql = "SELECT * FROM projetos ORDER BY data_inicio DESC";
            $stmt = $pdo->query($sql);
            while ($projeto = $stmt->fetch()) {
                $statusClass = '';
                switch($projeto['status']) {
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
                }
                ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <?php if ($projeto['imagem']): ?>
                                    <img src="<?php echo htmlspecialchars($projeto['imagem']); ?>" alt="imagem" class="me-2" style="width: 40px; height: 40px; object-fit: contain;">
                                <?php else: ?>
                                    <i class="bi bi-building me-2" style="font-size: 2rem;"></i>
                                <?php endif; ?>
                                <h5 class="card-title mb-0"><?php echo htmlspecialchars($projeto['nome']); ?></h5>
                            </div>
                            
                            <p class="card-text">
                                <strong>Objetivo:</strong><br>
                                <?php echo htmlspecialchars($projeto['objetivo']); ?>
                            </p>
                            
                            <p class="card-text">
                                <strong>Escopo:</strong><br>
                                <?php echo htmlspecialchars($projeto['escopo']); ?>
                            </p>
                            
                            <div class="row mb-3">
                                <div class="col-6">
                                    <small class="text-muted">
                                        <i class="bi bi-person"></i> <?php echo htmlspecialchars($projeto['gestor']); ?>
                                    </small>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">
                                        <i class="bi bi-person-badge"></i> <?php echo htmlspecialchars($projeto['ponto_focal']); ?>
                                    </small>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-6">
                                    <small class="text-muted">
                                        <i class="bi bi-calendar3"></i> <?php echo date('d/m/Y', strtotime($projeto['data_inicio'])); ?>
                                    </small>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">
                                        <i class="bi bi-calendar-check"></i> <?php echo date('d/m/Y', strtotime($projeto['data_conclusao'])); ?>
                                    </small>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars($projeto['status']); ?></span>
                                <div class="btn-group">
                                    <a href="detalhes_projeto.php?id=<?php echo $projeto['id']; ?>" class="btn btn-sm btn-info">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <?php if ($podeEditar): ?>
                                    <a href="editar_projeto.php?id=<?php echo $projeto['id']; ?>" class="btn btn-sm btn-warning">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <?php endif; ?>
                                    <?php if ($podeExcluir): ?>
                                    <a href="index.php?delete=<?php echo $projeto['id']; ?>" class="btn btn-sm btn-danger" 
                                       onclick="return confirm('Tem certeza que deseja excluir este projeto?')">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php
            }
            ?>
        </div>
    </div>

    <?php if ($podeCriar): ?>
    <!-- Modal Novo Projeto -->
    <div class="modal fade" id="novoProjetoModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Novo Projeto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="novoProjetoForm" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="mb-3">
                            <label for="nome" class="form-label">Nome do Projeto</label>
                            <input type="text" class="form-control" id="nome" name="nome" required>
                        </div>

                        <div class="mb-3">
                            <label for="logo" class="form-label">Logo do Projeto</label>
                            <input type="file" class="form-control" id="logo" name="logo" accept="image/*">
                            <small class="text-muted">Formatos aceitos: JPG, PNG, GIF</small>
                        </div>

                        <div class="mb-3">
                            <label for="objetivo" class="form-label">Objetivo</label>
                            <textarea class="form-control" id="objetivo" name="objetivo" rows="3" required></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="escopo" class="form-label">Escopo</label>
                            <textarea class="form-control" id="escopo" name="escopo" rows="3" required></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="gestor" class="form-label">Gestor do Projeto</label>
                            <input type="text" class="form-control" id="gestor" name="gestor" required>
                        </div>

                        <div class="mb-3">
                            <label for="ponto_focal" class="form-label">Ponto Focal do Cliente</label>
                            <input type="text" class="form-control" id="ponto_focal" name="ponto_focal" required>
                        </div>

                        <div class="mb-3">
                            <label for="data_inicio" class="form-label">Data de Início</label>
                            <input type="date" class="form-control" id="data_inicio" name="data_inicio" required>
                        </div>

                        <div class="mb-3">
                            <label for="data_conclusao" class="form-label">Data de Conclusão</label>
                            <input type="date" class="form-control" id="data_conclusao" name="data_conclusao" required>
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

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Adicionar Projeto</button>
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