<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require VALIDATOR_PATH;
require ISLOGIN;

if (!role_has("SUPER_ADMIN")) {
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
        u.user_role AS roles,
        u.email_address,
        u.position
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
$total_records = count($table_array);
$unique_users = 0;
$detail_rows = 0;
$user_seen = array();
foreach ($table_array as $row) {
    $uid = trim((string)($row['user_id'] ?? ''));
    if ($uid !== '') {
        $user_seen[$uid] = true;
    }
    if (!empty($row['details_pretty'])) {
        $detail_rows++;
    }
}
$unique_users = count($user_seen);
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
        .section-card .card-header{ background: var(--bg-eclearance-rgb); color: #fff; border-radius: 12px 12px 0 0; font-weight: 600; }
        .toolbar-grid { display: grid; grid-template-columns: minmax(240px, 1fr) auto; gap: 10px; align-items: end; }
        .filter-label { font-size: 12px; color: #6c757d; margin-bottom: 4px; }
        .toolbar-actions { display: flex; flex-wrap: wrap; gap: 8px; justify-content: flex-end; }
        .tabulator { font-size: 0.875rem; }
        .tabulator .tabulator-cell { vertical-align: middle; }
        .tabulator .tabulator-row:hover { background: #f8fbff; }
        .detail-line { display: flex; gap: 6px; font-size: 0.9rem; line-height: 1.35; }
        .detail-label { font-weight: 600; color: #0d6efd; min-width: 90px; }
        .detail-value { flex: 1; color: #495057; }
        .detail-preview-more { font-size: 0.8rem; color: #6c757d; margin-top: 2px; }
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
            <h1 class="h4 fw-semibold mb-1">Activity Logs</h1>
            <p class="text-muted small mb-0">Review action history with the same current admin layout language.</p>
        </div>

        <section class="section">
            <div class="card section-card">
                <div class="card-header bg-eclearance text-white fw-semibold d-flex align-items-center justify-content-between">
                    <span><i class="bi bi-clock-history"></i>&ensp;Activity Log Feed</span>
                    <button type="button" class="btn btn-light btn-sm" id="print-table"><i class="bi bi-printer"></i> Print</button>
                </div>
                <div class="card-body mt-3 bg-white">
                    <div class="toolbar-grid mb-3">
                        <div>

                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                                <input type="text" id="globalSearch" class="form-control" placeholder="Search activity logs...">
                            </div>
                        </div>
                        <div class="toolbar-actions">
                            <button class="btn btn-outline-secondary" id="download-csv" type="button">CSV</button>
                            <button class="btn btn-outline-secondary" id="download-json" type="button">JSON</button>
                            <button class="btn btn-outline-secondary" id="download-xlsx" type="button">XLSX</button>
                            <button class="btn btn-outline-primary" id="print-table-btn" type="button">Print</button>
                        </div>
                    </div>

                    <div id="activity-log-table" class="tabulator"></div>
                </div>
            </div>
        </section>
    </main>

    <div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold" id="detailModalTitle">Activity Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="detailModalContent" style="word-break: break-word;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
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
        const content = document.getElementById('detailModalContent');
        content.innerHTML = '';
        if (!lines.length) {
            content.textContent = 'No additional details provided.';
        } else {
            content.appendChild(buildDetailList(lines));
        }
        $('#detailModal').modal('show');
    }

    const table = new Tabulator("#activity-log-table", {
        data: tableData,
        layout: "fitColumns",
        pagination: "local",
        paginationSize: 10,
        paginationSizeSelector: [5, 10, 20, 50, true],
        paginationCounter: "rows",
        placeholder: "No activity logs found",
        movableColumns: true,
        responsiveLayout: "collapse",
        cellVertAlign: "middle",
        printAsHtml: true,
        columns: [
            { title: "Date & Time", field: "date_log", hozAlign: "center", headerFilter: "input", minWidth: 150 },
            { title: "User", field: "name", headerFilter: "input", minWidth: 150 },
            { title: "Role", field: "role_label", hozAlign: "center", headerFilter: "input", minWidth: 130 },
            { title: "Action", field: "action_type", hozAlign: "left", headerFilter: "input", minWidth: 200 },
            { title: "Status", field: "action_status", hozAlign: "center", headerFilter: "input", minWidth: 100 },
            {
                title: "Details",
                field: "details_pretty",
                headerFilter: "input",
                minWidth: 200,
                formatter: function(cell) {
                    const raw = cell.getValue() || '';
                    const lines = splitDetailLines(raw);
                    if (!lines.length) return '-';
                    const previewCount = 2;
                    const previewLines = lines.slice(0, previewCount);
                    const container = document.createElement('div');
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
                    btn.className = 'btn btn-link btn-sm px-0';
                    btn.style.fontSize = '0.8rem';
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
    document.getElementById('print-table-btn').addEventListener('click', function() {
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

</body>
</html>
