<?php

namespace App\Http\Controllers;

use App\Bug;
use App\Bugassign;
use App\Bugcomment;
use App\Setting;
use App\Staff;
use App\Test;
use App\Testcase;
use App\Testsuite;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Session;

class BugController extends Controller
{
    public function Run()
    {
        $Testsuites = Testsuite::all()->sortByDesc('id');
        return view('Bugs.Run', compact('Testsuites'));
    }

    public function index()
    {
        $Bugs = Bug::all()->sortByDesc('id');
        return view('Bugs.index', compact('Bugs'));
    }

    public function MyWork()
    {
        $myBugs = new Collection();
        $Bugassigns = Bugassign::all();
        $staffs = Staff::all();
        foreach ($Bugassigns as $bugassign) {
            if (($bugassign->status === 'assigned')
                && $bugassign->staff_id === Session::get('user')->id) {
                $myBugs->push($bugassign);
            }
        }
        Session::put('MyNumber', Staff::find(Session::get('user')->id)->workLoad(Staff::find(Session::get('user')->id)->bugassigns));
        return view('Bugs.MyWork', compact('myBugs', 'staffs'));
    }

    public function AssignIndex()
    {
        AuthController::IsManager();
        $Bugs = new Collection();
        $AllBugs = Bug::all();
        foreach ($AllBugs as $bug) {
            if ($bug->state === 'open' || $bug->state === 'reOpened') {
                $Bugs->push($bug);
            }
        }
        return view('Bugs.AssignIndex', compact('Bugs'));
    }

    public function Assign($id)
    {
        AuthController::IsManager();
        $date = date_format(Carbon::tomorrow(), 'Y-m-d');
        $bug = Bug::find($id);
        $staffs = DB::table('staff')->where('title', '=', 'developer')->get();
        return view('Bugs.Assign', compact('bug', 'staffs', 'date'));
    }

    public function Reject(Request $request, $id)
    {
        AuthController::IsManager();
        DB::table('Bugs')
            ->where('id', $id)
            ->update(['state' => 'rejected']);
        $bugAssign = new Bugassign([
            'bug_id' => $id
            , 'staff_id' => Bug::find($id)->test->staff_id
        ]);
        $bugAssign->save();
        if(isset($_POST['comment'])&&$_POST['comment']!==''){
            $validatorComments = Validator::make($request->all(), [
                'comment'=>'max:500',
            ]);
            if ($validatorComments->fails()) {
                return redirect(route('BugAssignIndex'));

            }
            $BugComment = new Bugcomment([
                'staff_id' => Session::get('user')->id
                , 'bug_id' => $id
                , 'comment' => $_POST['comment']
            ]);
            $BugComment->save();
        }
        return redirect()->route('BugAssignIndex');
    }

    public function Create()
    {
        AuthController::IsNotDeveloper();
        $tests = Test::all();
        return view('Bugs.Create', compact('tests'));
    }

    public function ReAssign(Request $request, $id)
    {
        AuthController::IsManager();
        DB::table('bugassigns')
            ->where('id', $id)
            ->update(['staff_id' => $_POST['staff_id']]);
        return redirect()->to($request->session()->previousUrl());
    }

    public function StaffAssign(Request $request, $id)
    {

        $bug = Bugassign::find($id);
        $staffs = Staff::all();
        return view('Bugs.MyBugAssign', compact('bug', 'staffs'));
    }

