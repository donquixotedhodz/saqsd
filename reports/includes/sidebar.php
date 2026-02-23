<!-- Sidebar Component for Reports System -->
<!-- Variables to set before including:
    $activePage - The current page identifier (required)
                  Options: 'dashboard', 'reports', 'storage', 'new-report', 'report-list', 'profile'
    $userInfo - User information array (required)
-->
<aside class="w-64 bg-white border-r border-gray-200 overflow-y-auto flex flex-col">
    <!-- Logo Section -->
    <div class="px-6 py-8">
        <div class="flex items-center gap-3">
            <img src="../images/nealogo.png" alt="NEA Logo" class="w-10 h-10 object-contain">
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">SAQSD</h1>
        </div>
    </div>
    
    <!-- Main Navigation -->
    <nav class="mt-6 flex-1">
        <a href="dashboard.php" class="sidebar-link <?php echo($activePage === 'dashboard') ? 'sidebar-active' : ''; ?>">
            <svg class="w-5 h-5 mr-3 inline-block align-text-bottom" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path></svg><span class="align-middle">Dashboard</span>
        </a>
        <a href="reports.php" class="sidebar-link <?php echo($activePage === 'reports') ? 'sidebar-active' : ''; ?>">
            <svg class="w-5 h-5 mr-3 inline-block align-text-bottom" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg><span class="align-middle">My Reports</span>
        </a>
        <a href="storage.php" class="sidebar-link <?php echo($activePage === 'storage') ? 'sidebar-active' : ''; ?>">
            <svg class="w-5 h-5 mr-3 inline-block align-text-bottom" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path></svg><span class="align-middle">Database Storage</span>
        </a>
        
        <div class="border-t border-gray-200 mt-6 pt-6">
            <a href="profile.php" class="sidebar-link <?php echo($activePage === 'profile') ? 'sidebar-active' : ''; ?>">
                <svg class="w-5 h-5 mr-3 inline-block align-text-bottom" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg><span class="align-middle">My Profile</span>
            </a>
             <a href="logout.php" class="block px-6 py-3 text-gray-700 hover:bg-red-500 hover:text-white border-l-4 border-transparent">
            <svg class="w-5 h-5 mr-3 inline-block align-text-bottom" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg><span class="align-middle">Logout</span>
        </a>
        </div>
    </nav>
    
    <!-- User Profile Section -->
    <!-- <div class="p-6 border-t border-gray-200">
        <a href="profile.php" class="flex items-center gap-3 hover:opacity-80 transition">
            <div class="w-10 h-10 bg-gradient-to-br from-blue-400 to-blue-600 rounded-full flex items-center justify-center text-white font-bold">
                <?php echo strtoupper(substr(($userInfo['first_name'] ?? 'U'), 0, 1) . substr(($userInfo['last_name'] ?? ''), 0, 1)); ?>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-gray-800 truncate"><?php echo htmlspecialchars(($userInfo['first_name'] ?? '') . ' ' . ($userInfo['last_name'] ?? '')); ?></p>
                <p class="text-xs text-gray-600 truncate"><?php echo htmlspecialchars($userInfo['position'] ?? $userInfo['role'] ?? 'Employee'); ?></p>
            </div>
        </a>
    </div> -->
</aside>

<style>
    .sidebar-link {
        display: block;
        padding: 0.75rem 1.5rem;
        color: #374151;
        font-weight: 500;
        border-left: 4px solid transparent;
        transition: all 0.2s ease;
    }
    .sidebar-link:hover {
        background-color: #f3f4f6;
    }
    .sidebar-active {
        border-left-color: var(--color-primary, #3c50e0) !important;
        background-color: #f3f4f6 !important;
        color: var(--color-primary, #3c50e0) !important;
    }
</style>
