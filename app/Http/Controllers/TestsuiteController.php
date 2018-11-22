<?php

namespace App\Http\Controllers;

use App\Bug;
use App\Bugcomment;
use App\Project;
use App\Setting;
use App\Staff;
use App\Test;
use App\Testcase;
use App\Testsuite;
use DemeterChain\C;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Session;

class TestsuiteController extends Controller
{
    public function index()
    {
        AuthController::IsUser();
        $Testsuites = Testsuite::all()->sortByDesc('id');
        return view('Testsuites.index', compact('Testsuites'));
    }


    public function CreateSingle()
    {
        AuthController::IsNotDeveloper();
        $testcases = Testcase::all();
        $settings = Setting::all();
        $ALLtests=Test::all();
        $tests=new Collection();
        foreach ($ALLtests as $test){
            if($test->testsuite_id===null){
                $tests->push($test);
            }

        }
        $tests=$tests->reverse();
        return view('Testsuites.CreateSingle', compact('testcases','settings','tests'));
    }


    public function CreateSinglePost(Request $request)
    {
        AuthController::IsNotDeveloper();
        $validator = Validator::make($request->all(), [
            'planTime' => $_POST['classification'] === 'manual' ? 'between:0.1,999|numeric' : ''

        ]);

        if ($validator->fails()) {
            return redirect()->route('testsuiteCreateSingle')
                ->withErrors($validator)
                ->withInput();
        }
        $Test = new Test([
            'testcase_id' => $_POST['testcase_id']
            , 'setting_id' => $_POST['setting_id']
            , 'planTime' => $_POST['classification'] === 'manual' ? $_POST['planTime'] : 0
            , 'classification' => $_POST['classification']
        ]);
        $Test->save();
        Session::put('stsuccess', 'Successfully enter the new Test!');

        return redirect()->route('testsuiteCreateSingle');
    }

    public function TakeSinglePost(Request $request, $id)
    {
        AuthController::IsNotDeveloper();
        $test = Test::find($id);
        DB::table('tests')
            ->where('id', $test->id)
            ->update(['staff_id' => Session::get('user')->id, 'status' => 'testing']);
        if (Session::has('user')){
            $newid=Session::get('user')->id;
            Session::put('user',Staff::find( $newid));}
        return redirect()->back();
    }

    public function EnterSingle()
    {
        AuthController::IsNotDeveloper();
        $ALLtests=Test::all();
        $tests=new Collection();
        foreach ($ALLtests as $test){
            if($test->testsuite_id===null){
                if($test->staff_id===Session::get('user')->id){
                $tests->push($test);}
            }

        }

        return view('Testsuites.EnterSingle', compact('tests'));
    }

    public function EnterSinglePost(Request $request)
    {

        AuthController::IsNotDeveloper();
        $validatorTest = Validator::make($request->all(), [
            'test_id'=>'required',
        ]);
        if ($validatorTest->fails()) {
            return redirect('Testsuites/EnterSingle')
                ->withErrors($validatorTest)
                ->withInput();
        }
        $test = Test::find($_POST['test_id']);
        $validatorTest1 = Validator::make($request->all(), [
            'test_id'=>'required',
            'costTime' => $test->classification === 'manual' ? 'between:0.1,999|numeric' : ''

        ]);

        if ($validatorTest1->fails()) {
            return redirect('Testsuites/EnterSingle')
                ->withErrors($validatorTest1)
                ->withInput();
        }

        if ($_POST['ifPassTest'] === '2') {

            DB::table('tests')
                ->where('id', $test->id)
                ->update(['status' => 'pass','updated_at' => date_format(Carbon::now(), 'Y-m-d H:m:s'), 'costTime' => $test->classification === 'manual' ? $_POST['costTime'] : 0]);
            $csuccess = 'Successfully Record the Test!';
        } else {

            $validator = Validator::make($request->all(), [
                'description' => 'required|max:1000',
                'estimatedFixDate' =>(isset($_POST['estimatedFixDate']) && $_POST['estimatedFixDate'] !== '')?'after:yesterday':'',
            ]);

            if ($validator->fails()) {
                return redirect('Testsuites/EnterSingle')
                    ->withErrors($validator)
                    ->withInput();
            }

            DB::table('tests')
                ->where('id', $test->id)
                ->update(['status' => 'failed','updated_at' => date_format(Carbon::now(), 'Y-m-d H:m:s'), 'costTime' => $test->classification === 'manual' ? $_POST['costTime'] : 0]);

            $Bug = new Bug([
                'priority' => $_POST['priority']
                , 'severity' => $_POST['severity']
                , 'test_id' => $_POST['test_id']
                , 'description' => $_POST['description'],
                'estimatedFixDate'=>(isset($_POST['estimatedFixDate']) && $_POST['estimatedFixDate'] !== '')?$_POST['estimatedFixDate']:null,
                'taxonomy'    =>    (isset($_POST['taxonomy']) && $_POST['taxonomy'] !== '')?$_POST['taxonomy']:null
            ]);
            $Bug->save();
            Session::put('OpenBugNumber', Bug::AllOpenBugNumber());
            $csuccess = 'Successfully enter the new Bug!';
            if(isset($_POST['comment'])&&$_POST['comment']!==''){
                $validatorComments = Validator::make($request->all(), [
                    'comment'=>'max:500',
                ]);
                if ($validatorComments->fails()) {
                    return redirect('Testsuites/EnterSingle')->with('csuccess', $csuccess);
                }
                $BugComment = new Bugcomment([
                    'staff_id' => Session::get('user')->id
                    , 'bug_id' => $Bug->id
                    , 'comment' => $_POST['comment']
                ]);
                $BugComment->save();
            }

        }
        if (Session::has('user')){
            $newid=Session::get('user')->id;
            Session::put('user',Staff::find( $newid));}
        return redirect('Testsuites/EnterSingle')->with('csuccess', $csuccess);
    }




