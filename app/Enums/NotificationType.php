<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumHelpers;

/**
 * أنواع الإشعارات.
 *
 * كل نوع بيحدد أيقونته ومستوى أهميته والصلاحية اللي لازم المستلم
 * يكون عنده — عشان مانبعتش إشعار سلفة لحد مالوش صلاحية السلف.
 */
enum NotificationType: string
{
    use HasEnumHelpers;

    case LeaveRequested        = 'leave_requested';
    case LeaveDecided          = 'leave_decided';
    case PermissionRequested   = 'permission_requested';
    case AdvanceRequested      = 'advance_requested';
    case AdvanceDecided        = 'advance_decided';
    case PayrollAwaitingReview = 'payroll_awaiting_review';
    case SubscriptionDueSoon   = 'subscription_due_soon';
    case PettyCashLow          = 'petty_cash_low';
    case InvoiceOverdue        = 'invoice_overdue';

    public function label(): string
    {
        return __('notification.types.' . $this->value);
    }

    public function badge(): string
    {
        return match ($this->level()) {
            'danger'  => 'bg-label-danger',
            'warning' => 'bg-label-warning',
            'success' => 'bg-label-success',
            default   => 'bg-label-info',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::LeaveRequested, self::LeaveDecided     => 'fa-solid fa-calendar-check',
            self::PermissionRequested                    => 'fa-solid fa-clock',
            self::PermissionRequested                    => 'fa-solid fa-clock',
            self::AdvanceRequested, self::AdvanceDecided => 'fa-solid fa-hand-holding-dollar',
            self::PayrollAwaitingReview                  => 'fa-solid fa-money-check-dollar',
            self::SubscriptionDueSoon                    => 'fa-solid fa-repeat',
            self::PettyCashLow                           => 'fa-solid fa-wallet',
            self::InvoiceOverdue                         => 'fa-solid fa-file-invoice-dollar',
        };
    }

    public function level(): string
    {
        return match ($this) {
            self::PettyCashLow, self::InvoiceOverdue => 'danger',
            self::SubscriptionDueSoon                => 'warning',
            self::LeaveDecided, self::AdvanceDecided => 'success',
            default                                  => 'info',
        };
    }

    /** الصلاحية المطلوبة لاستقبال النوع ده. */
    public function requiredPermission(): string
    {
        return match ($this) {
            self::LeaveRequested, self::LeaveDecided     => 'leave_show',
            self::PermissionRequested                    => 'permission_show',
            self::AdvanceRequested, self::AdvanceDecided => 'advance_show',
            self::PayrollAwaitingReview                  => 'payroll_show',
            self::SubscriptionDueSoon                    => 'vendor_subscription_show',
            self::PettyCashLow                           => 'expense_show',
            self::InvoiceOverdue                         => 'sale_show',
        };
    }
}
