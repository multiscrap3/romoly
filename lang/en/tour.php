<?php

/*
|--------------------------------------------------------------------------
| Guided Tour / User Guide — text (EN)
|--------------------------------------------------------------------------
| Mirror of lang/id/tour.php — keep step order & count identical.
*/

return [

    'common' => [
        'next'          => 'Next',
        'prev'          => 'Back',
        'done'          => 'Done',
        'progress'      => 'Step {{current}} of {{total}}',
        'replay_button' => 'Guide',
        'replay_title'  => 'Replay the guide for this page',
        'reset_confirm' => 'Replay all guides from the start? They will appear again when you open each page.',
    ],

    // ── Global "Welcome" tour (once per account, on Dashboard) ──
    'welcome' => [
        ['tour' => 'nav-sidebar',    'title' => 'Main Menu',           'body' => 'This is the main menu. Every Romoly feature lives here.'],
        ['tour' => 'fab-add',        'title' => 'Add Transaction',     'body' => 'This button quickly adds a transaction: income, expense, or transfer.', 'roles' => ['admin', 'member']],
        ['tour' => 'topbar-notif',   'title' => 'Notifications',       'body' => 'Budget alerts, bills, and family activity show up here.'],
        ['tour' => 'topbar-theme',   'title' => 'Appearance',          'body' => 'Switch between light and dark mode to your taste.'],
        ['tour' => 'topbar-profile', 'title' => 'Account & Settings',  'body' => 'Your account, settings, and sign-out live here.'],
        ['tour' => 'nav-gamifikasi', 'title' => 'Your Progress',       'body' => 'See your progress, level, and financial achievements here.'],
    ],

    // ── Dashboard ──
    'dashboard' => [
        ['tour' => 'dash-hero-saldo',          'title' => 'Total Balance',      'body' => 'Your total balance across all fund sources appears here. It fills in once you record transactions.'],
        ['tour' => 'dash-card-transaksi',       'title' => 'Monthly Summary',    'body' => 'A summary of this month\'s income and expenses.'],
        ['tour' => 'dash-card-anggaran',        'title' => 'Budget',             'body' => 'Track your budget — green means safe, red means over the limit.'],
        ['tour' => 'dash-gamification-insight', 'title' => 'Financial Progress', 'body' => 'Your progress box: XP, momentum, and financial missions.'],
        ['tour' => 'dash-chart-kategori',       'title' => 'Spending',           'body' => 'Your biggest spending per category is visualized here.'],
        ['tour' => 'dash-edit-layout',          'title' => 'Arrange Layout',     'body' => 'Rearrange your dashboard widgets to fit your needs.'],
        ['tour' => 'fab-add',                   'title' => 'Start Recording',    'body' => 'Ready to begin? Record your first transaction from this button.', 'roles' => ['admin', 'member']],
    ],

    // ── Transactions ──
    'transaksi.index' => [
        ['tour' => 'transaksi-add',    'title' => 'Add Transaction', 'body' => 'This button records a new income, expense, or transfer.', 'roles' => ['admin', 'member']],
        ['tour' => 'transaksi-filter', 'title' => 'Filter',          'body' => 'Filter transactions by type, date range, or fund source.'],
        ['tour' => 'transaksi-search', 'title' => 'Search',          'body' => 'Type a keyword here to find a transaction by its description.'],
        ['tour' => 'transaksi-export', 'title' => 'Export',          'body' => 'Download your transactions to Excel, CSV, or PDF for archiving or reports.'],
        ['tour' => 'transaksi-list',   'title' => 'Transaction List','body' => 'Click any row to view full details or edit it.'],
    ],

    'transaksi.create' => [
        ['tour' => 'tx-jenis',      'title' => 'Transaction Type', 'body' => 'Pick first: income (money in), expense (money out), or transfer between fund sources.'],
        ['tour' => 'tx-jumlah',     'title' => 'Amount',           'body' => 'Enter the amount here. It auto-formats to Rupiah, so just type the number.'],
        ['tour' => 'tx-kategori',   'title' => 'Category',         'body' => 'Group the transaction (e.g. Food, Transport) so your reports and budgets stay tidy.'],
        ['tour' => 'tx-sumber',     'title' => 'Fund Source',      'body' => 'Pick which account or wallet the money comes from or goes to.'],
        ['tour' => 'tx-ocr',        'title' => 'Scan Receipt (OCR)','body' => 'Got a receipt? Snap a photo here and let AI fill in the amount and details automatically.'],
        ['tour' => 'tx-keterangan', 'title' => 'Notes & Tags',     'body' => 'Add notes and tags to make the transaction easier to find later.'],
        ['tour' => 'tx-submit',     'title' => 'Save',             'body' => 'Done? Save the transaction. Your fund source balance updates instantly.'],
    ],

    // ── Bank Import ──
    'import-bank.web.index' => [
        ['tour' => 'import-start',    'title' => 'Import Statements', 'body' => 'Instead of recording one by one, import your bank statement file all at once.', 'roles' => ['admin', 'member']],
        ['tour' => 'import-history',  'title' => 'Import History',    'body' => 'All previous imports are listed here with their status.'],
        ['tour' => 'import-template', 'title' => 'Template',          'body' => 'No matching format yet? Download this template as a reference.'],
    ],

    // ── Reports ──
    'laporan.index' => [
        ['tour' => 'laporan-jenis',   'title' => 'Report Type', 'body' => 'Choose your view: daily, weekly, monthly, yearly, or compare periods.'],
        ['tour' => 'laporan-periode', 'title' => 'Period',      'body' => 'Set the time range you want to review here.'],
        ['tour' => 'laporan-export',  'title' => 'Export',      'body' => 'Download the report as PDF or Excel to share or archive.'],
    ],

    // ── Gamification ──
    'gamifikasi.index' => [
        ['tour' => 'game-level',       'title' => 'Level & XP',   'body' => 'Every time you record finances consistently you earn XP and level up. This is your progress.'],
        ['tour' => 'game-momentum',    'title' => 'Momentum',     'body' => 'Momentum tracks your daily habit — the more consistent, the higher. Don\'t break the streak!'],
        ['tour' => 'game-achievement', 'title' => 'Achievements', 'body' => 'Badges you collect from good financial habits.'],
        ['tour' => 'game-challenge',   'title' => 'Challenges',   'body' => 'Missions to drive healthy habits — complete them for bonus XP.'],
    ],

    // ── Budget ──
    'anggaran.index' => [
        ['tour' => 'anggaran-add',      'title' => 'Add Budget',     'body' => 'Set a spending limit per category to keep your spending in check.', 'roles' => ['admin', 'member']],
        ['tour' => 'anggaran-progress', 'title' => 'Budget Progress','body' => 'This bar shows how much of the limit is used — green is safe, red is over.'],
    ],

    // ── Savings ──
    'tabungan.index' => [
        ['tour' => 'tabungan-add',      'title' => 'Add Goal',       'body' => 'Create a savings goal (e.g. Emergency Fund, Vacation) and track its progress.', 'roles' => ['admin', 'member']],
        ['tour' => 'tabungan-progress', 'title' => 'Savings Progress','body' => 'This bar shows how much you have collected toward your goal.'],
    ],

    // ── Debt & Receivables ──
    'hutang-piutang.index' => [
        ['tour' => 'hp-ringkasan', 'title' => 'Debt & Receivables', 'body' => 'Debt = money you borrowed; Receivable = money owed to you. Both are tracked here.'],
        ['tour' => 'hp-add',       'title' => 'Add Record',         'body' => 'Record a new debt or receivable here, with due date and installment options.', 'roles' => ['admin', 'member']],
    ],

    // ── Recurring ──
    'recurring.index' => [
        ['tour' => 'recurring-add',    'title' => 'Recurring',     'body' => 'For repeating transactions (salary, subscriptions, installments), set once and let it record automatically.', 'roles' => ['admin', 'member']],
        ['tour' => 'recurring-toggle', 'title' => 'On / Off',      'body' => 'Turn a recurring transaction on or pause it anytime without deleting it.'],
    ],

    // ── Categories ──
    'kategori.index' => [
        ['tour' => 'kategori-add', 'title' => 'Add Category', 'body' => 'Create income or expense categories for your family. This is the foundation of reports and budgets.', 'roles' => ['admin', 'member']],
    ],

    // ── Fund Sources ──
    'sumber-transaksi.index' => [
        ['tour' => 'sumber-add',   'title' => 'Add Fund Source', 'body' => 'Register your bank account, digital wallet, or cash here.', 'roles' => ['admin', 'member']],
        ['tour' => 'sumber-saldo', 'title' => 'Automatic Balance','body' => 'Balances are calculated automatically from transactions — no manual editing needed.'],
    ],

    // ── Tags ──
    'tags.index' => [
        ['tour' => 'tags-add', 'title' => 'Tags', 'body' => 'Tags label transactions across categories (e.g. vacation, kids) so related ones are easy to gather.', 'roles' => ['admin', 'member']],
    ],

    // ── Household ──
    'household.index' => [
        ['tour' => 'household-invite', 'title' => 'Invite Members', 'body' => 'Invite your partner or family so you can manage finances together in one data space.', 'roles' => ['admin']],
        ['tour' => 'household-join',   'title' => 'Join',           'body' => 'Have an invite code? Enter it here to join another household.'],
    ],

    // ── Notifications ──
    'notifikasi.index' => [
        ['tour' => 'notif-list',     'title' => 'Notifications', 'body' => 'Over-budget alerts, savings reached, debt due dates, and family activity show up here.'],
        ['tour' => 'notif-read-all', 'title' => 'Mark as Read',  'body' => 'Clear all unread notifications in one click.'],
    ],

    // ── Settings ──
    'settings.index' => [
        ['tour' => 'settings-profile',     'title' => 'Profile & Account', 'body' => 'Set your name, photo, password, and household details here.'],
        ['tour' => 'settings-preferensi',  'title' => 'Preferences',       'body' => 'Adjust language, light/dark theme, and currency format.'],
        ['tour' => 'settings-replay-tour', 'title' => 'Replay Guide',      'body' => 'Want to see the feature tour again from the start? Reset the guide here.'],
    ],

];
