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
        $users = $this->user->paginate(50);

        foreach ($users as $user){
            $user->address;
        }

        return response()->json($users, 200);
    }

    public function store(Request $request) {
        $this->validateFullRequestData($request);

        $emailAlreadyExists = $this->user->where('email', $request->get('email'))->first();
        if (!!$emailAlreadyExists)
            throw new BadRequestHttpException('E-mail já está em uso.');

        $addressData = $request->only(
            'zipcode',
            'public_area',
            'neighborhood',
            'locality',
            'uf'
        );
        $address = Address::create($addressData);

        if (!$address)
            throw new Exception('Falha ao salvar os dados de endereço.');

        $userData = $request->only('name', 'email', 'password');

        $userData['address_id'] = $address->id;
        $userData['password'] = Hash::make($userData['password']);

        if ($request->file('avatar') && $request->file('avatar')->isValid()) {
            $userData['avatar'] = $request->file('avatar')->hashName();
            $userAvatarController = new UserAvatarController();
            $userAvatarController->uploadFile($request);
        }

        $user = User::create($userData);
        $user->address;
        $token = JWTAuth::fromUser($user);

        return response()->json(
            [
                'message' => 'Usuário criado com sucesso.',
                'user'    => $user,
                'token'   => $token
            ],
            201
        );
    }

    public function show($id) {
        $user = User::find($id);
        if (!$user)
            throw new NotFoundHttpException('Não foi possível carregar os dados do usuário.');

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
            throw new NotFoundHttpException('Usuário não encontrado.');

        if (!!$user && $user->id != $id)
            throw new BadRequestHttpException('Usuário já cadastrado.');

        $address = Address::find($user->address_id);
        $address->zipcode = $addressData['zipcode'];
        $address->public_area = $addressData['public_area'];
        $address->neighborhood = $addressData['neighborhood'];
        $address->locality = $addressData['locality'];
        $address->uf = $addressData['uf'];

        $user->name = $request->get('name');
        $user->email = $request->get('email');

        if ($request->file('avatar') && $request->file('avatar')->isValid()) {
            $userAvatarController = new UserAvatarController();
            if (!!$user->avatar)
                $userAvatarController->removeFile($user->avatar);

            $user->avatar = $request->file('avatar')->hashName();
            $path = $userAvatarController->uploadFile($request);
            if (!$path)
                throw new Exception('Falha ao salvar o avatar.');
         }

        $address->save();
        $user->save();

        $user->address;

        return response()->json([
            'message' => 'Usuário atualizado com sucesso',
            'user'    => $user,
        ]);
    }

    public function destroy($id)
    {
        $user = User::find($id);
        if (!$user)
            throw new NotFoundHttpException('Não foi possível carregar os dados do usuário.');

        try {
            $userAvatarController = new UserAvatarController();
            if (!is_null($user->avatar)) {
                $userAvatarController->removeFile($user->avatar);
            }
        } catch (\Exception $e) {
            // Possivel LOG - Bugsnag
        }

        $this->user->destroy($id);

        if (!is_null($user->address_id)) {
            // Solução para contornar o BUG de delete CASCADE no Laravel 9
            Address::destroy($user->address_id);
        }

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
