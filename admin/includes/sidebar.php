<?php
/**
 * Admin Sidebar Component
 * Include this file in admin pages: <?php include 'includes/sidebar.php'; ?>
 * 
 * Required variables before including:
 * - $user: User array with 'first_name' and 'last_name'
 * - $activePage: String indicating current page (e.g., 'dashboard', 'users', 'reports', 'audit-logs', 'storage', 'settings')
 */

// Default active page if not set
if (!isset($activePage)) {
    $activePage = '';
}
?>
<!-- Sidebar -->
<aside id="adminSidebar" class="w-64 bg-white border-r border-gray-200 overflow-y-auto transition-all duration-300 flex-shrink-0">
    <div class="px-6 py-8">
        <div class="flex items-center gap-3">
            <img src="../images/nealogo.png" alt="NEA Logo" class="w-10 h-10 object-contain">
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">SAQSD</h1>
        </div>
    </div>
    
    <nav class="mt-6">
        <a href="dashboard.php" class="block px-6 py-3 text-gray-700 hover:bg-gray-100 <?php echo $activePage === 'dashboard' ? 'sidebar-active font-medium' : ''; ?>">
            <svg class="w-6 h-6 mr-3 inline-block align-text-bottom" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path></svg><span class="align-middle">Dashboard</span>
        </a>
        <a href="reports.php" class="block px-6 py-3 text-gray-700 hover:bg-gray-100 <?php echo $activePage === 'reports' ? 'sidebar-active font-medium' : ''; ?>">
            <svg class="w-6 h-6 mr-3 inline-block align-text-bottom" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg><span class="align-middle">All Reports</span>
        </a>
       
         <a href="storage.php" class="block px-6 py-3 text-gray-700 hover:bg-gray-100 <?php echo $activePage === 'storage' ? 'sidebar-active font-medium' : ''; ?>">
            <svg class="w-6 h-6 mr-3 inline-block align-text-bottom" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path></svg><span class="align-middle">Database Storage</span>
        </a>
          <a href="users.php" class="block px-6 py-3 text-gray-700 hover:bg-gray-100 <?php echo $activePage === 'users' ? 'sidebar-active font-medium' : ''; ?>">
            <svg class="w-6 h-6 mr-3 inline-block align-text-bottom" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg><span class="align-middle">Manage Users</span>
        </a>
        <a href="audit-logs.php" class="block px-6 py-3 text-gray-700 hover:bg-gray-100 <?php echo $activePage === 'audit-logs' ? 'sidebar-active font-medium' : ''; ?>">
            <svg class="w-6 h-6 mr-3 inline-block align-text-bottom" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg><span class="align-middle">Audit Logs</span>
        </a>
       
        <a href="settings.php" class="block px-6 py-3 text-gray-700 hover:bg-gray-100 <?php echo $activePage === 'settings' ? 'sidebar-active font-medium' : ''; ?>">
            <svg class="w-6 h-6 mr-3 inline-block align-text-bottom" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg><span class="align-middle">Settings</span>
        </a>
         <a href="reports_table.php" class="block px-6 py-3 text-gray-700 hover:bg-gray-100 <?php echo $activePage === 'reports_table' ? 'sidebar-active font-medium' : ''; ?>">
            <svg class="w-6 h-6 mr-3 inline-block align-text-bottom" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"></path></svg><span class="align-middle">Reports Overview</span>
        </a>
    </nav>
    
    <div class="p-6 absolute bottom-0 w-64 border-t border-gray-200">
        <a href="profile.php" class="flex items-center gap-3 hover:opacity-80 transition">
            <!-- <div class="w-10 h-10 bg-gradient-to-br from-blue-400 to-blue-600 rounded-full flex items-center justify-center text-white font-bold">
                <?php echo substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1); ?>
            </div> -->
            <!-- <div class="flex-1">
                <p class="text-sm font-semibold text-gray-800"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></p>
                <p class="text-xs text-gray-600">Administrator</p>
            </div> -->
        </a>
    </div>
</aside>
