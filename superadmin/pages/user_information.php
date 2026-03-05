<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require VALIDATOR_PATH;
require ISLOGIN;

if (!(role_has("SUPER_ADMIN") || role_has("ADMIN"))) {
    header("Location: " . BASE_URL); //balik sa login then sa login aalamain kung anung role at saang page landing dapat
    exit();
}

function sa_safe_query($sql)
{
    try {
        return call_mysql_query($sql);
    } catch (Throwable $e) {
        return false;
    }
}

function sa_table_exists($table)
{
    global $db_connect;
    $tableEsc = escape($db_connect, $table);
    $res = sa_safe_query("SHOW TABLES LIKE '{$tableEsc}'");
    return $res && mysqli_num_rows($res) > 0;
}

function sa_column_exists($table, $column)
{
    global $db_connect;
    if (!sa_table_exists($table)) return false;
    $tableSafe = str_replace('`', '``', $table);
    $columnEsc = escape($db_connect, $column);
    $res = sa_safe_query("SHOW COLUMNS FROM `{$tableSafe}` LIKE '{$columnEsc}'");
    return $res && mysqli_num_rows($res) > 0;
}

## table
$table_array = array();
$employment_statuses = array();
$hasEmploymentStatusTable = sa_table_exists('employment_status');
$hasUserEmploymentStatusColumn = sa_column_exists('users', 'employment_status_id');
$hasEmploymentStatusPkColumn = sa_column_exists('employment_status', 'employment_status_id');
$hasEmploymentStatusNameColumn = sa_column_exists('employment_status', 'status_name');
$canManageEmploymentStatus = $hasEmploymentStatusTable && $hasUserEmploymentStatusColumn && $hasEmploymentStatusPkColumn && $hasEmploymentStatusNameColumn;

if ($canManageEmploymentStatus) {
    $statusRes = sa_safe_query("SELECT employment_status_id, status_name, status_code FROM employment_status ORDER BY employment_status_id ASC");
    if ($statusRes) {
        while ($s = call_mysql_fetch_array($statusRes)) {
            $employment_statuses[] = array(
                'employment_status_id' => (int)$s['employment_status_id'],
                'status_name' => $s['status_name'],
                'status_code' => $s['status_code']
            );
        }
    }
}

$selectFields = "u.user_id,u.general_id,u.f_name,u.m_name,u.l_name,u.suffix,u.birth_date,u.sex,u.user_role as roles,u.username,u.email_address,u.position,u.status,u.locked";
$selectFields .= $hasUserEmploymentStatusColumn ? ",u.employment_status_id" : ",NULL AS employment_status_id";
$selectFields .= $canManageEmploymentStatus ? ",e.status_name" : ",''
 AS status_name";

$select = "SELECT {$selectFields} FROM users u";
if ($canManageEmploymentStatus) {
    $select .= " LEFT JOIN employment_status e ON u.employment_status_id = e.employment_status_id";
}
$select .= " ORDER BY u.user_id DESC";

