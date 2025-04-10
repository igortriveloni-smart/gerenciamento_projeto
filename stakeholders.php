<?php
require_once 'config/database.php';

// Processar formulário de adição de stakeholder
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'add') {
        $projeto_id = $_POST['projeto_id'];
        $nome = $_POST['nome'];
        $funcao_area = $_POST['funcao_area'];
        $telefone = $_POST['telefone'];
        $email = $_POST['email'];
        $observacao = $_POST['observacao'];

        $sql = "INSERT INTO stakeholders (projeto_id, nome, funcao_area, telefone, email, observacao) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$projeto_id, $nome, $funcao_area, $telefone, $email, $observacao]);
        
        header('Location: stakeholders.php');
        exit;
    }
}

// Processar exclusão de stakeholder
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $sql = "DELETE FROM stakeholders WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    header('Location: stakeholders.php');
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
    <title>Gerenciador de Projetos - Stakeholders</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/nav.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Stakeholders</h2>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#novoStakeholderModal">
                <i class="bi bi-plus-circle"></i> Novo Stakeholder
            </button>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Projeto</th>
                                <th>Função/Área</th>
                                <th>Telefone</th>
                                <th>E-mail</th>
                                <th>Observações</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT s.*, p.nome as projeto_nome 
                                   FROM stakeholders s 
                                   JOIN projetos p ON s.projeto_id = p.id 
                                   ORDER BY s.nome";
                            $stmt = $pdo->query($sql);
                            while ($row = $stmt->fetch()) {
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['nome']); ?></td>
                                    <td><?php echo htmlspecialchars($row['projeto_nome']); ?></td>
                                    <td><?php echo htmlspecialchars($row['funcao_area']); ?></td>
                                    <td><?php echo htmlspecialchars($row['telefone']); ?></td>
                                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                                    <td><?php echo htmlspecialchars($row['observacao']); ?></td>           
                                    <td>
                                        <div class="btn-group">
                                            <a href="editar_stakeholder.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Tem certeza que deseja excluir este stakeholder?')">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    </td>
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

    <!-- Modal Novo Stakeholder -->
    <div class="modal fade" id="novoStakeholderModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Novo Stakeholder</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="novoStakeholderForm">
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
                            <label for="nome" class="form-label">Nome</label>
                            <input type="text" class="form-control" id="nome" name="nome" required>
                        </div>

                        <div class="mb-3">
                            <label for="funcao_area" class="form-label">Função/Área</label> 
                            <input type="text" class="form-control" id="funcao_area" name="funcao_area" required>
                        </div>

                        <div class="mb-3">
                            <label for="telefone" class="form-label">Telefone</label>
                            <input type="text" class="form-control" id="telefone" name="telefone" required>
                        </div>  

                        <div class="mb-3">
                            <label for="email" class="form-label">E-mail</label>
                            <input type="text" class="form-control" id="email" name="email" required>
                        </div>

                        <div class="mb-3">
                            <label for="observacoes" class="form-label">Observações</label>
                            <textarea class="form-control" id="observacoes" name="observacoes" rows="3"></textarea>                            
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Adicionar Stakeholder</button>
                        </div>
                    </form>
                </div>                
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 