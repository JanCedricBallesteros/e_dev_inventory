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
<script type="text/javascript" src="<?php echo BASE_URL; ?>assets/dashboard/js/plugin/webfont/webfont.min.js?v=<?php echo FILE_VERSION; ?>"></script>
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
<script type="text/javascript" src="<?php echo BASE_URL; ?>assets/js/bootstrap.min.js?v=<?php echo FILE_VERSION; ?>"></script>

<!-- MAIN JS Files -->
<script type="text/javascript" src="<?php echo BASE_URL; ?>assets/js/jquery-3.7.1.min.js?v=<?php echo FILE_VERSION; ?>"></script>
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
<script type="text/javascript" src="<?php echo BASE_URL; ?>assets/dashboard/js/custom.min.js?v=<?php echo FILE_VERSION; ?>"></script>

<!-- [DASHBOARD] CORE JS Files -->
<script type="text/javascript" src="<?php echo BASE_URL; ?>assets/dashboard/js/core/popper.min.js?v=<?php echo FILE_VERSION; ?>"></script>

<!-- [DASHBOARD] PLUGIN JS Files -->
<script type="text/javascript" src="<?php echo BASE_URL; ?>assets/dashboard/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js?v=<?php echo FILE_VERSION; ?>"></script>
<script type="text/javascript" src="<?php echo BASE_URL; ?>assets/dashboard/js/plugin/chart.js/chart.min.js?v=<?php echo FILE_VERSION; ?>"></script>
<script type="text/javascript" src="<?php echo BASE_URL; ?>assets/dashboard/js/plugin/jquery.sparkline/jquery.sparkline.min.js?v=<?php echo FILE_VERSION; ?>"></script>
<script type="text/javascript" src="<?php echo BASE_URL; ?>assets/dashboard/js/plugin/chart-circle/circles.min.js?v=<?php echo FILE_VERSION; ?>"></script>
<script type="text/javascript" src="<?php echo BASE_URL; ?>assets/dashboard/js/plugin/bootstrap-notify/bootstrap-notify.min.js?v=<?php echo FILE_VERSION; ?>"></script>
<script type="text/javascript" src="<?php echo BASE_URL; ?>assets/dashboard/js/plugin/jsvectormap/jsvectormap.min.js?v=<?php echo FILE_VERSION; ?>"></script>
<script type="text/javascript" src="<?php echo BASE_URL; ?>assets/dashboard/js/plugin/jsvectormap/world.js?v=<?php echo FILE_VERSION; ?>"></script>
<script type="text/javascript" src="<?php echo BASE_URL; ?>assets/dashboard/js/plugin/sweetalert/sweetalert.min.js?v=<?php echo FILE_VERSION; ?>"></script>

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

<script>
    (function () {
        var hasWrapper = document.querySelector('.wrapper');
        var hasPanel = document.querySelector('.main-panel');
        var sidebar = document.querySelector('.sidebar');
        var header = document.querySelector('.main-header');
        var main = document.querySelector('#main.main');
        if (hasWrapper || hasPanel || !sidebar || !header || !main) return;

        var footer = document.querySelector('footer.footer');

        var wrapper = document.createElement('div');
        wrapper.className = 'wrapper';

        var panel = document.createElement('div');
        panel.className = 'main-panel';

        var anchor = sidebar;
        document.body.insertBefore(wrapper, anchor);
        wrapper.appendChild(sidebar);
        wrapper.appendChild(panel);

        panel.appendChild(header);
        panel.appendChild(main);
        if (footer) {
            panel.appendChild(footer);
        }
    })();
</script>

