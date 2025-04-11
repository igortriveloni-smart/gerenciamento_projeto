<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

// Verificar se o usuário está logado
verificarLogin();

// Verificar permissões
$podeVisualizar = verificarPermissao('visualizar_stakeholders');
$podeCriar = verificarPermissao('criar_stakeholders');
$podeEditar = verificarPermissao('editar_stakeholders');
$podeExcluir = verificarPermissao('excluir_stakeholders');

if (!$podeVisualizar) {
    header('Location: index.php');
    exit;
}

$mensagem = '';

// Processar formulário de adição de stakeholder
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'add' && $podeCriar) {
        $projeto_id = $_POST['projeto_id'];
        $nome = $_POST['nome'];
        $funcao_area = $_POST['funcao_area'];
        $telefone = $_POST['telefone'];
        $email = $_POST['email'];
        $observacao = $_POST['observacao'];

        try{
            $sql = "INSERT INTO stakeholders (projeto_id, nome, funcao_area, telefone, email, observacao) 
                VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$projeto_id, $nome, $funcao_area, $telefone, $email, $observacao]);

            $mensagem = '<div class="alert alert-success">
            Stakeholders adicionado com sucesso!</div>';
        
            // Adicionar script para remover a mensagem após 3 segundos
            echo '<script>
                setTimeout(function() {
                    document.querySelector(".alert").remove();
                }, 3000);
            </script>';
        }catch (PDOException $e) {
            $mensagem = '<div class="alert alert-danger">
            Erro ao adicionar stakeholder: ' . $e->getMessage() . '</div>';
        }        
    }
}

// Processar exclusão de stakeholder
if (isset($_GET['delete']) && $podeExcluir) {
    $id = $_GET['delete'];
    try {
        $sql = "DELETE FROM stakeholders WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);

        $mensagem = '<div class="alert alert-success">
            Stakeholder excluído com sucesso!</div>';

        // Adicionar script para remover a mensagem após 3 segundos
        echo '<script>
            setTimeout(function() {
                document.querySelector(".alert").remove();
            }, 3000);
        </script>';
    } catch (PDOException $e) {
        $mensagem = '<div class="alert alert-danger">
            Erro ao excluir stakeholder: ' . $e->getMessage() . '</div>';
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
    <title>Gerenciador de Projetos - Stakeholders</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/nav.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Stakeholders</h2>
            <?php if ($podeCriar): ?>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#novoStakeholderModal">
                <i class="bi bi-plus-circle"></i> Novo Stakeholder
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
                        <div class="col-md-6">
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

                        <!-- Filtro por Nome -->
                        <div class="col-md-6">
                            <label for="filtro_nome" class="form-label">Filtrar por Nome</label>
                            <select class="form-select" id="filtro_nome" name="nome">
                                <option value="">Todos os Stakeholders</option>
                                <?php
                                // Buscar nomes únicos de stakeholders
                                $stakeholders = $pdo->query("SELECT DISTINCT nome FROM stakeholders ORDER BY nome")->fetchAll();
                                foreach ($stakeholders as $stakeholder):
                                ?>
                                    <option value="<?php echo htmlspecialchars($stakeholder['nome']); ?>" 
                                        <?php echo (isset($_GET['nome']) && $_GET['nome'] == $stakeholder['nome']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($stakeholder['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
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
                            <a href="stakeholders.php" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Limpar Filtros
                            </a>
                        </div>
                    </div>
                </form>
            </div>
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
                                <?php if ($podeEditar || $podeExcluir): ?>
                                <th>Ações</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT s.*, p.nome as projeto_nome 
                                    FROM stakeholders s 
                                    JOIN projetos p ON s.projeto_id = p.id";

                            $conditions = [];
                            $params = [];

                            if (isset($_GET['projeto_id']) && !empty($_GET['projeto_id'])) {
                                $conditions[] = "s.projeto_id = :projeto_id";
                                $params[':projeto_id'] = $_GET['projeto_id'];
                            }

                            if (isset($_GET['nome']) && !empty($_GET['nome'])) {
                                $conditions[] = "s.nome = :nome";
                                $params[':nome'] = $_GET['nome'];
                            }

                            if (!empty($conditions)) {
                                $sql .= " WHERE " . implode(" AND ", $conditions);
                            }

                            $sql .= " ORDER BY s.nome ASC";
                            $stmt = $pdo->prepare($sql);

                            foreach ($params as $key => $value) {
                                $stmt->bindValue($key, $value);
                            }

                            $stmt->execute();

                            while ($row = $stmt->fetch()) {
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['nome']); ?></td>
                                    <td><?php echo htmlspecialchars($row['projeto_nome']); ?></td>
                                    <td><?php echo htmlspecialchars($row['funcao_area']); ?></td>
                                    <td><?php echo htmlspecialchars($row['telefone']); ?></td>
                                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                                    <td><?php echo htmlspecialchars($row['observacao']); ?></td>    
                                    <?php if ($podeEditar || $podeExcluir): ?>       
                                    <td>
                                        <div class="btn-group">
                                            <?php if ($podeEditar): ?>    
                                            <a href="editar_stakeholder.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <?php endif; ?>
                                            <?php if ($podeExcluir): ?>
                                            <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Tem certeza que deseja excluir este stakeholder?')">
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
                            <input type="tel" class="form-control" id="telefone" name="telefone" required>
                        </div>  

                        <div class="mb-3">
                            <label for="email" class="form-label">E-mail</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>

                        <div class="mb-3">
                            <label for="observacao" class="form-label">Observações</label>
                            <textarea class="form-control" id="observacao" name="observacao" rows="3"></textarea>                            
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
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const telefoneInput = document.getElementById('telefone');
            const emailInput = document.getElementById('email');
            const form = document.getElementById('novoStakeholderForm');

            // Formatar telefone no padrão (DDD) 12345-6789
            telefoneInput.addEventListener('input', function () {
                let telefone = telefoneInput.value.replace(/\D/g, ''); // Remove caracteres não numéricos
                if (telefone.length > 11) telefone = telefone.slice(0, 11); // Limita a 11 dígitos
                if (telefone.length === 11) {
                    telefone = `(${telefone.slice(0, 2)}) ${telefone.slice(2, 7)}-${telefone.slice(7)}`;
                }
                telefoneInput.value = telefone;
            });

            // Validar e padronizar o e-mail
            emailInput.addEventListener('blur', function () {
                emailInput.value = emailInput.value.trim().toLowerCase(); // Remove espaços e converte para minúsculas
            });

            // Validar o formulário antes de enviar
            form.addEventListener('submit', function (event) {
                const telefone = telefoneInput.value.replace(/\D/g, '');
                if (telefone.length !== 11) {
                    alert('O telefone deve conter 11 dígitos (incluindo DDD).');
                    event.preventDefault();
                    return;
                }

                const email = emailInput.value;
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(email)) {
                    alert('Por favor, insira um e-mail válido.');
                    event.preventDefault();
                    return;
                }
            });
        });
    </script>
</body>
</html>