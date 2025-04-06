<?php
session_start();
include('../includes/db_connect.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../public/index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$userStmt = $conn->prepare("SELECT admin FROM users WHERE id = :user_id");
$userStmt->execute([':user_id' => $user_id]);
$userInfo = $userStmt->fetch(PDO::FETCH_ASSOC);

if (!$userInfo || !$userInfo['admin']) {
    header("Location: ../public/index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'], $_POST['action'])) {
    $requestId = intval($_POST['request_id']);
    $action = $_POST['action'];

    if ($action === 'approve') {
        // Approve the request and delete the object
        $deleteStmt = $conn->prepare("
            DELETE FROM ObjetConnecte 
            WHERE ID = (SELECT object_id FROM DeleteRequests WHERE id = :request_id)
        ");
        $deleteStmt->execute([':request_id' => $requestId]);

        // Remove the request
        $removeRequestStmt = $conn->prepare("DELETE FROM DeleteRequests WHERE id = :request_id");
        $removeRequestStmt->execute([':request_id' => $requestId]);

        $_SESSION['success_message'] = "Objet supprimé avec succès.";
    } elseif ($action === 'reject') {
        // Reject the request
        $removeRequestStmt = $conn->prepare("DELETE FROM DeleteRequests WHERE id = :request_id");
        $removeRequestStmt->execute([':request_id' => $requestId]);

        $_SESSION['success_message'] = "Demande rejetée.";
    }
}

header("Location: admin.php");
exit();
