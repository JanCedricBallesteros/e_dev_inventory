<?php

/** 
==================================================================
 File name   : include_top.php
 Version     : 1.0.0
 Begin       : 
 Last Update : 
 Author      : 
 Description : include all CSS and STYLE (FOR ADMINS UI).
 =================================================================
 **/
?>

<!-- FAVICONS -->
<link rel="icon" type="image/x-icon" href="<?php echo FAVICON; ?>">
<link rel="apple-touch-icon" href="<?php echo FAVICON; ?>">

<!-- BOOTSTRAP CSS Files -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/bootstrap.min.css?v=<?php echo FILE_VERSION; ?>">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/bootstrap-icons/bootstrap-icons.css?v=<?php echo FILE_VERSION; ?>">

<!-- MAIN CSS Files -->
<link rel="stylesheet" type="text/css" href="<?php echo BASE_URL; ?>assets/css/custom-root.css?v=<?php echo FILE_VERSION; ?>">
<link rel="stylesheet" type="text/css" href="<?php echo BASE_URL; ?>assets/css/tabulator.min.css?v=<?php echo FILE_VERSION; ?>">
<link rel="stylesheet" type="text/css" href="<?php echo BASE_URL; ?>assets/css/tabulator_bootstrap.min.css?v=<?php echo FILE_VERSION; ?>">
<link rel="stylesheet" type="text/css" href="<?php echo BASE_URL; ?>assets/css/selectize.bootstrap3.css?v=<?php echo FILE_VERSION; ?>">
<link rel="stylesheet" type="text/css" href="<?php echo BASE_URL; ?>assets/css/bootstrap-datetimepicker.min.css?v=<?php echo FILE_VERSION; ?>">
<link rel="stylesheet" type="text/css" href="<?php echo BASE_URL; ?>assets/css/jquery.timepicker.min.css?v=<?php echo FILE_VERSION; ?>">
<link rel="stylesheet" type="text/css" href="<?php echo BASE_URL; ?>assets/css/dropify.css?v=<?php echo FILE_VERSION; ?>">
<link rel="stylesheet" type="text/css" href="<?php echo BASE_URL; ?>assets/css/daterangepicker.css?v=<?php echo FILE_VERSION; ?>">

<!-- DASHBOARD CSS Files -->

<!-- [DASHBOARD] PLUGIN CSS Files -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/dashboard/css/plugin/plugins.min.css?v=<?php echo FILE_VERSION; ?>">

<!-- DASHBOARD CSS Files -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/dashboard/css/custom.min.css?v=<?php echo FILE_VERSION; ?>">

<style>
:root {
    --bg-eclearance-rgb: #1E3A8A;
    --color-eclearance-btn-bg: #1E3A8A;
    --color-eclearance-btn-bg-hover: #2a4fb8;
    --color-eclearance-subtle-bg-hover-2: #183173;
    --color-eclearance-border: #1E3A8A;
}

.bg-eclearance {
    background-color: var(--bg-eclearance-rgb) !important;
    border-color: var(--bg-eclearance-rgb) !important;
}

.btn-eclearance {
    --bs-btn-color: #fff;
    --bs-btn-bg: var(--color-eclearance-btn-bg);
    --bs-btn-border-color: var(--color-eclearance-btn-bg);
    --bs-btn-hover-color: #fff;
    --bs-btn-hover-bg: var(--color-eclearance-btn-bg-hover);
    --bs-btn-hover-border-color: var(--color-eclearance-subtle-bg-hover-2);
    --bs-btn-focus-shadow-rgb: 130, 138, 145;
    --bs-btn-active-color: #fff;
    --bs-btn-active-bg: var(--color-eclearance-subtle-bg-hover-2);
    --bs-btn-active-border-color: var(--color-eclearance-border);
    --bs-btn-disabled-color: #fff;
    --bs-btn-disabled-bg: var(--color-eclearance-btn-bg);
    --bs-btn-disabled-border-color: var(--color-eclearance-btn-bg);
}

.btn-outline-eclearance {
    --bs-btn-color: var(--color-eclearance-btn-bg);
    --bs-btn-border-color: var(--color-eclearance-btn-bg);
    --bs-btn-hover-color: #fff;
    --bs-btn-hover-bg: var(--color-eclearance-btn-bg);
    --bs-btn-hover-border-color: var(--color-eclearance-btn-bg);
    --bs-btn-active-color: #fff;
    --bs-btn-active-bg: var(--color-eclearance-btn-bg);
    --bs-btn-active-border-color: var(--color-eclearance-btn-bg);
    --bs-btn-disabled-color: var(--color-eclearance-btn-bg);
    --bs-btn-disabled-bg: transparent;
    --bs-btn-disabled-border-color: var(--color-eclearance-btn-bg);
}

/* Legacy module pages: make #main behave like .main-panel > .container */
.main-panel > #main.main {
    min-height: calc(100% - 123px);
    margin-top: 69px;
    overflow: visible;
    width: 100%;
    max-width: unset;
    padding: 24px 30px;
}

@media (max-width: 991.5px) {
    .main-panel > #main.main {
        margin-top: 69px;
        padding: 16px 15px;
        transition: all .5s;
    }
}
</style>
