<?php
defined('DOMAIN_PATH') || define('DOMAIN_PATH', dirname(__DIR__, 1));
require DOMAIN_PATH . '/config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require VALIDATOR_PATH;
require ISLOGIN;

## page header title
$general_page_title = "User Log";
$_get_value = strtoupper($_GET['user'] ?? ''); ## change based on key
$page_header_title = ACCESS_NAME[$_get_value] ?? $general_page_title;;
$header_breadcrumbs = [
    ['label' => $general_page_title, 'url' => ''],
    ['label' => $page_header_title, 'url' => '']
];

## verify the access role
if (!($g_user_role == "SUPER_ADMIN")) {
    header("Location: " . BASE_URL . "index.php"); //balik sa login then sa login aalamain kung anung role at saang page landing dapat
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php
    include_once DOMAIN_PATH . '/global/meta_data.php'; ## meta
    include_once DOMAIN_PATH . '/global/include_top.php'; ## links
    ?>
</head>

<body>
    <div class="wrapper">
        <?php
        include_once DOMAIN_PATH . '/global/sidebar.php'; ## sidebar
        ?>

        <div class="main-panel">
            <?php
            include_once DOMAIN_PATH . '/global/header.php'; ## header
            ?>

            <div class="container">
                <div class="page-inner">
                    <?php
                    include_once DOMAIN_PATH . '/global/page_header.php'; ## page header 
                    ?>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="card card-round" style="min-height: 600px;">
                                <div class="card-header bg-custom"></div>
                                <div class="card-body">
                                    <div id="logs-table" class="table table-bordered tabulator"></div>
                                    <div>
                                        <button class="btn btn-sm btn-label-custom btn-round" id="download-csv">Download CSV</button>
                                        <button class="btn btn-sm btn-label-custom btn-round" id="download-json">Download JSON</button>
                                        <button class="btn btn-sm btn-label-custom btn-round" id="download-xlsx">Download XLSX</button>
                                        <button class="btn btn-sm btn-label-custom btn-round" id="print-table">Print</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php
            include_once DOMAIN_PATH . '/global/footer.php'; ## footer
            ?>
        </div>
    </div>
</body>

<?php
include_once DOMAIN_PATH . '/global/include_bottom.php'; ## sidebar
?>

<script>
    (function() {
        var total_record = 0;
        var lastfield = null;

        function record_details(values, data, calcParams) {
            if (values && values.length) {
                return values.length + ' of ' + total_record;
            }
        }

        table = new Tabulator("#logs-table", {
            ajaxSorting: true,
            ajaxFiltering: true,
            height: "700px",
            cellVertAlign: "middle",
            printAsHtml: true,
            headerFilterPlaceholder: "Search",
            layout: "fitColumns",
            placeholder: "No Data Found",
            movableColumns: true,
            selectable: false,
            headerHozAlign: 'center',
            ajaxURL: "<?php echo BASE_URL; ?>admin/table/user_log_table.php",
            ajaxParams: {
                user_type: <?php echo '"' . $_get_value . '"'; ?>,
                load_all: 0,
            },
            ajaxProgressiveLoad: "scroll",
            ajaxProgressiveLoadScrollMargin: 1,
            printConfig: {
                columnGroups: false,
                rowGroups: false,
            },
            ajaxLoader: true,
            ajaxLoaderLoading: 'Fetching data from Database..',
            selectableRollingSelection: false,
            paginationSize: <?php echo QUERY_LIMIT; ?>,

            columns: [{
                    title: "Login Date",
                    field: "login_date",
                    bottomCalc: record_details,
                    titlePrint: "Login",
                    sorter: "string",
                    headerFilter: "input",
                    headerFilterLiveFilter: false
                },
                {
                    title: "Logout Date",
                    field: "logout_date",
                    titlePrint: "Logout",
                    sorter: "string",
                    headerFilter: "input",
                    headerFilterLiveFilter: false
                },
                {
                    title: "Complete Name",
                    field: "name",
                    titlePrint: "Name",
                    sorter: "string",
                    headerFilter: "input",
                    headerFilterLiveFilter: false
                },
                {
                    title: "IP Adress",
                    field: "ip_address",
                    titlePrint: "IP address",
                    sorter: "string",
                    headerFilter: "input",
                    headerFilterLiveFilter: false
                },
                {
                    title: "Device",
                    field: "device",
                    titlePrint: "Device",
                    sorter: "string",
                    headerFilter: "input",
                    formatter: "textarea",
                    headerFilterLiveFilter: false
                },



            ],
            ajaxResponse: function(url, params, response) {
                if (response.total_record) {
                    total_record = response.total_record;
                }
                return response;
            },
            ajaxRequesting: function(url, params) {
                if (typeof this.modules.ajax.showLoader() != "undefined") {
                    this.modules.ajax.showLoader();
                }
            },
        });

        addListener(document.getElementById('download-csv'), "click", function() {
            table.download("csv", "user_log_" + getFormattedTime() + ".csv", {
                bom: true
            });
        });
        addListener(document.getElementById('download-json'), "click", function() {
            table.download("json", "user_log_" + getFormattedTime() + ".json");
        });
        addListener(document.getElementById('download-xlsx'), "click", function() {
            table.download("xlsx", "user_log_" + getFormattedTime() + ".xlsx");
        });
        addListener(document.getElementById('print-table'), "click", function() {
            table.print(false, true);
        });

    })();
</script>

</html>