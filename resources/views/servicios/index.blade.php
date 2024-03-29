@extends('layouts.layout')

@section('titulo', 'Servicios')

@section('content')
    <h2 class="texto-blanco pt-5 pb-3">Servicios</h2>

    @if ($mensaje = Session::get('exito'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <p>{{ $mensaje }}</p>
            {{-- <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button> --}}
        </div>
    @endif
    @if ($cantidad > 0)
        <table class="table table-hover table-bordered table-striped">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Descripcion</th>
                    <th>Valor del servicio</th>
                    <th>Opciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($servicios as $servicio)
                    <tr>
                        <td>{{ $servicio->nombre }}</td>
                        <td>{{ $servicio->descripcion }}</td>
                        <td>$ {{ number_format($servicio->valor, 2, ',', '.') }}</td>
                        <td>
                            <a href="{{ route('servicios.show', $servicio->id) }}" class="btn btn-info">Detalles</a>
                            @can('servicios')
                                <a href="{{ route('servicios.edit', $servicio->id) }}" class="btn btn-warning">Editar</a>
                                <form action="{{ route('servicios.destroy', $servicio->id) }}" method="post"
                                    class="d-inline-flex">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger"
                                        onclick="return confirm('¿Confirma la eliminacion del servicio de {{ $servicio->nombre }}?')">Eliminar</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="texto-blanco">No hay servicios registrados</p>
    @endif

@endsection