    public function MyWorkPost(Request $request, $id)
    {

        if (isset($_POST['costTime'])){
            $validatorTest1 = Validator::make($request->all(), [

                'costTime' => 'required|between:0.1,999|numeric'

            ]);

            if ($validatorTest1->fails()) {
                return redirect(route('StaffAssign', ['id' => $id]))
                    ->withErrors($validatorTest1)
                    ->withInput();
            }
        }
        if (Session::get('user')->title === 'developer') {
            DB::table('Bugassigns')
                ->where('id', $id)
                ->update(['status' => 'finished', 'costTime'=>$_POST['costTime'],
                    'updated_at' => date_format(Carbon::now(), 'Y-m-d H:m:s')]);
        }
        else{
        DB::table('Bugassigns')
            ->where('id', $id)
            ->update(['status' => 'finished',
                'costTime'=>isset($_POST['costTime'])?:0,
                'updated_at' => date_format(Carbon::now(), 'Y-m-d H:m:s')]);
        }

        Session::forget('MyNumber');
        Session::put('MyNumber', Staff::find(Session::get('user')->id)->workLoad(Staff::find(Session::get('user')->id)->bugassigns));

        if (Session::get('user')->title === 'developer') {
            $bugAssign = new Bugassign([
                'bug_id' => Bugassign::find($id)->bug->id
                , 'staff_id' => $_POST['staff_id']
            ]);
            $bugAssign->save();
            if (Staff::find($_POST['staff_id'])->title !== 'developer') {
                DB::table('Bugs')
                    ->where('id', Bugassign::find($id)->bug->id)
                    ->update(['state' => 'test']);
                if (isset($_POST['taxonomy']) && $_POST['taxonomy'] !== '') {
                    DB::table('Bugs')
                        ->where('id', Bugassign::find($id)->bug->id)
                        ->update(['taxonomy' => $_POST['taxonomy']]);
                }
            }
        } else {
            if ($_POST['state'] === 'closed') {
                $theBug=Bug::find(Bugassign::find($id)->bug->id);
                $extim=date_format(Carbon::now(), 'Y-m-d');
                if ($theBug->estimatedFixDate!==null){
                    $extim=$theBug->estimatedFixDate;
                }
                DB::table('Bugs')
                    ->where('id', Bugassign::find($id)->bug->id)
                    ->update(['state' => $_POST['state'], 'taxonomy' => $_POST['taxonomy'], 'actualFixDate' => date_format(Carbon::now(), 'Y-m-d'), 'estimatedFixDate' =>$extim ]);
                  $test=Test::find(Bugassign::find($id)->bug->test->id);
                  $pass=true;
                  foreach ($test->bugs as $aBug){

                      if($aBug->state!=='closed'&&$aBug->state!=='deferred'){
                          $pass=false;
                          break;
                      }

                  }
                  if ($pass===true){
                      DB::table('tests')
                          ->where('id', $test->id)
                          ->update(['status' =>'closed']);
                  }


            } else {

                    DB::table('Bugs')
                        ->where('id', Bugassign::find($id)->bug->id)
                        ->update(['state' => $_POST['state'], 'taxonomy' => $_POST['taxonomy']]);

            }
        }
        if(isset($_POST['comment'])&&$_POST['comment']!==''){
            $validatorComments = Validator::make($request->all(), [
                'comment'=>'max:500',
            ]);
            if ($validatorComments->fails()) {
                return redirect(route('MyWork'));

            }
            $BugComment = new Bugcomment([
                'staff_id' => Session::get('user')->id
                , 'bug_id' => Bugassign::find($id)->bug->id
                , 'comment' => $_POST['comment']
            ]);
            $BugComment->save();
        }

        return redirect(route('MyWork'));
    }

    public function AssignPost(Request $request, $id)
    {

        AuthController::IsManager();
        if (isset($_POST['estimatedFixDate']) && $_POST['estimatedFixDate'] !== '') {
            $validator = Validator::make($request->all(), [
                'description' => 'required|max:1000',
                'estimatedFixDate' => 'after:yesterday',
            ]);
        } else {
            $validator = Validator::make($request->all(), [
                'description' => 'required|max:1000',

            ]);
        }
        if ($validator->fails()) {
            return redirect('Bugs/Assign/' . $id)
                ->withErrors($validator)
                ->withInput();
        }
        $bugAssign = new Bugassign([
            'bug_id' => $id
            , 'staff_id' => $_POST['staff_id']
        ]);
        $bugAssign->save();
        if (isset($_POST['estimatedFixDate']) && $_POST['estimatedFixDate'] !== '') {
            DB::table('Bugs')
                ->where('id', $id)
                ->update(['estimatedFixDate' => $_POST['estimatedFixDate']]);
        }
        if (isset($_POST['taxonomy']) && $_POST['taxonomy'] !== '') {
            DB::table('Bugs')
                ->where('id', $id)
                ->update(['taxonomy' => $_POST['taxonomy']]);
        }
        DB::table('Bugs')
            ->where('id', $id)
            ->update(['state' => 'assigned', 'priority' => $_POST['priority']
                , 'severity' => $_POST['severity']]);
        if(isset($_POST['comment'])&&$_POST['comment']!==''){
            $validatorComments = Validator::make($request->all(), [
                'comment'=>'max:500',
            ]);
            if ($validatorComments->fails()) {
                return redirect(route('BugAssignIndex'));

            }
            $BugComment = new Bugcomment([
                'staff_id' => Session::get('user')->id
                , 'bug_id' => $id
                , 'comment' => $_POST['comment']
            ]);
            $BugComment->save();
        }

        return redirect(route('BugAssignIndex'));
    }

