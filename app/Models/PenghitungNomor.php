<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenghitungNomor extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    protected $table = 'penghitung_nomor';

    protected $primaryKey = 'tahun';

    protected $fillable = ['tahun', 'terakhir'];
}
