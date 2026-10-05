<?php

namespace App\Enums;

enum ActivityAction: string
{
    case CREATE = 'create';
    case UPDATE = 'update';
    case DELETE = 'delete';
    case STOCK_IN = 'stock_in';
    case STOCK_OUT = 'stock_out';
}
