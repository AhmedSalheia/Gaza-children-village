<?php

declare(strict_types=1);

namespace Modules\Authorization\Data;

enum ActorSource: string
{
    case Request = 'request';
    case QueuedJob = 'queued_job';
    case Cli = 'cli';
    case SystemProcess = 'system_process';
}
