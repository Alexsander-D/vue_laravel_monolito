<?php

namespace App\Models\External;

use App\Models\Spatie\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Screening extends Model
{
    use HasFactory;

    protected $table = 'screening';

    protected $fillable = [
        'customers_id',
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
        'scheduling_date',
        'service_start',
        'completion_date',
        'approval_date',
        'rm',
        'recovered_value',
        'return_value',
        'ndoa_value',
        'observation',
        'type_service',
        'protocol',
        'air_ticket',
        'reject_report',
        'status',
    ];

    protected $casts = [
        'scheduling_date' => 'datetime',
        'service_start' => 'datetime',
        'completion_date' => 'datetime',
        'approval_date' => 'datetime',
    ];


    /**
     * Escuta o evento de update e impede que o campo 'type_service'
     * seja alterado.
     */
    protected static function booted()
    {
        static::updating(function ($screening) {
            if ($screening->isDirty('type_service')) {
                // Restaura o valor original de 'type_service' se tentarem alterá-lo
                $screening->type_service = $screening->getOriginal('type_service');
            }
        });
    }

    /**
     * Retorna o relacionamento entre o Screening e o seu respectivo cliente.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function customers()
    {
        return $this->belongsTo(Customers::class, 'customers_id');
    }

    /**
     * Retorna as escalas técnicas relacionadas a este screening.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function technicalScales()
    {
        return $this->hasMany(TechnicalScale::class, 'screening_id');
    }

    /**
     * Retorna o relacionamento entre o Screening e o seu respectivo usuário.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Retorna os materiais relacionados a este screening.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function materials()
    {
        return $this->hasMany(Material::class, 'screening_id');
    }

    /**
     * Retorna os relatórios de triagem relacionados a este screening.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function screeningReports()
    {
        return $this->hasMany(ScreeningReport::class, 'screening_id');
    }

    public function technicians()
    {
        return $this->belongsToMany(
            User::class,
            'technical_scales',
            'screening_id',
            'user_id'
        );
    }
}
