<?php

/** 
==================================================================
 File name   : include_bottom.php
 Version     : 1.0.0
 Begin       : 2025-08-05
 Last Update :
 Author      : 
 Description : include all JS and OTHER SCRIPTS.
 =================================================================
 **/

if ($g_user_role == "ADMIN" or $g_user_role == "OFFICIAL" or $g_user_role == "FACULTY" or $g_user_role == "STUDENT") {
    $collect_year = array();
    if ($result = call_mysql_query("SELECT * FROM  school_year ORDER BY school_year DESC")) {
        if ($num = call_mysql_num_rows($result)) {
            while ($data = call_mysql_fetch_array($result)) {
                array_push($collect_year, array_html($data));
            }
        }
    }
}
?>

<style>
    .swal2-content {
        z-index: 2 !important;
    }
</style>
<a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up"></i></a>

<!-- Vendor JS Files -->
<script src="<?php echo BASE_URL; ?>assets/bootstrap/js/bootstrap.bundle.min.js?v=<?php echo FILE_VERSION; ?>"></script>

<!-- Main JS Files -->
<script type="text/javascript" src="<?php echo BASE_URL; ?>assets/js/jquery.min.js?v=<?php echo FILE_VERSION; ?>"></script>
<script type="text/javascript" src="<?php echo BASE_URL; ?>assets/js/main.js?v=<?php echo FILE_VERSION; ?>"></script>
<script type="text/javascript" src="<?php echo BASE_URL; ?>assets/js/app.js?v=<?php echo FILE_VERSION; ?>"></script>
<script type="text/javascript" src="<?php echo BASE_URL; ?>assets/js/sweetalert2.js?v=<?php echo FILE_VERSION; ?>"></script>
<script type="text/javascript" src="<?php echo BASE_URL; ?>assets/js/tabulator.min.js?v=<?php echo FILE_VERSION; ?>"></script>
<script type="text/javascript" src="<?php echo BASE_URL; ?>assets/js/bootstrap-datetimepicker.min.js?v=<?php echo FILE_VERSION; ?>"></script>
<script type="text/javascript" src="<?php echo BASE_URL; ?>assets/js/selectize.js?v=<?php echo FILE_VERSION; ?>"></script>
<script type="text/javascript" src="<?php echo BASE_URL; ?>assets/js/validate_mod.js?v=<?php echo FILE_VERSION; ?>"></script>
<script type="text/javascript" src="<?php echo BASE_URL; ?>assets/js/dropify.js?v=<?php echo FILE_VERSION; ?>"></script>
<script type="text/javascript" src="<?php echo BASE_URL; ?>assets/js/moment.js?v=<?php echo FILE_VERSION; ?>"></script>
<script type="text/javascript" src="<?php echo BASE_URL; ?>assets/js/moment-timezone-with-data.js?v=<?php echo FILE_VERSION; ?>"></script>
<script type="text/javascript" src="<?php echo BASE_URL; ?>assets/js/xlsx.full.min.js?v=<?php echo FILE_VERSION; ?>"></script>
<script type="text/javascript" src="<?php echo BASE_URL; ?>assets/js/jquery.timepicker.min.js?v=<?php echo FILE_VERSION; ?>"></script>
<script type="text/javascript" src="<?php echo BASE_URL; ?>assets/js/clipboard.min.js?v=<?php echo FILE_VERSION; ?>"></script>
<script type="text/javascript" src="<?php echo BASE_URL; ?>assets/js/daterangepicker.js?v=<?php echo FILE_VERSION; ?>"></script>
<!-- Template Main JS File -->
<script src="<?php echo BASE_URL; ?>assets/js/main-script.js?v=<?php echo FILE_VERSION; ?>"></script>

