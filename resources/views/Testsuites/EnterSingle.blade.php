@extends('Shared._layout')
@section('title', 'Bug Create')
@section('content')

    <h2></h2>

    <h4>Bug/Test Enter</h4>
    <hr/>
    @if(Session::has('csuccess'))
        <div class="alert alert-success alert-dismissible" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span
                        aria-hidden="true">×</span></button>
            {{Session::get('csuccess')}}
        </div>
    @endif
    <div class="row">

        <div class="col-md-9">
            <form method="post" action="{{url('Testsuites/EnterSingle')}}">
                @csrf
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <div class="form-group">
                    <label>Test Failed(Enter Bug) /Test Pass</label>
                    <select id="ifPassTest" onchange="check()" style="width:60%;" name="ifPassTest"
                            class="form-control">

                        <option value="1">The Test Failed(Enter Bug)</option>
                        <option value="2">The Test Pass(No Bugs)</option>
                        <option value="3">Enter more Bugs under Failed Test</option>
                    </select>
                </div>
                <hr>
                <div style="width: 100%; height: 80%" id="costTime" class="form-group">

                    <label class="control-label">Test Cost Time (Ignore the field with Automatic Test )</label>
                    <input id="costTime1" style="border-radius: 1px; width: 8%;" type="text" name="costTime" value="1"/>Hours
                </div>

                <div class="form-group">
                    <label class="control-label">Test</label>
                    <select id="testId" style="width:140%;" name="test_id" class="form-control">
                        @foreach($tests as $test)
                            @if(($test->status==='testing'||$test->status==='failed')&&$test->staff_id===Session::get('user')->id)
                                <option class="options" value="{{$test->id}}">Test Id: {{$test->id}};
                                    Testcase: {{$test->testcase->name}}; Setting: {{$test->setting->description}};
                                    Classification: {{$test->classification}}@if($test->classification==='manual')(Plan time: {{$test->planTime}} hours)@endif; <span
                                            class="spanss">Status:</span>{{$test->status}}</option>
                            @endif
                        @endforeach
                    </select>
                    <p id="noTesta" style="color: red;">You have No Single tests</p>
                </div>


                <hr>
                <div id="enterBug">
                    <div class="form-group">
                        <label class="control-label">Bug Description</label>
                        </span>  <textarea style="height: 100px" name="description"
                                           class="form-control">{{ old('description') }}</textarea>
                    </div>

                    <div class="form-group">

                        <label>Severity</label>
                        <select onchange="select()" id="severity" name="severity">

                            <option value="1">1: Loss of data, hardware damage, safety issue.</option>
                            <option value="2">2: Loss of functionality with no workaround.</option>
                            <option value="3">3: Loss of functionality with a workaround.</option>
                            <option value="4">4: Partial loss of functionality.</option>
                            <option value="5">5: Cosmetic or trivial.</option>
                        </select>
                        <label> </label>
                        <label> </label>
                        <label>Priority</label>
                        <select id="priority" onchange="select()" name="priority">

                            <option value="1">1: Complete loss of system value.</option>
                            <option value="2">2: Unacceptable loss of system value.</option>
                            <option value="3">3: Possibly acceptable reduction in system value.</option>
                            <option value="4">4: Acceptable reduction in system value.</option>
                            <option value="5">5: Negligible reduction in system value.</option>
                        </select>
                        <label> </label>
                        <br>
                        <label style="font-size: 23px">Bug RPN: </label>
                        <label id="BUGRPN" style="font-size: 27px"> 1 </label>
                        <br>
                    </div>
                    <hr>
                    <h4>Optional Fields</h4>
                    <br>
                    <div class="form-group">
                        <label class="control-label">Estimated Fix Date</label>
                        <input type="date" name="estimatedFixDate" value="{{ old('estimatedFixDate') }}"/>
                        <label> </label>
                        <label> </label>
                        <label>Taxonomy</label>
                        <select name="taxonomy">
                            <option value="">-----Select Taxonomy------</option>
                            <option value="functional">Functional</option>
                            <option value="system">System</option>
                            <option value="process">Process</option>
                            <option value="data">Data</option>
                            <option value="documentation">Documentation</option>
                            <option value="code">Code</option>
                            <option value="standards">Standards</option>
                            <option value="other">Other</option>
                            <option value="duplicate">Duplicate</option>
                            <option value="NAP">NAP</option>
                            <option value="badUnit">Bad Unit</option>
                            <option value="RCN">RCN</option>
                            <option value="unknown">Unknown</option>
                        </select>

                    </div>
                    <div class="form-group">
                        <label class="control-label">Bug Comments</label>
                        </span>  <input name="comment" value="{{ old('comment') }}" class="form-control"/>
                    </div>
                </div>

                <div class="form-group">
                    <input id="submitTest" type="submit" class="btn btn-default"/>
                </div>

            </form>
        </div>

    </div>



    <script>

        window.onload=function() {check()};
        var noTesta = document.getElementById("noTesta");
        var testId = document.getElementById("testId");
        var enterBug = document.getElementById("enterBug");
        var ifPassTest = document.getElementById("ifPassTest");
        var submitTest = document.getElementById("submitTest");
        var options = document.getElementsByClassName('options');
        var costTime=document.getElementById('costTime');


        function check() {
            var index = -1;
            noTesta.style.display = 'none';
            if (ifPassTest.value === '2') {
                costTime.style.display='block';

                enterBug.style.display = 'none';

                for (i = options.length - 1; i >= 0; i--) {
                    if (options[i].childNodes[0].nodeValue.split('Status:')[1] === 'failed') {
                        options[i].style.display = 'none';
                        options[i].selected = options[i].defaultSelected;
                    }
                    else {
                        options[i].style.display = 'block';
                        index = i;
                    }
                }
                if (index === -1) {
                    testId.style.display = 'none';
                    noTesta.style.display = 'block';
                    submitTest.disabled = true;
                } else {
                    testId.style.display = 'block';
                    noTesta.style.display = 'none';
                    testId.selectedIndex = index;
                    submitTest.disabled = false;
                }
            }

            else {
                enterBug.style.display = 'block';
                if (ifPassTest.value === '1') {
                    costTime.style.display='block';

                    for (i = options.length - 1; i >= 0; i--) {
                        if (options[i].childNodes[0].nodeValue.split('Status:')[1] === 'failed') {
                            options[i].style.display = 'none';
                            options[i].selected = options[i].defaultSelected;
                        }
                        else {
                            options[i].style.display = 'block';
                            index = i;
                        }
                    }
                    if (index === -1) {
                        testId.style.display = 'none';
                        noTesta.style.display = 'block';
                        submitTest.disabled = true;
                    } else {
                        testId.style.display = 'block';
                        noTesta.style.display = 'none';
                        testId.selectedIndex = index;
                        submitTest.disabled = false;
                    }
                }
                else {
                    costTime.style.display='none';
                    for (i =0; i < options.length; i++) {
                        if (options[i].childNodes[0].nodeValue.split('Status:')[1] === 'testing') {
                            options[i].style.display = 'none';
                            options[i].selected = options[i].defaultSelected;
                        }
                        else {
                            options[i].style.display = 'block';
                            options[i].selected = options[i].defaultSelected;
                            index = i;
                        }
                    }
                    if (index === -1) {
                        testId.style.display = 'none';
                        noTesta.style.display = 'block';
                        submitTest.disabled = true;
                    } else {

                        testId.style.display = 'block';
                        testId.selectedIndex = index;
                        noTesta.style.display = 'none';
                        submitTest.disabled = false;
                    }

                }

            }
        }
        function select() {
            var priority = document.getElementById('priority');
            var severity = document.getElementById('severity');
            var BUGRPN = document.getElementById('BUGRPN');
            BUGRPN.innerHTML = priority.value * severity.value;

        }
    </script>

    <div class="container-fluid">
