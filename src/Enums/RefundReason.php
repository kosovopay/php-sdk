<?php

declare(strict_types=1);

namespace KosovoPay\Enums;

enum RefundReason: string
{
    case RequestedByCustomer = 'requested_by_customer';
    case Duplicate = 'duplicate';
    case Fraudulent = 'fraudulent';
    case Other = 'other';
}
