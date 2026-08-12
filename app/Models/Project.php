<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'due_date' => 'date',
            'priority' => ProjectPriority::class,
            'status' => ProjectStatus::class,
        ];
    }
}
