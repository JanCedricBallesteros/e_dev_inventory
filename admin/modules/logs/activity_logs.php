<?php
require_once dirname(__DIR__, 3) . '/config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require VALIDATOR_PATH;
require ISLOGIN;

if (!role_has('ADMIN')) {
    header("Location: " . BASE_URL);
    exit();
}

function is_assoc_activity_detail($array) {
    if (!is_array($array)) {
        return false;
    }
    return array_keys($array) !== range(0, count($array) - 1);
}

function format_activity_detail_value($value) {
    if (is_bool($value)) {
        return $value ? 'Yes' : 'No';
    }
    if ($value === null) {
        return 'None';
    }
    if ($value === '') {
        return '—';
    }
    if (is_scalar($value)) {
        return (string)$value;
    }
    return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function flatten_activity_detail_lines($value, $prefix = '') {
    $lines = [];
    if (is_array($value)) {
        $assoc = is_assoc_activity_detail($value);
        foreach ($value as $key => $val) {
            $indexLabel = is_numeric($key) ? (int)$key + 1 : $key;
            $label = $assoc
                ? ($prefix === '' ? (string)$key : $prefix . ' > ' . $key)
                : ($prefix === '' ? 'Item ' . $indexLabel : $prefix . ' #' . $indexLabel);
            if (is_array($val)) {
                $lines = array_merge($lines, flatten_activity_detail_lines($val, $label));
            } else {
                $lines[] = ($label !== '' ? $label . ': ' : '') . format_activity_detail_value($val);
            }
        }
    } else {
        if ($prefix !== '') {
            $lines[] = $prefix . ': ' . format_activity_detail_value($value);
        } else {
            $lines[] = format_activity_detail_value($value);
        }
    }
    return $lines;
}

$table_array = array();

$select = "
    SELECT
        a.activity_log_id,
        a.user_id,
        a.action,
        a.date_log,
        a.session_id,
        a.user_level,
        u.general_id,
        u.f_name,
        u.m_name,
        u.l_name,
        u.suffix,
        u.email_address,
        u.position,
        u.employment_status_id,
        u.user_role AS roles
    FROM activity_log a
    LEFT JOIN users u ON u.user_id = a.user_id
    ORDER BY a.activity_log_id DESC
";

if ($query = call_mysql_query($select)) {
    if (mysqli_num_rows($query)) {
        while ($data = call_mysql_fetch_array($query)) {
            $data['name'] = get_full_name(
                $data['f_name'] ?? '',
                $data['m_name'] ?? '',
                $data['l_name'] ?? '',
                $data['suffix'] ?? ''
            );
            $roleLabels = role_labels_from_raw($data['user_level'] ?? '');
            if (empty($roleLabels)) {
                $roleLabels = role_labels_from_raw($data['roles'] ?? '');
            }
            $data['role_label'] = !empty($roleLabels) ? implode(', ', $roleLabels) : 'UNKNOWN';

            $adminRelated = false;
            foreach ($roleLabels as $roleLabel) {
                if ($roleLabel === 'ADMIN' || $roleLabel === 'ADMIN STAFF') {
                    $adminRelated = true;
                    break;
                }
            }
            if (!$adminRelated) {
                continue;
            }

            $action_raw = $data['action'] ?? '';
            $data['action_raw'] = $action_raw;
            $data['action_type'] = $action_raw;
            $data['action_status'] = '';
            $data['details_raw'] = '';
            $data['details_pretty'] = '';

            if (strpos($action_raw, ':: DETAILS::') !== false) {
                $parts = explode(':: DETAILS::', $action_raw, 2);
                $left = trim($parts[0] ?? '');
                $details_raw = trim($parts[1] ?? '');
                $data['details_raw'] = $details_raw;

                $left_parts = array_map('trim', explode('::', $left));
                $data['action_type'] = $left_parts[0] ?? $left;
                $data['action_status'] = $left_parts[1] ?? '';

                $decoded = json_decode($details_raw, true);
                if (is_array($decoded)) {
                    $lines = flatten_activity_detail_lines($decoded);
                    if (!empty($lines)) {
                        $data['details_pretty'] = implode("\n", $lines);
                    } else {
                        $data['details_pretty'] = $details_raw;
                    }
                } else {
                    $data['details_pretty'] = $details_raw;
                }
            }

            $table_array[] = $data;
        }
    }
}

$json_table = output($table_array);
?>
<!DOCTYPE html>
<html lang="en" class="h-100">
<head>
    <?php
    include_once META_PATH;
    include_once DOMAIN_PATH . '/global/include_top.php';
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
        @media (max-width: 768px) {
            .superadmin-toolbar { flex-direction: column; align-items: stretch; justify-content: flex-start; gap: 8px; }
            .superadmin-toolbar .toolbar-left { flex: 0 0 auto; }
            .superadmin-toolbar .toolbar-right { width: 100%; display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
            .superadmin-toolbar .toolbar-right .btn { width: 100%; }
        }
        .detail-line { display: flex; gap: 6px; font-size: 0.9rem; line-height: 1.35; }
        .detail-label { font-weight: 600; color: #0d6efd; min-width: 90px; }
        .detail-value { flex: 1; color: #495057; }
        .detail-preview-more { font-size: 0.8rem; color: #6c757d; margin-top: 2px; }
        .detail-cell .detail-view-btn { font-size: 0.8rem; }
        .tabulator { font-size: 0.875rem; }
    </style>
</head>

<body class="d-flex flex-column h-100">

    <?php
    include_once DOMAIN_PATH . '/global/header.php';
    include_once DOMAIN_PATH . '/global/sidebar.php';
    ?>

    <main id="main" class="main">
        <section class="section">
            <div class="card">
                <div class="card-header bg-eclearance text-white fw-semibold d-flex align-items-center justify-content-between">
                    <div>
                        <i class="bi bi-clock-history"></i>&ensp;Activity Logs
                    </div>
                </div>
                <div class="card-body mt-3 bg-white">
                    <div class="superadmin-toolbar mb-3">
                        <div class="toolbar-left d-flex gap-2">
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                                <input type="text" class="form-control" id="globalSearch" placeholder="Search logs...">
                            </div>
                        </div>
                        <div class="toolbar-right">
                            <button class="btn btn-sm btn-outline-secondary" id="download-csv">Download CSV</button>
                            <button class="btn btn-sm btn-outline-secondary" id="download-json">Download JSON</button>
                            <button class="btn btn-sm btn-outline-primary" id="download-xlsx">Download XLSX</button>
                            <button class="btn btn-sm btn-outline-secondary" id="print-table">Print</button>
                        </div>
                    </div>
                    <div id="activity-log-table" class="table table-bordered tabulator"></div>
                </div>
            </div>
        </section>
    </main>

    <!-- Cell Detail Modal -->
    <div class="modal fade" id="cellDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cellDetailModalTitle"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                        <div id="cellDetailContent" class="detail-modal-content" style="word-break: break-word;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <?php include_once FOOTER_PATH; ?>

</body>
<?php include_once DOMAIN_PATH . '/global/include_bottom.php'; ?>
</html>
<script>
(function() {
    var table_data = <?php echo $json_table ? $json_table : '[]'; ?>;
    var total_record = Array.isArray(table_data) ? table_data.length : 0;

    function record_details(values) {
        if (values && values.length) {
            return values.length + ' of ' + total_record;
        }
        return '0 of ' + total_record;
    }

    function splitDetailLines(raw) {
        if (!raw) return [];
        return raw.split('\n').map(function(line) {
            return line.trim();
        }).filter(function(line) { return line.length; });
    }

    function buildDetailList(lines) {
        const wrapper = document.createElement('div');
        wrapper.className = 'detail-lines-wrapper';
        lines.forEach(function(line) {
            const row = document.createElement('div');
            row.className = 'detail-line';
            const idx = line.indexOf(':');
            if (idx !== -1) {
                const label = document.createElement('span');
                label.className = 'detail-label';
                label.textContent = line.slice(0, idx).trim();
                const value = document.createElement('span');
                value.className = 'detail-value';
                value.textContent = line.slice(idx + 1).trim();
                row.append(label, value);
            } else {
                row.textContent = line;
            }
            wrapper.appendChild(row);
        });
        return wrapper;
    }

    function showDetailModal(lines) {
        const modalTitle = document.getElementById('cellDetailModalTitle');
        modalTitle.innerHTML = '<i class="bi bi-card-text"></i>&ensp;Details';
        const content = document.getElementById('cellDetailContent');
        content.innerHTML = '';
        if (!lines.length) {
            content.textContent = 'No additional details provided.';
        } else {
            content.appendChild(buildDetailList(lines));
        }
        $('#cellDetailModal').modal('show');
    }

    const table = new Tabulator("#activity-log-table", {
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
        selectableRollingSelection: false,
        headerHozAlign: "center",
        cellVertAlign: "middle",
        printConfig: { columnGroups: false, rowGroups: false },

        columns: [
            { title: "Date & Time", field: "date_log", bottomCalc: record_details, hozAlign: "center", headerFilter: "input", minWidth: 150 },
            { title: "User", field: "name", headerFilter: "input", minWidth: 150 },
            { title: "Role", field: "role_label", hozAlign: "center", headerFilter: "input", minWidth: 130 },
            { title: "Action", field: "action_type", hozAlign: "left", headerFilter: "input", width: 700, minWidth: 200 },
            { title: "Status", field: "action_status", hozAlign: "center", headerFilter: "input", width: 80, minWidth: 80 },
            {
                title: "Details",
                field: "details_pretty",
                headerFilter: "input",
                width: 275,
                minWidth: 150,
                formatter: function(cell) {
                    const raw = cell.getValue() || '';
                    const lines = splitDetailLines(raw);
                    if (!lines.length) return '-';
                    const previewCount = 3;
                    const previewLines = lines.slice(0, previewCount);
                    const container = document.createElement('div');
                    container.className = 'detail-cell';
                    container.appendChild(buildDetailList(previewLines));

                    if (lines.length > previewLines.length) {
                        const hint = document.createElement('div');
                        const remaining = lines.length - previewLines.length;
                        hint.className = 'detail-preview-more';
                        hint.textContent = `+${remaining} more detail${remaining > 1 ? 's' : ''}`;
                        container.appendChild(hint);
                    }

                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'btn btn-link btn-sm px-0 detail-view-btn';
                    btn.textContent = 'View details';
                    btn.addEventListener('click', function(e) {
                        e.stopPropagation();
                        showDetailModal(lines);
                    });
                    container.appendChild(btn);
                    return container;
                }
            }
        ]
    });

    document.getElementById('download-csv').addEventListener('click', function() {
        table.download("csv", "activity_logs.csv");
    });
    document.getElementById('download-json').addEventListener('click', function() {
        table.download("json", "activity_logs.json");
    });
    document.getElementById('download-xlsx').addEventListener('click', function() {
        table.download("xlsx", "activity_logs.xlsx", { sheetName: "Activity Logs" });
    });
    document.getElementById('print-table').addEventListener('click', function() {
        table.print(false, true);
    });

    document.getElementById('globalSearch').addEventListener('input', function() {
        const q = this.value.trim().toLowerCase();
        if (!q) {
            table.clearFilter(true);
            return;
        }
        table.setFilter(function(data) {
            const hay = [
                data.name, data.role_label, data.action_type,
                data.action_status, data.details_pretty, data.date_log
            ].join(' ').toLowerCase();
            return hay.indexOf(q) !== -1;
        });
    });
})();
</script>
