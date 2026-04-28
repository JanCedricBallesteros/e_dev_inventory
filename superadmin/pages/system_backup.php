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

function sb_table_exists($table)
{
    global $db_connect;
    $tableEsc = escape($db_connect, $table);
    $res = call_mysql_query("SHOW TABLES LIKE '{$tableEsc}'");
    return $res && mysqli_num_rows($res) > 0;
}

function sb_column_exists($table, $column)
{
    global $db_connect;
    if (!sb_table_exists($table)) return false;
    $tableSafe = str_replace('`', '``', $table);
    $columnEsc = escape($db_connect, $column);
    $res = call_mysql_query("SHOW COLUMNS FROM `{$tableSafe}` LIKE '{$columnEsc}'");
    return $res && mysqli_num_rows($res) > 0;
}

function sb_quote_value($value)
{
    if ($value === null) {
        return 'NULL';
    }
    if (is_bool($value)) {
        return $value ? '1' : '0';
    }
    if (is_int($value) || is_float($value)) {
        return (string)$value;
    }
    $escaped = str_replace(array("\\", "\n", "\r", "\t", "\x1a", "'"), array("\\\\", "\\n", "\\r", "\\t", "\\Z", "\\'"), (string)$value);
    return "'" . $escaped . "'";
}

function sb_sql_literal($value)
{
    if ($value === null) return 'NULL';
    if (is_bool($value)) return $value ? '1' : '0';
    if (is_int($value) || is_float($value)) return (string)$value;
    return sb_quote_value($value);
}

function sb_dump_table_schema($table)
{
    $sql = array();
    $tableSafe = str_replace('`', '``', $table);
    $sql[] = "-- --------------------------------------------------------";
    $sql[] = "-- Table structure for table `{$tableSafe}`";
    $sql[] = "-- --------------------------------------------------------";
    $sql[] = "DROP TABLE IF EXISTS `{$tableSafe}`;";
    $res = call_mysql_query("SHOW CREATE TABLE `{$tableSafe}`");
    if ($res && ($row = call_mysql_fetch_array($res))) {
        $createSql = $row['Create Table'] ?? array_values($row)[1] ?? '';
        $sql[] = $createSql . ';';
    }
    return implode("\n", $sql);
}

function sb_pick_change_column($table)
{
    $preferred = array('updated_at', 'modified_at', 'last_updated', 'created_at', 'event_time', 'date_log', 'login_date', 'logout_date');
    foreach ($preferred as $column) {
        if (sb_column_exists($table, $column)) {
            return $column;
        }
    }
    return '';
}

function sb_dump_table_data($table, $cutoff = null)
{
    $tableSafe = str_replace('`', '``', $table);
    $columnsRes = call_mysql_query("SHOW COLUMNS FROM `{$tableSafe}`");
    if (!$columnsRes) {
        return '';
    }

    $columns = array();
    while ($col = call_mysql_fetch_array($columnsRes)) {
        $columns[] = $col['Field'];
    }

    if (empty($columns)) {
        return '';
    }

    $where = '';
    $changeColumn = $cutoff ? sb_pick_change_column($table) : '';
    if ($cutoff && $changeColumn !== '') {
        $cutoffEsc = escape($GLOBALS['db_connect'], $cutoff);
        $where = " WHERE `{$changeColumn}` >= '{$cutoffEsc}'";
    }

    $query = call_mysql_query("SELECT * FROM `{$tableSafe}`{$where}");
    if (!$query || mysqli_num_rows($query) === 0) {
        return '';
    }

    $lines = array();
    while ($row = call_mysql_fetch_array($query, MYSQLI_ASSOC)) {
        $values = array();
        foreach ($columns as $column) {
            $values[] = sb_sql_literal(array_key_exists($column, $row) ? $row[$column] : null);
        }
        $lines[] = "INSERT INTO `{$tableSafe}` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $values) . ");";
    }
    return implode("\n", $lines);
}

