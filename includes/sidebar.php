<aside class="app-sidebar shadow">
    <div class="sidebar-brand text-center py-4">
        <a href="<?= BASE_URL ?>index.php" class="brand-link text-decoration-none">
            <span class="brand-text fw-bold fs-4 text-primary" style="letter-spacing: 1px;">HR MANAGEMENT</span>
        </a>
    </div>
    <div class="sidebar-wrapper">
        <nav class="mt-2">
            <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu" data-accordion="false">
                <?php
                function renderMenu($pdo, $parentId = 0, $userRole, $currentPath)
                {
                    $sql = "
                        SELECT p.* FROM sys_pages p
                        JOIN role_access ra ON p.id = ra.page_id
                        WHERE p.parent_id = ? AND ra.role_key = ?
                        ORDER BY p.sort_order ASC
                    ";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$parentId, $userRole]);
                    $items = $stmt->fetchAll();

                    foreach ($items as $item) {
                        $itemUrl = str_replace('\\', '/', $item['page_url']);
                        
                        // Fetch children that this user has access to
                        $childSql = "
                            SELECT p.* FROM sys_pages p
                            JOIN role_access ra ON p.id = ra.page_id
                            WHERE p.parent_id = ? AND ra.role_key = ?
                            ORDER BY p.sort_order ASC
                        ";
                        $childStmt = $pdo->prepare($childSql);
                        $childStmt->execute([$item['id'], $userRole]);
                        $children = $childStmt->fetchAll();
                        $hasChildren = count($children) > 0;

                        // Logic to determine if this item (or any of its children) is active
                        $isActive = ($currentPath === $itemUrl && $itemUrl !== '#');
                        $isExpanded = false;

                        if ($hasChildren) {
                            foreach ($children as $child) {
                                if ($currentPath === str_replace('\\', '/', $child['page_url'])) {
                                    $isExpanded = true;
                                    break;
                                }
                            }
                        }

                        // AdminLTE standard classes
                        $itemClass = "nav-item";
                        if ($hasChildren && $isExpanded) $itemClass .= " menu-open";
                        
                        $linkClass = "nav-link";
                        if ($isActive || ($hasChildren && $isExpanded)) $linkClass .= " active";

                        echo '<li class="' . $itemClass . '">';
                        echo '<a href="' . ($hasChildren ? '#' : BASE_URL . $itemUrl) . '" class="' . $linkClass . '">';
                        echo '<i class="nav-icon ' . ($item['icon_class'] ?: 'bi bi-circle') . '"></i>';
                        echo '<p>' . htmlspecialchars($item['page_name']);
                        if ($hasChildren) {
                            echo '<i class="nav-arrow bi bi-chevron-right"></i>';
                        }
                        echo '</p></a>';

                        if ($hasChildren) {
                            echo '<ul class="nav nav-treeview">';
                            foreach ($children as $child) {
                                $childUrl = str_replace('\\', '/', $child['page_url']);
                                $isChildActive = ($currentPath === $childUrl);
                                echo '<li class="nav-item">';
                                echo '<a href="' . BASE_URL . $childUrl . '" class="nav-link ' . ($isChildActive ? 'active' : '') . '">';
                                echo '<i class="nav-icon ' . ($child['icon_class'] ?: 'bi bi-circle') . '"></i>';
                                echo '<p>' . htmlspecialchars($child['page_name']) . '</p>';
                                echo '</a></li>';
                            }
                            echo '</ul>';
                        }
                        echo '</li>';
                    }
                }

                // Normalize path for comparison
                $currentPath = str_replace('\\', '/', substr($_SERVER['SCRIPT_NAME'], strlen('/universal/')));
                renderMenu($pdo, 0, $_SESSION['role'], $currentPath);
                ?>
            </ul>
        </nav>
    </div>
</aside>
