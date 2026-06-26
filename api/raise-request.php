<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../includes/db.php';

function send_json_response(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json_response(405, [
        'Success' => false,
        'Message' => 'Only POST method is allowed.',
    ]);
}

$rawInput = file_get_contents('php://input');
$decodedInput = json_decode($rawInput ?: '', true);

if (!is_array($decodedInput)) {
    send_json_response(400, [
        'Success' => false,
        'Message' => 'Invalid JSON input.',
    ]);
}

$citizenUserId = (int) ($decodedInput['CitizenUserId'] ?? 0);
$requestTypeId = (int) ($decodedInput['RequestTypeId'] ?? 0);
$wardId = (int) ($decodedInput['WardId'] ?? 0);
$areaId = (int) ($decodedInput['AreaId'] ?? 0);
$address = trim((string) ($decodedInput['Address'] ?? ''));
$aadhaarNo = trim((string) ($decodedInput['AadhaarNo'] ?? ''));
$description = trim((string) ($decodedInput['Description'] ?? ''));

if ($citizenUserId <= 0) {
    send_json_response(422, [
        'Success' => false,
        'Message' => 'CitizenUserId is required.',
    ]);
}

if ($requestTypeId <= 0) {
    send_json_response(422, [
        'Success' => false,
        'Message' => 'RequestTypeId is required.',
    ]);
}

if ($wardId <= 0) {
    send_json_response(422, [
        'Success' => false,
        'Message' => 'WardId is required.',
    ]);
}

if ($areaId <= 0) {
    send_json_response(422, [
        'Success' => false,
        'Message' => 'AreaId is required.',
    ]);
}

if ($address === '') {
    send_json_response(422, [
        'Success' => false,
        'Message' => 'Address is required.',
    ]);
}

if (mb_strlen($address) > 500) {
    send_json_response(422, [
        'Success' => false,
        'Message' => 'Address must be 500 characters or fewer.',
    ]);
}

if ($aadhaarNo === '') {
    send_json_response(422, [
        'Success' => false,
        'Message' => 'Aadhaar Number is required.',
    ]);
}

if (!preg_match('/^[0-9]{12}$/', $aadhaarNo)) {
    send_json_response(422, [
        'Success' => false,
        'Message' => 'Aadhaar Number must contain exactly 12 digits.',
    ]);
}

if ($description === '') {
    send_json_response(422, [
        'Success' => false,
        'Message' => 'Description is required.',
    ]);
}

try {
    $pdo = app_pdo();

    $citizenStmt = $pdo->prepare(
        'SELECT CitizenUserId
         FROM CitizenUser
         WHERE CitizenUserId = :citizen_user_id
           AND IsActive = 1
         LIMIT 1'
    );
    $citizenStmt->execute([
        'citizen_user_id' => $citizenUserId,
    ]);

    if (!$citizenStmt->fetch()) {
        send_json_response(422, [
            'Success' => false,
            'Message' => 'Invalid CitizenUserId.',
        ]);
    }

    $requestTypeStmt = $pdo->prepare(
        'SELECT RequestTypeId
         FROM RequestTypeMaster
         WHERE RequestTypeId = :request_type_id
           AND IsActive = 1
         LIMIT 1'
    );
    $requestTypeStmt->execute([
        'request_type_id' => $requestTypeId,
    ]);

    if (!$requestTypeStmt->fetch()) {
        send_json_response(422, [
            'Success' => false,
            'Message' => 'Invalid RequestTypeId.',
        ]);
    }

    $wardStmt = $pdo->prepare(
        'SELECT WardId
         FROM Ward
         WHERE WardId = :ward_id
           AND IsActive = 1
         LIMIT 1'
    );
    $wardStmt->execute([
        'ward_id' => $wardId,
    ]);

    if (!$wardStmt->fetch()) {
        send_json_response(422, [
            'Success' => false,
            'Message' => 'Invalid WardId.',
        ]);
    }

    $areaStmt = $pdo->prepare(
        'SELECT AreaId
         FROM Area
         WHERE AreaId = :area_id
           AND WardId = :ward_id
           AND IsActive = 1
         LIMIT 1'
    );
    $areaStmt->execute([
        'area_id' => $areaId,
        'ward_id' => $wardId,
    ]);

    if (!$areaStmt->fetch()) {
        send_json_response(422, [
            'Success' => false,
            'Message' => 'Invalid AreaId.',
        ]);
    }

    $statusStmt = $pdo->prepare(
        'SELECT RequestStatusId
         FROM RequestStatusMaster
         WHERE RequestStatusId = 1
           AND IsActive = 1
         LIMIT 1'
    );
    $statusStmt->execute();

    if (!$statusStmt->fetch()) {
        send_json_response(500, [
            'Success' => false,
            'Message' => 'Raised request status is not configured.',
        ]);
    }

    $pdo->beginTransaction();

    $temporaryRequestNo = 'TMP' . date('YmdHis') . bin2hex(random_bytes(4));

    $insertStmt = $pdo->prepare(
        'INSERT INTO CitizenRequest (
            RequestNo,
            CitizenUserId,
            RequestTypeId,
            WardId,
            AreaId,
            Address,
            AadhaarNo,
            Description,
            RequestStatusId,
            RaisedDate,
            CreatedDate,
            IsActive,
            Remark
         ) VALUES (
            :request_no,
            :citizen_user_id,
            :request_type_id,
            :ward_id,
            :area_id,
            :address,
            :aadhaar_no,
            :description,
            1,
            NOW(),
            NOW(),
            1,
            :remark
         )'
    );
    $insertStmt->execute([
        'request_no' => $temporaryRequestNo,
        'citizen_user_id' => $citizenUserId,
        'request_type_id' => $requestTypeId,
        'ward_id' => $wardId,
        'area_id' => $areaId,
        'address' => $address,
        'aadhaar_no' => $aadhaarNo,
        'description' => $description,
        'remark' => '',
    ]);

    $citizenRequestId = (int) $pdo->lastInsertId();
    $requestNo = 'REQ' . str_pad((string) $citizenRequestId, 5, '0', STR_PAD_LEFT);

    $updateRequestNoStmt = $pdo->prepare(
        'UPDATE CitizenRequest
         SET RequestNo = :request_no
         WHERE CitizenRequestId = :citizen_request_id'
    );
    $updateRequestNoStmt->execute([
        'request_no' => $requestNo,
        'citizen_request_id' => $citizenRequestId,
    ]);

    $pdo->commit();

    send_json_response(201, [
        'Success' => true,
        'Message' => 'Request raised successfully.',
        'CitizenRequestId' => $citizenRequestId,
        'RequestNo' => $requestNo,
    ]);
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    send_json_response(500, [
        'Success' => false,
        'Message' => 'Unable to raise request.',
    ]);
}
