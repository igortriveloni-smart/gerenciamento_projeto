<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

// Verificar se o usuário está logado
verificarLogin();

// Verificar permissões
$podeVisualizar = verificarPermissao('visualizar_reunioes');
$podeCriar = verificarPermissao('criar_reunioes');
$podeEditar = verificarPermissao('editar_reunioes');
$podeExcluir = verificarPermissao('excluir_reunioes');

if (!$podeVisualizar) {
    header('Location: index.php');
    exit;
}

$mensagem = '';

// Processar formulário de adição de reunião
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'add' && $podeCriar) {
        $projeto_id = $_POST['projeto_id'];
        $data_reuniao = $_POST['data_reuniao'];
        $participantes = $_POST['participantes'];
        $principais_decisoes = $_POST['principais_decisoes'];
        $proximas_acoes = $_POST['proximas_acoes'];
        $responsavel = $_POST['responsavel'];
        $link_video = $_POST['link_video'];
        $observacoes = $_POST['observacoes'];

        try{
            $sql = "INSERT INTO reunioes (projeto_id, data_reuniao, participantes, principais_decisoes, 
                proximas_acoes, responsavel, link_video, observacoes) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$projeto_id, $data_reuniao, $participantes, $principais_decisoes, 
                       $proximas_acoes, $responsavel, $link_video, $observacoes]);
            
            $mensagem = '<div class="alert alert-success">Reunião adicionada com sucesso!</div>';

            // Adicionar script para remover a mensagem após 3 segundos
            echo '<script>
                setTimeout(function() {
                    document.querySelector(".alert").remove();
                }, 3000);
            </script>';
        }catch (PDOException $e) {
            $mensagem = '<div class="alert alert-danger">Erro ao adicionar reunião: ' . $e->getMessage() . '</div>';
        }
    }
}

// Processar exclusão de reunião
if (isset($_GET['delete']) && $podeExcluir) {
    $id = $_GET['delete'];
    
    try{
        $sql = "DELETE FROM reunioes WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        
        $mensagem = '<div class="alert alert-success">Reunião excluída com sucesso!</div>';

        // Adicionar script para remover a mensagem após 3 segundos
        echo '<script>
            setTimeout(function() {
                document.querySelector(".alert").remove();
            }, 3000);
        </script>';
    }catch (PDOException $e){
        $mensagem = '<div class="alert alert-danger">Erro ao excluir reunião: ' . $e->getMessage() . '</div>';
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
    <title>Gerenciador de Projetos - Reuniões</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/nav.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Reuniões</h2>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#novaReuniaoModal">
                <i class="bi bi-plus-circle"></i> Nova Reunião
            </button>
        </div>

        <?php echo $mensagem; ?> 

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Projeto</th>
                                <th>Responsável</th>
                                <th>Participantes</th>
                                <th>Decisões</th>
                                <th>Próximas Ações</th>
                                <?php if ($podeEditar || $podeExcluir): ?>
                                <th>Ações</th>
                                <?php endif; ?>                                
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT r.*, p.nome as projeto_nome 
                                   FROM reunioes r 
                                   JOIN projetos p ON r.projeto_id = p.id 
                                   ORDER BY r.data_reuniao DESC";
                            $stmt = $pdo->query($sql);
                            while ($row = $stmt->fetch()) {
                                ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($row['data_reuniao'])); ?></td>
                                    <td><?php echo htmlspecialchars($row['projeto_nome']); ?></td>
                                    <td><?php echo htmlspecialchars($row['responsavel']); ?></td>
                                    <td><?php echo htmlspecialchars($row['participantes']); ?></td>
                                    <td><?php echo htmlspecialchars($row['principais_decisoes']); ?></td>
                                    <td><?php echo htmlspecialchars($row['proximas_acoes']); ?></td>
                                    <?php if ($podeEditar || $podeExcluir): ?>
                                    <td>
                                        <div class="btn-group">
                                            <?php if ($podeEditar): ?>
                                            <a href="editar_reuniao.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <?php endif; ?>
                                            <?php if ($podeExcluir): ?>
                                            <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Tem certeza que deseja excluir esta reunião?')">
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
    <!-- Modal Nova Reunião -->
    <div class="modal fade" id="novaReuniaoModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nova Reunião</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="novaReuniaoForm">
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
                            <label for="data_reuniao" class="form-label">Data da Reunião</label>
                            <input type="date" class="form-control" id="data_reuniao" name="data_reuniao" required>
                        </div>

                        <div class="mb-3">
                            <label for="participantes" class="form-label">Participantes</label>
                            <textarea class="form-control" id="participantes" name="participantes" rows="3" required></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="principais_decisoes" class="form-label">Principais Decisões</label>
                            <textarea class="form-control" id="principais_decisoes" name="principais_decisoes" rows="3"></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="proximas_acoes" class="form-label">Próximas Ações</label>
                            <textarea class="form-control" id="proximas_acoes" name="proximas_acoes" rows="3"></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="responsavel" class="form-label">Responsável</label>
                            <input type="text" class="form-control" id="responsavel" name="responsavel" required>
                        </div>

                        <div class="mb-3">
                            <label for="link_video" class="form-label">Link do Vídeo</label>
                            <input type="url" class="form-control" id="link_video" name="link_video">
                        </div>

                        <div class="mb-3">
                            <label for="observacoes" class="form-label">Observações</label>
                            <textarea class="form-control" id="observacoes" name="observacoes" rows="3"></textarea>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Adicionar Reunião</button>
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