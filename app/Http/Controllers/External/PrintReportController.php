<?php

namespace App\Http\Controllers\External;

use App\Http\Controllers\Controller;
use App\Models\External\Customers;
use App\Models\External\TechnicalScale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PrintReportController extends Controller
{
    public function printView($screeningId)
    {
        $isProduction = app()->environment('production');
        $schema = $isProduction ? 'assistencia_vex.' : '';

        // TABELA DETALHADA
        $reportRows = DB::table("{$schema}screening_report as sr")
            ->leftJoin("{$schema}products as p", 'p.id', '=', 'sr.products_id')
            ->leftJoin("{$schema}screening_defects_solutions as sds", 'sr.id', '=', 'sds.screening_report_id')
            ->leftJoin("{$schema}components as c", 'sds.component_id', '=', 'c.id')
            ->leftJoin("{$schema}defects_solutions as ds", 'sds.defects_solutions_id', '=', 'ds.id')
            ->where('sr.screening_id', $screeningId)
            ->select([
                'p.sku as product',
                'p.family',
                'sr.price',
                'c.component',
                'ds.defect',
                'ds.solution',
                'sr.imei1',
                'sr.imei2',
                'sr.serial_number',
                'sr.status',
                'sr.observation'
            ])
            ->get();

        // TABELA AGRUPADA
        $finalReport = DB::table("{$schema}screening_report as sr")
            ->leftJoin("{$schema}products as p", 'p.id', '=', 'sr.products_id')
            ->where('sr.screening_id', $screeningId)
            ->select([
                'p.sku',
                'p.family',
                'sr.guarantee',
                'sr.status',
                'sr.price',
            ])
            ->get();

        $groupedProducts = $finalReport->groupBy(function ($entry) {
            $guarantee = strtolower($entry->guarantee) === 'em garantia' ? 'EM GARANTIA' : 'FORA DE GARANTIA';
            return "{$entry->sku}-{$guarantee}";
        })->map(function ($group, $key) {
            [$sku, $guaranteeStatus] = explode('-', $key);

            $statusCounts = [
                'Recuperado' => 0,
                'Devolução' => 0,
                'Mau uso' => 0,
                'Não encontrado' => 0,
                'Próxima triagem' => 0,
            ];

            foreach ($group as $item) {
                $status = trim($item->status ?? '');
                if (isset($statusCounts[$status])) {
                    $statusCounts[$status]++;
                }
            }

            return [
                'family' => $group->first()->family ?? 'N/A',
                'product' => $sku,
                'warranty' => $guaranteeStatus,
                'total' => $group->count(),
                'recovered' => $statusCounts['Recuperado'],
                'return' => $statusCounts['Devolução'],
                'misuse' => $statusCounts['Mau uso'],
                'not_found' => $statusCounts['Não encontrado'],
                'next_screening' => $statusCounts['Próxima triagem'],
            ];
        })->values();

        // Dados do cliente
        $customer = Customers::whereHas('screenings', function ($query) use ($screeningId) {
            $query->where('id', $screeningId);
        })->first();

        // Técnicos escalados 
        $technicalScales = DB::table("{$schema}technical_scales as ts")
            ->leftJoin("{$schema}users as u", "u.id", "=", "ts.user_id")
            ->where("ts.screening_id", $screeningId)
            ->select([
                "ts.id",
                "ts.user_id",
                "u.name as user_name"
            ])
            ->get()
            ->map(function ($row) {
                return [
                    'id' => $row->id,
                    'user' => [
                        'id' => $row->user_id,
                        'name' => $row->user_name,
                    ],
                ];
            });


        return Inertia::render('ExternalService/CustomerService/Partials/PrintReport', [
            'screeningId'     => $screeningId,
            'rows'            => $reportRows,
            'groupedProducts' => $groupedProducts,
            'customer'        => $customer,
            'technicalScales' => $technicalScales,
        ]);
    }
}
