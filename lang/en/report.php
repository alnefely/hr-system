<?php

return [
    'title'                     => 'Reports',
    'financial_pl'              => 'Financial Profit & Loss Report (P&L)',
    'payroll_report'            => 'Payroll & Wages Cost Report',
    'expense_report'            => 'Petty Cash & Administrative Expenses Report',
    'vendor_report'             => 'External Contracts & Subscriptions Report',
    'branch_comparison_report'  => 'Branch Performance Comparison Report',
    'employee_statement'        => 'Employee Account Statement',
    'documents'                 => 'Employee Documents Monitor',
    'salary_audit'              => 'Employee Salary Audit Sheet (Monthly)',

    'pl' => [
        'total_sales'   => 'Total Sales & Revenues',
        'payroll_cost'  => 'Total Salaries & Wages',
        'expenses_cost' => 'Total Petty Cash & Administrative Expenses',
        'vendor_cost'   => 'Total External Subscription Payments',
        'total_costs'   => 'Total Comprehensive Costs & Expenses',
        'net_profit'    => 'Company Net Profit',
        'net_loss'      => 'Company Net Loss',
        'profit_margin' => 'Net Profit Margin',
        'status_profit' => 'Profitable',
        'status_loss'   => 'Operating at a Loss',
    ],

    'filters' => [
        'date_from' => 'Date From',
        'date_to'   => 'Date To',
        'branch'    => 'Select Branch',
        'print'     => 'Print Report',
    ],

    'doc_status' => [
        'attention'  => 'Everything needing attention',
        'expiring'   => 'Expiring soon',
        'expired'    => 'Expired',
        'pending'    => 'Pending review',
        'days_left'  => ':days days left',
        'all_clear'  => 'No documents need attention right now',
    ],

    'doc_hints' => [
        'expiring' => 'Documents expiring within :days days and needing renewal',
        'expired'  => 'Documents whose validity has already lapsed',
        'pending'  => 'Uploaded documents not yet approved',
    ],

    'doc_filters' => [
        'days'      => 'Alert window',
        'days_hint' => 'How many days ahead counts as "expiring soon". Defaults to 30 days.',
    ],

];
