<?php
require_once "database.php";
function logapi($apiKey, $ipAddress) {
    include "secrets.php";

    $dbResult = getDbConnection();
    if (!$dbResult['success']) {
        echo json_encode(['success' => false, 'result' => $dbResult['error']]);
        exit();
    }
    $conn = $dbResult['connection'];

    $keyCheckQuery = $conn->prepare("SELECT id, times_used FROM api_keys WHERE api_key = ?");
    $keyCheckQuery->bind_param("s", $apiKey);
    $keyCheckQuery->execute();
    $keyCheckQuery->store_result();

    if ($keyCheckQuery->num_rows === 0) {
        $keyCheckQuery->close();
        closeDbConnection($conn);
        return [
            'success' => false,
            'result' => 'API key does not exist'
        ];
    }

    $keyCheckQuery->bind_result($id, $timesUsed);
    $keyCheckQuery->fetch();
    $keyCheckQuery->close();

    $newTimesUsed = $timesUsed + 1;
    $updateQuery = $conn->prepare("
        UPDATE api_keys 
        SET last_used = ?, times_used = ? 
        WHERE id = ?
    ");
    $updateQuery->bind_param("sii", $ipAddress, $newTimesUsed, $id);

    if ($updateQuery->execute()) {
        $updateQuery->close();
        $conn->close();
        return [
            'success' => true,
            'result' => 'API key updated successfully'
        ];
    } else {
        $updateQuery->close();
        $conn->close();
        return [
            'success' => false,
            'result' => 'Error updating API key: ' . $updateQuery->error
        ];
    }
}