<!-- Scripts -->
<script>
    <?php
    if ($g_user_role == "ADMIN" or $g_user_role == "OFFICIAL" or $g_user_role == "FACULTY" or $g_user_role == "STUDENT") {
        echo "\r\nvar select_fy_list=" . output($collect_year) . ";\r\n"; ?>

        var set_year = document.getElementById('set_year');
        addListener(set_year, "click", function() {
            Swal.fire({
                title: "Set Fiscal Year",
                html: '<div><select class="form-control" id="global_year_select">/select></div>',
                confirmButtonText: "Set",
                showCancelButton: true,
                allowOutsideClick: false,
                preConfirm: () => {
                    if (selected_year.val() == "") {
                        Swal.showValidationMessage('Please Select Fiscal Year!');
                        return '';
                    } else {
                        $.ajax({
                            type: "POST",
                            url: "<?php echo BASE_URL ?>app/ajax_set_year.php",
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            data: {
                                'action': 'SET_YEAR',
                                'year_id': selected_year.val()
                            },
                            success: function(response) {
                                var obj = JSON.parse(response);

                                if (obj.status == "success") {
                                    msg_modal(
                                        "Success!",
                                        "Your Fiscal Year has been set!",
                                        "success"
                                    );

                                    setTimeout(function() {
                                        window.location.reload();
                                    }, 1000);
                                } else {
                                    msg_modal(
                                        "Internal Error",
                                        "Oops, Fiscal Year was not set.",
                                        "error"
                                    );
                                }
                            },
                            failure: function(response) {
                                msg_modal(
                                    "Internal Error",
                                    "Oops, Fiscal Year was not set.",
                                    "error"
                                );
                            }
                        });

                    }
                },
                willOpen: () => {
                    selected_year = $('#global_year_select').selectize({
                        valueField: 'school_year_id',
                        labelField: ['school_year', 'sem'],
                        searchField: ['school_year', 'sem'],
                        options: select_fy_list,
                        persist: false,
                        maxItems: 1,
                        dropdownParent: "body",
                        render: {
                            option: function(item, escape) {
                                return '<div class="p-2"> <h5 class="fw-bold text-monoscope">Fiscal Year: ' + escape(item.school_year) + '</h5>' + '<span class="fs-6 text-monoscope">Semester: ' + escape(item.sem) + '</span></div>';
                            },
                            item: function(item, escape) {
                                return '<div class="p-2"> <span class="fw-bold text-monoscope">' + escape(item.school_year) + '</span>' + '<span> / </span><span class="fs-6 text-monoscope">' + escape(item.sem) + '</span></div>';
                            }
                        }
                    });
                    $('.selectize-dropdown').css('z-index', 9999);
                },
            }).then((result) => {
                if (result.value) {
                    //successs auto
                }
            });
        });

    <?php } ?>

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

    (function() {
        function add_overlay() {
            var body = document.querySelector('body');
            var overlay = document.querySelector('.overlay');
            if (overlay) {} else {
                var div = document.createElement('div');
                div.className = "overlay";
                body.appendChild(div);
            }
        }
        add_overlay();
        $(document).on({
            ajaxStart: function() {
                addClass(document.querySelector('body'), 'loading');
                isPaused = true;
            },
            ajaxStop: function() {
                removeClass(document.querySelector('body'), "loading");
                isPaused = false;
            }
        });
    })();

    function msg_modal(title, msg, type) {
        Swal.fire(title, msg, type);
    }

    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer)
            toast.addEventListener('mouseleave', Swal.resumeTimer)
        }
    });

    function error_notif(value = "", options) {
        opt = {
            position: 'top-end',
            timer: 3000,
            confirm: false,
            progress: true,
            bg: "#f27474",
            iconColor: "white"
        };
        if (!jQuery.isEmptyObject(options)) { // true)
            for (var prop in opt) {
                // skip loop if the property is from prototype
                if (!opt.hasOwnProperty(prop)) continue;
                if (options.hasOwnProperty(prop)) {
                    opt[prop] = options[prop];
                }

            }
        }

        Toast.fire({
            icon: 'error',
            iconColor: opt.iconColor,
            position: opt.position,
            showConfirmButton: opt.confirm,
            timer: opt.timer,
            timerProgressBar: opt.progress,
            background: opt.bg,
            customClass: {
                popup: 'colored-toast'
            },
            title: value
        })
    }

    function success_notif(value = "", options) {
        opt = {
            position: 'top-end',
            timer: 3000,
            confirm: false,
            progress: true,
            bg: "#a5dc86",
            iconColor: "white"
        };
        if (!jQuery.isEmptyObject(options)) { // true)
            for (var prop in opt) {
                // skip loop if the property is from prototype
                if (!opt.hasOwnProperty(prop)) continue;
                if (options.hasOwnProperty(prop)) {
                    opt[prop] = options[prop];
                }

            }
        }

        Toast.fire({
            icon: 'success',
            iconColor: opt.iconColor,
            position: opt.position,
            showConfirmButton: opt.confirm,
            timer: opt.timer,
            timerProgressBar: opt.progress,
            background: opt.bg,
            customClass: {
                popup: 'colored-toast'
            },
            title: value
        })
    }

    function warning_notif(value = "", options) {

        opt = {
            position: 'top-end',
            timer: 3000,
            confirm: false,
            progress: true,
            bg: "#f8bb86",
            iconColor: "white"
        };
        if (!jQuery.isEmptyObject(options)) { // true)
            for (var prop in opt) {
                // skip loop if the property is from prototype
                if (!opt.hasOwnProperty(prop)) continue;
                if (options.hasOwnProperty(prop)) {
                    opt[prop] = options[prop];
                }

            }
        }
        Toast.fire({
            icon: 'warning',
            iconColor: opt.iconColor,
            position: opt.position,
            showConfirmButton: opt.confirm,
            timer: opt.timer,
            timerProgressBar: opt.progress,
            background: opt.bg,
            customClass: {
                popup: 'colored-toast'
            },
            title: value
        })
    }

    function info_notif(value = "", options) {
        opt = {
            position: 'top-end',
            timer: 3000,
            confirm: false,
            progress: true,
            bg: "#3fc3ee",
            iconColor: "white"
        };
        if (!jQuery.isEmptyObject(options)) { // true)
            for (var prop in opt) {
                // skip loop if the property is from prototype
                if (!opt.hasOwnProperty(prop)) continue;
                if (options.hasOwnProperty(prop)) {
                    opt[prop] = options[prop];
                }

            }
        }
        Toast.fire({
            icon: 'info',
            iconColor: opt.iconColor,
            position: opt.position,
            showConfirmButton: opt.confirm,
            timer: opt.timer,
            timerProgressBar: opt.progress,
            background: opt.bg,
            customClass: {
                popup: 'colored-toast'
            },
            title: value
        });
    }

    function question_notif(value = "", options) {

        opt = {
            position: 'top-end',
            timer: 3000,
            confirm: false,
            progress: true,
            bg: "#87adbd",
            iconColor: "white"
        };
        if (!jQuery.isEmptyObject(options)) { // true)
            for (var prop in opt) {
                // skip loop if the property is from prototype
                if (!opt.hasOwnProperty(prop)) continue;
                if (options.hasOwnProperty(prop)) {
                    opt[prop] = options[prop];
                }

            }
        }
        Toast.fire({
            icon: 'info',
            iconColor: opt.iconColor,
            position: opt.position,
            showConfirmButton: opt.confirm,
            timer: opt.timer,
            timerProgressBar: opt.progress,
            background: opt.bg,
            customClass: {
                popup: 'colored-toast'
            },
            title: value
        })
    }

    // app_onsite main sweetalert
    function msg_alert(title, icon) {
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-right',
            iconColor: 'white',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer)
                toast.addEventListener('mouseleave', Swal.resumeTimer)
            },
            customClass: {
                popup: 'colored-toast'
            }
        })
        Toast.fire({
            title: title,
            icon: icon
        })
    }

    function msg_html(img, name, position, remark, time = "") {
        var time = time == '' ? 3000 : time;
        var footer_class = 'swal-footer-timein';
        if (remark == 'Successfully Time In') {
            footer_class = 'swal-footer-timein';
        } else if (remark == 'Successfully Break Out') {
            footer_class = 'swal-footer-breakout';
        } else if (remark == 'Successfully Break In') {
            footer_class = 'swal-footer-breakin';
        } else if (remark == 'Successfully Time Out') {
            footer_class = 'swal-footer-timeout';
        }

        Swal.fire({
            html: `<div class="row">
                    <div class="col-12">` + img + `</div>
                    <div class="row">
                        <div class="col-12 fw-bold fs-3 text-dark">` + name + `</div>
                        <div class="col-12">` + position + `</div>
                    </div>
                </div>`,
            footer: remark,
            showConfirmButton: false,
            timerProgressBar: true,
            width: 450,
            padding: '1em 0 0',
            customClass: {
                popup: 'swal-popup-modal',
                footer: footer_class,
            },
            timer: time
        });
    }

    function msg_error(title, msg, time = "") {
        var time = time == '' ? 3000 : time;
        Swal.fire({
            title: title,
            text: msg,
            icon: 'error',
            timerProgressBar: true,
            showConfirmButton: false,
            timer: time,
            footer: `    `,
            padding: '1em 0 0',
            customClass: {
                popup: 'swal-popup-modal',
                footer: 'swal-footer-error',
            },
        });
    }

    function msg_warning(title, msg, time = "") {
        var time = time == '' ? 3000 : time;
        Swal.fire({
            title: title,
            text: msg,
            icon: 'warning',
            timerProgressBar: true,
            showConfirmButton: false,
            timer: time,
            footer: `    `,
            padding: '1em 0 0',
            customClass: {
                popup: 'swal-popup-modal',
                footer: 'swal-footer-warning',
            },
        });
    }

    function msg_success(title, msg, time = "") {
        var time = time == '' ? 3000 : time;
        Swal.fire({
            title: title,
            text: msg,
            icon: 'success',
            timerProgressBar: true,
            showConfirmButton: false,
            timer: time,
            footer: `    `,
            padding: '1em 0 0',
            customClass: {
                popup: 'swal-popup-modal',
                footer: 'swal-footer-success',
            }
        });
    }

    function msg_login(title) {
        Swal.fire({
            html: `<div class="d-flex align-items-center" style="padding: 1.8em 0 1.8em ;">
                        <div style="padding: 0 1.8em;">
                            <img src="<?php echo BASE_URL; ?>assets/img/eams-logo.png" alt="" height="110px">
                        </div>
                        <div style="font-size: 1.5em;">
                            <strong>` + title + `<strong>
                        </div>
                    </div>`,
            timerProgressBar: true,
            showConfirmButton: false,
            padding: 0,
            timer: 2000,
            footer: `    `,
            padding: '1em 0 0',
            customClass: {
                popup: 'swal-popup-modal',
                footer: 'swal-footer-success',
            }
        });
    }

    function isUrl(str) {
        try {
            const url = new URL(str);
            return url.protocol === "http:" || url.protocol === "https:";
        } catch (e) {
            return false;
        }
    }

    function not_header() {
        // var i = <?php echo json_encode($notif_header) ?>;
        // document.getElementById("new_notif_count").innerHTML = i.length;
        // if (i.length > 1) {
        //     document.getElementById("notif_dd_hd").innerHTML = `You have ${i.length} new notifications <a href="<?php echo BASE_URL ?>app/notification.php"><span class="badge rounded-pill bg-primary p-2 ms-2">View all</span></a>`;
        // } else if (i.length == 1) {
        //     document.getElementById("notif_dd_hd").innerHTML = `You have ${i.length} new notification <a href="<?php echo BASE_URL ?>app/notification.php"><span class="badge rounded-pill bg-primary p-2 ms-2">View all</span></a>`;
        // } else if (i.length == 0) {
        //     document.getElementById("notif_dd_hd").innerHTML = `You have no new notifications`;
        //     document.getElementById("new_notif_count").innerHTML = '';
        // };
        // for (x = 0; x != i.length; x++) {
        //     var type = i[x].type;
        //     var content = i[x].content;
        //     var when = i[x].when;
        //     switch (type) {
        //         case type = 'decline':
        //             $notif_icon = 'bi bi-x-circle text-danger';
        //             break;
        //         case type = 'approve':
        //             $notif_icon = 'bi bi-check-circle text-success';
        //             break;
        //         case type = 'request':
        //             $notif_icon = 'bi bi-info-circle text-primary';
        //             break;
        //         default:
        //             $notif_icon = 'bi bi-exclamation-circle text-warning';
        //     }
        //     document.getElementById("notif_content").innerHTML += `<li class="notification-item">
        //                 <i class="${$notif_icon}"></i>
        //                 <div>
        //                     <h4>${type}</h4>
        //                     <p>${content}</p>
        //                     <p>${when} days ago</p>
        //                 </div>
        //             </li>

        //             <li>
        //                 <hr class="dropdown-divider">
        //             </li>`;
        // }
    }
    window.addEventListener("load", not_header());
</script>