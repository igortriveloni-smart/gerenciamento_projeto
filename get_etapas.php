<?php
require_once 'config/database.php';

if (!isset($_GET['projeto_id'])) {
    http_response_code(400);
    exit('ID do projeto não fornecido');
}

$projeto_id = $_GET['projeto_id'];

$sql = "SELECT id, etapa FROM etapas_cronograma WHERE projeto_id = ? ORDER BY etapa";
$stmt = $pdo->prepare($sql);
$stmt->execute([$projeto_id]);
$etapas = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($etapas); 