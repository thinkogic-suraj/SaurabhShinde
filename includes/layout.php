<?php

function app_asset(string $path): string
{
    return 'themesdesign.in/nazox/layouts/' . ltrim($path, '/');
}

function is_menu_active(string $menuKey, string $activeMenu): string
{
    return $menuKey === $activeMenu ? 'mm-active' : '';
}

function is_link_active(string $menuKey, string $activeMenu): string
{
    return $menuKey === $activeMenu ? 'active' : '';
}

function render_admin_header(string $title, array $extraCss = [], string $activeMenu = 'dashboard', bool $showPageTitle = true): void
{
    $mobile = $_SESSION['admin_mobile'] ?? 'Admin';
    ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?> | Saurabh Shinde Foundation</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="<?php echo app_asset('assets/images/favicon.ico'); ?>">
    <link href="<?php echo app_asset('assets/css/bootstrap.min.css'); ?>" id="bootstrap-style" rel="stylesheet" type="text/css">
    <link href="<?php echo app_asset('assets/css/icons.min.css'); ?>" rel="stylesheet" type="text/css">
    <link href="<?php echo app_asset('assets/css/app.min.css'); ?>" id="app-style" rel="stylesheet" type="text/css">
<?php foreach ($extraCss as $css): ?>
    <link href="<?php echo htmlspecialchars($css, ENT_QUOTES, 'UTF-8'); ?>" rel="stylesheet" type="text/css">
<?php endforeach; ?>
    <style>
        body, .page-content, #layout-wrapper {
            background-color: #fff !important;
        }
    </style>
</head>
<body data-sidebar="dark" data-keep-enlarged="true" class="vertical-collpsed">
    <div id="layout-wrapper">
        <header id="page-topbar">
            <div class="navbar-header">
                <div class="d-flex">
                    <div class="navbar-brand-box">
                        <a href="dashboard.php" class="logo logo-dark">
                            <span class="logo-sm">
                                <span class="avatar-title rounded-circle bg-primary text-white fw-bold">S</span>
                            </span>
                            <span class="logo-lg text-dark fw-bold">SSF Admin</span>
                        </a>

                        <a href="dashboard.php" class="logo logo-light">
                            <span class="logo-sm">
                                <span class="avatar-title rounded-circle bg-primary text-white fw-bold">S</span>
                            </span>
                            <span class="logo-lg text-white fw-bold">SSF Admin</span>
                        </a>
                    </div>

                    <button type="button" class="btn btn-sm px-3 font-size-24 header-item waves-effect" id="vertical-menu-btn">
                        <i class="ri-menu-2-line align-middle"></i>
                    </button>
                </div>

                <div class="d-flex">
                    <div class="dropdown d-inline-block">
                        <button type="button" class="btn header-item waves-effect">
                            <i class="ri-smartphone-line align-middle me-1"></i>
                            <?php echo htmlspecialchars($mobile, ENT_QUOTES, 'UTF-8'); ?>
                        </button>
                    </div>
                    <div class="d-inline-block">
                        <a href="logout.php" class="btn header-item waves-effect">
                            <i class="ri-logout-box-r-line align-middle me-1"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <div class="vertical-menu">
            <div data-simplebar class="h-100">
                <div id="sidebar-menu">
                    <ul class="metismenu list-unstyled" id="side-menu">
                        <li class="menu-title">Menu</li>

                        <li class="<?php echo is_menu_active('dashboard', $activeMenu); ?>">
                            <a href="dashboard.php" class="waves-effect <?php echo is_link_active('dashboard', $activeMenu); ?>">
                                <i class="ri-dashboard-line"></i>
                                <span>Dashboard</span>
                            </a>
                        </li>

                        <li class="<?php echo is_menu_active('ward', $activeMenu); ?>">
                            <a href="wards.php" class="waves-effect <?php echo is_link_active('ward', $activeMenu); ?>">
                                <i class="ri-map-pin-2-line"></i>
                                <span>Ward</span>
                            </a>
                        </li>

                        <li class="<?php echo is_menu_active('area', $activeMenu); ?>">
                            <a href="areas.php" class="waves-effect <?php echo is_link_active('area', $activeMenu); ?>">
                                <i class="ri-road-map-line"></i>
                                <span>Area</span>
                            </a>
                        </li>

                        <li class="<?php echo is_menu_active('age-category', $activeMenu); ?>">
                            <a href="age-categories.php" class="waves-effect <?php echo is_link_active('age-category', $activeMenu); ?>">
                                <i class="ri-user-star-line"></i>
                                <span>Age Category</span>
                            </a>
                        </li>

                        <li class="<?php echo is_menu_active('request-type', $activeMenu); ?>">
                            <a href="request-types.php" class="waves-effect <?php echo is_link_active('request-type', $activeMenu); ?>">
                                <i class="ri-file-list-3-line"></i>
                                <span>Request Type</span>
                            </a>
                        </li>

                        <li class="<?php echo is_menu_active('my-requests', $activeMenu); ?>">
                            <a href="my-requests.php" class="waves-effect <?php echo is_link_active('my-requests', $activeMenu); ?>">
                                <i class="ri-inbox-archive-line"></i>
                                <span>My Requests</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
<?php if ($showPageTitle): ?>
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="page-title-box">
                                <h4 class="mb-1"><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h4>
                                <div>
                                    <ol class="breadcrumb m-0">
                                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                        <li class="breadcrumb-item active"><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>
<?php endif; ?>
<?php
}

function render_admin_footer(array $extraJs = []): void
{
    ?>
                </div>
            </div>
            <footer class="footer">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-sm-6">
                            <script>document.write(new Date().getFullYear())</script> © Saurabh Shinde Foundation.
                        </div>
                        <div class="col-sm-6">
                            <div class="text-sm-end d-none d-sm-block">
                                Admin Panel
                            </div>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <div class="rightbar-overlay"></div>

    <script src="<?php echo app_asset('assets/libs/jquery/jquery.min.js'); ?>"></script>
    <script src="<?php echo app_asset('assets/libs/bootstrap/js/bootstrap.bundle.min.js'); ?>"></script>
    <script src="<?php echo app_asset('assets/libs/metismenu/metisMenu.min.js'); ?>"></script>
    <script src="<?php echo app_asset('assets/libs/simplebar/simplebar.min.js'); ?>"></script>
    <script src="<?php echo app_asset('assets/libs/node-waves/waves.min.js'); ?>"></script>
<?php foreach ($extraJs as $js): ?>
    <script src="<?php echo htmlspecialchars($js, ENT_QUOTES, 'UTF-8'); ?>"></script>
<?php endforeach; ?>
    <script src="<?php echo app_asset('assets/js/app.js'); ?>"></script>
</body>
</html>
<?php
}
