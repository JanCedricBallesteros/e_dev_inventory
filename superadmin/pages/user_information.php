<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require VALIDATOR_PATH;
require ISLOGIN;

if (!(role_has("SUPER_ADMIN") || role_has("ADMIN"))) {
    header("Location: " . BASE_URL);
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
        while ($status = call_mysql_fetch_array($statusRes)) {
            $employment_statuses[] = array(
                'employment_status_id' => (int)$status['employment_status_id'],
                'status_name' => $status['status_name'],
                'status_code' => $status['status_code']
            );
        }
    }
}

$selectFields = "u.user_id,u.general_id,u.f_name,u.m_name,u.l_name,u.suffix,u.user_role as roles,u.username,u.email_address,u.position,u.status,u.locked";
$selectFields .= $hasUserEmploymentStatusColumn ? ",u.employment_status_id" : ",NULL AS employment_status_id";
$selectFields .= $canManageEmploymentStatus ? ",e.status_name" : ",'' AS status_name";

$select = "SELECT {$selectFields} FROM users u";
if ($canManageEmploymentStatus) {
    $select .= " LEFT JOIN employment_status e ON u.employment_status_id = e.employment_status_id";
}
$select .= " ORDER BY u.user_id DESC";

if ($query = sa_safe_query($select)) {
    while ($data = call_mysql_fetch_array($query)) {
        $role_ids = json_decode($data['roles'], true);
        $role_ids = is_array($role_ids) ? $role_ids : array();

        if (role_has("ADMIN") && !role_has("SUPER_ADMIN")) {
            $allowedRoles = array('2', '3', 2, 3);
            $hasAllowed = false;
            foreach ($role_ids as $rid) {
                if (in_array($rid, $allowedRoles, true)) {
                    $hasAllowed = true;
                    break;
                }
            }
            if (!$hasAllowed) {
                continue;
            }
        }

        $data['name'] = get_full_name($data['f_name'], '', $data['m_name'], $data['l_name'], $data['suffix']);
        $data['role_ids'] = $role_ids;
        $data['is_admin_staff'] = in_array(3, $role_ids, true) || in_array('3', $role_ids, true);

        $label_list = array();
        foreach ($role_ids as $role) {
            if (isset(SYSTEM_ACCESS['E-INVENTORY']['role'][$role])) {
                $label_list[] = SYSTEM_ACCESS['E-INVENTORY']['role'][$role];
            }
        }
        $data['user_role'] = implode(', ', $label_list);

        $position_display = $data['position'] ?? '';
        $employment_status = $data['status_name'] ?? '';
        if ($position_display !== '' && $employment_status !== '') {
            $data['position_status'] = $position_display . ' - ' . $employment_status;
        } elseif ($employment_status !== '') {
            $data['position_status'] = $employment_status;
        } else {
            $data['position_status'] = $position_display;
        }

        $access_codes = array();
        $accessRes = call_mysql_query("SELECT access_code FROM user_access WHERE user_id = " . (int)$data['user_id'] . " AND is_active = 1");
        if ($accessRes) {
            while ($accessRow = call_mysql_fetch_array($accessRes)) {
                if (!empty($accessRow['access_code'])) {
                    $access_codes[] = strtoupper(trim($accessRow['access_code']));
                }
            }
        }
        $data['access_codes'] = $access_codes;

        if ((int)$data['status'] === 1) {
            $data['account_status'] = 'Deactivated';
        } elseif ((int)$data['locked'] === 1) {
            $data['account_status'] = 'Locked';
        } else {
            $data['account_status'] = 'Active';
        }

        $table_array[] = $data;
    }
}

