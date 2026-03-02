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
