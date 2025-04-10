<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

// Verificar se o usuário está logado e tem permissão
verificarLogin();
if (!verificarPermissao('editar_stakeholders')) {
    header('Location: stakeholders.php');
    exit;
}

// Verificar se o ID foi fornecido
if (!isset($_GET['id'])) {   
    exit;
}

$id = $_GET['id'];

$mensagem = "";

// Processar formulário de edição
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $projeto_id = $_POST['projeto_id'];
    $nome = $_POST['nome'];
    $funcao_area = $_POST['funcao_area'];
    $telefone = $_POST['telefone'];
    $email = $_POST['email'];
    $observacao = $_POST['observacao'];

    try {
        $sql = "UPDATE stakeholders SET 
        projeto_id = ?,
        nome = ?, 
        funcao_area = ?, 
        telefone = ?, 
        email = ?, 
        observacao = ? 
        WHERE id = ?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$projeto_id, $nome, $funcao_area, $telefone, $email, $observacao, $id]);

        $mensagem = '<div class="alert alert-success">Stakeholder atualizado com sucesso! Redirecionando em 3 segundos...</div>';
        echo '<script>
                setTimeout(function() {
                    window.location.href = "stakeholders.php";
                }, 3000);
            </script>';            
    } catch (PDOException $e){
        $mensagem = '<div class="alert alert-danger">Erro ao atualizar stakeholder: ' . $e->getMessage() . '</div>';
    }
}

// Buscar dados do stakeholder
$sql = "SELECT * FROM stakeholders WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$stakeholder = $stmt->fetch();

if (!$stakeholder) {
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
    <title>Gerenciador de Projetos - Editar Stakeholder</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/nav.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Editar Stakeholder</h2>
            <a href="stakeholders.php" class="btn btn-secondary">
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
                                <option value="<?php echo $projeto['id']; ?>" <?php echo $stakeholder['projeto_id'] == $projeto['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($projeto['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="nome" class="form-label">Nome do Stakeholder</label>
                        <input type="text" class="form-control" id="nome" name="nome" value="<?php echo htmlspecialchars($stakeholder['nome']); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="funcao_area" class="form-label">Função/Área</label>
                        <input type="text" class="form-control" id="funcao_area" name="funcao_area" value="<?php echo htmlspecialchars($stakeholder['funcao_area']); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="telefone" class="form-label">Telefone</label>
                        <input type="tel" class="form-control" id="telefone" name="telefone" value="<?php echo htmlspecialchars($stakeholder['telefone']); ?>">
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">E-mail</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($stakeholder['email']); ?>">
                    </div>

                    <div class="mb-3">
                        <label for="observacao" class="form-label">Observação</label>
                        <textarea class="form-control" id="observacao" name="observacao" rows="3"><?php echo htmlspecialchars($stakeholder['observacao']); ?></textarea>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="stakeholders.php" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>            
    </div>    

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>       
        document.addEventListener('DOMContentLoaded', function () {
            const telefoneInput = document.getElementById('telefone');
            const emailInput = document.getElementById('email');
            const form = document.querySelector('form');

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