function sb_list_tables()
{
    $tables = array();
    $res = call_mysql_query('SHOW TABLES');
    if ($res) {
        while ($row = call_mysql_fetch_array($res, MYSQLI_NUM)) {
            if (!empty($row[0])) {
                $tables[] = $row[0];
            }
        }
    }
    sort($tables);
    return $tables;
}

function sb_build_backup_sql($mode, $cutoff = null)
{
    $dbName = DB_NAME;
    $now = date('Y-m-d H:i:s');
    $tables = sb_list_tables();
    $sql = array();
    $sql[] = "-- e_inventory {$mode} backup";
    $sql[] = "-- Generated: {$now}";
    if ($cutoff) {
        $sql[] = "-- Cutoff: {$cutoff}";
    }
    $sql[] = "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';";
    $sql[] = "SET AUTOCOMMIT = 0;";
    $sql[] = "START TRANSACTION;";
    $sql[] = "SET time_zone = '+00:00';";
    $sql[] = "";
    $sql[] = "CREATE DATABASE IF NOT EXISTS `{$dbName}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;";
    $sql[] = "USE `{$dbName}`;";
    $sql[] = "";

    foreach ($tables as $table) {
        $sql[] = sb_dump_table_schema($table);
        $sql[] = '';
        $dataSql = sb_dump_table_data($table, $mode === 'differential' ? $cutoff : null);
        if ($dataSql !== '') {
            $sql[] = $dataSql;
            $sql[] = '';
        } elseif ($mode === 'differential') {
            $sql[] = "-- No changed rows exported for `{$table}`.";
            $sql[] = '';
        }
    }

    $sql[] = "COMMIT;";
    return implode("\n", $sql);
}

$mode = isset($_GET['mode']) ? strtolower(trim((string)$_GET['mode'])) : '';
$cutoff = isset($_GET['cutoff']) ? trim((string)$_GET['cutoff']) : '';