<div class="col-md-2">
        <a href="{{url('/Bugs/Run')}}">Back to list</a>
</div>
        <div   class="dropdown col-md-10"  >
            <a  id="dropdownMenu1" data-toggle="dropdown">

                Taxonomy <span class="glyphicon glyphicon-question-sign"> </span>
            </a>
            <div id="displayMenu"  class="dropdown-menu dropdown-menu-left">
                <div>Functional<br>
                    Specification. The specification is wrong.<br>
                    Function. The specification is right, but implementation is wrong.<br>
                    Test. The system works correctly, but the test reports a spurious error.<br>
                    <br>

                    System<br>
                    Internal Interface. The internal system communication failed.<br>
                    Hardware Devices. The hardware failed.<br>
                    Operating System. The operating system failed.<br>
                    Software Architecture. A fundamental design assumption proved invalid.<br>
                    Resource Management. The design assumptions are OK, but some implementation of the assumption is wrong.<br>
                    <br>

                    Process<br>
                    Arithmetic. The program incorrectly adds, divides, multiplies, factors, integrates numerically, or otherwise
                    fails to perform an arithmetic operation properly.
                    Initialization. An operation fails on its first use.<br>
                    Control or Sequence. An action occurs at the wrong time or for the wrong reason.<br>
                    Static Logic. Boundaries are misdefined, logic is invalid, “can’t happen” events do happen, “won’t matter”
                    events do matter, and so forth.<br>
                    Other. A control-flow or processing error occurs that doesn’t fit in the preceding buckets.<br>
                    <br>

                    Data<br>
                    Type. An integer should be a float, an unsigned integer stores or retrieves a negative value, an object is
                    improperly defined, and so forth.<br>
                    Structure. A complex data structure is invalid or inappropriate.<br>
                    Initial Value. A data element’s initialized value is incorrect. (This might not result in a process
                    initialization error.)<br>
                    Other. A data-related error occurs that doesn’t fit in the preceding buckets.<br>
                    <br>

                    Code<br>
                    A typo, misspelling, stylistic error, or other coding error occurs that results in a failure.
                    <br>
                    <br>
                    Documentation<br>
                    The documentation says the system does X on condition Y, but the system does Z—a valid and correct
                    action—instead.
                    <br>
                    <br>
                    Standards<br>
                    The system fails to meet industry or vendor standards, follow code standards, adhere to naming conventions, and
                    so forth.
                    <br>
                    <br>
                    Other<br>
                    The root cause is known, but fits none of the preceding categories.
                    <br>
                    <br>
                    Duplicate<br>
                    Two bug reports describe the same bug. (This can happen when two testers report the same symptom, or when two
                    testers report different symptoms that share the same underlying code problem.)
                    <br>
                    <br>
                    NAP<br>
                    The bug as described in the bug report is “not a problem” because the operation noted is correct. The report
                    arises from a misunderstanding on the part of the tester about correct behavior. This situation is distinct from
                    a test failure (whose root cause is cate- gorized as functional/test) in that this is tester failure; that is,
                    human error.
                    <br>
                    <br>
                    Bad Unit<br>
                    The bug is a real problem, but it arises from a random hardware failure that is unlikely to occur in the field.
                    (If the bug indicates a lack of reliability in some hardware compo- nent, this is not the root cause.)
                    <br>
                    <br>
                    RCN<br>
                    A “root cause needed”; the bug is confirmed as closed by test, but no one in develop- ment has supplied a root
                    cause.
                    <br>
                    <br>
                    Unknown<br>
                    No one knows what is broken. This root cause usually fits best when a sporadic bug doesn’t appear for quite
                    awhile, leading people to conclude that some other change fixed the bug as a side effect.
                </div>
            </div>
        </div>
    </div>
    <br>


@endsection