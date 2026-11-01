<?php

namespace App\Enums;

enum ItemType: string
{
    case Dress = 'dress';
    case Top = 'top';
    case Skirt = 'skirt';
    case Trousers = 'trousers';
    case Accessory = 'accessory';
    case Other = 'other';
}
