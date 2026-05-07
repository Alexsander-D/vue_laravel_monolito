<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\Internal\ProductTransfer;
use App\Models\Internal\Queue;
use App\Models\Internal\Timeline;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\Request;

class ProductTransferControllerAdmin extends Controller
{
    /**
     * Mostra a tela de transferência de produtos.
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        $data = [
            'allowedUsers' => Auth::user()->currentTeam->giveUsersByTeam(),
        ];

        return Inertia::render('Internal/ProductTransferAdmin', $data);
    }

    /**
     * Retorna os dados para a Datatable.
     *
     * @return \Yajra\DataTables\DataTables
     */
    public function datatable()
    {
        $user = Auth::user();

        $outputs = ProductTransfer::withQueueAndActualDate()->get();

        $formatted = $outputs->map(function ($output) {
            return [
                'id' => $output->id,
                'sent_by' => $output->sentBy->name,
                'received_by' => $output->receivedBy->name,
                'product' => $output->queue->product,
                'status' => $output->status,
                'created_at' => $output->created_at->format('d/m/Y H:i:s'),
                'updated_at' => $output->updated_at->format('d/m/Y H:i:s'),
            ];
        });

        return DataTables::of($formatted)
            ->addColumn('button', function ($output) use ($user) {
                $url = route('product_transfer.update', $output['id']);

                $disabled = '';
                $classes = '';

                $notOwner = $user->id !== $output['received_by_id'];
                $invalidStatus = $output['status'] !== 'AGUARDANDO CONFIRMACAO';

                if ($notOwner || $invalidStatus) {
                    $classes = 'bg-gray-700 hover:bg-gray-800 cursor-not-allowed';
                    $disabled = 'disabled="true"';
                }

                return
                    '<a href="' . $url . '">
                        <button type="button" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150 disabled:bg-gray-900 ' . $classes . '" ' . $disabled . '>
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                        </button>
                    </a>';
            })
            ->rawColumns(['button'])
            ->make(true);
    }

    /**
     * Cria uma transferência de produto.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function create(Request $request)
    {
        $validated = $request->validate([
            'responsableSelect' => 'required|array',
            'responsableSelect.id' => 'exists:users,id',
            'product' => 'required|array',
            'product.label' => 'required|string|exists:products,sku',
            'product.queue_id' => 'required|integer|exists:queue,id',
            'receivedSelect' => 'required|array',
            'receivedSelect.id' => 'exists:users,id'
        ]);

        $queueId = $validated['product']['queue_id'];

        if (!$queueId) {
            return back()->withErrors(['product' => 'Produto não encontrado na fila.'])->withInput();
        }

        $product_transfer = ProductTransfer::where('queue_id', $queueId)->first();

        if ($product_transfer) {
            return back()->withErrors(['product' => 'Esse produto já está sendo transferido e ainda não foi aceito.'])->withInput();
        }

        ProductTransfer::create([
            'sent_by' => Auth::id(),
            'queue_id' => $queueId,
            'received_by' => $validated['receivedSelect']['id'],
            'status' => 'AGUARDANDO CONFIRMACAO',
        ]);

        return back()->with('message', 'Transferência criada com sucesso!');
    }
}
