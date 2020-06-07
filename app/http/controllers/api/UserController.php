<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\User;
use App\Role;
use App\Profile;
use App\Http\Resources\User as UserResource;
use App\Http\Resources\UserCollection;
class UserController extends Controller
{

    public function index(Request $request)
    {
        $per_page   = $request->per_page;
        $sort_by    = $request->sort_by;
        $order_by   = $request->order_by;
        return response()
               ->json([
                    'users' => new UserCollection(User::orderBy($sort_by,$order_by)->paginate($per_page)),
                    'roles' => Role::pluck('name')->all()
                ],200);
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email','password');
        if(Auth::attempt($credentials))
        {
            $token                  = Str::random(80);
            Auth::user()->api_token = $token;
            Auth::user()->save();
            $admin                  = Auth::user()->isAdmin();
            return response()->json(['token' => $token,'isAdmin'=> $admin],200);
        }
        return response()->json(['status'=>'Email or Password is wrong'],403);
    }

    public function store(Request $request)
    {
        $role = Role::where('name',$request->role)->first();
        $user = new User([
             'name'     => $request->name,
             'email'    => $request->email,
             'password' => bcrypt($request->password),
        ]);
        $user->role()->associate($role);
        $user->save();
        $user->profile()->save(new Profile());
        if($user)
            return response()->json(['user'=>new UserResource($user)],200);
    }

    public function verify(Request $request)
    {
        return $request->user()->only('name','email');
    }

    public function show($id)
    {
        $roles = User::where('name','LIKE',"%$id%")->paginate();
        return response()->json(['roles'=>$roles],200);
    }

    public function deleteAll(Request $request)
    {
        User::whereIn('id',$request->users)->delete();
        return response()->json(['message'=>'Records deleted Successfully']);
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
        $role           = Role::where('name',$request->role)->first();
        $user           = User::find($id);
        $user->name     = $request->name;
        $user->role()->dissociate($user->role);
        $user->role()->associate($role);
        $user->save();
        return response()->json(['user'=>new UserResource($user)],200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $user = User::find($id)->delete();
        Profile::where('user_id',$id)->delete();
        return response()->json(['user'=>$user],200);
    }

    public function changeRole(Request $request){

        $user         = User::find($request->user);
        $loggedInUser = $request->user();
        if($user->id === $loggedInUser->id)
        {
            return response()
                   ->json(['user' => new UserResource($loggedInUser)],422);
        }
        $role   = Role::where('name',$request->role)->first();
        $user->role()->dissociate($user->role);
        $user->role()->associate($role);
        $user->save();
        return response()->json(['user' => new UserResource($user)],200);
    }
    public function changePhoto(Request $request)
    {
        $user       = User::find($request->user);
        $profile    = Profile::where('user_id',$user->id)->first();
        $ext        = $request->photo->extension();
        $image_name = Str::random(20).".".$ext;
        $photo      = $request->photo->storeAs('images',$image_name,'public');
        $profile->photo = $photo;
        $user->profile()->save($profile);
        return response()->json(['user'=>new UserResource($user)],200);
    }

    public function VerifyEmail(Request $request)
    {
        $request->validate([
            'email'  => 'required|unique:users',
        ]);
         return response()->json(['message'=>'Valid Email'],200);
    }


}

