<?php
defined('DOMAIN_PATH') || define('DOMAIN_PATH', dirname(__DIR__, 1));
require DOMAIN_PATH . '/config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require VALIDATOR_PATH;
require ISLOGIN;

## page header title
$general_page_title = "User Information";
$get_user_value = strtoupper($_GET['none'] ?? ''); ## change based on key
$page_header_title = ACCESS_NAME[$get_user_value] ?? $general_page_title;;
$header_breadcrumbs = [
    ['label' => $page_header_title, 'url' => '']
];

if (!($g_user_role == "SUPER_ADMIN")) {
    header("Location: " . BASE_URL . "index.php"); //balik sa login then sa login aalamain kung anung role at saang page landing dapat
    exit();
}

## table
$table_array = array();
$select = "SELECT user_id,general_id,f_name,m_name,l_name,suffix,birth_date,sex,user_role as roles,username,email_address,position,status,locked FROM users ORDER BY user_id DESC";
if ($query = call_mysql_query($select)) {
    if ($num = mysqli_num_rows($query)) {
        while ($data = call_mysql_fetch_array($query)) {
            $data['name'] = get_full_name($data['f_name'], $data['m_name'], $data['l_name'], $data['suffix']);

            $data['user_roles'] = array();
            foreach (json_decode($data['roles']) as $role) {
                $data['user_roles'][] = SYSTEM_ACCESS[GLOBAL_SYSTEM_ACCESS]['role'][$role];
            }
            $data['user_roles'] = isset($data['user_roles']) ? json_encode($data['user_roles']) : '';

            if ($data['status'] == 1) {
                $data['account_status'] = ACCOUNT_STATUS[$data['status']];
            } elseif ($data['locked'] == 1) {
                $data['account_status'] = 'Locked';
            } elseif ($data['status'] == 0 && $data['locked'] == 0) {
                $data['account_status'] = ACCOUNT_STATUS[$data['status']];
            }

            array_push($table_array, $data);
        }
    }
}

$json_table = output($table_array);
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
                                <div class="card-header">
                                    <div class="card-head-row">
                                        <!-- <div class="card-title">User Management</div> -->
                                        <!-- remove if not needed -->
                                        <div class="card-tools">
                                            <a href="#" class="btn btn-label-success btn-round btn-sm me-2">
                                                <span class="btn-label">
                                                    <i class="fa fa-pencil"></i>
                                                </span>
                                                Export
                                            </a>
                                            <a href="#" class="btn btn-label-info btn-round btn-sm">
                                                <span class="btn-label">
                                                    <i class="fa fa-print"></i>
                                                </span>
                                                Print
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div id="user-table" class="table table-bordered tabulator"></div>
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
        var table_data = <?php echo $json_table . ";\r\n" ?>;
        total_record = table_data.length;

        const statusClass = function(cell, formatterParams, onRendered) {
            const span = document.createElement("span");
            const row = cell.getRow();
            const data = row.getData();
            if (data.account_status == 'Active') {
                span.classList.add("badge", "badge-success");
                span.style.fontSize = "small";
                span.innerHTML = "Active";
            } else if (data.account_status == 'Locked') {
                span.classList.add("badge", "badge-warning");
                span.style.fontSize = "small";
                span.innerHTML = "Locked";
            } else if (data.account_status == 'Suspended') {
                span.classList.add("badge", "badge-danger");
                span.style.fontSize = "small";
                span.innerHTML = "Suspended";
            }
            return span;
        };

        function record_details(values, data, calcParams) {
            if (values && values.length) {
                return values.length + ' of ' + total_record;
            }
        }

        const table = new Tabulator("#user-table", {
            data: table_data,
            height: "700px",
            printAsHtml: true,
            headerFilterPlaceholder: "Search",
            layout: "fitDataStretch",
            placeholder: "No Data Found",
            movableColumns: true,
            selectable: true,
            printConfig: {
                columnGroups: false,
                rowGroups: false,
            },
            selectableRollingSelection: false,
            paginationSize: <?php echo QUERY_LIMIT; ?>,
            headerHozAlign: 'center',
            cellVertAlign: "middle",
            columns: [{
                    title: "General ID",
                    field: "general_id",
                    bottomCalc: record_details,
                    vertAlign: 'middle',
                    hozAlign: "center",
                    headerFilter: "input",
                    headerFilterFunc: "like",
                    headerFilterParams: {
                        allowEmpty: true
                    },
                    minWidth: 200,
                    headerHozAlign: 'center'
                },
                {
                    title: "Complete Name",
                    field: "name",
                    headerFilter: "input",
                    headerFilterFunc: "like",
                    headerFilterParams: {
                        allowEmpty: true
                    },
                    formatter: 'textarea',
                    minWidth: 250,
                    headerHozAlign: 'center',
                    vertAlign: 'middle',
                },
                {
                    title: "Username",
                    field: "username",
                    headerFilter: "input",
                    headerFilterFunc: "like",
                    headerFilterParams: {
                        allowEmpty: true
                    },
                    minWidth: 200,
                    vertAlign: 'middle',
                    hozAlign: "center",
                    headerHozAlign: 'center'
                },
                {
                    title: "Email Address",
                    field: "email_address",
                    headerFilter: "input",
                    headerFilterFunc: "like",
                    headerFilterParams: {
                        allowEmpty: true
                    },
                    formatter: 'textarea',
                    minWidth: 200,
                    vertAlign: 'middle',
                    hozAlign: "center",
                    headerHozAlign: 'center'
                },
                {
                    title: "Position",
                    field: "position",
                    formatter: 'textarea',
                    headerFilter: "input",
                    headerFilterFunc: "like",
                    headerFilterParams: {
                        allowEmpty: true
                    },
                    minWidth: 200,
                    vertAlign: 'middle',
                    hozAlign: "center",
                    headerHozAlign: 'center'
                },
                {
                    title: "User Role",
                    field: "user_roles",
                    formatter: 'textarea',
                    headerFilter: "input",
                    headerFilterFunc: "like",
                    headerFilterParams: {
                        allowEmpty: true
                    },
                    minWidth: 300,
                    vertAlign: 'middle',
                    hozAlign: "center",
                    headerHozAlign: 'center'
                },
                {
                    title: "Account Status",
                    field: "account_status",
                    formatter: statusClass,
                    minWidth: 100,
                    vertAlign: 'middle',
                    hozAlign: "center",
                    headerHozAlign: 'center'
                },
            ],
        });

        addListener(document.getElementById('download-csv'), "click", function() {
            table.download("csv", "list_" + getFormattedTime() + ".csv", {
                bom: true
            });
        });
        addListener(document.getElementById('download-json'), "click", function() {
            table.download("json", "list_" + getFormattedTime() + ".json");
        });
        addListener(document.getElementById('download-xlsx'), "click", function() {
            table.download("xlsx", "list_" + getFormattedTime() + ".xlsx");
        });
        addListener(document.getElementById('print-table'), "click", function() {
            table.print(false, true);
        });

    })();
</script>

</html>