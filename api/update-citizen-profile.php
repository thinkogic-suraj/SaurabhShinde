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
$mobileNo = trim((string) ($decodedInput['MobileNo'] ?? ''));
$name = trim((string) ($decodedInput['Name'] ?? ''));
$aadhaarNo = trim((string) ($decodedInput['AadhaarNo'] ?? ''));
$email = trim((string) ($decodedInput['Email'] ?? ''));
$genderId = (int) ($decodedInput['GenderId'] ?? 0);
$ageCategoryId = (int) ($decodedInput['AgeCategoryId'] ?? 0);

if ($citizenUserId <= 0) {
    send_json_response(422, [
        'Success' => false,
        'Message' => 'CitizenUserId is required.',
    ]);
}

if ($mobileNo === '') {
    send_json_response(422, [
        'Success' => false,
        'Message' => 'Mobile Number is required.',
    ]);
}

if (!preg_match('/^[0-9]{10}$/', $mobileNo)) {
    send_json_response(422, [
        'Success' => false,
        'Message' => 'Mobile Number must contain exactly 10 digits.',
    ]);
}

if ($name === '') {
    send_json_response(422, [
        'Success' => false,
        'Message' => 'Name is required.',
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

if ($genderId <= 0) {
    send_json_response(422, [
        'Success' => false,
        'Message' => 'GenderId is required.',
    ]);
}

if ($ageCategoryId <= 0) {
    send_json_response(422, [
        'Success' => false,
        'Message' => 'AgeCategoryId is required.',
    ]);
}

if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    send_json_response(422, [
        'Success' => false,
        'Message' => 'Email is invalid.',
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

    $duplicateMobileStmt = $pdo->prepare(
        'SELECT CitizenUserId
         FROM CitizenUser
         WHERE MobileNo = :mobile_no
           AND CitizenUserId <> :citizen_user_id
         LIMIT 1'
    );
    $duplicateMobileStmt->execute([
        'mobile_no' => $mobileNo,
        'citizen_user_id' => $citizenUserId,
    ]);

    if ($duplicateMobileStmt->fetch()) {
        send_json_response(422, [
            'Success' => false,
            'Message' => 'Mobile Number already exists.',
        ]);
    }

    $genderStmt = $pdo->prepare(
        'SELECT GenderId
         FROM GenderMaster
         WHERE GenderId = :gender_id
           AND IsActive = 1
         LIMIT 1'
    );
    $genderStmt->execute([
        'gender_id' => $genderId,
    ]);

    if (!$genderStmt->fetch()) {
        send_json_response(422, [
            'Success' => false,
            'Message' => 'Invalid GenderId.',
        ]);
    }

    $ageCategoryStmt = $pdo->prepare(
        'SELECT AgeCategoryId
         FROM AgeCategoryMaster
         WHERE AgeCategoryId = :age_category_id
           AND IsActive = 1
         LIMIT 1'
    );
    $ageCategoryStmt->execute([
        'age_category_id' => $ageCategoryId,
    ]);

    if (!$ageCategoryStmt->fetch()) {
        send_json_response(422, [
            'Success' => false,
            'Message' => 'Invalid AgeCategoryId.',
        ]);
    }

    $updateStmt = $pdo->prepare(
        'UPDATE CitizenUser
         SET Name = :name,
             MobileNo = :mobile_no,
             AadhaarNo = :aadhaar_no,
             Email = :email,
             GenderId = :gender_id,
             AgeCategoryId = :age_category_id,
             UpdatedDate = NOW()
         WHERE CitizenUserId = :citizen_user_id
           AND IsActive = 1'
    );
    $updateStmt->execute([
        'name' => $name,
        'mobile_no' => $mobileNo,
        'aadhaar_no' => $aadhaarNo,
        'email' => $email === '' ? null : $email,
        'gender_id' => $genderId,
        'age_category_id' => $ageCategoryId,
        'citizen_user_id' => $citizenUserId,
    ]);

    send_json_response(200, [
        'Success' => true,
        'Message' => 'Citizen profile updated successfully.',
    ]);
} catch (Throwable $exception) {
    send_json_response(500, [
        'Success' => false,
        'Message' => 'Unable to update citizen profile.',
    ]);
}
