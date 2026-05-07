<?php

namespace App\Http\Controllers\Registration;

use App\Http\Controllers\Controller;
use App\Models\Registration\Components;
use App\Models\Registration\DefectsSolutions;
use App\Models\Registration\Products;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Controller responsável pelas operações de componentes e defeitos/soluções
 */
class ComponentsDefectsAndSolutionsController extends Controller
{
    /**
     * Exibe a view de componentes e defeitos/soluções
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        return Inertia::render(
            'Internal/DefectsAndSolutions',
            [
                'componentsInfo' => Components::all(),
                'defectsSolutionsInfo' => DefectsSolutions::getDefectsSolutionsOnComponentAndFamily(),
            ]
        );
    }

    /**
     * Cria um novo componente
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $this->validateComponent($request);

        Components::create([
            'component' => $request->input('component'),
            'family' => $request->input('family.label'),
            'user_id' => Auth::id(),
        ]);

        return back();
    }

    /**
     * Cria um novo defeito/solução
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function create(Request $request)
    {
        $this->validateDefectAndSolution($request);

        DefectsSolutions::create([
            'components_id' => $request->input('components_id.id'),
            'defect' => strtoupper($request->input('defect')),
            'solution' => strtoupper($request->input('solution')),
            'user_id' => Auth::id(),
        ]);

        return back();
    }

    /**
     * Valida os dados do componente
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    private function validateComponent(Request $request)
    {
        $rules = [
            'component' => [
                'required',
                'string',
                'max:255',
                Rule::unique('components')
                    ->where(function ($query) use ($request) {
                        return $query->where('component', $request->input('component'))
                            ->where('family', $request->input('family.label'));
                    }),
            ],
            'family.label' => 'required|string',
        ];

        $messages = [
            'component.required' => 'O CAMPO NOME É OBRIGATÓRIO.',
            'component.string' => 'O NOME DEVE SER UMA STRING.',
            'component.max' => 'O NOME NÃO PODE TER MAIS DE 255 CARACTERES.',
            'component.unique' => 'ESTE COMPONENTE E FAMÍLIA JÁ ESTÃO REGISTRADOS.',
            'family.label.required' => 'O CAMPO FAMÍLIA É OBRIGATÓRIO.',
        ];

        Validator::make($request->all(), $rules, $messages)->validate();
    }

    /**
     * Valida os dados do defeito/solução
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    private function validateDefectAndSolution(Request $request)
    {
        $request->validate([
            'components_id' => 'required|array',
            'components_id.id' => 'required|integer|exists:components,id',
            'defect' => 'required|string|max:100',
            'solution' => 'required|string|max:100',
        ], [
            'components_id.required' => 'COMPONENTE E FAMÍLIA É OBRIGATÓRIO.',
            'components_id.array' => 'COMPONENTE E FAMÍLIA DEVE SER UM ARRAY.',
            'components_id.id.required' => 'O CAMPO É OBRIGATÓRIO.',
            'components_id.id.integer' => 'O CAMPO DEVE SER UM NÚMERO INTEIRO.',
            'components_id.id.exists' => 'COMPONENTE E FAMÍLIA NÃO ENCONTRADA.',

            'defect.required' => 'O CAMPO DEFEITO É OBRIGATÓRIO.',
            'defect.string' => 'O DEFEITO DEVE SER UMA STRING.',
            'defect.max' => 'O DEFEITO NÃO PODE TER MAIS DE 100 CARACTERES.',

            'solution.required' => 'O CAMPO SOLUÇÃO É OBRIGATÓRIO.',
            'solution.string' => 'A SOLUÇÃO DEVE SER UMA STRING.',
            'solution.max' => 'A SOLUÇÃO NÃO PODE TER MAIS DE 100 CARACTERES.',
        ]);
    }

    /**
     * Retorna os componentes por família
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function findComponentsByFamily(Request $request)
    {
        $components = Components::where('family', $request["family"])->get();

        return response()->json($components);
    }

    /**
     * Retorna os defeitos/soluções por componente
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function findDefects(Request $request)
    {
        $defects = DefectsSolutions::select('id', 'defect', 'solution')
            ->where('components_id', $request["component_id"])
            ->get();

        return response()->json($defects);
    }

    /**
     * Retorna as famílias por nome
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function findFamily(Request $request)
    {
        $families = Products::select('family')
            ->where('family', 'LIKE', "%" . $request->input('family') . "%")
            ->groupBy('family')
            ->get();

        return response()->json($families);
    }
}
