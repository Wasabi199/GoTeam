<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\{LoginRequest, RegisterRequest, CreatTaskRequest, UpdateStatusRequest, TaskDeleteRequest, TaskUpdateRequest};
use App\Models\{User,task};
use Illuminate\Support\Facades\{Hash, DB};

class ApiController extends Controller
{

    public function login(LoginRequest $loginRequest){
        $validated_data = $loginRequest->validated();

        $user = User::where('email', $validated_data['email'])->first();


        if (!$user || !Hash::check($validated_data['password'], $user->password)) {
            return response()->json([
                'error' => [
                    'user' => 'Provided credentials are invalid. Please check if your credentials are correct then try again.',
                ]
            ], 401);
        } 

        return response()->json([
            'token' => $user->createToken($loginRequest->header('User-Agent'))->plainTextToken,
            // 'user' =>$user
        ]);
    }

    public function register(RegisterRequest $registerRequest){
        $validated_data = $registerRequest->validated();

        DB::transaction(function () use($validated_data){
            User::create([
            'name' => $validated_data['username'],
            'email' => $validated_data['email'],
            'email_verified_at' => now(),
            'password' => Hash::make($validated_data['password'])
            ]);
        });

        return response()->json([
            'message'=>'Registered Successfully!'
        ],200);
    }

    public function createTask(CreatTaskRequest $createTaskRequest){
        $validated_data = $createTaskRequest->validated();

        DB::transaction(function () use($validated_data) {
            task::create([
                'title'=>$validated_data['title'],
                'description'=>$validated_data['description'],
                'deadline'=>$validated_data['deadline'],
                'status'=>false,
            ]);
        });

        return response()->json([
            'message'=>'Task Register Successful'
        ],200);
    }

    public function getTask(Request $request){

        $filters = $request->only(['search']);

        $task = task::when($filters['search']??null,function($query, $search){
            $query->where(function($query)use($search) {
                $query->where('title', 'like', '%' . $search . '%')
                ->orWhere('description', 'like', '%' . $search . '%');
            });
        })->get();

        return response()->json([
            'task'=>$task
        ], 200);

        // return response()->json(
        //     ['message'=>],200
        // );
    }

    public function updateStatus(UpdateStatusRequest $updateStatusRequest){
        $validated_data = $updateStatusRequest->validated();
        DB::transaction(function () use($validated_data){
            $task = task::findOrFail($validated_data['id']);
            $task->update([
                'status'=>$validated_data['status']
            ]);
        });

        return response()->json(['message'=>'Update Successful'],200);
    }

    public function taksDelete(TaskDeleteRequest $taskDeleteRequest){
        $validated_data = $taskDeleteRequest->validated();

        DB::transaction(function () use($validated_data){
            $task = task::findOrFail($validated_data['id']);
            $task->delete();
        });

        return response()->json([
            'message'=>'Task Deleted Successfully!'
        ], 200);
    }

    public function taskUpdate(TaskUpdateRequest $taskUpdateRequest){
        $validated_data = $taskUpdateRequest->validated();

        DB::transaction(function () use($validated_data){
            $task = task::findOrFail($validated_data['id']);
            $task->update([
                'title'=>$validated_data['title'],
                'description'=>$validated_data['description'],
                'deadline'=>$validated_data['deadline'],
            ]);
        });

        return response()->json(['message'=>'Update Successfull!'], 200);
    }
}
