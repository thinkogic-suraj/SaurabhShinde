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

function normalize_date_value(mixed $value): ?string
{
    if (!is_string($value)) {
        return null;
    }

    $value = trim($value);

    if ($value === '') {
        return null;
    }

    $date = DateTime::createFromFormat('Y-m-d', $value);

    return $date !== false && $date->format('Y-m-d') === $value ? $value : null;
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

$requestStatusId = isset($decodedInput['RequestStatusId']) && $decodedInput['RequestStatusId'] !== null
    ? (int) $decodedInput['RequestStatusId']
    : 0;
$fromDate = normalize_date_value($decodedInput['FromDate'] ?? $decodedInput['from_date'] ?? null);
$toDate = normalize_date_value($decodedInput['ToDate'] ?? $decodedInput['to_date'] ?? null);

if ((array_key_exists('FromDate', $decodedInput) || array_key_exists('from_date', $decodedInput)) && $fromDate === null) {
    send_json_response(422, [
        'Success' => false,
        'Message' => 'FromDate must be in YYYY-MM-DD format.',
    ]);
}

if ((array_key_exists('ToDate', $decodedInput) || array_key_exists('to_date', $decodedInput)) && $toDate === null) {
    send_json_response(422, [
        'Success' => false,
        'Message' => 'ToDate must be in YYYY-MM-DD format.',
    ]);
}

if (($fromDate === null) !== ($toDate === null)) {
    send_json_response(422, [
        'Success' => false,
        'Message' => 'Both FromDate and ToDate are required to apply date filter.',
    ]);
}

if ($fromDate !== null && $toDate !== null && $fromDate > $toDate) {
    send_json_response(422, [
        'Success' => false,
        'Message' => 'Date range is invalid.',
    ]);
}

try {
    $pdo = app_pdo();

    if ($requestStatusId > 0) {
        $statusStmt = $pdo->prepare(
            'SELECT RequestStatusId
             FROM RequestStatusMaster
             WHERE RequestStatusId = :request_status_id
               AND IsActive = 1
             LIMIT 1'
        );
        $statusStmt->execute([
            'request_status_id' => $requestStatusId,
        ]);

        if (!$statusStmt->fetch()) {
            send_json_response(422, [
                'Success' => false,
                'Message' => 'Invalid RequestStatusId.',
            ]);
        }
    }

    $whereParts = [
        'cr.IsActive = 1',
    ];
    $queryParams = [];

    if ($requestStatusId > 0) {
        $whereParts[] = 'cr.RequestStatusId = :request_status_id';
        $queryParams['request_status_id'] = $requestStatusId;
    }

    if ($fromDate !== null && $toDate !== null) {
        $whereParts[] = 'DATE(cr.RaisedDate) BETWEEN :from_date AND :to_date';
        $queryParams['from_date'] = $fromDate;
        $queryParams['to_date'] = $toDate;
    }

    $stmt = $pdo->prepare(
        'SELECT cr.CitizenRequestId,
                cr.RequestNo,
                cr.CitizenUserId,
                cr.RequestTypeId,
                cr.WardId,
                cr.AreaId,
                cr.Address,
                cr.AadhaarNo,
                cr.Description,
                cr.RequestStatusId,
                cr.Remark,
                cr.RaisedDate,
                cr.ClosedDate,
                rtm.RequestTypeName,
                w.WardName,
                a.AreaName,
                rsm.StatusName
         FROM CitizenRequest cr
         LEFT JOIN RequestTypeMaster rtm ON rtm.RequestTypeId = cr.RequestTypeId
         LEFT JOIN Ward w ON w.WardId = cr.WardId
         LEFT JOIN Area a ON a.AreaId = cr.AreaId
         LEFT JOIN RequestStatusMaster rsm ON rsm.RequestStatusId = cr.RequestStatusId
         WHERE ' . implode(' AND ', $whereParts) . '
         ORDER BY cr.CitizenRequestId DESC'
    );
    $stmt->execute($queryParams);

    $requests = $stmt->fetchAll();
    $data = [];

    foreach ($requests as $request) {
        $data[] = [
            'CitizenRequestId' => (int) $request['CitizenRequestId'],
            'RequestNo' => (string) ($request['RequestNo'] ?? ''),
            'CitizenUserId' => isset($request['CitizenUserId']) ? (int) $request['CitizenUserId'] : null,
            'RequestTypeId' => isset($request['RequestTypeId']) ? (int) $request['RequestTypeId'] : null,
            'RequestTypeName' => (string) ($request['RequestTypeName'] ?? ''),
            'WardId' => isset($request['WardId']) ? (int) $request['WardId'] : null,
            'WardName' => (string) ($request['WardName'] ?? ''),
            'AreaId' => isset($request['AreaId']) ? (int) $request['AreaId'] : null,
            'AreaName' => (string) ($request['AreaName'] ?? ''),
            'Address' => (string) ($request['Address'] ?? ''),
            'AadhaarNo' => (string) ($request['AadhaarNo'] ?? ''),
            'Description' => (string) ($request['Description'] ?? ''),
            'RequestStatusId' => isset($request['RequestStatusId']) ? (int) $request['RequestStatusId'] : null,
            'StatusName' => (string) ($request['StatusName'] ?? ''),
            'Remark' => (string) ($request['Remark'] ?? ''),
            'RaisedDate' => (string) ($request['RaisedDate'] ?? ''),
            'ClosedDate' => (string) ($request['ClosedDate'] ?? ''),
        ];
    }

    send_json_response(200, [
        'Success' => true,
        'Message' => empty($data) ? 'No active requests found.' : 'Requests fetched successfully.',
        'Data' => $data,
    ]);
} catch (Throwable $exception) {
    send_json_response(500, [
        'Success' => false,
        'Message' => 'Unable to fetch requests.',
    ]);
}
