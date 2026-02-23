<!-- Global Resources -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../src/css/style.css">

<!-- Header Component -->
<!-- Variables to set before including:
    $pageTitle - The main title of the page (required)
    $pageIcon - Font Awesome icon class (optional, default: none)
    $headerRight - Custom HTML for right side (optional)
    $showUserWelcome - Show welcome message with user name (optional, default: false)
    $totalCount - Number to display with count label (optional)
    $countLabel - Label for the count (optional, default: 'items')
    $countIcon - Icon for count display (optional, default: 'fa-list')
-->
<header class="bg-white border-b border-gray-200 px-8 py-4 flex justify-between items-center" style="font-family: 'Inter', sans-serif;">
    <div class="flex items-center gap-3">
        <button id="sidebarToggleBtn" class="w-10 h-10 flex items-center justify-center rounded-xl bg-slate-50 text-slate-500 hover:bg-indigo-600 hover:text-white transition-all shadow-sm border border-slate-100">
            <i class="fas fa-bars"></i>
        </button>
    </div>
    
    <div class="flex items-center gap-4">
        <?php if (isset($headerRight)): ?>
            <?php echo $headerRight; ?>
        <?php
elseif (isset($showUserWelcome) && $showUserWelcome): ?>
            <!-- <div class="text-sm text-gray-600">
                Welcome, <span class="font-semibold"><?php echo htmlspecialchars($user['first_name'] ?? 'User'); ?></span>
            </div> -->
        <?php
elseif (isset($totalCount)): ?>
            <div class="text-sm text-gray-600">
                <i class="fas <?php echo $countIcon ?? 'fa-list'; ?> mr-2"></i>
                Total: <span class="font-semibold"><?php echo number_format($totalCount); ?></span> <?php echo $countLabel ?? 'items'; ?>
            </div>
        <?php
endif; ?>
        
        <!-- User Profile Dropdown -->
        <div class="relative" id="profileDropdown">
            <button onclick="toggleProfileDropdown()" class="flex items-center gap-3 px-3 py-2 rounded-xl hover:bg-slate-50 transition-all border border-transparent hover:border-slate-100 group">
                <?php if (!empty($user['profile_photo']) && file_exists('../uploads/profiles/' . $user['profile_photo'])): ?>
                    <img src="../uploads/profiles/<?php echo htmlspecialchars($user['profile_photo']); ?>?t=<?php echo time(); ?>" alt="Profile" class="w-10 h-10 rounded-xl object-cover border-2 border-white shadow-sm group-hover:border-indigo-100 transition-all">
                <?php
else: ?>
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white text-base font-bold shadow-sm group-hover:scale-105 transition-all">
                        <?php echo strtoupper(substr($user['first_name'] ?? 'U', 0, 1)); ?>
                    </div>
                <?php
endif; ?>
                <div class="hidden md:flex flex-col items-start leading-tight">
                    <span class="text-sm font-bold text-slate-900"><?php echo htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')); ?></span>
                    <span class="text-[10px] font-medium text-indigo-500 uppercase tracking-wider">Super Admin</span>
                </div>
                <i class="fas fa-chevron-down text-[10px] text-slate-300 group-hover:text-indigo-500 transition-all"></i>
            </button>
            
            <!-- Dropdown Menu -->
            <div id="profileMenu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-2 z-50">
                <a href="profile.php" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    My Profile
                </a>
                <a href="settings.php" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    Settings
                </a>
                <hr class="my-2 border-gray-200">
                <a href="logout.php" class="flex items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    Logout
                </a>
            </div>
        </div>
    </div>
</header>

<script>
function toggleProfileDropdown() {
    const menu = document.getElementById('profileMenu');
    menu.classList.toggle('hidden');
}

// Close dropdown when clicking outside
document.addEventListener('click', function(e) {
    const dropdown = document.getElementById('profileDropdown');
    const menu = document.getElementById('profileMenu');
    if (dropdown && menu && !dropdown.contains(e.target)) {
        menu.classList.add('hidden');
    }
});
</script>
<script src="js/sidebar.js"></script>
