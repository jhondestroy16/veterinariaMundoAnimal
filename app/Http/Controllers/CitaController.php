<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

use App\Models\Cita;
use App\Models\CitaServicio;
use App\Models\Mascota;
use App\Models\Horario;
use App\Models\Servicio;
use Illuminate\Http\Request;

class CitaController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:citas');
    }

    public function index()
    {
        $citas = Cita::orderBy('fechaCita', 'asc')
            ->orderBy('horaCita', 'asc')
            ->get();

        $cantidad = DB::table('citas')
            ->select()->count('*');
        return view('citas.index', compact('citas', 'cantidad'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $id = Auth::id();
        $mascotas = Mascota::all()
            ->where('clienteId', '=', $id);

        $cantidad = DB::table('horarios')
            ->select()->count('*');

        $cantidadMascotas = DB::table('mascotas')
            ->select()->count('*');

        $cantidadServicios = DB::table('servicios')
            ->select()->count('*');

        $servicios = Servicio::orderBy('id', 'asc')->get();

        $fechas = DB::select('SELECT DISTINCT fecha FROM horarios WHERE disponibilidad = "si"');
        $horas = DB::select('SELECT * FROM horarios WHERE disponibilidad = "si"');

        return view('citas.insert', compact('servicios', 'mascotas', 'fechas', 'horas', 'cantidad', 'cantidadMascotas', 'cantidadServicios'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'fechaCita' => ['required'],
            'horaCita' => ['required'],
            'descripcion' => ['required', 'max:200'],
            'mascotaId' => ['required']
        ]);

        $cita = Cita::create($request->all());

        $id = $cita->id;
        $fecha = $cita->fechaCita; //No
        $hora = $cita->horaCita; //No

        $servicios = $request->servicios;

        foreach ($servicios as $servicio) {
            $citaServicio = new CitaServicio();
            $citaServicio->cita_id = $id;
            $citaServicio->servicio_id = $servicio;
            $citaServicio->save();
        }
        DB::table('citas')
            ->where('id', $id)
            ->update(['estado' => 'Aceptada']);

        DB::table('horarios')
            ->where('fecha', $fecha)
            ->where('hora', $hora)
            ->update(['cita_id' => $id]);

        $duraciones = CitaServicio::join('servicios', 'cita_servicios.servicio_id', '=', 'servicios.id')
            ->select('servicios.*', 'cita_servicios.*')
            ->where('cita_servicios.cita_id', '=', $id)
            ->get();
        $horaInicio = Horario::join('citas', 'horarios.cita_id', '=', 'citas.id')
            ->select('citas.id', 'horarios.turno')
            ->where('citas.id', '=', $id)
            ->first();

        $turnoInicial = $horaInicio->turno;

        $total = 0;
        $totalValor = 0;
        foreach ($duraciones as $duracion) {
            //Tiempo
            $total = ($duracion->turno  + $total);
            //Dinero
            $totalValor += $duracion->valor;
        }
        $total += $turnoInicial;

        $horaFin = Horario::select('hora', 'turno')
            ->where('turno', '=', $total)
            ->first();
        $horaFinal = $horaFin->hora;

        DB::table('citas')
            ->where('fechaCita', $fecha)
            ->where('horaCita', $hora)
            ->update(['horaCitaFin' => $horaFinal]);

        DB::table('citas')
            ->where('id', $id)
            ->update(['valorTotal' => $totalValor]);

        DB::table('horarios')
            ->where('fecha', $fecha)
            ->where('hora', '>=', $hora)
            ->where('hora', '<=', $horaFinal)
            ->update(['cita_id' => $id]);

        DB::table('horarios')
            ->where('cita_id', $id)
            ->update(['disponibilidad' => 'no']);

        return redirect()->route('citas.index')->with('exito', 'Se ha solicitado la cita exitosamente.');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Cita  $cita
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $mascota = Cita::join('mascotas', 'citas.mascotaId', '=', 'mascotas.id')
            ->join('users', 'mascotas.clienteId', '=', 'users.id')
            ->select('mascotas.*', 'citas.id as idCita', 'citas.fechaCita', 'citas.horaCita', 'citas.horaCitaFin', 'citas.valorTotal', 'citas.descripcion', 'citas.mascotaId', 'citas.estado', 'users.*')
            ->where('citas.id', '=', $id)
            ->first();

        $servicios = CitaServicio::join('servicios', 'cita_servicios.servicio_id', '=', 'servicios.id')
            ->select('servicios.*', 'cita_servicios.*')
            ->where('cita_servicios.cita_id', '=', $id)
            ->get();
        return view('citas.view', compact('mascota', 'servicios'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Cita  $cita
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Cita  $cita
     * @return \Illuminate\Http\Response
     */
    public function update($id)
    {
        DB::table('citas')
            ->where('id', $id)
            ->update(['estado' => 'Aceptada']);

        DB::table('horarios')
            ->where('cita_id', $id)
            ->update(['disponibilidad' => 'no']);

        return redirect()->route('citas.index')->with('exito', 'Se ha aprobado la cita');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Cita  $cita
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        DB::table('citas')
            ->where('id', $id)
            ->update(['estado' => 'Rechazada']);

        DB::table('horarios')
            ->where('cita_id', $id)
            ->update(['disponibilidad' => 'si']);

        return redirect()->route('citas.index')->with('exito', 'Se ha solicitado la cancelacion de la cita');
    }
}
