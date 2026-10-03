<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeneralJournal extends Model
{
    protected $table = 'general_journals';
    public $timestamps = false;
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $casts = [
        'transaction_date' => 'datetime',
        'amount_in' => 'integer',
        'amount_out' => 'integer',
    ];

    public function getProofImageUrlAttribute(): ?string
    {
        if (!$this->proof_image) return null;
        if (filter_var($this->proof_image, FILTER_VALIDATE_URL)) return $this->proof_image;
        return asset('storage/' . $this->proof_image);
    }
}
