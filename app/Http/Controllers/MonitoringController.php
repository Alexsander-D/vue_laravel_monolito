<?php

namespace App\Http\Controllers;

use App\Models\Spatie\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

class MonitoringController extends Controller
{
    private const PERMISSION = 'visualizar-monitoramento';
    private const TEAM_NAME = 'MONITORAMENTO DO SISTEMA';

    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless($this->canView($user), 403);

        $metrics = [
            'host' => $this->query('100 * (1 - node_memory_MemAvailable_bytes / node_memory_MemTotal_bytes)'),
            'containers' => $this->query('container_memory_working_set_bytes{name!=""}'),
        ];

        return Inertia::render('Monitoring/Index', [
            'metrics' => [
                'hostMemoryUsagePercent' => $this->firstValue($metrics['host']),
                'containers' => collect($metrics['containers'])->map(fn (array $sample) => [
                    'name' => $sample['metric']['name'] ?? $sample['metric']['container'] ?? 'container',
                    'memoryBytes' => $this->numericValue($sample['value'] ?? null),
                ])->sortByDesc('memoryBytes')->values()->all(),
            ],
            'available' => ! empty($metrics['host']) || ! empty($metrics['containers']),
            'checkedAt' => now()->toIso8601String(),
        ]);
    }

    private function query(string $query): array
    {
        try {
            $response = Http::timeout(5)->get(rtrim(config('services.prometheus.url'), '/') . '/api/v1/query', [
                'query' => $query,
            ]);

            if (! $response->successful()) {
                return [];
            }

            return $response->json('data.result', []);
        } catch (ConnectionException) {
            return [];
        }
    }

    private function firstValue(array $samples): ?float
    {
        return $this->numericValue($samples[0]['value'] ?? null);
    }

    private function numericValue(?array $value): ?float
    {
        return isset($value[1]) && is_numeric($value[1]) ? (float) $value[1] : null;
    }

    private function canView(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return strcasecmp(trim((string) $user->currentTeam?->name), self::TEAM_NAME) === 0
            || collect($user->getPermissionsInTeamByUser())->contains(self::PERMISSION);
    }
}
