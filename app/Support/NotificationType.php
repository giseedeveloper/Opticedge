<?php

namespace App\Support;

final class NotificationType
{
    public const ORDER_CREATED = 'order.created';

    public const ORDER_PAYMENT_SUCCESS = 'order.payment_success';

    public const ORDER_PAYMENT_FAILED = 'order.payment_failed';

    public const ORDER_STATUS_CHANGED = 'order.status_changed';

    public const USER_REGISTRATION_PENDING = 'user.registration_pending';

    public const USER_DEALER_APPROVED = 'user.dealer_approved';

    public const USER_DEALER_REJECTED = 'user.dealer_rejected';

    public const USER_ACTIVATED = 'user.activated';

    public const USER_DEACTIVATED = 'user.deactivated';

    public const TRANSFER_INCOMING = 'transfer.incoming';

    public const TRANSFER_ACCEPTED = 'transfer.accepted';

    public const TRANSFER_DECLINED = 'transfer.declined';

    public const TRANSFER_CANCELLED = 'transfer.cancelled';

    public const RETURN_INCOMING = 'return.incoming';

    public const RETURN_ACCEPTED = 'return.accepted';

    public const RETURN_DECLINED = 'return.declined';

    public const RETURN_CANCELLED = 'return.cancelled';

    public const DEVICES_ASSIGNED = 'devices.assigned';
}
