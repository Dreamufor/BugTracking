@extends('Shared.backend')
@section('title', 'Bugs Index')
@section('content')

    <h2>Index</h2>

    <table class="table">
        <thead>
        <tr>
            <th>
                ID
            </th>
            <th style="width: 40%">
             Description
            </th>
            <th>
                Bug State
            </th>
            <th>
                Bug RPN
            </th>
            <th>
                Open Date
            </th>
            <th>Project Name</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @foreach($Bugs as $Bug)
        <tr>
            <td>
                {{ $Bug->id}}
            </td>
            <td>
                {{ $Bug->description}}
            </td>

            <td>
                {{ $Bug->state}}
            </td>
            <td>
                {{ $Bug->bugRPN}}
            </td>
            <td>
                {{ date_format( $Bug->created_at,'d/m/Y')}}
            </td>
            <td>
                {{ $Bug->Test->testcase->Usecase->subsystem->project->name}}
            </td>
            <td>
                <a href="{{url('Bugs/Details/'.$Bug->id)}}">Details</a> |
                @if($Bug->Test->testcase->Usecase->subsystem->project->status==='testing'&&$Bug->state!=='closed'&&$Bug->state!=='deferred'&&Session::has('user')&&Session::get('user')->title==='manager')
                <a href="{{url('Bugs/Edit/'.$Bug->id)}}">Defer</a>
                @endif
            </td>
        </tr>
       @endforeach
        </tbody>
    </table>
@endsection