    public function TakeSingle()
    {
        AuthController::IsNotDeveloper();
        $ALLtests=Test::all();
        $tests=new Collection();
        foreach ($ALLtests as $test){
            if($test->testsuite_id===null&&$test->status==='waiting'){
                $tests->push($test);
            }
        }
        $tests=$tests->reverse();
        return view('Testsuites.TakeSingle', compact('tests'));
    }


    public function Create()
    {
        AuthController::IsManager();
        $projects = Project::all();
        $settings = Setting::all();
        return view('Testsuites.Create', compact('projects','settings'));
    }


    public function CreatePost(Request $request)
    {
        AuthController::IsManager();
        $validator = Validator::make($request->all(), [

            'summary' => 'max:500|required',
        ]);

        if ($validator->fails()) {
            return redirect('Testsuites/Create')
                ->withErrors($validator)
                ->withInput();
        }
        $Testsuite = new Testsuite([
            'summary' => $_POST['summary']
            , 'project_id' => $_POST['project_id']
            ,'setting_id'=>$_POST['setting_id']
        ]);
        $Testsuite->save();
        return redirect('Testsuites');
    }

    public function Edit($id)
    {
        AuthController::IsManager();
        $Testsuite = Testsuite::find($id);
        $projects = Project::all();
        return view('Testsuites.Edit', compact('Testsuite', 'projects'));
    }

    public function EditPost(Request $request, $id)
    {
        AuthController::IsManager();
        $validator = Validator::make($request->all(), [

            'summary' => 'max:500|required',
        ]);

        if ($validator->fails()) {
            return redirect('Testsuites/Edit/' . $id)
                ->withErrors($validator)
                ->withInput();
        }
        DB::table('Testsuites')
            ->where('id', $id)
            ->update(['summary' => $_POST['summary']]);

        return redirect('Testsuites');
    }

    public function Details($id)
    {
        AuthController::IsUser();
        $Testsuite = Testsuite::find($id);

        return view('Testsuites.Details', compact('Testsuite'));
    }

    public function TakeTest(Request $request, $id)
    {
        AuthController::IsNotDeveloper();
        $test = Test::find($id);
        DB::table('tests')
            ->where('id', $test->id)
            ->update(['staff_id' => Session::get('user')->id, 'status' => 'testing']);
        if (Session::has('user')){
        $newid=Session::get('user')->id;
Session::put('user',Staff::find( $newid));}
        return redirect()->back();
    }

    public function Take($id)
    {
        AuthController::IsNotDeveloper();
        $Testsuite = Testsuite::find($id);
        return view('Testsuites.Take', compact('Testsuite'));
    }

    public function Set($id)
    {
        AuthController::IsNotDeveloper();
        $Testsuite = Testsuite::find($id);
        $testcases = new Collection();
        foreach (Testcase::all() as $testcase) {
            if ($testcase->usecase->subsystem->project->id === $Testsuite->project_id) {
                $has=false;
                foreach ($Testsuite->tests as $test){
                    if ($test->testcase_id==$testcase->id)
                    {$has=true;break;}

                }
                if ($has===false){
                    $testcases->push($testcase);
                }
            }
        }
        if (Session::has('tsuccess')) {
            $tsuccess = Session::get('tsuccess');
        }
        Session::forget('tsuccess');
        return view('Testsuites.SetTestSuite', compact('Testsuite', 'testcases', 'tsuccess'));
    }

    public function SetPost($id, Request $request)
    {
        AuthController::IsNotDeveloper();
        $validator = Validator::make($request->all(), [
            'planTime' => $_POST['classification'] === 'manual' ? 'between:0.1,999|numeric' : ''

        ]);

        if ($validator->fails()) {
            return redirect()->route('testsuiteSet', ['id' => $id])
                ->withErrors($validator)
                ->withInput();
        }
        $Test = new Test([
            'testcase_id' => $_POST['testcase_id']
            , 'setting_id' => Testsuite::find($id)->setting_id
            , 'testsuite_id' => $id
            , 'planTime' => $_POST['classification'] === 'manual' ? $_POST['planTime'] : 0
            , 'classification' => $_POST['classification']
        ]);
        $Test->save();
        Session::put('tsuccess', 'Successfully enter the new Test!');

        return redirect()->route('testsuiteSet', ['id' => $id]);
    }
}
