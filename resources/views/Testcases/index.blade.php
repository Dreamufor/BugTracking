@extends('Shared.backend')
@section('title', 'Testcases Index')
@section('content')

    <h2>Test Cases</h2>

    <p>
        @if(Session::has('user')&&Session::get('user')->title==='manager')
        <a href="{{url('Testcases/Create')}}">Create New</a>
          @endif
    </p>
    <table class="table">
        <thead>
        <tr>
            <th>
                ID
            </th>
            <th>
            Name
            </th>
            <th style="max-width: 29%">
             Description
            </th>
            <th>Usecase Name
            </th>
            <th>Project Name
            </th>
            <th style="width: 10%"></th>
        </tr>
        </thead>
        <tbody>
        @foreach($Testcases as $Testcase)
        <tr>
            <td>
                {{ $Testcase->id}}
            </td>
            <td>
                {{ $Testcase->name}}
            </td>

            <td>
                {{ $Testcase->description}}
            </td>
            <td>
                {{ $Testcase->Usecase->name}}
            </td>
            <td>
                {{ $Testcase->Usecase->subsystem->project->name}}
            </td>
            <td >
                @if($Testcase->Usecase->Subsystem->project->status==='testing'&&Session::has('user')&&Session::get('user')->title==='manager')
                <a href="{{url('Testcases/Edit/'.$Testcase->id)}}">Edit</a> |
                @endif
                <a href="{{url('Testcases/Details/'.$Testcase->id)}}">Details</a>
            </td>
        </tr>
       @endforeach
        </tbody>
    </table>
@endsection