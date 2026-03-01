<?php

/** 
==================================================================
 File name   : include_bottom.php
 Version     : 1.0.0
 Begin       : 2026-02-26
 Last Update :
 Author      : 
 Description : include all JS and OTHER SCRIPTS (FOR ADMINS UI).
 =================================================================
 **/
?>
<!-- FONTS AND ICON JS Files -->
<script type="text/javascript" src="<?php echo BASE_URL; ?>assets/dashboard/js/plugin/webfont/webfont.min.js"></script>
<script>
    WebFont.load({
        google: {
            families: ["Public Sans:300,400,500,600,700"]
        },
        custom: {
            families: [
                "Font Awesome 5 Solid",
                "Font Awesome 5 Regular",
                "Font Awesome 5 Brands",
                "simple-line-icons",
            ],
            urls: ["<?php echo BASE_URL; ?>assets/dashboard/css/fonts.min.css"],
        },
        active: function() {
            sessionStorage.fonts = true;
        },
    });
</script>

<!-- BOOTSRAP JS Files -->
<script type="text/javascript" src="<?php echo BASE_URL; ?>assets/js/bootstrap.min.js"></script>

<!-- MAIN JS Files -->
<script type="text/javascript" src="<?php echo BASE_URL; ?>assets/js/jquery-3.7.1.min.js"></script>
<script type="text/javascript" src="<?php echo BASE_URL; ?>assets/js/main.js?v=<?php echo FILE_VERSION; ?>"></script>
<script type="text/javascript" src="<?php echo BASE_URL; ?>assets/js/app.js?v=<?php echo FILE_VERSION; ?>"></script>
<script type="text/javascript" src="<?php echo BASE_URL; ?>assets/js/moment.js?v=<?php echo FILE_VERSION; ?>"></script>
<script type="text/javascript" src="<?php echo BASE_URL; ?>assets/js/moment-timezone-with-data.js?v=<?php echo FILE_VERSION; ?>"></script>
<script type="text/javascript" src="<?php echo BASE_URL; ?>assets/js/tabulator.min.js?v=<?php echo FILE_VERSION; ?>"></script>
<script type="text/javascript" src="<?php echo BASE_URL; ?>assets/js/bootstrap-datetimepicker.min.js?v=<?php echo FILE_VERSION; ?>"></script>
<script type="text/javascript" src="<?php echo BASE_URL; ?>assets/js/selectize.js?v=<?php echo FILE_VERSION; ?>"></script>
<script type="text/javascript" src="<?php echo BASE_URL; ?>assets/js/jquery.timepicker.min.js?v=<?php echo FILE_VERSION; ?>"></script>
<script type="text/javascript" src="<?php echo BASE_URL; ?>assets/js/dropify.js?v=<?php echo FILE_VERSION; ?>"></script>
<script type="text/javascript" src="<?php echo BASE_URL; ?>assets/js/daterangepicker.js?v=<?php echo FILE_VERSION; ?>"></script>


<!-- DASHBOARD JS Files -->
<script type="text/javascript" src="<?php echo BASE_URL; ?>assets/dashboard/js/custom.min.js"></script>

<!-- [DASHBOARD] CORE JS Files -->
<script type="text/javascript" src="<?php echo BASE_URL; ?>assets/dashboard/js/core/popper.min.js"></script>

<!-- [DASHBOARD] PLUGIN JS Files -->
<script type="text/javascript" src="<?php echo BASE_URL; ?>assets/dashboard/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
<script type="text/javascript" src="<?php echo BASE_URL; ?>assets/dashboard/js/plugin/chart.js/chart.min.js"></script>
<script type="text/javascript" src="<?php echo BASE_URL; ?>assets/dashboard/js/plugin/jquery.sparkline/jquery.sparkline.min.js"></script>
<script type="text/javascript" src="<?php echo BASE_URL; ?>assets/dashboard/js/plugin/chart-circle/circles.min.js"></script>
<script type="text/javascript" src="<?php echo BASE_URL; ?>assets/dashboard/js/plugin/bootstrap-notify/bootstrap-notify.min.js"></script>
<script type="text/javascript" src="<?php echo BASE_URL; ?>assets/dashboard/js/plugin/jsvectormap/jsvectormap.min.js"></script>
<script type="text/javascript" src="<?php echo BASE_URL; ?>assets/dashboard/js/plugin/jsvectormap/world.js"></script>
<script type="text/javascript" src="<?php echo BASE_URL; ?>assets/dashboard/js/plugin/sweetalert/sweetalert.min.js"></script>

<!-- SCRIPTS -->
<script>
    (function() {
        /** date and time */
        var set_server_time = <?php echo "'" . DATE_TIME . "';\r\n"; ?>
        var serverOffset = moment(set_server_time).diff(new Date());
        var clock_id = datetime();

        function datetime() {
            setInterval(function() {
                if (document.getElementById('now')) {
                    var now_server = moment();
                    now_server.add(serverOffset, 'milliseconds');
                    var timeNow = now_server.format('ddd | MMMM DD, YYYY h:mm:ss A');
                    $('#now').html(timeNow);
                    $('#printTime').html('Date Printed : ' + timeNow);
                } else {
                    clearInterval(clock_id);
                }
            }, 1000);
        }
    })();
</script>