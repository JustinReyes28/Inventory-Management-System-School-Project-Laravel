<?php

namespace App\Enums;

enum NotificationType: string
{
    case CREATE = 'create';
    case UPDATE = 'update';
    case DELETE = 'delete';
    case STOCK_IN = 'stock_in';
    case STOCK_OUT = 'stock_out';
    case LOW_STOCK = 'low_stock';
    case NEAR_EXPIRY = 'near_expiry';
}
