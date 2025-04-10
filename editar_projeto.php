<?php
require_once 'config/database.php';

// Verificar se foi fornecido um ID
if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$id = $_GET['id'];

// Buscar dados do projeto
$sql = "SELECT * FROM projetos WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$projeto = $stmt->fetch();

if (!$projeto) {
    header('Location: index.php');
    exit;
}

// Processar formulário de edição
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = $_POST['nome'];
    $objetivo = $_POST['objetivo'];
    $escopo = $_POST['escopo'];
    $gestor = $_POST['gestor'];
    $ponto_focal = $_POST['ponto_focal'];
    $data_inicio = $_POST['data_inicio'];
    $data_conclusao = $_POST['data_conclusao'];
    $status = $_POST['status'];

    // Processar upload do novo logo
    $logo_path = $projeto['imagem']; // Manter o logo atual por padrão
    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] == 0) {
        $upload_dir = 'uploads/logos/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // Excluir o logo antigo se existir
        if ($projeto['imagem'] && file_exists($projeto['imagem'])) {
            unlink($projeto['imagem']);
        }

        $file_extension = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));
        $file_name = uniqid() . '.' . $file_extension;
        $target_file = $upload_dir . $file_name;

        if (move_uploaded_file($_FILES['imagem']['tmp_name'], $target_file)) {
            $logo_path = $target_file;
        }
    }

    $sql = "UPDATE projetos SET nome = ?, imagem = ?, objetivo = ?, escopo = ?, 
            gestor = ?, ponto_focal = ?, data_inicio = ?, data_conclusao = ?, 
            status = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$nome, $logo_path, $objetivo, $escopo, $gestor, $ponto_focal, 
                   $data_inicio, $data_conclusao, $status, $id]);
    
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciador de Projetos - Editar Projeto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/nav.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Editar Projeto</h2>
            <a href="index.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Voltar
            </a>
        </div>

        <div class="card">
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="nome" class="form-label">Nome do Projeto</label>
                                <input type="text" class="form-control" id="nome" name="nome" 
                                       value="<?php echo htmlspecialchars($projeto['nome']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="imagem" class="form-label">Logo do Projeto</label>
                                <?php if ($projeto['imagem']): ?>
                                    <div class="mb-2">
                                        <img src="<?php echo htmlspecialchars($projeto['imagem']); ?>" 
                                             alt="Logo atual" style="max-width: 100px; max-height: 100px;">
                                    </div>
                                <?php endif; ?>
                                <input type="file" class="form-control" id="imagem" name="imagem" accept="image/*">
                                <small class="text-muted">Deixe em branco para manter o logo atual</small>
                            </div>

                            <div class="mb-3">
                                <label for="objetivo" class="form-label">Objetivo</label>
                                <textarea class="form-control" id="objetivo" name="objetivo" rows="3" required><?php echo htmlspecialchars($projeto['objetivo']); ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="escopo" class="form-label">Escopo</label>
                                <textarea class="form-control" id="escopo" name="escopo" rows="3" required><?php echo htmlspecialchars($projeto['escopo']); ?></textarea>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="gestor" class="form-label">Gestor do Projeto</label>
                                <input type="text" class="form-control" id="gestor" name="gestor" 
                                       value="<?php echo htmlspecialchars($projeto['gestor']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="ponto_focal" class="form-label">Ponto Focal do Cliente</label>
                                <input type="text" class="form-control" id="ponto_focal" name="ponto_focal" 
                                       value="<?php echo htmlspecialchars($projeto['ponto_focal']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="data_inicio" class="form-label">Data de Início</label>
                                <input type="date" class="form-control" id="data_inicio" name="data_inicio" 
                                       value="<?php echo $projeto['data_inicio']; ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="data_conclusao" class="form-label">Data de Conclusão</label>
                                <input type="date" class="form-control" id="data_conclusao" name="data_conclusao" 
                                       value="<?php echo $projeto['data_conclusao']; ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="Não Iniciado" <?php echo $projeto['status'] == 'Não Iniciado' ? 'selected' : ''; ?>>Não Iniciado</option>
                                    <option value="Em Andamento" <?php echo $projeto['status'] == 'Em Andamento' ? 'selected' : ''; ?>>Em Andamento</option>
                                    <option value="Concluído" <?php echo $projeto['status'] == 'Concluído' ? 'selected' : ''; ?>>Concluído</option>
                                    <option value="Atrasado" <?php echo $projeto['status'] == 'Atrasado' ? 'selected' : ''; ?>>Atrasado</option>
                                    <option value="Cancelado" <?php echo $projeto['status'] == 'Cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Salvar Alterações
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 