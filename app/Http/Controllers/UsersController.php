<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Exceptions\NotFoundException;
use App\Exceptions\BadParamsException;
use App\Models\Profile;
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
            $validate = Validator::make(
                $request->all(),
                [
                    'name' => 'required|min:5|max:150',
                    'email' => 'required|email|unique:users,email',
                    'cpf' => 'required|min:5|unique:users,cpf',
                    'password' => 'required|min:6',
                    'profile_id' => [
                        function ($attribute, $value, $fail) {
                            if (!Profile::find($value)) {
                                $fail('"' . $attribute . '": "' . $value . '") don\'t exist');
                            }
                        },
                    ],
                    'addresses'=>[
                        function ($attribute, $value, $fail) {
                            foreach($value??[] as $address){
                                $validate = Validator::make($address, [
                                    'address' => 'required|min:10|max:255',
                                    'description' => 'required|min:1|max:50',
                                    'principal' => 'nullable|boolean'
                                ]);
                                if($validate->fails()){
                                    $fail($validate->errors());
                                }
                            }
                        },
                    ],
                    'phoneNumbers'=>[
                        function ($attribute, $value, $fail) {
                            foreach($value??[] as $address){
                                $validate = Validator::make($address, [
                                    'number' => 'required|min:5',
                                    'description' => 'required|min:1|max:50',
                                    'principal' => 'nullable|boolean'
                                ]);
                                if($validate->fails()){
                                    $fail($validate->errors());
                                }
                            }
                        },
                    ]
                ]
            );
            if ($validate->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Bad request',
                    'errors' => $validate->errors()
                ], 400);
            }
            DB::beginTransaction();
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'cpf' => $request->cpf,
                'password' => Hash::make($request->password),
                'profile_id' => $request->profile_id ?? Profile::PROFILES['user']
            ]);
            foreach($request->addresses??[] as $address){
                UserService::addAddress($user->id, $address);
            }
            foreach($request->phoneNumbers??[] as $phoneNumber){
                UserService::addPhoneNumber($user->id, $phoneNumber);
            }
            DB::commit();
            return response()->json([
                'status' => true,
                'message' => 'User created successfully',
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
            $validate = Validator::make(
                $request->all(),
                [
                    'email' => 'required|email',
                    'password' => 'required'
                ]
            );
            if ($validate->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Bad params',
                    'errors' => $validate->errors()
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
            $user->profile;
            return $user;
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function listUsers(Request $request)
    {
        $users = User::where('id', '<>', Auth::id())->get();
        foreach ($users as $user) {
            $user->addresses;
            $user->telephoneNumbers;
            $user->profile;
        }
        return $users;
    }

    public function loggedUser(Request $request)
    {
        $user = User::find(Auth::id());
        $user->addresses;
        $user->telephoneNumbers;
        $user->profile;
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
            $validate = Validator::make(
                $request->all(),
                [
                    'name' => 'nullable|min:5|max:150',
                    'email' => 'nullable|email',
                    'cpf' => 'nullable|min:5',
                    'password' => 'nullable|min:6',
                    'profile_id' => [
                        function ($attribute, $value, $fail) {
                            if (!Profile::find($value)) {
                                $fail('"' . $attribute . '": "' . $value . '") don\'t exist');
                            }
                        },
                    ],
                ]
            );
            if ($validate->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Bad request',
                    'errors' => $validate->errors()
                ], 400);
            }
            UserService::updateUserById($id, $request->only(['name', 'email', 'cpf', 'profile_id', 'password']));
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
            $validate = Validator::make(
                $request->all(),
                [
                    'name' => 'nullable|min:5|max:150',
                    'password' => 'nullable|string|min:6'
                ],
            );
            if ($validate->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Bad params',
                    'errors' => $validate->errors()
                ], 400);
            }
            UserService::updateUserById(Auth::id(), $request->only(['name', 'password']));
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

    public function addAddress(Request $request)
    {
        try {
            $validate = Validator::make(
                $request->all(),
                [
                    'address' => 'required|min:10|max:255',
                    'description' => 'required|min:1|max:50',
                    'principal' => 'nullable|boolean'
                ],
            );
            if ($validate->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Bad params',
                    'errors' => $validate->errors()
                ], 400);
            }
            UserService::addAddress(Auth::id(), $request->only(['address', 'description', 'principal']));
            return response()->json([
                'status' => true,
                'message' => 'Address successfully created'
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function addAddressByUserId(Request $request, $id)
    {
        try {
            $validate = Validator::make(
                $request->all(),
                [
                    'address' => 'required|min:10|max:255',
                    'description' => 'required|min:1|max:50',
                    'principal' => 'nullable|boolean'
                ],
            );
            if ($validate->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Bad params',
                    'errors' => $validate->errors()
                ], 400);
            }
            $user = User::find($id);
            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'User "' . $id . '" not found',
                ], 404);
            }
            UserService::addAddress($id, $request->only(['address', 'description', 'principal']));
            return response()->json([
                'status' => true,
                'message' => 'Address successfully created'
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function deleteAddress(Request $request, $id)
    {
        try {
            UserService::removeAddress(Auth::id(), $id);
            return response()->json([
                'status' => true,
                'message' => 'Address successfully deleted',
            ], 200);
        } catch (NotFoundException $notFoundException) {
            return response()->json([
                'status' => false,
                'message' => $notFoundException->getMessage()
            ], 404);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function deleteAddressFromUserId(Request $request, $user_id, $address_id)
    {
        try {
            UserService::removeAddress($user_id, $address_id);
            return response()->json([
                'status' => true,
                'message' => 'Address successfully deleted'
            ], 200);
        } catch (NotFoundException $notFoundException) {
            return response()->json([
                'status' => false,
                'message' => $notFoundException->getMessage()
            ], 404);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function updateAddress(Request $request)
    {
        try {
            $validate = Validator::make(
                $request->all(),
                [
                    'id' => 'required|integer',
                    'address' => 'required|min:10|max:255',
                    'description' => 'required|min:1|max:50',
                    'principal' => 'nullable|boolean'
                ],
            );
            if ($validate->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Bad params',
                    'errors' => $validate->errors()
                ], 400);
            }
            UserService::updateAddress(Auth::id(), $request->only(['id', 'address', 'description', 'principal']));
            return response()->json([
                'status' => true,
                'message' => 'Address successfully updated'
            ], 202);
        } catch (BadParamsException $badParamsException) {
            return response()->json([
                'status' => false,
                'message' => $badParamsException->getMessage()
            ], 400);
        } catch (NotFoundException $notFoundException) {
            return response()->json([
                'status' => false,
                'message' => $notFoundException->getMessage()
            ], 404);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function updateAddressByUserId(Request $request, $id)
    {
        try {
            $validate = Validator::make(
                $request->all(),
                [
                    'id' => 'required|integer',
                    'address' => 'required|min:10|max:255',
                    'description' => 'required|min:1|max:50',
                    'principal' => 'nullable|boolean'
                ],
            );
            if ($validate->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Bad params',
                    'errors' => $validate->errors()
                ], 400);
            }
            UserService::updateAddress($id, $request->only(['id', 'address', 'description', 'principal']));
            return response()->json([
                'status' => true,
                'message' => 'Address successfully updated'
            ], 202);
        } catch (BadParamsException $badParamsException) {
            return response()->json([
                'status' => false,
                'message' => $badParamsException->getMessage()
            ], 400);
        } catch (NotFoundException $notFoundException) {
            return response()->json([
                'status' => false,
                'message' => $notFoundException->getMessage()
            ], 404);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function addPhoneNumber(Request $request)
    {
        try {
            $validate = Validator::make(
                $request->all(),
                [
                    'number' => 'required|min:5',
                    'description' => 'required|min:1|max:50',
                    'principal' => 'nullable|boolean'
                ],
            );
            if ($validate->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Bad params',
                    'errors' => $validate->errors()
                ], 400);
            }
            UserService::addPhoneNumber(Auth::id(), $request->only(['number', 'description', 'principal']));
            return response()->json([
                'status' => true,
                'message' => 'Phone number successfully created'
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function addPhoneNumberByUserId(Request $request, $id)
    {
        try {
            $validate = Validator::make(
                $request->all(),
                [
                    'number' => 'required|min:5',
                    'description' => 'required|min:1|max:50',
                    'principal' => 'nullable|boolean'
                ],
            );
            if ($validate->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Bad params',
                    'errors' => $validate->errors()
                ], 400);
            }
            $user = User::find($id);
            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'User "' . $id . '" not found',
                ], 404);
            }
            UserService::addPhoneNumber($id, $request->only(['number', 'description', 'principal']));
            return response()->json([
                'status' => true,
                'message' => 'Phone number successfully created'
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function deletePhoneNumber(Request $request, $id)
    {
        try {
            UserService::removePhoneNumber(Auth::id(), $id);
            return response()->json([
                'status' => true,
                'message' => 'Phone number successfully deleted'
            ], 200);
        } catch (NotFoundException $notFoundException) {
            return response()->json([
                'status' => false,
                'message' => $notFoundException->getMessage()
            ], 404);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function deletePhoneNumberFromUserId(Request $request, $user_id, $phone_id)
    {
        try {
            UserService::removePhoneNumber($user_id, $phone_id);
            return response()->json([
                'status' => true,
                'message' => 'Phone number successfully deleted'
            ], 200);
        } catch (NotFoundException $notFoundException) {
            return response()->json([
                'status' => false,
                'message' => $notFoundException->getMessage()
            ], 404);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function updatePhoneNumber(Request $request)
    {
        try {
            $validate = Validator::make(
                $request->all(),
                [
                    'id' => 'required|integer',
                    'number' => 'required|min:5',
                    'description' => 'required|min:1|max:50',
                    'principal' => 'nullable|boolean'
                ],
            );
            if ($validate->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Bad params',
                    'errors' => $validate->errors()
                ], 400);
            }
            UserService::updatePhoneNumber(Auth::id(), $request->only(['id', 'number', 'description', 'principal']));
            return response()->json([
                'status' => true,
                'message' => 'Phone number successfully updated'
            ], 202);
        } catch (BadParamsException $badParamsException) {
            return response()->json([
                'status' => false,
                'message' => $badParamsException->getMessage()
            ], 400);
        } catch (NotFoundException $notFoundException) {
            return response()->json([
                'status' => false,
                'message' => $notFoundException->getMessage()
            ], 404);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function updatePhoneNumberByUserId(Request $request, $id)
    {
        try {
            $validate = Validator::make(
                $request->all(),
                [
                    'id' => 'required|integer',
                    'number' => 'required|min:5',
                    'description' => 'required|min:1|max:50',
                    'principal' => 'nullable|boolean'
                ],
            );
            if ($validate->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Bad params',
                    'errors' => $validate->errors()
                ], 400);
            }
            UserService::updatePhoneNumber($id, $request->only(['id', 'number', 'description', 'principal']));
            return response()->json([
                'status' => true,
                'message' => 'Phone number successfully updated'
            ], 202);
        } catch (BadParamsException $badParamsException) {
            return response()->json([
                'status' => false,
                'message' => $badParamsException->getMessage()
            ], 400);
        } catch (NotFoundException $notFoundException) {
            return response()->json([
                'status' => false,
                'message' => $notFoundException->getMessage()
            ], 404);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function getProfiles(Request $request){
        return Profile::all();
    }
}
