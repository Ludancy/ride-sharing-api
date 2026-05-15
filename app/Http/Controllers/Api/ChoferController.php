<?php

namespace App\Http\Controllers\Api;

use App\Models\Chofer;
use App\Models\PruebaChofer;
use App\Models\Traslado;
use App\Models\PruebaVehiculo;
use App\Models\ContactoEmergenciaChofer;
use App\Models\BancoChofer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;



use App\Http\Controllers\Controller; // Asegúrate de incluir esta línea

class ChoferController extends Controller
{

    // Agrega esta función al final de tu controlador ChoferController.php
    public function evaluacionPsicologica(Request $request)
    {
        // Validación de datos
        $validator = Validator::make($request->all(), [
            'idChofer' => 'required|exists:chofers,id',
            'calificacion' => 'required|numeric|min:0|max:100',
            // Agrega otras reglas de validación según tus necesidades
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        try {
            // Iniciar transacción
            DB::beginTransaction();

            // Crear la evaluación psicológica directamente en la base de datos
            $pruebaChoferId = DB::table('pruebaChofer')->insertGetId([
                'idChofer' => $request->input('idChofer'),
                'calificacion' => $request->input('calificacion'),
                'fecha_creacion' => now(),
                // Agrega otros campos según tus necesidades
            ]);

            // Confirmar la transacción
            DB::commit();

            // Obtener la evaluación psicológica recién creada
            $pruebaChofer = DB::table('pruebaChofer')->find($pruebaChoferId);

            return response()->json($pruebaChofer, 201);
        } catch (\Exception $e) {
            // Revertir la transacción en caso de error
            DB::rollBack();

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Agrega esta función al final de tu controlador ChoferController.php
    public function getEvaluacionPsicologica($id)
    {
        // Validar que el chofer exista
        $validator = Validator::make(['idChofer' => $id], [
            'idChofer' => 'exists:chofers,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 404);
        }

        try {
            // Obtener la evaluación psicológica del chofer
            $evaluacion = DB::table('pruebaChofer')
                ->where('idChofer', $id)
                ->orderBy('fecha_creacion', 'desc')
                ->first();

            if (!$evaluacion) {
                return response()->json(['message' => 'No se encontró ninguna evaluación psicológica para el chofer.'], 404);
            }

            return response()->json($evaluacion);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Obtener todas las evaluaciones psicológicas de todos los choferes
    public function indexTodasEvaluacionesPsicologicas()
    {
        try {
            $evaluaciones = DB::table('pruebaChofer')
                ->orderBy('fecha_creacion', 'desc')
                ->get();

            if ($evaluaciones->isEmpty()) {
                return response()->json(['message' => 'No se encontraron evaluaciones psicológicas.'], 404);
            }

            return response()->json($evaluaciones);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function deleteEvaluacionPsicologica($id)
    {
        try {
            // Eliminar la evaluación psicológica
            DB::table('pruebaChofer')->where('idChofer', $id)->delete();

            return response()->json(['message' => 'Evaluación psicológica eliminada correctamente.'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    public function updateEvaluacionPsicologica(Request $request, $id)
    {
        // Validación de datos
        $validator = Validator::make($request->all(), [
            'calificacion' => 'required|numeric|min:0|max:100',
            // Agrega otras reglas de validación según tus necesidades
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        try {
            // Actualizar la evaluación psicológica
            DB::table('pruebaChofer')
                ->where('idChofer', $id)
                ->update([
                    'calificacion' => $request->input('calificacion'),
                    // Agrega otros campos según tus necesidades
                ]);

            // Obtener la evaluación psicológica actualizada
            $evaluacion = DB::table('pruebaChofer')->where('idChofer', $id)->first();

            return response()->json($evaluacion, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getChoferes()
    {
        try {
            // Obtener todos los choferes utilizando una consulta sin Eloquent ORM
            $choferes = DB::table('chofers')->get();

            return response()->json(['choferes' => $choferes], 200);
        } catch (\Exception $e) {
            // Manejo de excepciones
            return response(["error" => $e->getMessage()], 500);
        }
    }
    public function getInfo($id)
    {
        try {
            // Obtener datos del chofer sin Eloquent ORM
            $chofer = DB::table('chofers')
                ->where('chofers.id', $id)
                ->first();
    
            // Si el chofer no existe, devuelve un mensaje de error
            if (!$chofer) {
                return response(["message" => "Chofer no encontrado"], 404);
            }
    
            // Obtener la cuenta bancaria activa del chofer utilizando una consulta sin Eloquent ORM
            $cuentaBancariaActiva = DB::table('banco_chofer')
                ->join('bancos', 'banco_chofer.idBanco', '=', 'bancos.id')
                ->where('banco_chofer.idChofer', $id)
                ->where('banco_chofer.estado', 'activo') // Condición adicional para cuentas activas
                ->select('banco_chofer.*', 'bancos.nombre as entidadBancaria', 'bancos.codigo as bancoCodigo')
                ->first();
    
            // Obtener todas las cuentas bancarias del chofer
            $cuentasBancarias = DB::table('banco_chofer')
                ->join('bancos', 'banco_chofer.idBanco', '=', 'bancos.id')
                ->where('banco_chofer.idChofer', $id)
                ->select('banco_chofer.*', 'bancos.nombre as entidadBancaria', 'bancos.codigo as numeroCuenta')
                ->get();
    
            // Obtener contactos de emergencia utilizando una consulta sin Eloquent ORM
            $contactosEmergencia = DB::table('contacto_emergencia_chofer')
                ->where('idChofer', $id)
                ->get();
    
            // Estructurar los datos de respuesta
            $respuesta = [
                "id" => $chofer->id,
                "nombre" => $chofer->nombre,
                "apellido" => $chofer->apellido,
                "cedula" => $chofer->cedula,
                "fechaNacimiento" => $chofer->fechaNacimiento,
                "idAuth" => $chofer->idAuth,
                "saldo" => "0.00",
                "idChofer" => $cuentaBancariaActiva ? $cuentaBancariaActiva->idChofer : null,
                "idBanco" => $cuentaBancariaActiva ? $cuentaBancariaActiva->idBanco : null,
                "nroCuenta" => $cuentaBancariaActiva ? $cuentaBancariaActiva->nroCuenta : null,
                "bancoNombre" => $cuentaBancariaActiva ? $cuentaBancariaActiva->entidadBancaria : null,
                "estado" => $cuentaBancariaActiva ? $cuentaBancariaActiva->estado : null,
                "bancoCodigo" => $cuentaBancariaActiva ? $cuentaBancariaActiva->bancoCodigo : null,
                "cuentas_bancarias" => $cuentasBancarias,
                "contactosEmergencia" => $contactosEmergencia,
            ];
    
            return response()->json($respuesta, 200);
    
        } catch (\Exception $e) {
            // Manejo de excepciones
            return response(["error" => $e->getMessage()], 500);
        }
    }

    

    public function getContactosEmergenciaById($idContactoEmergencia)
    {
        try {
            // Obtener un solo contacto de emergencia por su ID sin Eloquent ORM
            $contactoEmergencia = DB::table('contacto_emergencia_chofer')
                ->where('id', $idContactoEmergencia)
                ->first();
    
            // Si no se encuentra el contacto de emergencia, devuelve un mensaje de error
            if (!$contactoEmergencia) {
                return response(["message" => "No se encontró el contacto de emergencia con ID $idContactoEmergencia"], 404);
            }
    
            return response()->json($contactoEmergencia, 200);
    
        } catch (\Exception $e) {
            // Manejo de excepciones
            return response(["error" => $e->getMessage()], 500);
        }
    } 

    public function getContactosEmergenciaByChoferId($id)
    {
        try {
            // Obtener contactos de emergencia utilizando una consulta sin Eloquent ORM
            $contactosEmergencia = DB::table('contacto_emergencia_chofer')
                ->where('idChofer', $id)
                ->get();
    
  
    
            return response()->json($contactosEmergencia, 200);
    
        } catch (\Exception $e) {
            // Manejo de excepciones
            return response(["error" => $e->getMessage()], 500);
        }
    }

    public function actualizarContactoEmergencia(Request $request, $idContactoEmergencia)
    {
        try {
            $validator = Validator::make($request->all(), [
                'nombre' => 'required|string',
                'telefono' => 'required|string',
            ]);
    
            if ($validator->fails()) {
                return response(['error' => $validator->errors()], 400);
            }
    
            DB::table('contacto_emergencia_chofer')
                ->where('id', $idContactoEmergencia)
                ->update([
                    'nombre' => $request->nombre,
                    'telefono' => $request->telefono,
                ]);
    
            return response(['message' => 'Contacto de emergencia actualizado con éxito'], 200);
    
        } catch (\Exception $e) {
            return response(['error' => $e->getMessage()], 500);
        }
    }

    public function eliminarContactoEmergencia($idContactoEmergencia)
    {
        try {
            DB::table('contacto_emergencia_chofer')->where('id', $idContactoEmergencia)->delete();
    
            return response(['message' => 'Contacto de emergencia eliminado con éxito'], 200);
    
        } catch (\Exception $e) {
            return response(['error' => $e->getMessage()], 500);
        }
    }



    public function crearContactoEmergencia(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'choferId' => 'required|integer', // Asegúrate de que 'choferId' sea un entero
                'nombre' => 'required|string',
                'telefono' => 'required|string',
            ]);
    
            if ($validator->fails()) {
                return response(['error' => $validator->errors()], 400);
            }
    
            $nuevoContactoEmergencia = [
                'idChofer' => $request->choferId,
                'nombre' => $request->nombre,
                'telefono' => $request->telefono,
            ];
    
            $idContactoEmergencia = DB::table('contacto_emergencia_chofer')->insertGetId($nuevoContactoEmergencia);
    
            return response(['message' => 'Contacto de emergencia creado con éxito', 'idContactoEmergencia' => $idContactoEmergencia], 201);
    
        } catch (\Exception $e) {
            return response(['error' => $e->getMessage()], 500);
        }
    }

    public function getBancoByChoferId($id)
    {
        try {
            // Obtener una cuenta bancaria activa del chofer utilizando una consulta sin Eloquent ORM
            $cuentaBancaria = DB::table('banco_chofer')
                ->join('bancos', 'banco_chofer.idBanco', '=', 'bancos.id')
                ->where('banco_chofer.idChofer', $id)
                ->where('banco_chofer.estado', 'activo') // Condición adicional para cuentas activas
                ->select('banco_chofer.*', 'bancos.nombre as entidadBancaria', 'bancos.codigo as numeroCuenta')
                ->first();
    
            // Si no se encuentra una cuenta bancaria activa, devuelve un mensaje de error
            if (!$cuentaBancaria) {
                return response(["message" => "No se encontró una cuenta bancaria activa para el chofer con ID $id"], 404);
            }
    
            return response()->json($cuentaBancaria, 200);
    
        } catch (\Exception $e) {
            // Manejo de excepciones
            return response(["error" => $e->getMessage()], 500);
        }
    }



    // Actualizar un chofer por ID sin usar Eloquent ORM
    public function update(Request $request, $id)
    {
        try {
            // Verificar si el chofer existe
            $chofer = DB::table('chofers')->where('id', $id)->first();

            if (!$chofer) {
                return response()->json(['message' => 'Chofer no encontrado'], 404);
            }

            // Validaciones y lógica para actualizar el chofer
            // ...

            // Actualizar el chofer sin Eloquent ORM
            DB::table('chofers')->where('id', $id)->update($request->all());

            return response()->json(['message' => 'Chofer actualizado con éxito']);
        } catch (\Exception $e) {
            // Manejo de excepciones
            return response(["error" => $e->getMessage()], 500);
        }
    }
    
    // Eliminar un chofer por ID sin usar Eloquent ORM
    public function destroy($id)
    {
        try {
            // Verificar si el chofer existe
            $chofer = DB::table('chofers')->where('id', $id)->first();

            if (!$chofer) {
                return response()->json(['message' => 'Chofer no encontrado'], 404);
            }

            // Eliminar el chofer y su registro en auths sin Eloquent ORM
            DB::table('chofers')->where('id', $id)->delete();
            DB::table('auths')->where('id', $chofer->idAuth)->delete();

            return response()->json(['message' => 'Chofer y registro en auths eliminados con éxito']);
        } catch (\Exception $e) {
            // Manejo de excepciones
            return response(["error" => $e->getMessage()], 500);
        }
    }


    public function getTraslados($id)
    {
        try {
            // Realizar una consulta SQL para obtener los traslados del chofer con el ID proporcionado
            $traslados = DB::table('traslados')
                ->where('idChofer', $id)
                ->get();

            // Verificar si el chofer existe
            $chofer = DB::table('chofers')->where('id', $id)->first();

            if (!$chofer) {
                return response()->json(['error' => 'Chofer no encontrado'], 404);
            }

            // Puedes personalizar el formato de respuesta según tus necesidades
            $response = [
                'chofer' => $chofer,
                'traslados' => $traslados,
            ];

            return response()->json($response, 200);
        } catch (\Exception $e) {
            // Manejo de excepciones
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

 
// Obtener los vehículos de un chofer sin usar Eloquent ORM
    public function getVehiculos($id)
    {
        try {
            // Realizar una consulta SQL para obtener los vehículos del chofer con el ID proporcionado
            $vehiculos = DB::table('vehiculos')
                ->where('idChofer', $id)
                ->select('id', 'idChofer', 'marca', 'color', 'placa', 'anio_fabricacion', 'estado_vehiculo', 'estado_actual')
                ->get();

            // Verificar si el chofer existe
            $chofer = DB::table('chofers')->where('id', $id)->first();

            if (!$chofer) {
                return response()->json(['error' => 'Chofer no encontrado'], 404);
            }

            // Puedes personalizar el formato de respuesta según tus necesidades
            $response = [
                'chofer' => [
                    'id' => $chofer->id,
                    'nombre' => $chofer->nombre,
                    'apellido' => $chofer->apellido,
                    'cedula' => $chofer->cedula,
                    'fechaNacimiento' => $chofer->fechaNacimiento,
                    'idAuth' => $chofer->idAuth,
                    'entidadBancaria' => $chofer->entidadBancaria,
                    'numeroCuenta' => $chofer->numeroCuenta,
                    'saldo' => $chofer->saldo,
                    'vehiculos' => $vehiculos,
                ],
            ];

            return response()->json($response, 200);
        } catch (\Exception $e) {
            // Manejo de excepciones
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    public function getVehiculosdeChofer($id)
    {
        try {
            // Realizar una consulta SQL para obtener los vehículos del chofer con el ID proporcionado
            $vehiculos = DB::table('vehiculos')
                ->where('idChofer', $id)
                ->select('id', 'marca', 'color', 'placa', 'anio_fabricacion', 'estado_vehiculo', 'estado_actual')
                ->get();

            // Verificar si el chofer existe
            $chofer = DB::table('chofers')->where('id', $id)->first();

            if (!$chofer) {
                return response()->json(['error' => 'Chofer no encontrado'], 404);
            }

            return response()->json($vehiculos, 200);
        } catch (\Exception $e) {
            // Manejo de excepciones
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getCuentasBancarias($idChofer)
    {
        try {
            // Obtener cuentas bancarias utilizando una consulta sin Eloquent ORM
            $cuentasBancarias = DB::table('banco_chofer')
                ->join('bancos', 'banco_chofer.idBanco', '=', 'bancos.id')
                ->where('banco_chofer.idChofer', $idChofer)
                ->select('bancos.nombre', 'banco_chofer.nroCuenta')
                ->get();

            return response()->json($cuentasBancarias, 200);
        } catch (\Exception $e) {
            // Manejo de excepciones
            return response(["error" => $e->getMessage()], 500);
        }
    }

    public function obtenerResultadoEvaluacionVehiculo($idChofer, $idVehiculo)
    {
        try {
            // Validar que el chofer exista
            $chofer = DB::table('chofers')
                ->join('vehiculos', 'chofers.id', '=', 'vehiculos.idChofer')
                ->where('chofers.id', $idChofer)
                ->where('vehiculos.id', $idVehiculo)
                ->select('chofers.*', 'vehiculos.*')
                ->first();
    
            if (!$chofer) {
                return response()->json(['message' => 'No se encontró el chofer o el vehículo asociado.'], 404);
            }
    
            // Obtener la última evaluación de vehículo asociada al vehículo del chofer
            $evaluacionVehiculo = DB::table('PruebaVehiculo')
                ->where('idVehiculo', $idVehiculo)
                ->orderBy('fecha_creacion', 'desc')
                ->first();
    
            if (!$evaluacionVehiculo) {
                return response()->json(['message' => 'No se encontró ninguna evaluación de vehículo para el chofer.'], 404);
            }
    
            return response()->json($evaluacionVehiculo);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function revisarTrasladosRealizados(Request $request, $choferId)
    {
        try {
            // Obtener el chofer por ID
            $chofer = DB::table('chofers')->where('id', $choferId)->first();
    
            // Validar si el chofer existe
            if (!$chofer) {
                return response()->json(['message' => 'Chofer no encontrado.'], 404);
            }
    
            // Validar datos de la solicitud
            $request->validate([
                'fecha_inicio' => 'required|date',
                'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            ]);
    
            // Obtener los traslados realizados por el chofer en un período de tiempo
            $traslados = DB::table('traslados')
                ->select(
                    'traslados.id',
                    'idChofer',
                    'idCliente',
                    'costo',
                    'estado',
                    'idVehiculo',
                    'traslados.fecha_creacion',
                    'direccion_origen as origennombre',
                    'direccion_destino as destinonombre',
                    'lat_origen',
                    'lng_origen',
                    'lat_destino',
                    'lng_destino'
                )
                ->where('idChofer', $chofer->id)
                ->whereBetween('traslados.fecha_creacion', [$request->fecha_inicio, $request->fecha_fin])
                ->orderBy('traslados.fecha_creacion', 'desc')
                ->get();
    
            return response()->json(['traslados_realizados' => $traslados]);
    
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }



    public function trasladosCanceladosChofer($choferId)
    {
        try {
            $trasladosCancelados = DB::table('traslados')
                ->where('idChofer', $choferId)
                ->where('estado', 'cancelado')
                ->get();
    
            return response()->json(['trasladosCancelados' => $trasladosCancelados]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    
    public function trasladosPendientesCancelarChofer($choferId)
    {
        try {
            $trasladosPendientes = DB::table('traslados')
                ->where('idChofer', $choferId)
                ->where('estado', 'pendiente') // Ajusta según la lógica de tu aplicación
                ->get();
    
            return response()->json(['trasladosPendientes' => $trasladosPendientes]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function agregarContactosChofer(Request $request, $idChofer)
    {
        try {
            // Validar que el chofer exista
            $chofer = DB::table('chofers')->where('id', $idChofer)->first();
    
            if (!$chofer) {
                return response()->json(['message' => 'Chofer no encontrado'], 404);
            }
    
            // Validar datos de la solicitud
            $validator = Validator::make($request->all(), [
                'contactosEmergencia' => 'required|array|min:2', // al menos dos contactos
                'contactosEmergencia.*.nombre' => 'required|string|max:255',
                'contactosEmergencia.*.telefono' => 'required|string|max:20',
            ]);
    
            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 400);
            }
    
            // Iniciar transacción
            DB::beginTransaction();
    
            try {
                // Agregar cada contacto de emergencia al chofer
                foreach ($request->contactosEmergencia as $contacto) {
                    DB::table('contacto_emergencia_chofer')->insert([
                        'idChofer' => $idChofer,
                        'nombre' => $contacto['nombre'],
                        'telefono' => $contacto['telefono'],
                    ]);
                }
    
                // Confirmar la transacción
                DB::commit();
    
                return response()->json(['message' => 'Contactos de emergencia agregados con éxito.']);
            } catch (\Exception $e) {
                // Revertir la transacción en caso de error
                DB::rollBack();
    
                return response()->json(['error' => $e->getMessage()], 500);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    
    public function agregarBancoChofer(Request $request, $idChofer)
    {
        try {
            // Validar que el chofer exista
            $choferExists = DB::table('chofers')->where('id', $idChofer)->exists();
    
            if (!$choferExists) {
                return response()->json(['message' => 'Chofer no encontrado.'], 404);
            }
    
            // Validar datos de la solicitud
            $validator = Validator::make($request->all(), [
                'idBanco' => 'required|exists:bancos,id',
                'nroCuenta' => 'required|string|max:255',
                'estado' => 'nullable|string|max:255',
            ]);
    
            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 400);
            }
    
            // Agregar la relación entre el chofer y el banco
            DB::table('banco_chofer')->insert([
                'idChofer' => $idChofer,
                'idBanco' => $request->idBanco,
                'nroCuenta' => $request->nroCuenta,
                'estado' => $request->estado
            ]);
    
            return response()->json(['message' => 'Datos bancarios del chofer agregados con éxito.']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
