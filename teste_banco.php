<?php
require_once 'config/database.php';

try {
    // Testar conexão
    echo "Testando conexão com o banco de dados...<br>";
    $pdo->query("SELECT 1");
    echo "Conexão OK!<br><br>";

    // Verificar tabelas
    echo "Verificando tabelas...<br>";
    
    // Tabela usuarios
    $stmt = $pdo->query("SHOW TABLES LIKE 'usuarios'");
    if ($stmt->rowCount() > 0) {
        echo "Tabela 'usuarios' existe<br>";
        
        // Verificar registros
        $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios");
        echo "Total de usuários: " . $stmt->fetchColumn() . "<br>";
        
        // Mostrar usuários
        $stmt = $pdo->query("SELECT * FROM usuarios");
        echo "<br>Usuários cadastrados:<br>";
        while ($row = $stmt->fetch()) {
            echo "ID: " . $row['id'] . " | Nome: " . $row['nome'] . " | Email: " . $row['email'] . "<br>";
        }
    } else {
        echo "Tabela 'usuarios' não existe<br>";
    }
    
    echo "<br>";
    
    // Tabela permissoes
    $stmt = $pdo->query("SHOW TABLES LIKE 'permissoes'");
    if ($stmt->rowCount() > 0) {
        echo "Tabela 'permissoes' existe<br>";
        
        // Verificar registros
        $stmt = $pdo->query("SELECT COUNT(*) FROM permissoes");
        echo "Total de permissões: " . $stmt->fetchColumn() . "<br>";
        
        // Mostrar permissões
        $stmt = $pdo->query("SELECT * FROM permissoes");
        echo "<br>Permissões cadastradas:<br>";
        while ($row = $stmt->fetch()) {
            echo "ID: " . $row['id'] . " | Nome: " . $row['nome'] . " | Descrição: " . $row['descricao'] . "<br>";
        }
    } else {
        echo "Tabela 'permissoes' não existe<br>";
    }
    
    echo "<br>";
    
    // Tabela usuario_permissoes
    $stmt = $pdo->query("SHOW TABLES LIKE 'usuario_permissoes'");
    if ($stmt->rowCount() > 0) {
        echo "Tabela 'usuario_permissoes' existe<br>";
        
        // Verificar registros
        $stmt = $pdo->query("SELECT COUNT(*) FROM usuario_permissoes");
        echo "Total de atribuições de permissões: " . $stmt->fetchColumn() . "<br>";
    } else {
        echo "Tabela 'usuario_permissoes' não existe<br>";
    }
    
} catch(PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
?> 