$json_table = output($table_array);
$json_employment_statuses = json_encode(array_reduce($employment_statuses, function($carry, $item) {
    $carry[(string)$item['employment_status_id']] = $item['status_name'];
    return $carry;
}, array()));
?>
<!DOCTYPE html>
<html lang="en" class="h-100">
<head>
    <?php
    include_once DOMAIN_PATH . '/global/meta_data.php';
    include_once DOMAIN_PATH . '/global/include_top.php';
    ?>
    <link href="<?= BASE_URL ?>assets/css/tabulator_bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --dash-ink: #1f2937;
            --dash-muted: #6c757d;
            --dash-border: #e5e7eb;
            --dash-card: #ffffff;
            --dash-accent: #0d6efd;
            --dash-accent-soft: #eef5ff;
        }
        .section-card { border: 1px solid var(--dash-border); border-radius: 12px; box-shadow: 0 2px 6px rgba(0,0,0,0.06); }
        .section-card .card-header { background: var(--bg-eclearance-rgb); color: #fff; border-radius: 12px 12px 0 0; font-weight: 600; }
        .toolbar-grid { display: grid; grid-template-columns: minmax(240px, 1fr) auto; gap: 10px; align-items: end; }
        .filter-label { font-size: 12px; color: #6c757d; margin-bottom: 4px; }
        .toolbar-actions { display: flex; flex-wrap: wrap; gap: 8px; justify-content: flex-end; }
        .tabulator { font-size: 0.875rem; }
        .tabulator .tabulator-cell { vertical-align: middle; }
        .tabulator .tabulator-row:hover { background: #f8fbff; }
        .superadmin-table-actions { display: flex; gap: 6px; justify-content: center; flex-wrap: wrap; }
        .btn-label { margin-left: 6px; }
        .wrap-text { display: block; white-space: normal; word-break: break-word; line-height: 1.25; }
        @media (max-width: 992px) {
            .toolbar-grid { grid-template-columns: 1fr; }
            .toolbar-actions { justify-content: flex-start; }
        }
    </style>
</head>

<body class="d-flex flex-column h-100">
<?php
include_once DOMAIN_PATH . '/global/header.php';
include_once DOMAIN_PATH . '/global/sidebar.php';
?>

<main id="main" class="main">
    <div class="pagetitle">
        <h1 class="h4 fw-semibold mb-1">User Information</h1>
        <p class="text-muted small mb-0">Manage user accounts and access permissions.</p>
    </div>

    <section class="section">
        <div class="card section-card">
            <div class="card-header bg-eclearance text-white fw-semibold d-flex align-items-center justify-content-between">
                <span><i class="bi bi-people"></i>&ensp;User Directory</span>
                <button type="button" class="btn btn-light btn-sm" id="refresh-users"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
            </div>
            <div class="card-body mt-3 bg-white">
                <div class="toolbar-grid mb-3">
                    <div>

                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                            <input type="text" id="globalSearch" class="form-control" placeholder="Search users...">
                        </div>
                    </div>
                    <div class="toolbar-actions">
                        <button class="btn btn-outline-secondary" id="download-csv" type="button">CSV</button>
                        <button class="btn btn-outline-secondary" id="download-json" type="button">JSON</button>
                        <button class="btn btn-outline-secondary" id="download-xlsx" type="button">XLSX</button>
                        <button class="btn btn-outline-primary" id="print-table" type="button">Print</button>
                    </div>
                </div>

                <div id="user-table" class="tabulator"></div>
            </div>
        </div>
    </section>
</main>

<div class="modal fade" id="employmentStatusModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-semibold" id="employmentStatusModalLabel">Update Employment Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="userInfo" class="mb-3"></p>
                <form id="employmentStatusForm">
                    <input type="hidden" id="userId" name="user_id">
                    <?php if ($canManageEmploymentStatus) { ?>
                        <div class="mb-3">
                            <label for="employmentStatusSelect" class="form-label">Employment Status</label>
                            <select class="form-select" id="employmentStatusSelect" name="employment_status_id" required>
                                <option value="">-- Select Employment Status --</option>
                                <?php foreach ($employment_statuses as $status): ?>
                                    <option value="<?php echo (int)$status['employment_status_id']; ?>"><?php echo htmlspecialchars($status['status_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php } else { ?>
                        <div class="alert alert-warning mb-0">Employment Status is unavailable in this database schema.</div>
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

<div class="modal fade" id="accessModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-semibold" id="accessModalLabel">Update User Access</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="accessUserInfo" class="mb-3"></p>
                <form id="accessForm">
                    <input type="hidden" id="accessUserId" name="user_id">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="accessRadio" id="accessNone" value="">
                        <label class="form-check-label" for="accessNone">None</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="accessRadio" id="accessCSM" value="CSM">
                        <label class="form-check-label" for="accessCSM">Consumable (CSM)</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="accessRadio" id="accessAST" value="AST">
                        <label class="form-check-label" for="accessAST">Non-Consumable (AST)</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="accessRadio" id="accessPO" value="PO">
                        <label class="form-check-label" for="accessPO">Procurement (PO)</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" id="accessCancelBtn">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" id="saveAccessBtn">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<?php include_once FOOTER_PATH; ?>

<?php include_once DOMAIN_PATH . '/global/include_bottom.php'; ?>
<script>
(function() {
    var tableData = <?php echo $json_table ? $json_table : '[]'; ?>;
    var totalRecords = Array.isArray(tableData) ? tableData.length : 0;
    var employmentStatusMap = <?php echo $json_employment_statuses ? $json_employment_statuses : '{}'; ?>;

    function statusClass(cell) {
        const span = document.createElement('span');
        const data = cell.getRow().getData();
        if (data.account_status === 'Active') {
            span.classList.add('status-green');
            span.style.fontSize = 'small';
            span.textContent = 'Active';
        } else if (data.account_status === 'Locked') {
            span.classList.add('status-red');
            span.style.fontSize = 'small';
            span.textContent = 'Locked';
        } else if (data.account_status === 'Deactivated') {
            span.classList.add('status-orange');
            span.style.fontSize = 'small';
            span.textContent = 'Deactivated';
        }
        return span;
    }

    window.showEmploymentStatusModal = function(userId, generalId, userName, currentStatusId, positionStatus) {
        if (!<?php echo $canManageEmploymentStatus ? 'true' : 'false'; ?>) {
            if (typeof error_notif === 'function') error_notif('Employment status is unavailable in this database schema.');
            return;
        }
        document.getElementById('userId').value = userId;
        document.getElementById('employmentStatusSelect').value = currentStatusId || '';
        document.getElementById('userInfo').innerHTML = '<strong>' + userName + '</strong> (ID: ' + generalId + ')<br><small>Current: ' + (positionStatus || '-') + '</small>';
        $('#employmentStatusModal').modal('show');
    };

    window.showAccessModal = function(userId, generalId, userName, accessCodes) {
        document.getElementById('accessUserId').value = userId;
        document.getElementById('accessUserInfo').innerHTML = '<strong>' + userName + '</strong> (ID: ' + generalId + ')';
        const codes = Array.isArray(accessCodes) ? accessCodes : [];
        const selected = codes.length ? codes[0] : '';
        document.querySelectorAll('input[name="accessRadio"]').forEach(function(radio) {
            radio.checked = radio.value === selected;
        });
        $('#accessModal').modal('show');
    };

    const table = new Tabulator('#user-table', {
        data: tableData,
        layout: 'fitColumns',
        pagination: 'local',
        paginationSize: 10,
        paginationSizeSelector: [5, 10, 20, 50, true],
        paginationCounter: 'rows',
        placeholder: 'No users found',
        movableColumns: true,
        responsiveLayout: 'collapse',
        cellVertAlign: 'middle',
        printAsHtml: true,
        columns: [
            { title: 'General ID', field: 'general_id', headerFilter: 'input', minWidth: 100, hozAlign: 'center' },
            { title: 'Name', field: 'name', headerFilter: 'input', minWidth: 180, formatter: function(cell) { var v = cell.getValue() || ''; var span = document.createElement('span'); span.className = 'wrap-text'; span.textContent = v; return span; } },
            { title: 'Username', field: 'username', headerFilter: 'input', minWidth: 150 },
            { title: 'Email', field: 'email_address', headerFilter: 'input', minWidth: 200, formatter: function(cell) { var v = cell.getValue() || ''; var span = document.createElement('span'); span.className = 'wrap-text'; span.textContent = v; return span; } },
            { title: 'Position & Status', field: 'position_status', headerFilter: 'input', minWidth: 200, formatter: function(cell) { var v = cell.getValue() || ''; var span = document.createElement('span'); span.className = 'wrap-text'; span.textContent = v; return span; } },
            { title: 'User Role', field: 'user_role', headerFilter: 'input', minWidth: 150, formatter: function(cell) { var v = cell.getValue() || ''; var span = document.createElement('span'); span.className = 'wrap-text'; span.textContent = v; return span; } },
            { title: 'Access', field: 'access_codes', headerFilter: 'input', minWidth: 120, hozAlign: 'center', formatter: function(cell) { var data = cell.getRow().getData(); if (!data.is_admin_staff) return 'N/A'; var v = cell.getValue() || []; return Array.isArray(v) && v.length ? v.join(', ') : '-'; } },
            { title: 'Status', field: 'account_status', formatter: statusClass, minWidth: 100, hozAlign: 'center' },
            { title: 'Actions', field: 'user_id', minWidth: 180, hozAlign: 'center', formatter: function(cell) {
                const wrap = document.createElement('div');
                wrap.className = 'superadmin-table-actions';
                const rowData = cell.getRow().getData();

                const btnStatus = document.createElement('button');
                btnStatus.className = 'btn btn-sm btn-primary';
                btnStatus.innerHTML = "<i class='bi bi-pencil'></i><span class='btn-label'>Status</span>";
                if (!<?php echo $canManageEmploymentStatus ? 'true' : 'false'; ?>) {
                    btnStatus.disabled = true;
                } else {
                    btnStatus.addEventListener('click', function(e) {
                        e.preventDefault();
                        showEmploymentStatusModal(rowData.user_id, rowData.general_id, rowData.name, rowData.employment_status_id, rowData.position_status);
                    });
                }
                wrap.appendChild(btnStatus);

                if (rowData.is_admin_staff) {
                    const btnAccess = document.createElement('button');
                    btnAccess.className = 'btn btn-sm btn-outline-primary';
                    btnAccess.innerHTML = "<i class='bi bi-shield-lock'></i><span class='btn-label'>Access</span>";
                    btnAccess.addEventListener('click', function(e) {
                        e.preventDefault();
                        showAccessModal(rowData.user_id, rowData.general_id, rowData.name, rowData.access_codes || []);
                    });
                    wrap.appendChild(btnAccess);
                }

                return wrap;
            } }
        ]
    });

    document.getElementById('download-csv').addEventListener('click', function() {
        table.download('csv', 'user_information.csv');
    });
    document.getElementById('download-json').addEventListener('click', function() {
        table.download('json', 'user_information.json');
    });
    document.getElementById('download-xlsx').addEventListener('click', function() {
        table.download('xlsx', 'user_information.xlsx', { sheetName: 'Users' });
    });
    document.getElementById('print-table').addEventListener('click', function() {
        table.print(false, true);
    });

    document.getElementById('refresh-users').addEventListener('click', function() {
        window.location.reload();
    });

    document.getElementById('globalSearch').addEventListener('input', function() {
        const q = this.value.trim().toLowerCase();
        if (!q) {
            table.clearFilter(true);
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
                data.account_status,
                (data.access_codes || []).join(' ')
            ].join(' ').toLowerCase();
            return hay.indexOf(q) !== -1;
        });
    });

    document.getElementById('employmentStatusCancelBtn').addEventListener('click', function() {
        $('#employmentStatusModal').modal('hide');
    });

    document.getElementById('accessCancelBtn').addEventListener('click', function() {
        $('#accessModal').modal('hide');
    });

    document.getElementById('saveEmploymentStatusBtn').addEventListener('click', function() {
        const userId = document.getElementById('userId').value;
        const employmentStatusId = document.getElementById('employmentStatusSelect').value;
        if (!employmentStatusId) {
            if (typeof error_notif === 'function') error_notif('Please select an employment status');
            return;
        }

        const formData = new FormData();
        formData.append('user_id', userId);
        formData.append('employment_status_id', employmentStatusId);

        fetch('<?php echo BASE_URL; ?>superadmin/actions/update_employment_status.php', {
            method: 'POST',
            body: formData
        })
        .then(function(response) { return response.text(); })
        .then(function(text) {
            const data = JSON.parse(text);
            if (data.success) {
                if (typeof success_notif === 'function') success_notif(data.message || 'Employment status updated successfully');
                $('#employmentStatusModal').modal('hide');
                const row = table.getRows().find(function(r) { return String(r.getData().user_id) === String(userId); });
                if (row) {
                    const rowData = row.getData();
                    const statusName = employmentStatusMap[String(employmentStatusId)] || '';
                    rowData.employment_status_id = employmentStatusId;
                    rowData.status_name = statusName;
                    rowData.position_status = rowData.position && statusName ? (rowData.position + ' - ' + statusName) : (statusName || rowData.position || '');
                    row.update(rowData);
                }
            } else if (typeof error_notif === 'function') {
                error_notif(data.message || 'Failed to update employment status');
            }
        })
        .catch(function(error) {
            if (typeof error_notif === 'function') error_notif('Error: ' + error.message);
        });
    });

    document.getElementById('saveAccessBtn').addEventListener('click', function() {
        const userId = document.getElementById('accessUserId').value;
        const selected = document.querySelector('input[name="accessRadio"]:checked');
        const access = (selected && selected.value) ? [selected.value] : [];

        const formData = new FormData();
        formData.append('user_id', userId);
        formData.append('access', JSON.stringify(access));

        fetch('<?php echo BASE_URL; ?>superadmin/actions/update_user_access.php', {
            method: 'POST',
            body: formData
        })
        .then(function(response) { return response.text(); })
        .then(function(text) {
            const data = JSON.parse(text);
            if (data.success) {
                if (typeof success_notif === 'function') success_notif(data.message || 'Access updated');
                $('#accessModal').modal('hide');
                const row = table.getRows().find(function(r) { return String(r.getData().user_id) === String(userId); });
                if (row) {
                    const rowData = row.getData();
                    rowData.access_codes = access;
                    row.update(rowData);
                }
            } else if (typeof error_notif === 'function') {
                error_notif(data.message || 'Failed to update access');
            }
        })
        .catch(function(error) {
            if (typeof error_notif === 'function') error_notif('Error: ' + error.message);
        });
    });
})();
</script>

</body>
</html>