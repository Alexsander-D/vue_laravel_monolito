<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ExcelUploadController extends Controller
{
    /**
     * Exibe o formulário de upload
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('excel.upload');
    }

    /**
     * Processa o arquivo Excel
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function upload(Request $request)
    {
        try {
            $file = $request->file('excel_file');
            $filePath = $file->getRealPath();

            $reader = IOFactory::createReader('Xlsx');
            $spreadsheet = $reader->load($filePath);
            $sheet = $spreadsheet->getActiveSheet();

            $rows = [];
            foreach ($sheet->getRowIterator() as $row) {
                $cells = [];
                foreach ($row->getCellIterator() as $cell) {
                    $cells[] = $cell->getValue();
                }
                $rows[] = $cells;
            }

            return response()->json([$rows], 200);
        } catch (\Exception $e) {
            return back()->with('error', 'Erro ao processar o arquivo: ' . $e->getMessage());
        }
    }
}

