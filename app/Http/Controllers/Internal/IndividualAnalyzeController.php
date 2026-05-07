<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\Internal\Queue;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class IndividualAnalyzeController extends Controller
{
    /**
     * Mostra uma lista de recursos.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Inertia\Response
     */
    public function index(Request $request)
    {
        $startDate = $request->input('startDate') ?? now()->startOfMonth()->format('Y-m-d');
        $endDate = $request->input('endDate') ?? now()->endOfMonth()->format('Y-m-d');

        return Inertia::render('Internal/IndividualViewReport', [
            'date' => [
                'startDate' => $startDate,
                'endDate' => $endDate,
            ]
        ]);
    }

    /**
     * Retorna dados para o DataTable.
     *
     * @return \Yajra\DataTables\DataTables
     */
    public function datatable()
    {
        $startDate = request('startDate');
        $endDate = request('endDate');

        $data = $this->data($startDate, $endDate);

        return DataTables::of($data)->make(true);
    }

    /**
     * Retorna dados para exportar.
     *
     * @param  string  $startDate
     * @param  string  $endDate
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function export($startDate, $endDate)
    {
        echo "resp";
    }

    /**
     * Retorna dados para o DataTable e exportar.
     *
     * @param  string  $startDate
     * @param  string  $endDate
     * @return \Illuminate\Support\Collection
     */
    private function data($startDate, $endDate)
    {
        try {
            $user = Auth::user();

            $outputs = Queue::with([
                'entry',
                'user',
                'analysis.defectSolution.component',
                'analysis.user',
                'products'
            ])
                ->whereHas('analysis') // 🔥 só traz Queue que tem analysis associada
                ->whereBetween('updated_at', [$startDate, $endDate])
                ->get();


            return $outputs->map(function ($output) {
                $analysis = $output->analysis;
                $defectSolution = $analysis?->defectSolution;

                return [
                    'id'            => $analysis?->id,
                    'created_at'    => $analysis?->created_at?->format('d/m/Y H:i:s'),
                    'user_name'     => $analysis?->user?->name,
                    'product'       => $output->product_new ?? null,
                    'family'        => $output->products?->family ?? null,
                    'component'     => $defectSolution?->component?->component ?? null,
                    'defect'        => $defectSolution?->defect ?? null,
                    'solution'      => $defectSolution?->solution ?? null,
                    'serial_number' => $output->serial_number ?? null,
                    'updated_at'    => $analysis?->updated_at?->format('d/m/Y H:i:s'),
                    'status'        => $analysis?->status ?? null,
                ];
            });
        } catch (\Throwable $e) {

            // Retorna algo amigável pro front (não quebra a request)
            return collect([[
                'error' => true,
                'message' => 'Falha ao processar os dados. Verifique o log para detalhes.' . $e->getMessage()
            ]]);
        }
    }
}