if ($query = sa_safe_query($select)) {
    if ($num = mysqli_num_rows($query)) {
        while ($data = call_mysql_fetch_array($query)) {
            $data['name'] = get_full_name($data['f_name'],$data['m_name'],$data['l_name'],$data['suffix']) ;

            $role_ids = json_decode($data['roles'], true);
            $role_ids = is_array($role_ids) ? $role_ids : array();
            $data['role_ids'] = $role_ids;
            $data['is_admin_staff'] = in_array(3, $role_ids, true) || in_array('3', $role_ids, true);

            $data['user_role'] = array();
            foreach ($role_ids as $role) {
                $data['user_role'][] = SYSTEM_ACCESS['E-INVENTORY']['role'][$role];
            }
            $data['user_role'] = isset($data['user_role']) ? implode(', ', $data['user_role']) : '';

            // Combine position and employment status
            $position_display = $data['position'] ?? '';
            $employment_status = $data['status_name'] ?? '';
            if ($position_display !== '' && $employment_status !== '') {
                $data['position_status'] = $position_display . ' - ' . $employment_status;
            } elseif ($employment_status !== '') {
                $data['position_status'] = $employment_status;
            } else {
                $data['position_status'] = $position_display;
            }

            // Access codes (CSM/AST/PO)
            $access_codes = array();
            $accessRes = call_mysql_query("SELECT access_code FROM user_access WHERE user_id = " . (int)$data['user_id'] . " AND is_active = 1");
            if ($accessRes) {
                while ($ar = call_mysql_fetch_array($accessRes)) {
                    if (!empty($ar['access_code'])) {
                        $access_codes[] = strtoupper(trim($ar['access_code']));
                    }
                }
            }
            $data['access_codes'] = $access_codes;

            if ($data['status'] == 1) {
                $data['account_status'] = 'Deactivated';
            } elseif ($data['locked'] == 1) {
                $data['account_status'] = 'Locked';
            } elseif ($data['status'] == 0 && $data['locked'] == 0) {
                $data['account_status'] = 'Active';
            }
            array_push($table_array, $data);
        }
    }
}

$json_table = output($table_array);
?>
<!DOCTYPE html>
<html lang="en" class="h-100">

