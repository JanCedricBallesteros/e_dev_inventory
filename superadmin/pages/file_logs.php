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

function human_file_size($bytes) {
    $bytes = (int)$bytes;
    if ($bytes < 1024) return $bytes . ' B';
    $units = array('KB', 'MB', 'GB', 'TB');
    $size = $bytes / 1024;
    $unitIndex = 0;
    while ($size >= 1024 && $unitIndex < count($units) - 1) {
        $size /= 1024;
        $unitIndex++;
    }
    return number_format($size, $size >= 10 ? 0 : 1) . ' ' . $units[$unitIndex];
}

function normalize_slash_path($path) {
    return str_replace('\\', '/', (string)$path);
}

function path_to_public_url($absolutePath, $rootPath) {
    $absolutePath = normalize_slash_path($absolutePath);
    $rootPath = rtrim(normalize_slash_path($rootPath), '/');
    if (strpos($absolutePath, $rootPath) !== 0) {
        return '';
    }
    $relative = ltrim(substr($absolutePath, strlen($rootPath)), '/');
    return BASE_URL . $relative;
}

function relative_path_label($absolutePath, $rootPath) {
    $absolutePath = normalize_slash_path($absolutePath);
    $rootPath = rtrim(normalize_slash_path($rootPath), '/');
    if (strpos($absolutePath, $rootPath) !== 0) {
        return basename($absolutePath);
    }
    return ltrim(substr($absolutePath, strlen($rootPath)), '/');
}

function is_image_file($filename) {
    $ext = strtolower(pathinfo((string)$filename, PATHINFO_EXTENSION));
    return in_array($ext, array('jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'), true);
}

