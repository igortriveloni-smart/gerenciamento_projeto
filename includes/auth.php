<?php
session_start();

function verificarLogin() {
    if (!isset($_SESSION['usuario_id'])) {
        header('Location: login.php');
        exit;
    }
}

function verificarPermissao($permissao) {
    if (!isset($_SESSION['usuario_id'])) {
        return false;
    }

    global $pdo;
    $sql = "SELECT COUNT(*) FROM usuario_permissoes up
            JOIN permissoes p ON up.permissao_id = p.id
            WHERE up.usuario_id = ? AND p.nome = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$_SESSION['usuario_id'], $permissao]);
    return $stmt->fetchColumn() > 0;
}

function login($email, $senha) {
    global $pdo;
    $sql = "SELECT id, nome, senha FROM usuarios WHERE email = ? AND status = 'ativo'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();

    if ($usuario && password_verify($senha, $usuario['senha'])) {
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_nome'] = $usuario['nome'];
        return true;
    }
    return false;
}

function logout() {
    session_destroy();
    header('Location: login.php');
    exit;
}
?> 