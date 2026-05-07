<?php

namespace App\Models\External;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScreeningTimeline extends Model
{
    use HasFactory;

    protected $table = 'screening_timelines';

    protected $fillable = [
        'screening_id',
        'description',
        'responsible',
        'route',
    ];

    // Relacionamento com Screening
    public function screening()
    {
        return $this->belongsTo(Screening::class, 'screening_id');
    }

    /**
     * Método para criar um registro na timeline
     *
     * @param int $screening_id
     * @param string $description
     * @param string $responsible
     * @param string $route
     * @return \App\Models\ScreeningTimeline
     */
    public static function createHistory($screening_id, $description, $responsible, $route)
    {
        return self::create([
            'screening_id' => $screening_id,
            'description'  => $description,
            'responsible'  => $responsible,
            'route'        => $route,
        ]);
    }
}
