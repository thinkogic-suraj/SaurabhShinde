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

function parse_date_range(mixed $dateRange): array
{
    $fromDate = null;
    $toDate = null;

    if (is_array($dateRange)) {
        $fromDate = normalize_date_value($dateRange['FromDate'] ?? $dateRange['from_date'] ?? $dateRange['from'] ?? null);
        $toDate = normalize_date_value($dateRange['ToDate'] ?? $dateRange['to_date'] ?? $dateRange['to'] ?? null);
    } elseif (is_string($dateRange)) {
        $dateRange = trim($dateRange);

        if ($dateRange !== '') {
            $parts = preg_split('/\s*-\s*/', $dateRange);

            if (is_array($parts) && count($parts) === 2) {
                $fromDate = normalize_date_value($parts[0]);
                $toDate = normalize_date_value($parts[1]);
            }
        }
    }

    return [$fromDate, $toDate];
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
$requestStatusId = (int) ($decodedInput['RequestStatusId'] ?? 0);
[$fromDate, $toDate] = parse_date_range($decodedInput['DateRange'] ?? null);

if ($citizenUserId <= 0) {
    send_json_response(422, [
        'Success' => false,
        'Message' => 'CitizenUserId is required.',
    ]);
}

if (array_key_exists('DateRange', $decodedInput) && ($fromDate === null || $toDate === null)) {
    send_json_response(422, [
        'Success' => false,
        'Message' => 'DateRange must be in YYYY-MM-DD - YYYY-MM-DD format.',
    ]);
}

if ($fromDate !== null && $toDate !== null && $fromDate > $toDate) {
    send_json_response(422, [
        'Success' => false,
        'Message' => 'DateRange is invalid.',
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
        send_json_response(404, [
            'Success' => false,
            'Message' => 'Citizen not found.',
        ]);
    }

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
        'cr.CitizenUserId = :citizen_user_id',
        'cr.IsActive = 1',
    ];
    $queryParams = [
        'citizen_user_id' => $citizenUserId,
    ];

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
                cr.RequestTypeId,
                cr.WardId,
                cr.AreaId,
                cr.RequestStatusId,
                rtm.RequestTypeName,
                w.WardName,
                a.AreaName,
                cr.Address,
                cr.Description,
                cr.Remark,
                rsm.StatusName,
                cr.RaisedDate
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
            'WardId' => isset($request['WardId']) ? (int) $request['WardId'] : null,
            'AreaId' => isset($request['AreaId']) ? (int) $request['AreaId'] : null,
            'RequestTypeId' => isset($request['RequestTypeId']) ? (int) $request['RequestTypeId'] : null,
            'RequestStatusId' => isset($request['RequestStatusId']) ? (int) $request['RequestStatusId'] : null,
            'RequestTypeName' => (string) ($request['RequestTypeName'] ?? ''),
            'WardName' => (string) ($request['WardName'] ?? ''),
            'AreaName' => (string) ($request['AreaName'] ?? ''),
            'Address' => (string) ($request['Address'] ?? ''),
            'Description' => (string) ($request['Description'] ?? ''),
            'StatusName' => (string) ($request['StatusName'] ?? ''),
            'Remark' => (string) ($request['Remark'] ?? ''),
            'RaisedDate' => (string) ($request['RaisedDate'] ?? ''),
        ];
    }

    send_json_response(200, [
        'Success' => true,
        'Message' => empty($data) ? 'No active requests found for this citizen.' : 'Requests fetched successfully.',
        'Data' => $data,
    ]);
} catch (Throwable $exception) {
    send_json_response(500, [
        'Success' => false,
        'Message' => 'Unable to fetch requests.',
    ]);
}
