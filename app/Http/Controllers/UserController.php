<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class UserController extends Controller
{
    private User $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function index()
    {
        $users = $this->user->all();
        return response()->json($users, 200);
    }

    public function store(Request $request) {
        $requestValidate = Validator::make($request->all(), [
            'name'         => 'required|max:160',
            'email'        => 'required|email|max:160',
            'password'     => 'required|max:255|min:6',
            'zipcode'      => 'required|max:10',
            'public_area'  => 'required|max:255',
            'neighborhood' => 'required|max:100',
            'locality'     => 'required|max:100',
            'uf'           => 'required|max:2|min:2'
        ]);

        if ($requestValidate->fails())
            throw new BadRequestHttpException($requestValidate->errors()->first());

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

        $user = User::create($userData);
        $user->address;

        return response()->json($user, 201);

    }
}