<script>
    (function () {
        var baseUrl = <?php echo json_encode(BASE_URL); ?>;
        var main = document.querySelector('#main.main');
        if (!main) return;

        var oldTitle = main.querySelector('.pagetitle');
        if (!oldTitle) return;
        if (main.querySelector('.page-header')) return;

        function titleize(value) {
            return String(value || '')
                .replace(/[_-]+/g, ' ')
                .replace(/\s+/g, ' ')
                .trim()
                .replace(/\b\w/g, function (m) { return m.toUpperCase(); });
        }

        function buildCrumbs() {
            var path = (window.location.pathname || '').toLowerCase();
            var file = path.split('/').pop().replace('.php', '');
            var url = new URL(window.location.href);
            var type = (url.searchParams.get('type') || '').toUpperCase();
            var crumbs = [];

            if (path.indexOf('/admin/dashboard/') !== -1) {
                crumbs.push({ label: 'Dashboard', url: '' });
                return crumbs;
            }
            if (path.indexOf('/admin/modules/nonconsumable/') !== -1) {
                crumbs.push({ label: 'Non-Consumable (AST)', url: baseUrl + 'admin/modules/nonconsumable/ast_inventory.php' });
                var mapAst = {
                    'ast_category': 'Item Category',
                    'ast_inventory': 'Inventory',
                    'ast_manage_inventory': 'Add New Item',
                    'ast_qrcode': 'QR Code',
                    'ast_physical_checking': 'Physical Checking'
                };
                crumbs.push({ label: mapAst[file] || titleize(file), url: '' });
                return crumbs;
            }
            if (path.indexOf('/admin/modules/consumable/') !== -1) {
                crumbs.push({ label: 'Consumable (CSM)', url: baseUrl + 'admin/modules/consumable/csm_manage_inventory.php' });
                var mapCsm = {
                    'csm_category': 'Item Category',
                    'csm_manage_inventory': 'Inventory',
                    'csm_manage_invtest': 'Add New Item',
                    'csm_available_items': 'Available Items',
                    'csm_qrcode': 'QR Code',
                    'csm_physical_checking': 'Physical Checking'
                };
                crumbs.push({ label: mapCsm[file] || titleize(file), url: '' });
                return crumbs;
            }
            if (path.indexOf('/admin/modules/transactions/') !== -1) {
                crumbs.push({ label: 'Transactions', url: '' });
                var mapTx = {
                    'requisition': 'Requisition Item',
                    'manage_issuance': 'Property Report',
                    'manage_returns': 'Property Return'
                };
                crumbs.push({ label: mapTx[file] || titleize(file), url: '' });
                if (file === 'requisition' && (type === 'AST' || type === 'CSM')) {
                    crumbs.push({ label: type + ' Queue', url: '' });
                }
                return crumbs;
            }
            if (path.indexOf('/admin/modules/logs/') !== -1) {
                crumbs.push({ label: 'Logs', url: '' });
                crumbs.push({ label: 'Activity Logs', url: '' });
                return crumbs;
            }
            if (path.indexOf('/admin/modules/audit/') !== -1) {
                crumbs.push({ label: 'Audit', url: '' });
                crumbs.push({ label: 'Inventory Audit', url: '' });
                return crumbs;
            }
            crumbs.push({ label: titleize(file), url: '' });
            return crumbs;
        }

        var h1 = oldTitle.querySelector('h1');
        var subtitle = oldTitle.querySelector('p');
        var titleText = (h1 ? h1.textContent : '').trim() || 'Dashboard';
        var crumbs = buildCrumbs();

        var pageHeader = document.createElement('div');
        pageHeader.className = 'page-header';
        pageHeader.innerHTML = '<h3 class="fw-bold mb-3"></h3><ul class="breadcrumbs mb-3"></ul>';
        pageHeader.querySelector('h3').textContent = titleText;

        var ul = pageHeader.querySelector('.breadcrumbs');
        var liHome = document.createElement('li');
        liHome.className = 'nav-home';
        liHome.innerHTML = '<a href="' + baseUrl + 'index.php"><i class="icon-home"></i></a>';
        ul.appendChild(liHome);

        crumbs.forEach(function (crumb) {
            var sep = document.createElement('li');
            sep.className = 'separator';
            sep.innerHTML = '<i class="icon-arrow-right"></i>';
            ul.appendChild(sep);

            var li = document.createElement('li');
            li.className = 'nav-item';
            if (crumb.url) {
                li.innerHTML = '<a href="' + crumb.url + '">' + crumb.label + '</a>';
            } else {
                li.innerHTML = '<a href="#">' + crumb.label + '</a>';
            }
            ul.appendChild(li);
        });

        oldTitle.parentNode.insertBefore(pageHeader, oldTitle);
        if (subtitle && subtitle.textContent.trim()) {
            var sub = document.createElement('p');
            sub.className = 'text-muted small mb-3';
            sub.textContent = subtitle.textContent.trim();
            pageHeader.insertAdjacentElement('afterend', sub);
        }
        oldTitle.remove();
    })();
</script>
