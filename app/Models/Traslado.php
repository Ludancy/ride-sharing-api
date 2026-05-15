<?php

// app/Models/Traslado.php

// app/Models/Traslado.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Traslado extends Model
{
    use HasFactory;

    protected $fillable = [
        'idChofer',
        'idCliente',
        'direccion_origen',
        'lat_origen',
        'lng_origen',
        'direccion_destino',
        'lat_destino',
        'lng_destino',
        'costo',
        'estado',
        'idVehiculo',
    ];

    public function chofer()
    {
        return $this->belongsTo(Chofer::class, 'idChofer');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'idCliente');
    }

    public function vehiculo()
    {
        return $this->belongsTo(Vehiculo::class, 'idVehiculo');
    }
}

