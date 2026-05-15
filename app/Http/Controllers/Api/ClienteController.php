<?php

// app/Http/Controllers/ClienteController.php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\Cliente;
use App\Models\Traslado;
use App\Models\Banco;
use App\Models\SaldoCliente;
use App\Models\Chofer;
use App\Models\Vehiculo;
use App\Models\Lugar;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller; 
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ClienteController extends Controller
{
    public function index()
    {
        $clientes = DB::table('cliente')->get();
        return response()->json($clientes);
    }

    public function show($id)
    {
        $cliente = DB::table('cliente')->find($id);
        if (!$cliente) {
            return response()->json(['message' => 'Cliente no encontrado.'], 500);
        }
        return response()->json($cliente);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string',
            'apellido' => 'required|string',
            // Agrega otras reglas de validación según tus necesidades
        ]);

        $clienteId = DB::table('cliente')->insertGetId($request->all());

        $cliente = DB::table('cliente')->find($clienteId);

        return response()->json($cliente, Response::HTTP_CREATED);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nombre' => 'required|string',
            'apellido' => 'required|string',
            // Agrega otras reglas de validación según tus necesidades
        ]);

        $cliente = DB::table('cliente')->find($id);
        if (!$cliente) {
            return response()->json(['message' => 'Cliente no encontrado.'], 500);
        }

        DB::table('cliente')->where('id', $id)->update($request->all());

        $cliente = DB::table('cliente')->find($id);

        return response()->json($cliente, 200);
    }

    public function destroy($id)
    {
        $cliente = DB::table('cliente')->find($id);
        if (!$cliente) {
            return response()->json(['message' => 'Cliente no encontrado.'], 500);
        }

        DB::table('cliente')->where('id', $id)->delete();
        // Elimina el usuario relacionado (ajusta según tu lógica de base de datos)
        // DB::table('users')->where('cliente_id', $id)->delete();

        return response()->json(['message' => 'Cliente eliminado con éxito.'], 200);
    }
    public function solicitarTraslado(Request $request, $idCliente)
    {
        try {
            // Validar la solicitud
            $request->validate([
                'direccion_origen' => 'required|string',
                'lat_origen' => 'required|numeric',
                'lng_origen' => 'required|numeric',
                'direccion_destino' => 'required|string',
                'lat_destino' => 'required|numeric',
                'lng_destino' => 'required|numeric',
                'costo' => 'required|numeric',
            ]);
    
            // Buscar al cliente por ID
            $cliente = DB::table('cliente')->find($idCliente);
    
            if (!$cliente) {
                return response()->json(['message' => 'Cliente no encontrado.'], 404);
            }
    
            // Buscar chofer aleatorio con vehículo en estado "aprobado" y "activo"
            $choferAleatorio = DB::table('chofers')
                ->join('vehiculos', 'chofers.id', '=', 'vehiculos.idChofer')
                ->where('vehiculos.estado_actual', 'activo')
                ->where('vehiculos.estado_vehiculo', 'Aprobado')
                ->inRandomOrder()
                ->first();
    
            if (!$choferAleatorio) {
                return response()->json(['message' => 'No hay choferes disponibles con vehículos aprobados y activos.'], 400);
            }
    
            $costoTraslado = $request->costo;
    
            // Buscar vehículo activo del chofer
            $vehiculoChofer = DB::table('vehiculos')
                ->where('idChofer', $choferAleatorio->idChofer)
                ->where('estado_actual', 'activo')
                ->first();
    
            if (!$vehiculoChofer) {
                return response()->json(['message' => 'No hay vehículos disponibles para este chofer.'], 400);
            }
    
            // Crear el traslado
            $traslado = DB::table('traslados')->insertGetId([
                'direccion_origen' => $request->direccion_origen,
                'lat_origen' => $request->lat_origen,
                'lng_origen' => $request->lng_origen,
                'direccion_destino' => $request->direccion_destino,
                'lat_destino' => $request->lat_destino,
                'lng_destino' => $request->lng_destino,
                'costo' => $costoTraslado,
                'idCliente' => $idCliente,
                'estado' => 'Pendiente',
                'idChofer' => $choferAleatorio->idChofer,
                'idVehiculo' => $vehiculoChofer->id,
                'fecha_creacion' => now(),
            ]);
    
    
            // Actualizar saldos
            DB::table('cliente')->where('id', $idCliente)->decrement('saldo', $costoTraslado);
            DB::table('chofers')->where('id', $choferAleatorio->id)->increment('saldo', $costoTraslado * 0.7); // 70% para el chofer
    
            return response()->json(['message' => 'Traslado solicitado con éxito.','trasladoId'=>$traslado], 201);
    
        } catch (\Exception $e) {
            // Manejo de excepciones
            return response(['error' => $e->getMessage()]);
        }
    }
    

    public function recargaSaldo(Request $request, $idCliente)
    {
      // Validar la solicitud
      $validator = Validator::make($request->all(), [
        'fechaRecarga' => 'required|date',
        'referencia' => 'required',
        'idBanco' => 'required|exists:bancos,id',
        'monto' => 'required|numeric',
    ]);


        try {
            // Buscar al cliente por ID
            $cliente = DB::table('cliente')->find($idCliente);

            if (!$cliente) {
                return response()->json(['error' => 'Cliente no encontrado.'], 404);
            }

            // Buscar el banco por ID
            $banco = DB::table('bancos')->find($request->idBanco);

            if (!$banco) {
                return response()->json(['error' => 'Banco no encontrado.'], 404);
            }

            // Registrar la recarga de saldo con la asociación al cliente y al banco
            $recargaId = DB::table('saldo_clientes')->insertGetId([
                'fecha_recarga' => $request->fechaRecarga,
                'referencia' => $request->referencia,
                'monto' => $request->monto,
                'idBanco' => $banco->id,
                'idCliente' => $cliente->id
            ]);

            // Actualizar el saldo del cliente
            DB::table('cliente')->where('id', $idCliente)->increment('saldo', $request->monto);

            return response()->json(['message' => 'Recarga de saldo realizada con éxito.', 'recarga_id' => $recargaId]);
        } catch (\Exception $e) {
            // Manejar otras excepciones
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    
    public function obtenerTrasladosCliente(Request $request)
    {
        try {
            // Obtener el ID del usuario autenticado directamente desde la sesión
            $userId = auth()->id();
    
            // Verificar si el usuario autenticado es un cliente
            $cliente = DB::table('cliente')->where('idAuth', $userId)->first();
    
            if (!$cliente) {
                return response()->json(['message' => 'El usuario autenticado no es un cliente.'], 403);
            }
    
            // Obtener los traslados realizados por el cliente
            $traslados = DB::select('
                SELECT * 
                FROM traslados 
                WHERE idCliente = :cliente_id 
                ORDER BY fecha_creacion DESC
            ', ['cliente_id' => $cliente->id]);
    
            // Puedes personalizar la respuesta según tus necesidades
            return response()->json(['traslados' => $traslados]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    public function trasladosCliente($clienteId)
    {
        try {
            // Obtener los traslados realizados por el cliente con los nombres de los lugares de origen y destino,
            // así como el nombre del cliente y el nombre del chofer
            $traslados = DB::select('
                SELECT 
                    traslados.*, 
                    traslados.direccion_origen as nombre_origen,
                    traslados.direccion_destino as nombre_destino,
                    cliente.nombre as nombre_cliente,
                    chofers.nombre as nombre_chofer
                FROM traslados 
                JOIN cliente ON traslados.idCliente = cliente.id
                JOIN chofers ON traslados.idChofer = chofers.id
                WHERE traslados.idCliente = :cliente_id 
                ORDER BY traslados.fecha_creacion DESC
            ', ['cliente_id' => $clienteId]);
    
            // Puedes personalizar la respuesta según tus necesidades
            return response()->json(['traslados' => $traslados]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    public function obtenerTrasladoPorId($trasladoId)
    {
        try {
            // Buscar el traslado del cliente por ID
            $traslado = DB::table('traslados')
                ->select(
                    'traslados.id as trasladoId',
                    'traslados.*',
                    'chofers.id as choferId',
                    'chofers.*',
                    'vehiculos.id as vehiculoId',
                    'vehiculos.*',
                    'traslados.direccion_origen as origenNombre',
                    'traslados.direccion_destino as destinoNombre'
                )
                ->join('chofers', 'traslados.idChofer', '=', 'chofers.id')
                ->join('vehiculos', 'traslados.idVehiculo', '=', 'vehiculos.id')
                ->where('traslados.id', $trasladoId)
                ->orderBy('traslados.fecha_creacion', 'DESC')
                ->first();
    
            // Devolver los datos del traslado, chofer y vehículo asociado
            return response()->json($traslado, 200);
    
        } catch (\Exception $e) {
            // Manejo de excepciones
            return response(["error" => $e->getMessage()], 500);
        }
    }
    

    public function obtenerTodosLosTraslados()
    {
        try {
            $traslados = DB::table('traslados')
            ->select(
                'traslados.id as trasladoId',
                'traslados.*', // Puedes mantener el resto de los campos de traslados
                'chofers.id as choferId',
                'chofers.*', // Puedes mantener el resto de los campos de chofer
                'vehiculos.id as vehiculoId',
                'vehiculos.*' // Puedes mantener el resto de los campos de vehiculo
            )
            ->join('chofers', 'traslados.idChofer', '=', 'chofers.id')
            ->join('vehiculos', 'traslados.idVehiculo', '=', 'vehiculos.id')
            ->orderBy('traslados.fecha_creacion', 'DESC')
            ->get();
    
            if ($traslados->isEmpty()) {
                return response(["message" => "No se encontraron traslados"], 404);
            }
    
            return response()->json($traslados, 200);
    
        } catch (\Exception $e) {
            return response(["error" => $e->getMessage()], 500);
        }
    }

    public function eliminarTraslado($trasladoId)
{
    try {
        // Eliminar el traslado
        DB::table('traslados')->where('id', $trasladoId)->delete();

        // ... (realizar otras acciones si es necesario)

        return response()->json(['message' => 'Traslado eliminado con éxito.'], 200);

    } catch (\Exception $e) {
        return response(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
    public function historialRecargasCliente(Request $request, $clienteId)
    {
        try {
            // Obtener el historial de recargas del cliente
            $historialRecargas = DB::select('
                SELECT * 
                FROM saldo_clientes 
                WHERE idCliente = :cliente_id 
                ORDER BY fecha_recarga DESC
            ', ['cliente_id' => $clienteId]);
    
            // Puedes personalizar la respuesta según tus necesidades
            return response()->json(['historial_recargas' => $historialRecargas]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function obtenerDatosTrasladoCliente($clienteId)
    {
        try {
            // Buscar los traslados del cliente
            $traslados = DB::table('traslados')
                ->select('traslados.*', 'chofers.*', 'vehiculos.*')
                ->join('chofers', 'traslados.idChofer', '=', 'chofers.id')
                ->join('vehiculos', 'traslados.idVehiculo', '=', 'vehiculos.id')
                ->where('traslados.idCliente', $clienteId)
                ->get();
    
            if (empty($traslados)) {
                return response(["message" => "No hay traslados para este cliente"], 500);
            }
    
            // Devolver los datos de los traslados, chofer y vehículo asociado
            return response()->json($traslados, 200);
    
        } catch (\Exception $e) {
            // Manejo de excepciones
            return response(["error" => $e->getMessage()], 500);
        }
    }
}
