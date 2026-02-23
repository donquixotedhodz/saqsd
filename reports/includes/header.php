<!-- Global Resources -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../src/css/style.css">

<!-- Header Component for Reports System -->
<!-- Variables to set before including:
    $pageTitle - The main title of the page (required)
    $pageIcon - Font Awesome icon class (optional, default: none)
    $headerRight - Custom HTML for right side (optional)
    $showNewReport - Show "New Report" button (optional, default: false)
    $totalCount - Number to display with count label (optional)
    $countLabel - Label for the count (optional, default: 'items')
    $countIcon - Icon for count display (optional, default: 'fa-list')
-->
<header class="bg-white border-b border-gray-200 px-8 py-4 flex justify-between items-center">
    <div class="flex items-center gap-3">
        <!-- <img src="../images/nealogo.png" alt="NEA Logo" class="h-10 w-auto"> -->
        <!-- Title and icon removed as per request -->
    </div>
    
    <div class="flex items-center gap-4">
        <?php if (isset($headerRight)): ?>
            <?php echo $headerRight; ?>
        <?php
elseif (isset($showNewReport) && $showNewReport): ?>
            <!-- <a href="report-create.php" class="btn-primary">
                <i class="fas fa-plus mr-2"></i>New Report
            </a> -->
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
            <button onclick="toggleProfileDropdown()" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-gray-100 transition-colors">
                <?php if (!empty($userInfo['profile_photo']) && file_exists('../uploads/profiles/' . $userInfo['profile_photo'])): ?>
                    <img src="../uploads/profiles/<?php echo htmlspecialchars($userInfo['profile_photo']); ?>?t=<?php echo time(); ?>" alt="Profile" class="w-10 h-10 rounded-full object-cover border-2 border-gray-200">
                <?php
else: ?>
                    <div class="w-10 h-10 rounded-full bg-gradient-to-r from-blue-500 to-purple-500 flex items-center justify-center text-white text-base font-semibold">
                        <?php echo strtoupper(substr($userInfo['first_name'] ?? 'U', 0, 1)); ?>
                    </div>
                <?php
endif; ?>
                <span class="text-sm text-gray-700 hidden md:inline"><?php echo htmlspecialchars(($userInfo['first_name'] ?? '') . ' ' . ($userInfo['last_name'] ?? '')); ?></span>
                <i class="fas fa-chevron-down text-xs text-gray-400"></i>
            </button>
            
            <!-- Dropdown Menu -->
            <div id="profileMenu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-2 z-50">
                <a href="profile.php" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    My Profile
                </a>
                <a href="profile-edit.php" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                    Edit Profile
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
