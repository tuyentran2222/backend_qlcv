<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(),
        [
            'projectCode' => 'required|string',
            'projectName' => 'required|string',
            'projectStart' => 'required|date',
            'projectEnd' => 'date',
            'partner' => 'string|required',
            'status' => 'integer|required',
        ]);

        if ($validator->fails()) {
            return response()->json(
                [
                    'status' => 'error',
                    'error' => $validator->errors(),
                    'description'=>"Thông tin nhập vào chưa hợp lệ",
                    'code' => 401
                ],
                401
            );
        }

        $currentUser = $request->user(); //returns an instance of the authenticated user...
        $ownerId = $currentUser->id; // returns authenticated user id. 

        $projectArray = [
            'projectCode' => $request->projectCode,
            'projectName' => $request->projectName,
            'projectStart' => $request->projectStart,
            'projectEnd' => $request->projectEnd,
            'partner' => $request->partner,
            'status' => $request->status,
            'ownerId' => $currentUser
        ];
        
        $project = Project::create($projectArray);
        return Response()->json(
            array(
                "status" => 'success',
                "data" => $projectArray,
                'code' => 200
            )
        );
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function findProjectById(Request $request, $id) {
        $currentUser = $request->user(); 
        $ownerId = $currentUser->id;

        if ($id == $ownerId) {
            $projects = DB::table('projects')->where('owner', $id);
            return Response()->json(
                array(
                    'status' => 'success',
                    'data' => response()->json($projects),
                    'code' => 200
                ),200
            );
            
        }
        return Response()->json(
            array(
                'status' => 'error',
                'data' => [],
                'code' => 401
            ),401
        ); 
    }
}
