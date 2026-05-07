<?php

namespace App\Models\External;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customers extends Model
{
    use HasFactory;

    protected $table = 'customers';

    // Define as colunas que podem ser preenchidas em massa
    protected $fillable = [
        'type_person',
        'company_name',
        'trade_name',
        'cep',
        'state',
        'city',
        'road',
        'district',
        'number',
        'telephone',
        'email',
        'responsible',
        'observation',
        'government'
    ];

    /**
     * Retorna todas as triagens do cliente.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function screenings()
    {
        return $this->hasMany(Screening::class, 'customers_id', 'id');
    }

    /**
     * Define o valor da coluna 'type_person'.
     *
     * Verifica se o valor informado é um CPF ou CNPJ e, caso seja, formata
     * com pontos e tra o. Caso contr rio, n o altera o valor.
     *
     * @param string $value
     * @return void
     */
    public function setTypePersonAttribute($value)
    {
        $numericValue = preg_replace('/\D/', '', $value);

        if (strlen($numericValue) === 14) {
            // Formata como CNPJ
            $this->attributes['type_person'] = preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $numericValue);
        } elseif (strlen($numericValue) === 11) {
            // Formata como CPF
            $this->attributes['type_person'] = preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $numericValue);
        } else {
            // Não formata caso não seja válido
            $this->attributes['type_person'] = $value;
        }
    }

}
