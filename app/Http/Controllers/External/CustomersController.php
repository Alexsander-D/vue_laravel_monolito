<?php

namespace App\Http\Controllers\External;

use App\Http\Controllers\Controller;
use App\Models\External\Customers;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;

class CustomersController extends Controller
{
    public function show($customerId = "", $message = "")
    {
        if ($customerId) {
            return Inertia::render(
                'ExternalService/CustomerService/Customers',
                [
                    "customersInfo" => Customers::find($customerId),
                ]
            );
        } else {
            return Inertia::render(
                'ExternalService/CustomerService/Customers'
            );
        }
    }

    public function create(Request $request): RedirectResponse
    {
        $state = $request["state"];
        $city = $request["city"];

        $rules = [
            'cnpj' => 'nullable|max:20|unique:customers,type_person',
            'cpf' => 'nullable|max:20|unique:customers,type_person',
            'type_person' => 'required|max:255',
            'company_name' => 'required|max:255',
            'trade_name' => 'required_if:type_person,juridica',
            'cep' => 'required|max:255',
            'state' => 'required|array',
            'city' => 'required|array',
            'road' => 'required|max:255',
            'district' => 'required|max:255',
            'number' => 'required|max:255',
            'telephone' => 'required|max:255',
            'email' => 'required|email|max:255',
            'responsible' => 'nullable|max:255',
            'observation' => 'nullable|max:255',
            'government' => 'boolean',
        ];

        $messages = [
            'cnpj.unique' => 'O CNPJ informado já está cadastrado.',
            'cpf.unique' => 'O CPF informado já está cadastrado.',
            'company_name.required' => 'O nome da empresa é obrigatório.',
            'company_name.max' => 'O nome da empresa deve ter no máximo 100 caracteres.',
            'trade_name.required' => 'O nome fantasia é obrigatório.',
            'cep.required' => 'O CEP é obrigatório.',
            'cep.max' => 'O CEP deve ter no máximo 9 caracteres.',
            'cep.regex' => 'O CEP deve ter o formato "00000-000".',
            'state.required' => 'O estado é obrigatório.',
            'city.required' => 'A cidade é obrigatória.',
            'road.required' => 'A rua é obrigatória.',
            'road.max' => 'A rua deve ter no máximo 150 caracteres.',
            'district.required' => 'O bairro é obrigatório.',
            'district.max' => 'O bairro deve ter no máximo 100 caracteres.',
            'number.required' => 'O número é obrigatório.',
            'number.max' => 'O número deve ter no máximo 10 caracteres.',
            'telephone.required' => 'O telefone é obrigatório.',
            'telephone.max' => 'O telefone deve ter no máximo 15 caracteres.',
            'email.required' => 'O e-mail é obrigatório.',
            'email.email' => 'O e-mail deve ser um endereço de e-mail válido.',
            'email.max' => 'O e-mail deve ter no máximo 100 caracteres.',
            'responsible.required' => 'O responsável é obrigatório.',
            'responsible.max' => 'O responsável deve ter no máximo 100 caracteres.',

        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return back()->withErrors($validator)
                ->withInput();
        }

        $validatedData = $validator->validated();

        if ($validatedData['cnpj']) {
            $validatedData['type_person'] = $validatedData['cnpj'];
        } else {
            $validatedData['type_person'] = $validatedData['cpf'];
        }
        $validatedData['government'] = $request->government ? 'GOVERNO' : 'VAREJO';

        Customers::create([
            'type_person' => $validatedData['type_person'],
            'company_name' => $validatedData['company_name'],
            'trade_name' => $validatedData['trade_name'],
            'cep' => $validatedData['cep'],
            'state' => $state['label'],
            'city' => $city['label'],
            'road' => $validatedData['road'],
            'district' => $validatedData['district'],
            'number' => $validatedData['number'],
            'telephone' => $validatedData['telephone'],
            'email' => $validatedData['email'],
            'responsible' => $validatedData['responsible'],
            'observation' => $validatedData['observation'] ?? null,
            'government' => $validatedData['government'] ?? false,
        ]);
        return redirect()->route('viewCustomers.show');
    }

