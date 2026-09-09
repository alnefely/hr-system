<?php

/*
|--------------------------------------------------------------------------
| Dashboard
|--------------------------------------------------------------------------
*/

return [

    'title' => 'Dashboard',

    // Needs-attention bar
    'needs_attention' => 'Needs your attention',

    'pending' => [
        'leaves'    => 'Leave requests',
        'advances'  => 'Advance requests',
        'penalties' => 'Penalties awaiting approval',
        'rewards'   => 'Rewards awaiting approval',
        'payrolls'  => 'Draft payrolls',
    ],

    'alerts' => [
        'overdue_invoices'  => 'Overdue invoices',
        'due_subscriptions' => 'Subscriptions due soon',
    ],

    // Financial summary
    'financial_summary' => 'Financial Summary',
    'view_full_report'  => 'Full report',
    'revenue'           => 'Revenue',
    'expenses'          => 'Expenses',
    'net'               => 'Net',
    'outstanding'       => 'Outstanding',
    'invoice_count'     => 'invoices',
    'total'             => 'Total',

    'no_financial_data' => 'No financial activity this month',
    'no_financial_hint' => 'Record a sale or an expense and the numbers will appear here.',
    'no_data'           => 'Not enough data to display',

    // Charts
    'charts' => [
        'trend'               => 'Revenue vs Expenses',
        'expense_split'       => 'Expense breakdown',
        'sales_channels'      => 'Sales by channel',
        'employees_by_branch' => 'Employees by branch',
    ],

    'top_customers' => 'Top customers',

    // HR
    'hr_summary' => 'Human Resources',

    'hr' => [
        'active'     => 'Active employees',
        'on_leave'   => 'On leave',
        'new_hires'  => 'New hires this month',
        'total'      => 'Total employees',
    ],


    // Dashboard filters
    'all_currencies' => 'All currencies',

    'filters' => [
        'period'       => 'Period',
        'range_label'  => 'From / To',
        'range'        => 'From :from to :to',
        'branch'       => 'Branch',
        'all_branches' => 'All branches',
        'currency'     => 'Currency',
        'auto_currency'=> 'Automatic',
        'load_error'   => 'Could not refresh the data, please try again',
    ],

    'presets' => [
        'this_month'     => 'This month',
        'last_month'     => 'Last month',
        'this_quarter'   => 'This quarter',
        'this_year'      => 'This year',
        'last_12_months' => 'Last 12 months',
        'custom'         => 'Custom range',
    ],

    'hints' => [
        'period'         => 'Every figure and chart below is calculated for this period',
        'range'          => 'Pick your own start and end dates',
        'branch'         => 'Show a single branch only, or all branches combined',
        'currency'       => 'Pick a specific currency, or leave it automatic to use the most common one in the period',
        'revenue'        => 'Total value of sales invoices issued in the period, whether collected or not',
        'expenses'       => 'Total expenses recorded and approved in the period',
        'net'            => 'Revenue minus expenses. A negative number means expenses exceeded revenue',
        'outstanding'    => 'Amounts not yet collected from customers (unpaid or partially paid invoices) - money owed to you',
        'invoice_count'  => 'Number of sales invoices issued in this currency during the period',
        'trend_currency' => 'Switch currency to see each one trended separately',
        'expense_split'  => 'Top 6 expense categories in the period, in a single currency so the figures are comparable',
        'sales_channels' => 'Top 6 sales channels by value in the period',
    ],

    'chart_types' => [
        'pieLabeled' => 'Pie with outside labels',
        'change' => 'Change chart type',
        'line' => 'Line',
        'area' => 'Area',
        'bar' => 'Horizontal bars',
        'column' => 'Vertical bars',
        'donut' => 'Donut',
        'pie' => 'Pie',
        'radar' => 'Radar',
        'treemap' => 'Treemap',
        'polarArea' => 'Polar area',
    ],

];
