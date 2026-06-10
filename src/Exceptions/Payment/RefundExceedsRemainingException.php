<?php

declare(strict_types=1);

namespace KosovoPay\Exceptions\Payment;

use KosovoPay\Exceptions\PaymentException;

final class RefundExceedsRemainingException extends PaymentException {}
