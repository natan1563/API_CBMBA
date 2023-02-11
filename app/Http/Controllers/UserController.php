<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UserController extends Controller
{
    private User $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function index()
    {
        $users = $this->user->paginate(10);

        foreach ($users as $user){
            $user->address;
        }

        return response()->json($users, 200);
    }

    public function store(Request $request) {
        $this->validateFullRequestData($request);

        $emailAlreadyExists = $this->user->where('email', $request->get('email'))->first();
        if (!!$emailAlreadyExists)
            throw new BadRequestHttpException('Email already exists');

        $addressData = $request->only(
            'zipcode',
            'public_area',
            'neighborhood',
            'locality',
            'uf'
        );
        $address = Address::create($addressData);

        if (!$address)
            throw new Exception('Fail on save address.');

        $userData = $request->only('name', 'email', 'password');

        $userData['address_id'] = $address->id;
        $userData['password'] = Hash::make($userData['password']);

        if ($request->file('avatar')->isValid()) {
            $userData['avatar'] = $request->file('avatar')->hashName();
            $userAvatarController = new UserAvatarController();
            $userAvatarController->uploadFile($request);
        }

        $user = User::create($userData);
        $user->address;
        $token = JWTAuth::fromUser($user);

        return response()->json(
            [
                'message' => 'User created successfully',
                'user'    => $user,
                'token'   => $token
            ],
            201
        );
    }

    public function show($id) {
        $user = User::find($id);
        if (!$user)
            throw new NotFoundHttpException('Could not find the user, please check your id.');

        $user->address;
        return response()->json($user);
    }

    public function update(Request $request, $id) {
        $this->validateFullRequestData($request, true);

        $addressData = $request->only(
            'zipcode',
            'public_area',
            'neighborhood',
            'locality',
            'uf'
        );

        $user = $this->user->where('email', $request->get('email'))->first();
        if (!$user)
            throw new NotFoundHttpException('User not found.');

        if (!!$user && $user->id != $id)
            throw new BadRequestHttpException('User already registered.');

        $address = Address::find($user->address_id);
        $address->zipcode = $addressData['zipcode'];
        $address->public_area = $addressData['public_area'];
        $address->neighborhood = $addressData['neighborhood'];
        $address->locality = $addressData['locality'];
        $address->uf = $addressData['uf'];
        $address->save();

        $user->name = $request->get('name');
        $user->email = $request->get('email');
        $user->save();
        $user->address;

        return response()->json([
            'message' => 'User updated successfully',
            'user'    => $user,
        ]);
    }

    public function destroy($id)
    {
        $user = User::find($id);
        if (!$user)
            throw new NotFoundHttpException('Could not find the user, please check your id.');

        $this->user->destroy($id);
        return response()->noContent();
    }

    private function validateFullRequestData(Request $request, $unsetPassword = false) {
        $fields = [
            'name'         => 'required|max:160',
            'email'        => 'required|email|max:160',
            'password'     => 'required|max:255|min:6',
            'zipcode'      => 'required|max:10',
            'public_area'  => 'required|max:255',
            'neighborhood' => 'required|max:100',
            'locality'     => 'required|max:100',
            'uf'           => 'required|max:2|min:2'
        ];

        if ($unsetPassword)
            unset($fields['password']);

        $requestValidate = Validator::make($request->all(), $fields);
        if ($requestValidate->fails())
            throw new BadRequestHttpException($requestValidate->errors()->first());
    }
}
