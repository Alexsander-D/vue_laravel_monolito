<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductOutputResource;
use App\Models\Internal\ProductOutput;
use App\Models\Internal\Queue;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class ProductOutputController extends Controller
{
    /**
     * Mostra a tela de embalamento de produtos
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        $team = Auth::user()->currentTeam;
        $users = $team->giveUsersByTeam();

        return Inertia::render('Internal/ProductOutput', [
            'allowedUsers' => $users,
        ]);
    }

    /**
     * Cria um registro de embalamento de produtos
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function create(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'queue_id' => 'required|exists:queue,id',
        ]);

        ProductOutput::create([
            'queue_id' => $data['queue_id'],
            'user_id' => $user->id,
            'end_process' => true
        ]);

        return back()->with('message', 'Produto embalado com sucesso!');
    }

    /**
     * Retorna as informacoes de cada usuario que esta em um determinado status
     *
     * @param  int|null  $responsable
     * @return \Illuminate\Support\Collection
     */
    public function individualInfo($responsable = null)
    {
        return Queue::with('entry')
            ->with('user')
            ->when($responsable, fn($q) => $q->where('user_id', $responsable))
            ->where('status', 'RECUPERADO')
            ->whereDoesntHave('productOutput')
            ->get();
    }

    /**
     * Retorna a datatable com as informacoes de cada usuario que esta em um determinado status
     *
     * @param  int|null  $responsable
     * @return mixed
     */
    public function datatable($responsable = null)
    {
        $info = $this->individualInfo($responsable);
        $formatted = ProductOutputResource::collection($info)->response()->getData(true);

        return DataTables::of($formatted['data'])
            ->addColumn('button', function ($data) {
                return
                    '<button data-id="' . $data['id'] . '" type="button" class="create-product-output flex shrink-0 justify-center items-center gap-2 size-[38px] text-sm rounded-lg border border-transparent bg-green-600 text-white hover:bg-green-700 focus:outline-none focus:bg-green-700 disabled:opacity-50 disabled:pointer-events-none" ${disabled}>
                        ✓
                    </button>';
            })
            ->rawColumns(['button'])
            ->make(true);
    }
}

