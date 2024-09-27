<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Exceptions\NotFoundException;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class UsersController extends Controller
{
    /**
     * Create user
     * @param Request $request
     * @return User
     */
    public function createUser(Request $request)
    {
        try {
            $validateUser = Validator::make(
                $request->all(),
                [
                    'name' => 'required',
                    'email' => 'required|email|unique:users,email',
                    'cpf' => 'required|unique:users,cpf',
                    'password' => 'required',
                    'profile' => [
                        function ($attribute, $value, $fail) {
                            if (!in_array($value, ['admin', 'user', null]))
                                $fail('Parameter value ("' . $attribute . '" : "' . $value . '") not permited');
                        },
                    ],
                ]
            );
            if ($validateUser->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Bad request',
                    'errors' => $validateUser->errors()
                ], 400);
            }
            DB::beginTransaction();
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'cpf' => $request->cpf,
                'password' => Hash::make($request->password)
            ]);
            DB::commit();
            return response()->json([
                'status' => true,
                'message' => 'User created successfully',
                'token' => $user->createToken("API TOKEN")->plainTextToken
            ], 201);
        } catch (\Throwable $th) {
            DB::rollback();
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * User login
     * @param Request $request
     * @return User
     */
    public function userLogin(Request $request)
    {
        try {
            $validateUser = Validator::make(
                $request->all(),
                [
                    'email' => 'required|email',
                    'password' => 'required'
                ]
            );
            if ($validateUser->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Bad params',
                    'errors' => $validateUser->errors()
                ], 400);
            }
            if (!Auth::attempt($request->only(['email', 'password']))) {
                return response()->json([
                    'status' => false,
                    'message' => 'Email or password does not match.',
                ], 401);
            }
            $user = User::where('email', $request->email)->first();
            return response()->json([
                'status' => true,
                'message' => 'User logged in successfully',
                'token' => $user->createToken("API TOKEN")->plainTextToken
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function getUserById(Request $request, $id)
    {
        try {
            $user = User::find($id);
            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'User "' . $id . '" not found',
                ], 404);
            }
            $user->addresses;
            $user->telephoneNumbers;
            return $user;
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function listUsers()
    {
        $users = User::where('id', '<>', Auth::id())->get();
        foreach ($users as $user) {
            $user->addresses;
            $user->telephoneNumbers;
        }
        return $users;
    }

    public function loggedUser()
    {
        $user = User::find(Auth::id());
        $user->addresses;
        $user->telephoneNumbers;
        return $user;
    }

    public function deleteUser(Request $request, $id)
    {
        try {
            if (Auth::id() == $id) {
                return response()->json([
                    'status' => false,
                    'message' => 'Cannot delete your own user account',
                ], 400);
            }
            $deletedUser = User::where('id', '=', $id)->delete();
            if (!$deletedUser) {
                return response()->json([
                    'status' => false,
                    'message' => 'User "' . $id . '" not found',
                ], 404);
            }
            return response()->json([
                'status' => true,
                'message' => 'User deleted successfully',
                'userId' => $id
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function updateUserById(Request $request, $id)
    {
        try {
            if (Auth::id() == $id) {
                return response()->json([
                    'status' => false,
                    'message' => 'Wrong endpoint, use "/api/user/update"',
                ], 400);
            }
            $validateUser = Validator::make(
                $request->all(),
                [
                    'profile' => [
                        function ($attribute, $value, $fail) {
                            if (!in_array($value, ['admin', 'user', null]))
                                $fail('Parameter value ("' . $attribute . '" : "' . $value . '") not permited');
                        },
                    ],
                ]
            );
            if ($validateUser->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Bad request',
                    'errors' => $validateUser->errors()
                ], 400);
            }
            UserService::updateUserById($id, $request->all());
            return response()->json([
                'status' => true,
                'message' => "User successfully updated"
            ], 202);
        } catch (NotFoundException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 404);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function updateCurrentUser(Request $request)
    {
        try {
            $validateUser = Validator::make(
                $request->all(),
                [
                    'profile' => [
                        function ($attribute, $value, $fail) {
                            $fail('Parameter "' . $attribute . '" not permited');
                        },
                    ],
                    'email' => [
                        function ($attribute, $value, $fail) {
                            $fail('Parameter "' . $attribute . '" not permited');
                        },
                    ],
                    'cpf' => [
                        function ($attribute, $value, $fail) {
                            $fail('Parameter "' . $attribute . '" not permited');
                        },
                    ],
                    'updated_at' => [
                        function ($attribute, $value, $fail) {
                            $fail('Parameter "' . $attribute . '" not permited');
                        },
                    ],
                    'created_at' => [
                        function ($attribute, $value, $fail) {
                            $fail('Parameter "' . $attribute . '" not permited');
                        },
                    ],
                    'email_verified_at' => [
                        function ($attribute, $value, $fail) {
                            $fail('Parameter "' . $attribute . '" not permited');
                        },
                    ],
                ]
            );
            if ($validateUser->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Bad params',
                    'errors' => $validateUser->errors()
                ], 400);
            }
            UserService::updateUserById(Auth::id(), $request->all());
            return response()->json([
                'status' => true,
                'message' => "User successfully updated"
            ], 202);
        } catch (NotFoundException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 404);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }
}
