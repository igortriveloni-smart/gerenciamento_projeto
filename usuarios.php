<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

// Verificar se o usuário está logado e tem permissão
verificarLogin();
if (!verificarPermissao('gerenciar_usuarios')) {
    header('Location: index.php');
    exit;
}

$mensagem = '';

// Processar ações
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'add') {
        $nome = $_POST['nome'];
        $email = $_POST['email'];
        $senha = $_POST['senha'];
        $permissoes = $_POST['permissoes'] ?? [];

        try {
            // Verificar se o email já existe
            $sql = "SELECT COUNT(*) FROM usuarios WHERE email = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$email]);
            
            if ($stmt->fetchColumn() > 0) {
                $mensagem = '<div class="alert alert-danger">Este email já está cadastrado.</div>';
            } else {
                // Inserir novo usuário
                $hash = password_hash($senha, PASSWORD_DEFAULT);
                $sql = "INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$nome, $email, $hash]);
                
                $usuario_id = $pdo->lastInsertId();
                
                // Inserir permissões
                if (!empty($permissoes)) {
                    $sql = "INSERT INTO usuario_permissoes (usuario_id, permissao_id) VALUES (?, ?)";
                    $stmt = $pdo->prepare($sql);
                    foreach ($permissoes as $permissao_id) {
                        $stmt->execute([$usuario_id, $permissao_id]);
                    }
                }
                
                $mensagem = '<div class="alert alert-success">Usuário cadastrado com sucesso!</div>';
            }
        } catch(PDOException $e) {
            $mensagem = '<div class="alert alert-danger">Erro ao cadastrar usuário: ' . $e->getMessage() . '</div>';
        }
    } elseif ($_POST['action'] == 'edit') {
        $id = $_POST['id'];
        $nome = $_POST['nome'];
        $email = $_POST['email'];
        $senha = $_POST['senha'];
        $permissoes = $_POST['permissoes'] ?? [];

        try {
            // Verificar se o email já existe em outro usuário
            $sql = "SELECT COUNT(*) FROM usuarios WHERE email = ? AND id != ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$email, $id]);
            
            if ($stmt->fetchColumn() > 0) {
                $mensagem = '<div class="alert alert-danger">Este email já está cadastrado para outro usuário.</div>';
            } else {
                // Atualizar usuário
                if (!empty($senha)) {
                    $hash = password_hash($senha, PASSWORD_DEFAULT);
                    $sql = "UPDATE usuarios SET nome = ?, email = ?, senha = ? WHERE id = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$nome, $email, $hash, $id]);
                } else {
                    $sql = "UPDATE usuarios SET nome = ?, email = ? WHERE id = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$nome, $email, $id]);
                }
                
                // Remover permissões antigas
                $sql = "DELETE FROM usuario_permissoes WHERE usuario_id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$id]);
                
                // Inserir novas permissões
                if (!empty($permissoes)) {
                    $sql = "INSERT INTO usuario_permissoes (usuario_id, permissao_id) VALUES (?, ?)";
                    $stmt = $pdo->prepare($sql);
                    foreach ($permissoes as $permissao_id) {
                        $stmt->execute([$id, $permissao_id]);
                    }
                }
                
                $mensagem = '<div class="alert alert-success">Usuário atualizado com sucesso!</div>';
            }
        } catch(PDOException $e) {
            $mensagem = '<div class="alert alert-danger">Erro ao atualizar usuário: ' . $e->getMessage() . '</div>';
        }
    }
}

// Processar exclusão
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    try {
        // Não permitir excluir o próprio usuário
        if ($id == $_SESSION['usuario_id']) {
            $mensagem = '<div class="alert alert-danger">Você não pode excluir seu próprio usuário.</div>';
        } else {
            // Excluir permissões do usuário
            $sql = "DELETE FROM usuario_permissoes WHERE usuario_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id]);
            
            // Excluir usuário
            $sql = "DELETE FROM usuarios WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id]);
            
            $mensagem = '<div class="alert alert-success">Usuário excluído com sucesso!</div>';
        }
    } catch(PDOException $e) {
        $mensagem = '<div class="alert alert-danger">Erro ao excluir usuário: ' . $e->getMessage() . '</div>';
    }
}

