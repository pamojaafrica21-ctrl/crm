<?php

namespace App\Domain\Shared\Enums;

enum SyncStatus: string
{
    case Synced = 'synced';
    case Pending = 'pending';
    case Failed = 'failed';
    case Stale = 'stale';
}
