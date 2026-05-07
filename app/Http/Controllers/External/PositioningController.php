<?php

namespace App\Http\Controllers\External;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\External\Screening;
use Yajra\DataTables\Facades\DataTables;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
class PositioningController extends Controller
{
    public function index(Request $request)
    {
        // Datas iniciais padrão (mês atual)
        $startDate = $request->input('startDate') ?? now()->startOfMonth()->format('Y-m-d');
        $endDate = $request->input('endDate') ?? now()->endOfMonth()->format('Y-m-d');

        return inertia('ExternalService/CustomerService/Positioning', [
            'date' => [
                'startDate' => $startDate,
                'endDate' => $endDate,
            ]
        ]);
    }

    public function datatable(Request $request)
    {
        $startDate = $request->get('startDate');
        $endDate = $request->get('endDate');

        $query = Screening::query()
            ->with([
                'technicalScales',
                'screeningReports.products'
            ])
            ->join('customers', 'customers.id', '=', 'screening.customers_id')

            // 🔥 FILTRO DE DATAS COMPLETO (intervalo de horário)
            ->when($startDate && $endDate, function ($q) use ($startDate, $endDate) {
                $q->whereBetween('screening.service_start', [
                    $startDate . ' 00:00:00',
                    $endDate . ' 23:59:59'
                ]);
            })

            ->select(
                'screening.*',
                'customers.company_name',
                'customers.city',
                'customers.state'
            );

        return DataTables::of($query)
            ->addColumn('technicals', fn($s) => $s->technicalScales->pluck('technical')->implode(', '))
            ->addColumn('service_start', fn($s) => optional($s->service_start)->format('d/m/Y'))

            ->addColumn(
                'prod_total',
                fn($s) =>
                $s->screeningReports->whereNotIn('status', ['Proxima triagem', 'Produto nao encontrado'])->count()
            )

            ->addColumn(
                'tablet',
                fn($s) =>
                $s->screeningReports->where('products.family', 'TABLET')->count()
            )

            ->addColumn(
                'smart',
                fn($s) =>
                $s->screeningReports->where('products.family', 'SMARTPHONE')->count()
            )

            ->addColumn(
                'fp',
                fn($s) =>
                $s->screeningReports->where('products.family', 'FEATURE PHONE')->count()
            )

            ->addColumn(
                'audio',
                fn($s) =>
                $s->screeningReports->where('products.family', 'AUDIO')->count()
            )

            ->addColumn('per', function ($s) {

                $families = [
                    'AC MOBILE',
                    'AUDIO',
                    'AUTOMOTIVO',
                    'BABY',
                    'BRINQUEDO',
                    'COMPONENTE',
                    'DRONE',
                    'ELETRO BEAUTY',
                    'ELETROPORTATEIS',
                    'EMBALAGEM',
                    'ESCRITORIO',
                    'ESPORTES',
                    'FERRAMENTAS',
                    'GYM',
                    'HEALTH CARE',
                    'INFORMATICA',
                    'LIQUIDIFICADOR',
                    'MEMORIA RAM',
                    'MEMORY CARD',
                    'MIDIA',
                    'MOBILIDADE ELETRICA',
                    'OEM',
                    'PAPEL E ESCRITORIO',
                    'PEN DRIVE',
                    'SEGURANCA',
                    'SETTOP BOX',
                    'SSD',
                    'VIDEO',
                    'VENTILADOR',
                    'WEARABLE',
                    'ROYAL ENFIELD',
                    'MONTADORA RADIO',
                ];

                return $s->screeningReports->filter(function ($r) use ($families) {
                    return in_array(optional($r->products)->family, $families);
                })->count();
            })

            ->addColumn(
                'pc',
                fn($s) =>
                $s->screeningReports->where('products.family', 'PERSONAL COMPUTER')->count()
            )

            ->addColumn('rec', fn($s) => $s->screeningReports->where('status', 'Recuperado')->count())
            ->addColumn('dev', fn($s) => $s->screeningReports->where('status', 'Devolução')->count())
            ->addColumn('fg', fn($s) => $s->screeningReports->where('status', 'Fora de garantia')->count())
            ->addColumn('mu', fn($s) => $s->screeningReports->where('status', 'Mau uso')->count())
            ->addColumn('pne', fn($s) => $s->screeningReports->where('status', 'Nao encontrado')->count())
            ->addColumn('pt', fn($s) => $s->screeningReports->where('status', 'Próxima triagem')->count())

            ->make(true);
    }

