<?php

namespace App\Services;

use RuntimeException;

// خطایی که پیامش مستقیم به کاربر نمایش داده می‌شود
class OtpException extends RuntimeException
{
}