    public function update(Request $request)
    {
        $state = $request["state"];
        $city = $request["city"];

        $rules = [
            'cnpj' => 'nullable|max:20',
            'cpf' => 'nullable|max:20',
            'type_person' => 'required|max:255',
            'company_name' => 'required|max:255',
            'trade_name' => 'required_if:type_person,juridica|max:255',
            'cep' => 'required|max:255',
            'state' => 'required|array',
            'city' => 'required|array',
            'road' => 'required|max:255',
            'district' => 'required|max:255',
            'number' => 'required|max:255',
            'telephone' => 'required|max:255',
            'email' => 'required|email|max:255',
            'responsible' => 'nullable|max:255',
            'observation' => 'nullable|max:255',
            'government' => 'boolean',
        ];

        $messages = [
            'cnpj.max' => 'O preenchimento deve ter no máximo 20 caracteres.',
            'cpf.max' => 'O preenchimento deve ter no máximo 20 caracteres.',
            'company_name.required' => 'O nome da empresa é obrigatório.',
            'company_name.max' => 'O nome da empresa deve ter no máximo 100 caracteres.',
            'trade_name.required' => 'O nome fantasia é obrigatório.',
            'trade_name.max' => 'O nome fantasia deve ter no máximo 100 caracteres.',
            'cep.required' => 'O CEP é obrigatório.',
            'cep.max' => 'O CEP deve ter no máximo 9 caracteres.',
            'cep.regex' => 'O CEP deve ter o formato "00000-000".',
            'state.required' => 'O estado é obrigatório.',
            'city.required' => 'A cidade é obrigatória.',
            'road.required' => 'A rua é obrigatória.',
            'road.max' => 'A rua deve ter no máximo 150 caracteres.',
            'district.required' => 'O bairro é obrigatório.',
            'district.max' => 'O bairro deve ter no máximo 100 caracteres.',
            'number.required' => 'O número é obrigatório.',
            'number.max' => 'O número deve ter no máximo 10 caracteres.',
            'telephone.required' => 'O telefone é obrigatório.',
            'telephone.max' => 'O telefone deve ter no máximo 15 caracteres.',
            'email.required' => 'O e-mail é obrigatório.',
            'email.email' => 'O e-mail deve ser um endereço de e-mail válido.',
            'email.max' => 'O e-mail deve ter no máximo 100 caracteres.',
            'responsible.required' => 'O responsável é obrigatório.',
            'responsible.max' => 'O responsável deve ter no máximo 100 caracteres.',
            'government.boolean' => 'O valor deve ser ser uma boleana.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return back()->withErrors($validator)
                ->withInput();
        }

        $validatedData = $validator->validated();

        if ($validatedData['cnpj']) {
            $validatedData['type_person'] = $validatedData['cnpj'];
        } else {
            $validatedData['type_person'] = $validatedData['cpf'];
        }
        $validatedData['government'] = $request->government ? 'GOVERNO' : 'VAREJO';

        $customer = Customers::find($request['customerId']);
        if ($customer) {
            try {
                $customer->update([
                    'type_person' => $validatedData['type_person'],
                    'company_name' => $validatedData['company_name'],
                    'trade_name' => $validatedData['trade_name'],
                    'cep' => $validatedData['cep'],
                    'state' => $state['label'],
                    'city' => $city['label'],
                    'road' => $validatedData['road'],
                    'district' => $validatedData['district'],
                    'number' => $validatedData['number'],
                    'telephone' => $validatedData['telephone'],
                    'email' => $validatedData['email'],
                    'responsible' => $validatedData['responsible'],
                    'observation' => $validatedData['observation'],
                    'government' => $validatedData['government'] ?? false,
                ]);
                return redirect()->route('viewCustomers.show');
            } catch (QueryException $e) {
                return redirect()->route('viewCustomers.show')
                ->with('message', "Já existe um cliente cadastrado com essa informação!");
            }
        } else {
            return response()->json(['message' => 'Customer not found'], 404);
        }
    }
}
