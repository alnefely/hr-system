<?php

return [
    'title'               => 'Advances & Loans',
    'title_singular'      => 'Financial Advance',
    'singular'            => 'Advance',
    'add_new'             => 'Request New Advance',
    'create_title'        => 'Request New Advance',
    'edit'                => 'Edit Advance',
    'edit_title'          => 'Edit Advance Request',
    'trash_title'         => 'Advances Trash',
    'trash'               => 'Trash',
    'back'                => 'Back to List',

    'employee'            => 'Employee',
    'amount'              => 'Advance Amount',
    'installments_count'  => 'Installments Count (Months)',
    'monthly_installment' => 'Monthly Installment',
    'paid_amount'         => 'Paid Amount',
    'remaining_amount'    => 'Remaining Amount',
    'advance_date'        => 'Disbursement Date',
    'start_date'          => 'Deduction Start Date',
    'status'              => 'Status',
    'reason'              => 'Advance Reason',
    'notes'               => 'Administrative Notes',
    'actions'             => 'Actions',
    'approve'             => 'Approve Advance',
    'reject'              => 'Reject Advance',

    'fields' => [
        'employee'            => 'Employee',
        'amount'              => 'Advance Amount',
        'installments_count'  => 'Installments Count (Months)',
        'monthly_installment' => 'Monthly Installment',
        'paid_amount'         => 'Paid Amount',
        'waived_amount'       => 'Waived Amount',
        'remaining_amount'    => 'Remaining Amount',
        'advance_date'        => 'Disbursement Date',
        'start_date'          => 'Deduction Start Date',
        'status'              => 'Status',
        'reason'              => 'Advance Reason',
        'notes'               => 'Administrative Notes',
        'approved_by'         => 'Approved By',
        'approved_at'         => 'Approval Date',
    ],

    'enums' => [
        'pending'  => 'Pending Approval',
        'approved' => 'Approved & Active',
        'paid'     => 'Fully Paid',
        'rejected' => 'Rejected',
    ],

    'status_enums' => [
        'pending'  => 'Pending Approval',
        'approved' => 'Approved & Active',
        'paid'     => 'Fully Paid',
        'rejected' => 'Rejected',
    ],

    'actions' => [
        'add_new'        => 'Request Advance',
        'record_payment' => 'Record Payment',
        'waive'          => 'Manager Waiver',
        'view_details'   => 'View Repayments',
    ],

    'messages' => [
        'created'          => 'Advance requested successfully',
        'updated'          => 'Advance updated successfully',
        'approved'         => 'Advance approved successfully',
        'rejected'         => 'Advance rejected successfully',
        'deleted'          => 'Advance moved to trash successfully',
        'restored'         => 'Advance restored successfully',
        'payment_recorded' => 'Advance repayment installment recorded successfully and balances updated',
        'waived'           => 'Manager waiver recorded successfully and remaining balance updated',
        'status_updated'   => 'Advance status updated successfully',
    ],

    'errors' => [
        'not_pending'                     => 'Advance is not pending review or has already been processed.',
        'cannot_reject_repaid'            => 'Cannot reject an advance that has recorded repayment installments or manager waivers.',
        'cannot_mark_paid_with_remaining' => 'Cannot mark advance as fully paid while it still has a remaining balance (:remaining). Record repayment or waiver first.',
        'cannot_reset_pending_repaid'     => 'Cannot revert advance to pending status because repayment movements are already recorded.',
        'already_fully_paid'              => 'This advance is already fully paid and has no remaining balance.',
        'amount_exceeds_remaining'        => 'The requested amount (:amount) exceeds the remaining balance on this advance (:remaining).',
        'not_approved'                    => 'Payments or waivers can only be recorded on an approved advance.',
        'cannot_delete_repaid'            => 'An advance with recorded repayments or waivers cannot be deleted.',
    ],

    'export' => [
        'installments_count'  => 'Installments',
        'monthly_installment' => 'Monthly Installment',
        'paid'                => 'Paid',
        'waived'              => 'Waived',
        'remaining'           => 'Remaining',
        'advance_amount'      => 'Advance Amount',
        'advance_date'        => 'Advance Date',
        'start_date'          => 'Start Date',
        'file_title'          => 'Employee Advances & Personal Loans Log',
    ],

    'filters' => [
        'all_statuses'  => '-- All statuses --',
        'all_employees' => '-- All employees --',
        'from_date'     => 'From date',
        'to_date'       => 'To date',
    ],

    // شرح الحقول (أيقونة ؟ جنب العنوان)
    'hints' => [
        'amount'             => 'Total amount of the advance paid to the employee.',
        'installments_count' => 'Number of months to repay it. Monthly installment = amount ÷ installments, deducted automatically in each payroll.',
        'advance_date'       => 'The date the advance was actually paid to the employee.',
        'start_date'         => 'First month installments are deducted from salary. Payrolls before this month deduct nothing.',
        'payment_amount'     => 'A manual repayment outside payroll (e.g. cash) — reduces the remaining amount immediately.',
        'waive_amount'       => 'Part forgiven for the employee — reduces the remaining amount without any payment, recorded with the reason.',
    ],

];
