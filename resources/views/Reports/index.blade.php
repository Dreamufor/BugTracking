@extends('Shared._layout')
@section('title', 'Reports Index')
@section('content')

    <h3>Reports</h3>

    <hr>
    <br>
    <div>
        <a class="btn btn-default btn-lg " href="{{url('Reports/StaffReport')}}"> Developer Report</a> <a style="margin-left: 5%" class="btn btn-default btn-lg" href="{{url('Reports/TesterReport')}}">Tester Report</a>
    </div>
    <div>

    </div>
    <br>
    <hr>
    <br>
    <table class="table table-responsive">
        <thead>
        <tr>
            <th>
                ID
            </th>
            <th>
                Project Name
            </th>
            <th style="width: 29%">
                Description
            </th>
            <th >
                Status
            </th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @foreach($Projects as $Project)
            <tr>
                <td>
                    {{ $Project->id}}
                </td>
                <td>
                    {{ $Project->name}}
                </td>

                <td >
                    {{ $Project->description}}
                </td>
                <td >
                    {{ $Project->status}}
                </td>
                <td>
                    <a class="btn btn-default" href="{{url('Reports/ProjectReport/'.$Project->id)}}">Project Report</a>
                    @if($Project->status==='testing')
                  |  <a class="btn btn-default" href="{{url('Reports/TestingProjectReport/'.$Project->id)}}">Testing Project Report</a>
                        @endif
                </td>

            </tr>
        @endforeach




        </tbody>
    </table>

@endsection