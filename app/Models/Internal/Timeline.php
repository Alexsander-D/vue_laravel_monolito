<?php

namespace App\Models\Internal;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

class Timeline extends Model
{
    use HasFactory;

    /**
     * Define o nome da tabela.
     * Opcional se a tabela seguir a convenção padrão (plural do modelo).
     */
    protected $table = 'timeline';

    /**
     * Especifica os campos que podem ser preenchidos em massa.
     */
    protected $fillable = ['protocol', 'description', 'responsable', 'route'];

    /**
     * Cria um registro na timeline.
     *
     * @param string $protocol Protocolo a ser salvo.
     * @param string $description Descricao do registro a ser salvo.
     *
     * @return \App\Models\Timeline
     *
     * @throws \Exception Se houver um erro ao criar o registro.
     */
    public static function createHistory($protocol, $description)
    {
        $responsable = Auth::user()->name;
        $currentRoute = Route::currentRouteName();
        
        try {
            return self::create([
                'protocol' => $protocol,
                'description' => $description,
                'responsable' => $responsable,
                'route' => $currentRoute,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
