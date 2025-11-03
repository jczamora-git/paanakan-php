<?php if ($totalPages > 1): ?>
    <nav>
        <ul class="pagination justify-content-center mt-3">
            <!-- First Page -->
            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=1" aria-label="First">
                    <span class="material-icons">first_page</span>
                </a>
            </li>

            <!-- Previous Page -->
            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= max(1, $page - 1) ?>" aria-label="Previous">
                    <span class="material-icons">chevron_left</span>
                </a>
            </li>

            <!-- Page Numbers -->
            <?php
            $startPage = max(1, $page - 2);
            $endPage = min($totalPages, $page + 2);
            for ($i = $startPage; $i <= $endPage; $i++): ?>
                <li class="page-item <?= ($i === $page) ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>

            <!-- Next Page -->
            <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= min($totalPages, $page + 1) ?>" aria-label="Next">
                    <span class="material-icons">chevron_right</span>
                </a>
            </li>

            <!-- Last Page -->
            <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $totalPages ?>" aria-label="Last">
                    <span class="material-icons">last_page</span>
                </a>
            </li>
        </ul>
    </nav>
<?php endif; ?>