    public function positioningExport(Request $request)
    {
        $startDate = $request->get('startDate');
        $endDate = $request->get('endDate');

        // 📄 Criando planilha
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // 🧾 Cabeçalhos
        $headers = [
            'ID',
            'Cliente',
            'Cidade',
            'UF',
            'Início do Serviço',
            'Técnicos',
            'Total Produtos',
            'Tablet',
            'Smartphone',
            'Feature Phone',
            'Audio',
            'PER',
            'PC',
            'Recuperado',
            'Devolução',
            'Fora Garantia',
            'Mau Uso',
            'Não Encontrado',
            'Próxima Triagem'
        ];

        foreach ($headers as $i => $header) {
            $col = chr(65 + $i);
            $sheet->setCellValue($col . '1', $header);
        }

        // 🔍 Mesma query do DataTable
        $entries = Screening::query()
            ->with([
                'technicalScales',
                'screeningReports.products'
            ])
            ->join('customers', 'customers.id', '=', 'screening.customers_id')
            ->when($startDate && $endDate, function ($q) use ($startDate, $endDate) {
                $q->whereBetween('screening.service_start', [
                    $startDate . ' 00:00:00',
                    $endDate . ' 23:59:59'
                ]);
            })
            ->select(
                'screening.*',
                'customers.company_name',
                'customers.city',
                'customers.state'
            )
            ->get();

        // 📌 Famílias PER (mesmo bloco do datatable)
        $familiesPER = [
            'AC MOBILE',
            'AUDIO',
            'AUTOMOTIVO',
            'BABY',
            'BRINQUEDO',
            'COMPONENTE',
            'DRONE',
            'ELETRO BEAUTY',
            'ELETROPORTATEIS',
            'EMBALAGEM',
            'ESCRITORIO',
            'ESPORTES',
            'FERRAMENTAS',
            'GYM',
            'HEALTH CARE',
            'INFORMATICA',
            'LIQUIDIFICADOR',
            'MEMORIA RAM',
            'MEMORY CARD',
            'MIDIA',
            'MOBILIDADE ELETRICA',
            'OEM',
            'PAPEL E ESCRITORIO',
            'PEN DRIVE',
            'SEGURANCA',
            'SETTOP BOX',
            'SSD',
            'VIDEO',
            'VENTILADOR',
            'WEARABLE',
            'ROYAL ENFIELD',
            'MONTADORA RADIO'
        ];

        // 📤 Preenchendo linhas
        $row = 2;
        foreach ($entries as $s) {

            $sheet->setCellValue("A{$row}", $s->id);
            $sheet->setCellValue("B{$row}", $s->company_name);
            $sheet->setCellValue("C{$row}", $s->city);
            $sheet->setCellValue("D{$row}", $s->state);
            $sheet->setCellValue("E{$row}", optional($s->service_start)->format('d/m/Y'));

            $technicals = $s->technicalScales->pluck('technical')->implode(', ');
            $sheet->setCellValue("F{$row}", $technicals);

            $sheet->setCellValue("G{$row}", $s->screeningReports->whereNotIn('status', ['Proxima triagem', 'Produto nao encontrado'])->count());
            $sheet->setCellValue("H{$row}", $s->screeningReports->where('products.family', 'TABLET')->count());
            $sheet->setCellValue("I{$row}", $s->screeningReports->where('products.family', 'SMARTPHONE')->count());
            $sheet->setCellValue("J{$row}", $s->screeningReports->where('products.family', 'FEATURE PHONE')->count());
            $sheet->setCellValue("K{$row}", $s->screeningReports->where('products.family', 'AUDIO')->count());

            $sheet->setCellValue("L{$row}", $s->screeningReports->filter(function ($r) use ($familiesPER) {
                return in_array(optional($r->products)->family, $familiesPER);
            })->count());

            $sheet->setCellValue("M{$row}", $s->screeningReports->where('products.family', 'PERSONAL COMPUTER')->count());
            $sheet->setCellValue("N{$row}", $s->screeningReports->where('status', 'Recuperado')->count());
            $sheet->setCellValue("O{$row}", $s->screeningReports->where('status', 'Devolução')->count());
            $sheet->setCellValue("P{$row}", $s->screeningReports->where('status', 'Fora de garantia')->count());
            $sheet->setCellValue("Q{$row}", $s->screeningReports->where('status', 'Mau uso')->count());
            $sheet->setCellValue("R{$row}", $s->screeningReports->where('status', 'Nao encontrado')->count());
            $sheet->setCellValue("S{$row}", $s->screeningReports->where('status', 'Próxima triagem')->count());

            $row++;
        }

        // 💾 Download
        $writer = new Xlsx($spreadsheet);
        $filename = 'positioning_export_' . date('YmdHis') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), $filename);
        $writer->save($tempFile);

        return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
    }
}