function scan_files_in_directory($rootPath, $sourceLabel, $maxFiles = 500) {
    $items = array();
    if (!is_dir($rootPath)) {
        return $items;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($rootPath, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $fileInfo) {
        if (!$fileInfo->isFile()) {
            continue;
        }

        $absolutePath = $fileInfo->getPathname();
        $items[] = array(
            'source' => $sourceLabel,
            'file_name' => $fileInfo->getFilename(),
            'file_path' => normalize_slash_path($absolutePath),
            'relative_path' => relative_path_label($absolutePath, $rootPath),
            'download_url' => path_to_public_url($absolutePath, dirname(__DIR__, 2)),
            'extension' => strtolower(pathinfo($fileInfo->getFilename(), PATHINFO_EXTENSION)),
            'size_bytes' => (int)$fileInfo->getSize(),
            'size_label' => human_file_size($fileInfo->getSize()),
            'modified_ts' => (int)$fileInfo->getMTime(),
            'modified_at' => date('Y-m-d H:i:s', $fileInfo->getMTime()),
            'is_image' => is_image_file($fileInfo->getFilename())
        );
    }

    usort($items, function ($a, $b) {
        if ($a['modified_ts'] === $b['modified_ts']) {
            return strcmp($a['file_path'], $b['file_path']);
        }
        return $b['modified_ts'] <=> $a['modified_ts'];
    });

    return array_slice($items, 0, $maxFiles);
}

$appRoot = dirname(__DIR__, 2);
$sources = array(
    array('label' => 'Uploads', 'path' => $appRoot . '/upload'),
    array('label' => 'Exports', 'path' => $appRoot . '/db/exports')
);

$table_array = array();
foreach ($sources as $source) {
    $table_array = array_merge($table_array, scan_files_in_directory($source['path'], $source['label']));
}

usort($table_array, function ($a, $b) {
    if ($a['modified_ts'] === $b['modified_ts']) {
        return strcmp($a['file_path'], $b['file_path']);
    }
    return $b['modified_ts'] <=> $a['modified_ts'];
});

$totalFiles = count($table_array);
$uploadFiles = 0;
$exportFiles = 0;
$totalBytes = 0;
foreach ($table_array as $row) {
    $totalBytes += (int)($row['size_bytes'] ?? 0);
    if (($row['source'] ?? '') === 'Uploads') $uploadFiles++;
    if (($row['source'] ?? '') === 'Exports') $exportFiles++;
}

$json_table = json_encode($table_array, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
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
        .dash-header{ border: 1px solid var(--dash-border); border-radius: 12px; padding: 16px 18px; background: #fff; }
        .dash-header h1{ color: var(--dash-ink); font-weight: 700; }
        .dash-header p{ color: var(--dash-muted); }
        .summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; }
        .summary-card { border: 1px solid var(--dash-border); border-radius: 12px; padding: 12px 14px; background: #fff; }
        .summary-label { font-size: 12px; color: var(--dash-muted); text-transform: uppercase; letter-spacing: .5px; }
        .summary-value { font-size: 20px; font-weight: 700; color: var(--dash-ink); line-height: 1.2; }
        .summary-sub { font-size: 12px; color: var(--dash-muted); margin-top: 4px; }
        .toolbar-grid { display: grid; grid-template-columns: minmax(240px, 1fr) minmax(160px, 220px) auto; gap: 10px; align-items: end; }
        .filter-label { font-size: 12px; color: #6c757d; margin-bottom: 4px; }
        .file-chip { display: inline-flex; align-items: center; gap: 8px; }
        .file-thumb { width: 40px; height: 40px; border-radius: 8px; object-fit: cover; border: 1px solid #dee2e6; background: #f8f9fa; }
        .file-icon { width: 40px; height: 40px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; border: 1px solid #dee2e6; background: #f8f9fa; color: #0d6efd; font-size: 18px; }
        .file-name { font-weight: 600; color: #1f2937; }
        .file-path { font-size: 12px; color: #6c757d; word-break: break-word; }
        .file-meta { font-size: 12px; color: #495057; }
        .tabulator { font-size: 0.875rem; }
        .tabulator .tabulator-cell { vertical-align: middle; }
        .tabulator .tabulator-row:hover { background: #f8fbff; }
        .toolbar-actions { display: flex; flex-wrap: wrap; gap: 8px; justify-content: flex-end; }
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
            <h1 class="h4 fw-semibold mb-1">File Logs</h1>
            <p class="text-muted small mb-0">Track recently stored files from uploads and exports using the same current admin layout language.</p>
        </div>

        <section class="section">
            <div class="card section-card">
                <div class="card-header bg-eclearance text-white fw-semibold d-flex align-items-center justify-content-between">
                    <span><i class="bi bi-folder2-open"></i>&ensp;File Activity</span>
                    <button type="button" class="btn btn-light btn-sm" id="refreshFilesBtn"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
                </div>
                <div class="card-body mt-3 bg-white">
                    <div class="toolbar-grid mb-3">
                        <div>
                            <div class="filter-label">Search</div>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                                <input type="text" id="globalSearch" class="form-control" placeholder="File name, path, or source">
                            </div>
                        </div>
                        <div>
                            <div class="filter-label">Source</div>
                            <select id="sourceFilter" class="form-select">
                                <option value="">All sources</option>
                                <option value="Uploads">Uploads</option>
                                <option value="Exports">Exports</option>
                            </select>
                        </div>
                        <div class="toolbar-actions">
                            <button class="btn btn-outline-secondary" id="downloadCsvBtn" type="button">CSV</button>
                            <button class="btn btn-outline-secondary" id="downloadJsonBtn" type="button">JSON</button>
                            <button class="btn btn-outline-primary" id="printTableBtn" type="button">Print</button>
                        </div>
                    </div>

                    <div id="fileLogsTable"></div>
                </div>
            </div>
        </section>
    </main>

    <div class="modal fade" id="filePreviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold" id="filePreviewTitle">File Preview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3 align-items-start">
                        <div class="col-md-5 text-center">
                            <img id="filePreviewImage" src="" alt="Preview" class="img-fluid rounded border d-none" style="max-height: 360px; object-fit: contain;">
                            <div id="filePreviewNoImage" class="text-muted small border rounded p-4 bg-light">Preview available for image files only.</div>
                        </div>
                        <div class="col-md-7">
                            <div class="mb-2"><div class="text-muted small">Source</div><div class="fw-semibold" id="filePreviewSource">-</div></div>
                            <div class="mb-2"><div class="text-muted small">File</div><div class="fw-semibold" id="filePreviewName">-</div></div>
                            <div class="mb-2"><div class="text-muted small">Path</div><div class="file-path" id="filePreviewPath">-</div></div>
                            <div class="mb-2"><div class="text-muted small">Size</div><div class="fw-semibold" id="filePreviewSize">-</div></div>
                            <div class="mb-2"><div class="text-muted small">Modified</div><div class="fw-semibold" id="filePreviewModified">-</div></div>
                            <div class="mb-2"><div class="text-muted small">Download</div><a href="#" target="_blank" rel="noopener" id="filePreviewLink">Open file</a></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <?php include_once FOOTER_PATH; ?>

<?php include_once DOMAIN_PATH . '/global/include_bottom.php'; ?>
    <script>
(function() {
    var tableData = <?php echo $json_table ? $json_table : '[]'; ?>;
    var totalFiles = Array.isArray(tableData) ? tableData.length : 0;

    function formatBytes(bytes) {
        bytes = Number(bytes) || 0;
        if (bytes < 1024) return bytes + ' B';
        var units = ['KB', 'MB', 'GB', 'TB'];
        var size = bytes / 1024;
        var index = 0;
        while (size >= 1024 && index < units.length - 1) {
            size /= 1024;
            index++;
        }
        return (size >= 10 ? size.toFixed(0) : size.toFixed(1)) + ' ' + units[index];
    }

    function isImage(row) {
        return !!(row && row.is_image);
    }

    function showPreview(row) {
        var title = document.getElementById('filePreviewTitle');
        var image = document.getElementById('filePreviewImage');
        var noImage = document.getElementById('filePreviewNoImage');
        var source = document.getElementById('filePreviewSource');
        var name = document.getElementById('filePreviewName');
        var path = document.getElementById('filePreviewPath');
        var size = document.getElementById('filePreviewSize');
        var modified = document.getElementById('filePreviewModified');
        var link = document.getElementById('filePreviewLink');

        title.textContent = row.file_name || 'File Preview';
        source.textContent = row.source || '-';
        name.textContent = row.file_name || '-';
        path.textContent = row.file_path || '-';
        size.textContent = formatBytes(row.size_bytes);
        modified.textContent = row.modified_at || '-';
        link.href = row.download_url || '#';

        if (isImage(row) && row.download_url) {
            image.src = row.download_url;
            image.classList.remove('d-none');
            noImage.classList.add('d-none');
        } else {
            image.src = '';
            image.classList.add('d-none');
            noImage.classList.remove('d-none');
        }

        $('#filePreviewModal').modal('show');
    }

    var table = new Tabulator('#fileLogsTable', {
        data: tableData,
        layout: 'fitColumns',
        pagination: 'local',
        paginationSize: 10,
        paginationSizeSelector: [5, 10, 20, 50, true],
        paginationCounter: 'rows',
        placeholder: 'No files found',
        movableColumns: true,
        responsiveLayout: 'collapse',
        cellVertAlign: 'middle',
        printAsHtml: true,
        columns: [
            {
                title: 'File',
                field: 'file_name',
                minWidth: 260,
                formatter: function(cell) {
                    var row = cell.getRow().getData() || {};
                    var wrap = document.createElement('div');
                    wrap.className = 'file-chip';

                    if (row.is_image && row.download_url) {
                        var img = document.createElement('img');
                        img.className = 'file-thumb';
                        img.src = row.download_url;
                        img.alt = row.file_name || 'File';
                        img.title = 'Preview';
                        img.addEventListener('click', function() {
                            showPreview(row);
                        });
                        wrap.appendChild(img);
                    } else {
                        var icon = document.createElement('div');
                        icon.className = 'file-icon';
                        icon.innerHTML = '<i class="bi bi-file-earmark-text"></i>';
                        icon.addEventListener('click', function() {
                            showPreview(row);
                        });
                        wrap.appendChild(icon);
                    }

                    var text = document.createElement('div');
                    var fileName = document.createElement('div');
                    fileName.className = 'file-name';
                    fileName.textContent = row.file_name || '-';
                    var filePath = document.createElement('div');
                    filePath.className = 'file-path';
                    filePath.textContent = row.relative_path || row.file_path || '-';
                    text.appendChild(fileName);
                    text.appendChild(filePath);
                    wrap.appendChild(text);

                    return wrap;
                }
            },
            { title: 'Source', field: 'source', width: 130, hozAlign: 'center' },
            {
                title: 'Size',
                field: 'size_bytes',
                width: 110,
                hozAlign: 'right',
                formatter: function(cell) {
                    return formatBytes(cell.getValue());
                }
            },
            {
                title: 'Modified',
                field: 'modified_at',
                width: 180,
                hozAlign: 'center'
            },
            {
                title: 'Actions',
                field: 'download_url',
                width: 160,
                hozAlign: 'center',
                formatter: function(cell) {
                    var row = cell.getRow().getData() || {};
                    var container = document.createElement('div');
                    container.className = 'd-flex justify-content-center gap-1 flex-wrap';

                    var previewBtn = document.createElement('button');
                    previewBtn.type = 'button';
                    previewBtn.className = 'btn btn-sm btn-outline-primary';
                    previewBtn.innerHTML = '<i class="bi bi-eye"></i>';
                    previewBtn.title = 'Preview';
                    previewBtn.addEventListener('click', function(e) {
                        e.stopPropagation();
                        showPreview(row);
                    });
                    container.appendChild(previewBtn);

                    if (row.download_url) {
                        var link = document.createElement('a');
                        link.className = 'btn btn-sm btn-outline-secondary';
                        link.href = row.download_url;
                        link.target = '_blank';
                        link.rel = 'noopener';
                        link.innerHTML = '<i class="bi bi-download"></i>';
                        link.title = 'Open file';
                        container.appendChild(link);
                    }

                    return container;
                }
            }
        ]
    });

    document.getElementById('globalSearch').addEventListener('input', function() {
        var q = this.value.trim().toLowerCase();
        var source = document.getElementById('sourceFilter').value;
        table.setFilter(function(data) {
            var hay = [data.file_name, data.relative_path, data.file_path, data.source].join(' ').toLowerCase();
            var matchSearch = !q || hay.indexOf(q) !== -1;
            var matchSource = !source || data.source === source;
            return matchSearch && matchSource;
        });
    });

    document.getElementById('sourceFilter').addEventListener('change', function() {
        document.getElementById('globalSearch').dispatchEvent(new Event('input'));
    });

    document.getElementById('downloadCsvBtn').addEventListener('click', function() {
        table.download('csv', 'file_logs.csv');
    });

    document.getElementById('downloadJsonBtn').addEventListener('click', function() {
        table.download('json', 'file_logs.json');
    });

    document.getElementById('printTableBtn').addEventListener('click', function() {
        table.print(false, true);
    });

    document.getElementById('refreshFilesBtn').addEventListener('click', function() {
        window.location.reload();
    });
})();
    </script>

</body>
</html>
