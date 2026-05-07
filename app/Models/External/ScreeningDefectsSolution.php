<?php

namespace App\Models\External;

use App\Models\Registration\Components;
use App\Models\Registration\DefectsSolutions;
use App\Models\Registration\Products;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScreeningDefectsSolution extends Model
{
    use HasFactory;

    protected $table = 'screening_defects_solutions';

    protected $fillable = [
        'screening_report_id',
        'component_id',
        'defects_solutions_id',
        'product',
    ];

    /**
     * The Screening Report that this Defect Solution belongs to.
     *
     * @return BelongsTo
     */
    public function screeningReport()
    {
        return $this->belongsTo(ScreeningReport::class, 'screening_report_id');
    }

    /**
     * The Product that this Defect Solution belongs to.
     *
     * @return BelongsTo
     */
    public function product()
    {
        return $this->belongsTo(Products::class, 'products_id');
    }

    /**
     * The Component that this Defect Solution belongs to.
     *
     * @return BelongsTo
     */
    public function component()
    {
        return $this->belongsTo(Components::class, 'component_id');
    }
   
    /**
     * The Defect Solution that this Screening Defect Solution belongs to.
     *
     * @return BelongsTo
     */
    public function defectSolution()
    {
        return $this->belongsTo(DefectsSolutions::class, 'defects_solutions_id');
    }
}
