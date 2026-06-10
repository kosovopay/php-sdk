<?php

declare(strict_types=1);

namespace KosovoPay\Exceptions;

/**
 * The key authenticated but is not permitted to perform the request (e.g. a
 * test key reaching a live-only resource, or a disabled capability).
 */
final class PermissionException extends KosovoPayException {}