    public function CreatePost(Request $request)
    {
        AuthController::IsNotDeveloper();
        $validatorTest = Validator::make($request->all(), [
            'test_id'=>'required',
        ]);
        if ($validatorTest->fails()) {
            return redirect('Bugs/Create')
                ->withErrors($validatorTest)
                ->withInput();
        }
        $test = Test::find($_POST['test_id']);
        $validatorTest1 = Validator::make($request->all(), [
            'test_id'=>'required',
            'costTime' => $test->classification === 'manual' ? 'between:0.1,999|numeric' : ''

        ]);

        if ($validatorTest1->fails()) {
            return redirect('Bugs/Create')
                ->withErrors($validatorTest1)
                ->withInput();
        }

        if ($_POST['ifPassTest'] === '2') {

            DB::table('tests')
                ->where('id', $test->id)
                ->update(['status' => 'pass', 'costTime' => $test->classification === 'manual' ? $_POST['costTime'] : 0]);
            $csuccess = 'Successfully Record the Test!';
        } else {

                $validator = Validator::make($request->all(), [
                    'description' => 'required|max:1000',
                    'estimatedFixDate' =>(isset($_POST['estimatedFixDate']) && $_POST['estimatedFixDate'] !== '')?'after:yesterday':'',
                ]);

            if ($validator->fails()) {
                return redirect('Bugs/Create')
                    ->withErrors($validator)
                    ->withInput();
            }

            DB::table('tests')
                ->where('id', $test->id)
                ->update(['status' => 'failed', 'costTime' => $test->classification === 'manual' ? $_POST['costTime'] : 0]);

                $Bug = new Bug([
                    'priority' => $_POST['priority']
                    , 'severity' => $_POST['severity']
                    , 'test_id' => $_POST['test_id']
                    , 'description' => $_POST['description'],
                    'estimatedFixDate'=>(isset($_POST['estimatedFixDate']) && $_POST['estimatedFixDate'] !== '')?$_POST['estimatedFixDate']:null,
                    'taxonomy'    =>    (isset($_POST['taxonomy']) && $_POST['taxonomy'] !== '')?$_POST['taxonomy']:null
                ]);
            $Bug->save();
            $csuccess = 'Successfully enter the new Bug!';
            if(isset($_POST['comment'])&&$_POST['comment']!==''){
                $validatorComments = Validator::make($request->all(), [
                    'comment'=>'max:500',
                ]);
                if ($validatorComments->fails()) {
                    return redirect(route('BugCreate'))->with('csuccess', $csuccess);
                }
                $BugComment = new Bugcomment([
                    'staff_id' => Session::get('user')->id
                    , 'bug_id' => $Bug->id
                    , 'comment' => $_POST['comment']
                ]);
                $BugComment->save();
            }

        }
        return redirect(route('BugCreate'))->with('csuccess', $csuccess);
    }

    public function Edit($id)
    {
        AuthController::IsManager();
        $Bug = Bug::find($id);
        return view('Bugs.Edit', compact('Bug'));
    }


    public function EditPost(Request $request, $id)
    {
        $bug=Bug::find($id);
        foreach ($bug->bugassigns as $bugassign) {
            if ($bugassign->status === 'assigned') {
                DB::table('Bugassigns')
                    ->where('id', $bugassign->id)
                    ->update(['status' => 'finished', 'updated_at' => date_format(Carbon::now(), 'Y-m-d H:m:s')]);
            }

        }
        $extim=date_format(Carbon::now(), 'Y-m-d');
        if ($bug->estimatedFixDate!==null){
            $extim=$bug->estimatedFixDate;
        }

        DB::table('Bugs')
            ->where('id', $id)
            ->update(['state' => 'deferred', 'estimatedFixDate'=> $extim,'actualFixDate' => date_format(Carbon::now(), 'Y-m-d')]);


        $test=Test::find($bug->test->id);
        $pass=true;
        foreach ($test->bugs as $aBug){

            if($aBug->state!=='closed'&&$aBug->state!=='deferred'){
                $pass=false;
                break;
            }

        }
        if ($pass===true){
            DB::table('tests')
                ->where('id', $test->id)
                ->update(['status' =>'closed']);
        }


        return redirect('Bugs');
    }

    public function Details($id)
    {
        $Bug = Bug::find($id);

        return view('Bugs.Details', compact('Bug'));
    }
}
