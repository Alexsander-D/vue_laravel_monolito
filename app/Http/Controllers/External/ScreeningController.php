<?php

namespace App\Http\Controllers\External;

use App\Http\Controllers\Controller;
use App\Models\External\Customers;
use App\Models\External\Screening;
use App\Models\External\ScreeningTimeline;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ScreeningController extends Controller
{
    public function index()
    {
        return Inertia::render(
            'ExternalService/CustomerService/Screening',
            [
                "screeningInfo" => Customers::all(),
            ]
        );
    }

    public function create(Request $request)
    {
        $customers_id = $request->input('customers_id')['value'] ?? null;

        $request->merge(['customers_id' => $customers_id]);

        $validatedData = $request->validate([
            'customers_id' => 'required|exists:customers,id',
            'type_person' => 'required|string',
            'trade_name' => 'nullable|string',
            'company_name' => 'required|string',
            'cep' => 'required|string',
            'state' => 'required|string',
            'city' => 'required|string',
            'road' => 'required|string',
            'district' => 'required|string',
            'number' => 'required',
            'telephone' => 'required|string',
            'email' => 'required|email',
            'type_service' => 'required|string',
        ], [
            'customers_id.required' => 'A seleção do cliente é obrigatória.',
            'customers_id.exists' => 'O cliente selecionado não existe.',
            'required' => 'O campo :attribute é obrigatório.',
            'email' => 'O campo :attribute deve ser um e-mail válido.',
        ]);

        $validatedData['status'] = ($request->type_service === 'pre-agenda') ? 'aguardando agendamento' : 'aguardando produtos';

        $screening = Screening::create($validatedData);

        $responsible = Auth::user()->name;
        $description = "Triagem criada por: " . $responsible . " - ID da triagem: " . $screening->id;
        $route = route('ViewScreening.index');
        ScreeningTimeline::createHistory($screening->id, $description, $responsible, $route);


        return redirect()->route('ViewScreening.index')->with('success', 'Triagem criada com sucesso!');
    }

    public function show($customerId)
    {
        $customer = Customers::find($customerId);

        if (!$customer) {
            return response()->json(['error' => 'Cliente não encontrado'], 404);
        }

        return response()->json($customer);
    }
}