if ($mode === 'download') {
    $backupType = isset($_GET['type']) ? strtolower(trim((string)$_GET['type'])) : 'full';
    $cutoffTs = '';
    if ($backupType === 'differential' && $cutoff !== '') {
        $cutoffTs = date('Y-m-d H:i:s', strtotime(str_replace('T', ' ', $cutoff)));
        if (!$cutoffTs || $cutoffTs === '1970-01-01 00:00:00') {
            $cutoffTs = '';
        }
    }
    $sql = sb_build_backup_sql($backupType, $cutoffTs);
    $filename = 'e_inventory_' . $backupType . '_' . date('Ymd_His') . '.sql';

    header('Content-Type: application/sql; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($sql));
    echo $sql;
    exit();
}
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
        }
        .section-card { border: 1px solid var(--dash-border); border-radius: 12px; box-shadow: 0 2px 6px rgba(0,0,0,0.06); }
        .section-card .card-header { background: var(--bg-eclearance-rgb); color: #fff; border-radius: 12px 12px 0 0; font-weight: 600; }
        .toolbar-grid { display: grid; grid-template-columns: minmax(240px, 1fr) minmax(220px, 260px) auto; gap: 10px; align-items: end; }
        .filter-label { font-size: 12px; color: #6c757d; margin-bottom: 4px; }
        .toolbar-actions { display: flex; flex-wrap: wrap; gap: 8px; justify-content: flex-end; }
        @media (max-width: 992px) {
            .toolbar-grid { grid-template-columns: 1fr; }
            .toolbar-actions { justify-content: flex-start; }
        }
        .hint-box { border: 1px solid var(--dash-border); border-radius: 10px; background: #fff; padding: 14px; }
        .hint-title { font-weight: 600; color: var(--dash-ink); }
        .hint-text { color: var(--dash-muted); font-size: 0.95rem; }
    </style>
</head>

<body class="d-flex flex-column h-100">

    <?php
    include_once DOMAIN_PATH . '/global/header.php';
    include_once DOMAIN_PATH . '/global/sidebar.php';
    ?>

    <main id="main" class="main">
        <div class="pagetitle">
            <h1 class="h4 fw-semibold mb-1">System Backup</h1>
            <p class="text-muted small mb-0">Download a full database backup or a change-based differential backup.</p>
        </div>

        <section class="section">
            <div class="card section-card">
                <div class="card-header bg-eclearance text-white fw-semibold">
                    <i class="bi bi-database-fill"></i>&ensp;Database Backup
                </div>
                <div class="card-body mt-3 bg-white">
                    <div class="row g-3 mb-3">
                        <div class="col-lg-7">
                            <div class="hint-box h-100">
                                <div class="hint-title mb-2">Backup options</div>
                                <div class="hint-text mb-2">Full backup exports every table, schema, and row into a single SQL file.</div>
                                <div class="hint-text mb-2">Differential backup exports rows from tables with timestamp columns using the cutoff you choose.</div>
                                <div class="hint-text mb-0">Use the cutoff from your last backup time to keep the differential file compact.</div>
                            </div>
                        </div>
                        <div class="col-lg-5">
                            <div class="hint-box h-100">
                                <div class="hint-title mb-2">Backup notes</div>
                                <div class="hint-text mb-1">Full backup is the safest option for restore.</div>
                                <div class="hint-text mb-1">Differential backup works best after a recent full backup.</div>
                                <div class="hint-text mb-0">File is downloaded directly from this page; nothing is stored on the server.</div>
                            </div>
                        </div>
                    </div>

                    <div class="toolbar-grid mb-3">
                        <div>
                            <div class="filter-label">Differential cutoff</div>
                            <input type="datetime-local" id="cutoff" class="form-control" value="<?= date('Y-m-d\TH:i') ?>">
                        </div>
                        <div>
                            <div class="filter-label">Backup type</div>
                            <select id="backupType" class="form-select">
                                <option value="full">Full backup</option>
                                <option value="differential">Differential backup</option>
                            </select>
                        </div>
                        <div class="toolbar-actions">
                            <button class="btn btn-outline-secondary" id="previewCutoffBtn" type="button">Preview</button>
                            <button class="btn btn-outline-primary" id="downloadBackupBtn" type="button">Download SQL</button>
                        </div>
                    </div>

                    <div class="alert alert-info mb-0" id="backupHint">
                        Full backup downloads all tables. Differential backup uses the cutoff date and only includes changed rows for tables with timestamp columns.
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php include_once FOOTER_PATH; ?>

</body>
<?php include_once DOMAIN_PATH . '/global/include_bottom.php'; ?>
<script>
(function() {
    const typeSelect = document.getElementById('backupType');
    const cutoffInput = document.getElementById('cutoff');
    const hint = document.getElementById('backupHint');

    function refreshHint() {
        if (typeSelect.value === 'differential') {
            hint.className = 'alert alert-warning mb-0';
            hint.textContent = 'Differential backup exports rows changed after the chosen cutoff. Tables without timestamp columns are exported as schema only.';
        } else {
            hint.className = 'alert alert-info mb-0';
            hint.textContent = 'Full backup downloads every table and every row into one SQL file.';
        }
    }

    typeSelect.addEventListener('change', refreshHint);
    refreshHint();

    document.getElementById('previewCutoffBtn').addEventListener('click', function() {
        const cutoff = cutoffInput.value ? cutoffInput.value.replace('T', ' ') : '-';
        hint.className = 'alert alert-secondary mb-0';
        hint.textContent = 'Selected cutoff: ' + cutoff;
    });

    document.getElementById('downloadBackupBtn').addEventListener('click', function() {
        const type = encodeURIComponent(typeSelect.value || 'full');
        const cutoff = encodeURIComponent(cutoffInput.value || '');
        window.location.href = '<?php echo BASE_URL; ?>superadmin/pages/system_backup.php?mode=download&type=' + type + '&cutoff=' + cutoff;
    });
})();
</script>
</html>