<head>
    <?php
    include_once DOMAIN_PATH . '/global/meta_data.php'; //meta
    include_once DOMAIN_PATH . '/global/include_top.php'; //links
    ?>
    <style>
        .superadmin-toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
            justify-content: space-between;
        }
        .superadmin-toolbar .toolbar-left { flex: 1 1 260px; min-width: 220px; }
        .superadmin-toolbar .toolbar-right { display: flex; flex-wrap: wrap; gap: 8px; }
        .superadmin-table-actions { display: flex; gap: 6px; justify-content: center; }
        .btn-label { margin-left: 6px; }
        .wrap-text { display: block; white-space: normal; word-break: break-word; line-height: 1.25; }
        @media (max-width: 768px) {
            .superadmin-toolbar { flex-direction: column; align-items: stretch; justify-content: flex-start; gap: 8px; }
            .superadmin-toolbar .toolbar-left { flex: 0 0 auto; }
            .superadmin-toolbar .toolbar-right { width: 100%; display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
            .superadmin-toolbar .toolbar-right .btn { width: 100%; }
            .superadmin-table-actions .btn { padding: 4px 6px; }
            .btn-label { display: none; }
            .wrap-text { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        }
    </style>
</head>

<body class="d-flex flex-column h-100">

    <?php
    include_once DOMAIN_PATH . '/global/header.php'; //header
    include_once DOMAIN_PATH . '/global/sidebar.php'; //sidebar
    ?>

    <main id="main" class="main">
        <section class="section">
            <div class="card">
                <div class="card-header bg-eclearance text-white fw-semibold d-flex align-items-center justify-content-between">
                    <div>
                        <i class="fas fa-user"></i>&ensp;User Information
                    </div>
                </div>
                <div class="card-body mt-3 bg-white">
                    <div class="superadmin-toolbar mb-3">
                        <div class="toolbar-left">
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                                <input type="text" class="form-control" id="globalSearch" placeholder="Search users...">
                            </div>
                        </div>
                        <div class="toolbar-right">
                            <button class="btn btn-sm btn-outline-secondary" id="download-csv">Download CSV</button>
                            <button class="btn btn-sm btn-outline-secondary" id="download-json">Download JSON</button>
                            <button class="btn btn-sm btn-outline-primary" id="download-xlsx">Download XLSX</button>
                            <button class="btn btn-sm btn-outline-secondary" id="print-table">Print</button>
                        </div>
                    </div>
                    <div id="user-table" class="table table-bordered tabulator"></div>
                </div>
            </div>
        </section>

    </main><!-- End #main -->

    <?php include_once FOOTER_PATH; ?>

    <!-- Employment Status Modal -->
    <div class="modal fade" id="employmentStatusModal" tabindex="-1" aria-labelledby="employmentStatusModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="employmentStatusModalLabel">Update Employment Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="userInfo"></p>
                    <form id="employmentStatusForm">
                        <input type="hidden" id="userId" name="user_id">
                        <?php if ($canManageEmploymentStatus) { ?>
                            <div class="mb-3">
                                <label for="employmentStatusSelect" class="form-label">Employment Status</label>
                                <select class="form-select" id="employmentStatusSelect" name="employment_status_id" required>
                                    <option value="">-- Select Employment Status --</option>
                                    <?php foreach ($employment_statuses as $status): ?>
                                        <option value="<?php echo (int)$status['employment_status_id']; ?>">
                                            <?php echo htmlspecialchars($status['status_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php } else { ?>
                            <div class="alert alert-warning mb-0">
                                Employment Status is unavailable in this database schema.
                            </div>
                        <?php } ?>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" id="employmentStatusCancelBtn">Cancel</button>
                    <button type="button" class="btn btn-primary btn-sm" id="saveEmploymentStatusBtn" <?php echo $canManageEmploymentStatus ? '' : 'disabled'; ?>>Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Access Modaaaaal -->
    <div class="modal fade" id="accessModal" tabindex="-1" aria-labelledby="accessModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="accessModalLabel">Update Access</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="accessUserInfo"></p>
                    <form id="accessForm">
                        <input type="hidden" id="accessUserId" name="user_id">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="accessCSM" value="CSM">
                            <label class="form-check-label" for="accessCSM">Consumable (CSM)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="accessAST" value="AST">
                            <label class="form-check-label" for="accessAST">Non-Consumable (AST)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="accessPO" value="PO">
                            <label class="form-check-label" for="accessPO">Procurement (PO)</label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" id="accessCancelBtn">Cancel</button>
                    <button type="button" class="btn btn-primary btn-sm" id="saveAccessBtn">Save Access</button>
                </div>
            </div>
        </div>
    </div>

</body>
<?php include_once DOMAIN_PATH . '/global/include_bottom.php'; ?>

<script>
document.addEventListener("DOMContentLoaded", function () {
    if (typeof window.error_notif !== 'function') {
        window.error_notif = function(msg) {
            var text = String(msg || 'An error occurred.');
            if (window.Swal && typeof window.Swal.fire === 'function') {
                window.Swal.fire({ icon: 'error', title: 'Error', text: text });
                return;
            }
            if (window.jQuery && typeof window.jQuery.notify === 'function') {
                window.jQuery.notify({ message: text }, { type: 'danger', placement: { from: 'top', align: 'right' }, delay: 3000 });
            }
        };
    }
    if (typeof window.success_notif !== 'function') {
        window.success_notif = function(msg) {
            var text = String(msg || 'Success.');
            if (window.Swal && typeof window.Swal.fire === 'function') {
                window.Swal.fire({ icon: 'success', title: 'Success', text: text });
                return;
            }
            if (window.jQuery && typeof window.jQuery.notify === 'function') {
                window.jQuery.notify({ message: text }, { type: 'success', placement: { from: 'top', align: 'right' }, delay: 3000 });
            }
        };
    }

    const CAN_MANAGE_EMPLOYMENT_STATUS = <?php echo $canManageEmploymentStatus ? 'true' : 'false'; ?>;
    var total_record = 0;
    var table_data = <?php echo $json_table . ";\r\n" ?>;
    total_record = table_data.length;
    var employmentStatusMap = <?php echo json_encode(array_reduce($employment_statuses, function($carry, $item) {
        $carry[(string)$item['employment_status_id']] = $item['status_name'];
        return $carry;
    }, array())); ?>;

    // Cancel button handlers
    document.getElementById('employmentStatusCancelBtn').addEventListener('click', function() {
        $('#employmentStatusModal').modal('hide');
    });

    document.getElementById('accessCancelBtn').addEventListener('click', function() {
        $('#accessModal').modal('hide');
    });

    const statusClass = function(cell, formatterParams, onRendered) {
        const span = document.createElement("span");
        const row = cell.getRow();
        const data = row.getData();
        if (data.account_status == 'Active') {
            span.classList.add("status-green");
            span.style.fontSize = "small";
            span.innerHTML = "Active";
        } else if (data.account_status == 'Locked') {
            span.classList.add("status-red");
            span.style.fontSize = "small";
            span.innerHTML = "Locked";
        } else if (data.account_status == 'Deactivated') {
            span.classList.add("status-orange");
            span.style.fontSize = "small";
            span.innerHTML = "Deactivated";
        }
        return span;
    };

    function record_details(values, data, calcParams) {
        if (values && values.length) {
            return values.length + ' of ' + total_record;
        }
    }

    window.showEmploymentStatusModal = function(userId, generalId, userName, currentStatusId, positionStatus) {
        if (!CAN_MANAGE_EMPLOYMENT_STATUS) {
            error_notif('Employment status is unavailable in this database schema.');
            return;
        }
        document.getElementById('userId').value = userId;
        document.getElementById('employmentStatusSelect').value = currentStatusId || '';
        document.getElementById('userInfo').innerHTML = `<strong>${userName}</strong> (ID: ${generalId})<br><small>Current: ${positionStatus}</small>`;
        
        $('#employmentStatusModal').modal('show');
    };

    window.showAccessModal = function(userId, generalId, userName, accessCodes) {
        document.getElementById('accessUserId').value = userId;
        document.getElementById('accessUserInfo').innerHTML = `<strong>${userName}</strong> (ID: ${generalId})`;

        const codes = Array.isArray(accessCodes) ? accessCodes : [];
        document.getElementById('accessCSM').checked = codes.includes('CSM');
        document.getElementById('accessAST').checked = codes.includes('AST');
        document.getElementById('accessPO').checked = codes.includes('PO');

        $('#accessModal').modal('show');
    };

    document.getElementById('saveEmploymentStatusBtn').addEventListener('click', function() {
        if (!CAN_MANAGE_EMPLOYMENT_STATUS) {
            error_notif('Employment status is unavailable in this database schema.');
            return;
        }
        const userId = document.getElementById('userId').value;
        const employmentStatusId = document.getElementById('employmentStatusSelect').value;

        if (!employmentStatusId) {
            error_notif('Please select an employment status');
            return;
        }

        const formData = new FormData();
        formData.append('user_id', userId);
        formData.append('employment_status_id', employmentStatusId);

        fetch('<?php echo BASE_URL; ?>superadmin/actions/update_employment_status.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('HTTP error, status = ' + response.status);
            }
            return response.text();
        })
        .then(text => {
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    success_notif(data.message);
                    $('#employmentStatusModal').modal('hide');
                    
                    // Update table row without page reload
                    const currentRow = table.getRows().find(row => row.getData().user_id == userId);
                    if (currentRow) {
                        const rowData = currentRow.getData();
                        const statusName = employmentStatusMap[String(employmentStatusId)] || '';
                        rowData.employment_status_id = employmentStatusId;
                        rowData.status_name = statusName;
                        if (rowData.position && statusName) {
                            rowData.position_status = rowData.position + ' - ' + statusName;
                        } else if (statusName) {
                            rowData.position_status = statusName;
                        } else {
                            rowData.position_status = rowData.position || '';
                        }
                        currentRow.update(rowData);
                    }
                } else {
                    error_notif(data.message || 'Failed to update employment status');
                }
            } catch (e) {
                console.error('JSON Parse Error:', e);
                console.error('Response:', text);
                error_notif('Error parsing server response. Check console for details.');
            }
        })
        .catch(error => {
            console.error('Fetch Error:', error);
            error_notif('Error: ' + error.message);
        });
    });

    document.getElementById('saveAccessBtn').addEventListener('click', function() {
        const userId = document.getElementById('accessUserId').value;
        const access = [];
        if (document.getElementById('accessCSM').checked) access.push('CSM');
        if (document.getElementById('accessAST').checked) access.push('AST');
        if (document.getElementById('accessPO').checked) access.push('PO');

        const formData = new FormData();
        formData.append('user_id', userId);
        formData.append('access', JSON.stringify(access));

        fetch('<?php echo BASE_URL; ?>superadmin/actions/update_user_access.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('HTTP error, status = ' + response.status);
            }
            return response.text();
        })
        .then(text => {
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    success_notif(data.message || 'Access updated');
                    $('#accessModal').modal('hide');

                    const currentRow = table.getRows().find(row => row.getData().user_id == userId);
                    if (currentRow) {
                        const rowData = currentRow.getData();
                        rowData.access_codes = access;
                        currentRow.update(rowData);
                    }
                } else {
                    error_notif(data.message || 'Failed to update access');
                }
            } catch (e) {
                console.error('JSON Parse Error:', e);
                console.error('Response:', text);
                error_notif('Error parsing server response. Check console for details.');
            }
        })
        .catch(error => {
            console.error('Fetch Error:', error);
            error_notif('Error: ' + error.message);
        });
    });

    const table = new Tabulator("#user-table", {
            data: table_data,
            layout: "fitColumns",
            pagination: "local",
            paginationSize: 10,
            paginationSizeSelector: [5, 10, 20, 50, true],
            height: "700px",
            responsiveLayout: "collapse",
            printAsHtml: true,
            headerFilterPlaceholder: "Search",
            placeholder: "No Data Found",
            movableColumns: true,
            selectable: true,
            printConfig: {
                columnGroups: false,
                rowGroups: false,
            },
            selectableRollingSelection: false,
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
                    minWidth: 100,
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
                    formatter: function(cell) {
                        const v = cell.getValue() || '';
                        const span = document.createElement('span');
                        span.className = 'wrap-text';
                        span.textContent = v;
                        span.title = v;
                        return span;
                    },
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
                    formatter: function(cell) {
                        const v = cell.getValue() || '';
                        const span = document.createElement('span');
                        span.className = 'wrap-text';
                        span.textContent = v;
                        span.title = v;
                        return span;
                    },
                    minWidth: 200,
					vertAlign: 'middle',
					hozAlign: "center",
                    headerHozAlign: 'center'
                },
                {
                    title: "Position & Status",
                    field: "position_status",
                    formatter: function(cell) {
                        const v = cell.getValue() || '';
                        const span = document.createElement('span');
                        span.className = 'wrap-text';
                        span.textContent = v;
                        span.title = v;
                        return span;
                    },
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
                    field: "user_role",
                    formatter: function(cell) {
                        const v = cell.getValue() || '';
                        const span = document.createElement('span');
                        span.className = 'wrap-text';
                        span.textContent = v;
                        span.title = v;
                        return span;
                    },
                    headerFilter: "input",
                    headerFilterFunc: "like",
                    headerFilterParams: {
                        allowEmpty: true
                    },
                    minWidth: 120,
					vertAlign: 'middle',
					hozAlign: "center",
                    headerHozAlign: 'center'
                },
                {
                    title: "Access",
                    field: "access_codes",
                    formatter: function(cell) {
                        const data = cell.getRow().getData();
                        if (!data.is_admin_staff) return 'N/A';
                        const v = cell.getValue() || [];
                        return Array.isArray(v) && v.length ? v.join(', ') : '-';
                    },
                    headerFilter: "input",
                    headerFilterFunc: "like",
                    headerFilterParams: { allowEmpty: true },
                    minWidth: 100,
                    vertAlign: 'middle',
                    hozAlign: "center",
                    headerHozAlign: 'center'
                },
                {
                    title: "Status",
                    field: "account_status",
                    formatter: statusClass,
                    minWidth: 100,
                    headerHozAlign: 'center'
                },
                {
                    title: "Actions",
                    field: "user_id",
                    formatter: function(cell, formatterParams, onRendered) {
                        const wrap = document.createElement("div");
                        wrap.className = "superadmin-table-actions";

                        const btnStatus = document.createElement("button");
                        btnStatus.className = "btn btn-sm btn-primary";
                        btnStatus.innerHTML = "<i class='bi bi-pencil'></i><span class='btn-label'>Status</span>";
                        if (!CAN_MANAGE_EMPLOYMENT_STATUS) {
                            btnStatus.disabled = true;
                            btnStatus.title = "Employment status is unavailable in this database schema";
                        } else {
                            btnStatus.addEventListener("click", function(e) {
                                e.preventDefault();
                                const row = cell.getRow();
                                const data = row.getData();
                                showEmploymentStatusModal(data.user_id, data.general_id, data.name, data.employment_status_id, data.position_status);
                            });
                        }

                        wrap.appendChild(btnStatus);

                        const row = cell.getRow();
                        const data = row.getData();
                        if (data.is_admin_staff) {
                            const btnAccess = document.createElement("button");
                            btnAccess.className = "btn btn-sm btn-outline-primary";
                            btnAccess.innerHTML = "<i class='bi bi-shield-lock'></i><span class='btn-label'>Access</span>";
                            btnAccess.addEventListener("click", function(e) {
                                e.preventDefault();
                                showAccessModal(data.user_id, data.general_id, data.name, data.access_codes || []);
                            });
                            wrap.appendChild(btnAccess);
                        }
                        return wrap;
                    },
                    minWidth: 180,
                    headerHozAlign: 'center',
                    widthGrow: 1,
                    hozAlign: 'center'
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

        function applyGlobalSearch() {
            const val = (document.getElementById('globalSearch').value || '').toLowerCase().trim();
            if (!val) {
                table.clearFilter();
                return;
            }
            table.setFilter(function(data) {
                const hay = [
                    data.general_id,
                    data.name,
                    data.username,
                    data.email_address,
                    data.position_status,
                    data.user_role,
                    (data.access_codes || []).join(' ')
                ].join(' ').toLowerCase();
                return hay.includes(val);
            });
        }

        document.getElementById('globalSearch').addEventListener('input', applyGlobalSearch);

        function adjustTableHeight() {
            if (!table) return;
            const h = window.innerWidth < 768 ? 520 : 700;
            table.setHeight(h);
        }
        adjustTableHeight();
        window.addEventListener('resize', function() {
            clearTimeout(window.__saResize);
            window.__saResize = setTimeout(function() {
                adjustTableHeight();
                table.redraw(true);
            }, 150);
        });

        function reflowTableAfterSidebar() {
            if (!table) return;
            clearTimeout(window.__saSidebarTimer);
            window.__saSidebarTimer = setTimeout(function() {
                table.redraw(true);
            }, 50);
        }

        const sidebarToggleBtn = document.querySelector('.toggle-sidebar-btn');
        if (sidebarToggleBtn) {
            sidebarToggleBtn.addEventListener('click', reflowTableAfterSidebar);
        }

        const mainEl = document.getElementById('main');
        if (mainEl && window.MutationObserver) {
            const obs = new MutationObserver(function() {
                reflowTableAfterSidebar();
            });
            obs.observe(mainEl, { attributes: true, attributeFilter: ['class', 'style'] });
        }

});
</script>

<?php ## sweetalert msg session
    $msg_success = $session_class->getValue('msg_success');
    if (isset($msg_success) && $msg_success != "") {
        echo "success_notif('" . $msg_success . "');";
        $session_class->dropValue('msg_success');
    }
    $msg_error = $session_class->getValue('msg_error');
    if (isset($msg_error) && $msg_error != "") {
        echo "error_notif('" . $msg_error . "');";
        $session_class->dropValue('msg_error');
    }
    $msg_password = $session_class->getValue('msg_password');
    if (isset($msg_password) && $msg_password != "") {
        echo "password_modal('" . $msg_password['title'] . "','" . $msg_password['content_msg'] . "');";
        $session_class->dropValue('msg_password');
    }
    ?>
</script>

</html>