// Buscar lista de permissões
$sql = "SELECT * FROM permissoes ORDER BY nome";
$stmt = $pdo->query($sql);
$permissoes = $stmt->fetchAll();

// Buscar lista de usuários
$sql = "SELECT u.*, GROUP_CONCAT(p.nome) as permissoes 
        FROM usuarios u 
        LEFT JOIN usuario_permissoes up ON u.id = up.usuario_id 
        LEFT JOIN permissoes p ON up.permissao_id = p.id 
        GROUP BY u.id 
        ORDER BY u.nome";
$stmt = $pdo->query($sql);
$usuarios = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Usuários - Gerenciador de Projetos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/nav.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Gerenciar Usuários</h2>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#novoUsuarioModal">
                <i class="bi bi-person-plus"></i> Novo Usuário
            </button>
        </div>

        <?php echo $mensagem; ?>

        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Email</th>
                        <th>Permissões</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $usuario): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($usuario['nome']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['permissoes'] ?? 'Nenhuma'); ?></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-warning" 
                                        onclick="editarUsuario(<?php echo $usuario['id']; ?>, '<?php echo htmlspecialchars($usuario['nome']); ?>', '<?php echo htmlspecialchars($usuario['email']); ?>', '<?php echo htmlspecialchars($usuario['permissoes'] ?? ''); ?>')">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <?php if ($usuario['id'] != $_SESSION['usuario_id']): ?>
                                <a href="usuarios.php?delete=<?php echo $usuario['id']; ?>" 
                                   class="btn btn-sm btn-danger" 
                                   onclick="return confirm('Tem certeza que deseja excluir este usuário?')">
                                    <i class="bi bi-trash"></i>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Novo Usuário -->
    <div class="modal fade" id="novoUsuarioModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Novo Usuário</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="novoUsuarioForm">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="mb-3">
                            <label for="nome" class="form-label">Nome</label>
                            <input type="text" class="form-control" id="nome" name="nome" required>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>

                        <div class="mb-3">
                            <label for="senha" class="form-label">Senha</label>
                            <input type="password" class="form-control" id="senha" name="senha" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Permissões</label>
                            <?php foreach ($permissoes as $permissao): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="permissoes[]" 
                                           value="<?php echo $permissao['id']; ?>" id="perm_<?php echo $permissao['id']; ?>">
                                    <label class="form-check-label" for="perm_<?php echo $permissao['id']; ?>">
                                        <?php echo htmlspecialchars($permissao['nome']); ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Salvar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Editar Usuário -->
    <div class="modal fade" id="editarUsuarioModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Usuário</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="editarUsuarioForm">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="edit_id">
                        
                        <div class="mb-3">
                            <label for="edit_nome" class="form-label">Nome</label>
                            <input type="text" class="form-control" id="edit_nome" name="nome" required>
                        </div>

                        <div class="mb-3">
                            <label for="edit_email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="edit_email" name="email" required>
                        </div>

                        <div class="mb-3">
                            <label for="edit_senha" class="form-label">Senha</label>
                            <input type="password" class="form-control" id="edit_senha" name="senha">
                            <small class="text-muted">Deixe em branco para manter a senha atual</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Permissões</label>
                            <?php foreach ($permissoes as $permissao): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="permissoes[]" 
                                           value="<?php echo $permissao['id']; ?>" id="edit_perm_<?php echo $permissao['id']; ?>">
                                    <label class="form-check-label" for="edit_perm_<?php echo $permissao['id']; ?>">
                                        <?php echo htmlspecialchars($permissao['nome']); ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Salvar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editarUsuario(id, nome, email, permissoes) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nome').value = nome;
            document.getElementById('edit_email').value = email;
            
            // Limpar checkboxes
            document.querySelectorAll('#editarUsuarioModal input[type="checkbox"]').forEach(checkbox => {
                checkbox.checked = false;
            });
            
            // Marcar permissões atuais
            if (permissoes) {
                permissoes.split(',').forEach(permissao => {
                    document.querySelectorAll('#editarUsuarioModal input[type="checkbox"]').forEach(checkbox => {
                        if (checkbox.nextElementSibling.textContent.trim() === permissao.trim()) {
                            checkbox.checked = true;
                        }
                    });
                });
            }
            
            new bootstrap.Modal(document.getElementById('editarUsuarioModal')).show();
        }
    </script>
</body>
</html> 