<?php
$header_breadcrumbs = !empty($header_breadcrumbs) ? $header_breadcrumbs : [];
?>
<div class="page-header">
    <h3 class="fw-bold mb-3"><?php echo $page_header_title; ?></h3>
    <ul class="breadcrumbs mb-3">

        <!-- Home -->
        <li class="nav-home">
            <a href="<?php echo BASE_URL . 'index.php'; ?>">
                <i class="icon-home"></i>
            </a>
        </li>

        <?php if (!empty($header_breadcrumbs)): ?>
            <?php foreach ($header_breadcrumbs as $crumb): ?>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>

                <li class="nav-item">
                    <?php if (!empty($crumb['url'])): ?>
                        <a href="<?php echo $crumb['url']; ?>">
                            <?php echo $crumb['label']; ?>
                        </a>
                    <?php else: ?>
                        <a href="#">
                            <?php echo $crumb['label']; ?>
                        </a>
                    <?php endif; ?>
                </li>

            <?php endforeach; ?>
        <?php endif; ?>

    </ul>
</div>