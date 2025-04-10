<?php
require_once 'config/database.php';

// Senha que queremos usar
$senha = 'Smart@2025';
$hash = password_hash($senha, PASSWORD_DEFAULT);

try {
    // Verificar se o usuário admin existe
    $sql = "SELECT * FROM usuarios WHERE email = 'admin@admin.com'";
    $stmt = $pdo->query($sql);
    $usuario = $stmt->fetch();

    if ($usuario) {
        // Atualizar a senha
        $sql = "UPDATE usuarios SET senha = ? WHERE email = 'admin@admin.com'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$hash]);
        echo "Senha do administrador atualizada com sucesso!<br>";
        echo "Nova senha: Smart@2025<br>";
        echo "Hash gerado: " . $hash;
    } else {
        // Criar o usuário admin se não existir
        $sql = "INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['Administrador', 'admin@admin.com', $hash]);
        echo "Usuário administrador criado com sucesso!<br>";
        echo "Senha: Smart@2025<br>";
        echo "Hash gerado: " . $hash;
    }
} catch(PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
?> 