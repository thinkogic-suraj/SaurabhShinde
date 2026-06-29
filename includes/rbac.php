<?php

function app_role_ids(PDO $pdo): array
{
    static $cache = [];

    $cacheKey = spl_object_id($pdo);

    if (isset($cache[$cacheKey])) {
        return $cache[$cacheKey];
    }

    $roleIds = [
        'super_admin' => 0,
        'admin' => 0,
    ];

    $stmt = $pdo->query(
        'SELECT RoleId, RoleName
         FROM RoleMaster
         WHERE IsActive = 1'
    );

    foreach ($stmt as $role) {
        $normalizedRoleName = strtolower(trim((string) ($role['RoleName'] ?? '')));

        if ($normalizedRoleName === 'super admin') {
            $roleIds['super_admin'] = (int) $role['RoleId'];
        } elseif ($normalizedRoleName === 'admin') {
            $roleIds['admin'] = (int) $role['RoleId'];
        }
    }

    $cache[$cacheKey] = $roleIds;

    return $roleIds;
}

function current_admin_context(PDO $pdo): ?array
{
    $employeeId = (int) ($_SESSION['admin_id'] ?? 0);

    if ($employeeId <= 0) {
        return null;
    }

    $stmt = $pdo->prepare(
        'SELECT e.EmployeeId,
                e.UserName,
                e.MobileNo,
                e.RoleId,
                e.IsActive,
                COALESCE(r.RoleName, \'\') AS RoleName
         FROM Employee e
         LEFT JOIN RoleMaster r ON r.RoleId = e.RoleId
         WHERE e.EmployeeId = :employee_id
         LIMIT 1'
    );
    $stmt->execute([
        'employee_id' => $employeeId,
    ]);

    $admin = $stmt->fetch();

    if (!$admin) {
        return null;
    }

    $_SESSION['admin_name'] = (string) ($admin['UserName'] ?? '');
    $_SESSION['admin_mobile'] = (string) ($admin['MobileNo'] ?? '');
    $_SESSION['admin_role_id'] = isset($admin['RoleId']) ? (int) $admin['RoleId'] : 0;
    $_SESSION['admin_role_name'] = (string) ($admin['RoleName'] ?? '');

    return [
        'EmployeeId' => (int) $admin['EmployeeId'],
        'UserName' => (string) ($admin['UserName'] ?? ''),
        'MobileNo' => (string) ($admin['MobileNo'] ?? ''),
        'RoleId' => isset($admin['RoleId']) ? (int) $admin['RoleId'] : 0,
        'RoleName' => (string) ($admin['RoleName'] ?? ''),
        'IsActive' => isset($admin['IsActive']) ? (int) $admin['IsActive'] : 0,
    ];
}

function is_super_admin_user(array $adminContext, array $roleIds): bool
{
    return $roleIds['super_admin'] > 0 && $adminContext['RoleId'] === $roleIds['super_admin'];
}

function is_admin_user(array $adminContext, array $roleIds): bool
{
    return $roleIds['admin'] > 0 && $adminContext['RoleId'] === $roleIds['admin'];
}

function can_access_user_management(array $adminContext, array $roleIds): bool
{
    return (int) ($adminContext['IsActive'] ?? 0) === 1
        && (is_super_admin_user($adminContext, $roleIds) || is_admin_user($adminContext, $roleIds));
}

function can_view_managed_user(array $adminContext, array $targetUser, array $roleIds): bool
{
    if (is_super_admin_user($adminContext, $roleIds)) {
        return (int) $targetUser['EmployeeId'] === (int) $adminContext['EmployeeId']
            || (int) $targetUser['RoleId'] === $roleIds['admin'];
    }

    if (is_admin_user($adminContext, $roleIds)) {
        if ((int) $targetUser['EmployeeId'] === (int) $adminContext['EmployeeId']) {
            return true;
        }

        return (int) $targetUser['RoleId'] === $roleIds['admin']
            && (int) ($targetUser['CreatedBy'] ?? 0) === (int) $adminContext['EmployeeId'];
    }

    return false;
}

function can_edit_managed_user(array $adminContext, array $targetUser, array $roleIds): bool
{
    if (!can_view_managed_user($adminContext, $targetUser, $roleIds)) {
        return false;
    }

    if (is_super_admin_user($adminContext, $roleIds)) {
        return (int) $targetUser['EmployeeId'] === (int) $adminContext['EmployeeId']
            || (int) $targetUser['RoleId'] === $roleIds['admin'];
    }

    if (is_admin_user($adminContext, $roleIds)) {
        return (int) $targetUser['RoleId'] === $roleIds['admin']
            && (int) ($targetUser['CreatedBy'] ?? 0) === (int) $adminContext['EmployeeId'];
    }

    return false;
}

function can_soft_delete_managed_user(array $adminContext, array $targetUser, array $roleIds): bool
{
    if (!can_view_managed_user($adminContext, $targetUser, $roleIds)) {
        return false;
    }

    if ((int) $targetUser['EmployeeId'] === (int) $adminContext['EmployeeId']) {
        return false;
    }

    if (is_super_admin_user($adminContext, $roleIds)) {
        return (int) $targetUser['RoleId'] === $roleIds['admin'];
    }

    if (is_admin_user($adminContext, $roleIds)) {
        return (int) $targetUser['RoleId'] === $roleIds['admin']
            && (int) ($targetUser['CreatedBy'] ?? 0) === (int) $adminContext['EmployeeId'];
    }

    return false;
}

function can_change_managed_user_status(array $adminContext, array $targetUser, array $roleIds): bool
{
    if ((int) $targetUser['EmployeeId'] === (int) $adminContext['EmployeeId']) {
        return false;
    }

    return is_super_admin_user($adminContext, $roleIds)
        && (int) $targetUser['RoleId'] === $roleIds['admin'];
}
