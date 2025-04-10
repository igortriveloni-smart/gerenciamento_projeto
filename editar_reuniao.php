<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

// Verificar se o usuário está logado e tem permissão
verificarLogin();
if (!verificarPermissao('editar_stakeholders')) {
    header('Location: stakeholders.php');
    exit;
}

// Verificar se ID foi fornecido
if (!isset($_GET['id'])) {
    header('Location: reunioes.php');
    exit;
}

$id = $_GET['id'];

$mensagem = "";

// Processar formulário de edição
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $projeto_id = $_POST['projeto_id'];
    $data_reuniao = $_POST['data_reuniao'];
    $participantes = $_POST['participantes'];
    $principais_decisoes = $_POST['principais_decisoes'];
    $proximas_acoes = $_POST['proximas_acoes'];
    $responsavel = $_POST['responsavel'];
    $link_video = $_POST['link_video'];
    $observacoes = $_POST['observacoes'];

    try {
        $sql = "UPDATE reunioes SET 
        projeto_id = ?, 
        data_reuniao = ?, 
        participantes = ?, 
        principais_decisoes = ?, 
        proximas_acoes = ?, 
        responsavel = ?, 
        link_video = ?, 
        observacoes = ? 
        WHERE id = ?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$projeto_id, $data_reuniao, $participantes, $principais_decisoes, 
               $proximas_acoes, $responsavel, $link_video, $observacoes, $id]);
        
        $mensagem = '<div class="alert alert-success">Reunião atualizada com sucesso! Redirecionando em 3 segundos...</div>';
        echo '<script>
                setTimeout(function() {
                    window.location.href = "reunioes.php";
                }, 3000);
            </script>';
    } catch (PDOException $e) {
        $mensagem = '<div class="alert alert-danger">Erro ao atualizar reunião: ' . $e->getMessage() . '</div>';        
    }
}

// Buscar dados da reunião
$sql = "SELECT * FROM reunioes WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$reuniao = $stmt->fetch();

if (!$reuniao) {
    header('Location: reunioes.php');
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
    <title>Gerenciador de Projetos - Editar Reunião</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/nav.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Editar Reunião</h2>
            <a href="reunioes.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Voltar
            </a>
        </div>

        <?php echo $mensagem; ?>

        <div class="card">            
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label for="projeto_id" class="form-label">Projeto</label>
                        <select class="form-select" id="projeto_id" name="projeto_id" required>
                            <option value="">Selecione um projeto...</option>
                            <?php foreach ($projetos as $projeto): ?>
                                <option value="<?php echo $projeto['id']; ?>" 
                                        <?php echo $projeto['id'] == $reuniao['projeto_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($projeto['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="data_reuniao" class="form-label">Data da Reunião</label>
                        <input type="date" class="form-control" id="data_reuniao" name="data_reuniao" 
                            value="<?php echo $reuniao['data_reuniao']; ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="participantes" class="form-label">Participantes</label>
                        <textarea class="form-control" id="participantes" name="participantes" rows="3" required><?php echo htmlspecialchars($reuniao['participantes']); ?></textarea>
                        <small class="text-muted">Liste os participantes separados por vírgula</small>
                    </div>

                    <div class="mb-3">
                        <label for="principais_decisoes" class="form-label">Principais Decisões</label>
                        <textarea class="form-control" id="principais_decisoes" name="principais_decisoes" rows="3"><?php echo htmlspecialchars($reuniao['principais_decisoes']); ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="proximas_acoes" class="form-label">Próximas Ações</label>
                        <textarea class="form-control" id="proximas_acoes" name="proximas_acoes" rows="3"><?php echo htmlspecialchars($reuniao['proximas_acoes']); ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="responsavel" class="form-label">Responsável</label>
                        <input type="text" class="form-control" id="responsavel" name="responsavel" 
                            value="<?php echo htmlspecialchars($reuniao['responsavel']); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="link_video" class="form-label">Link do Vídeo</label>
                        <input type="url" class="form-control" id="link_video" name="link_video" 
                            value="<?php echo htmlspecialchars($reuniao['link_video']); ?>">
                    </div>

                    <div class="mb-3">
                        <label for="observacoes" class="form-label">Observações</label>
                        <textarea class="form-control" id="observacoes" name="observacoes" rows="3"><?php echo htmlspecialchars($reuniao['observacoes']); ?></textarea>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="reunioes.php" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>            
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 