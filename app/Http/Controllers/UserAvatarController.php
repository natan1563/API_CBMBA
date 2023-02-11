<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class UserAvatarController extends Controller
{
    private $driver;

    public function __construct() {
        $this->driver = env('UPLOAD_DRIVER');
    }

    public function updateProfileImage(Request $request) {
        $user = $request->user();

        if (!$request->file('avatar'))
            throw new BadRequestException('The parameter avatar is required.');

        if ($request->file('avatar')->isValid()) {
            $userAvatarController = new UserAvatarController();

            if (!!$user->avatar)
                $userAvatarController->removeFile($user->avatar);

            $user->avatar = $request->file('avatar')->hashName();
            $path = $userAvatarController->uploadFile($request);

            if (!$path)
                throw new Exception('Upload file failed.');

            $user->save();
            $user->address;
        }

        return response($user, 200);
    }

    public function uploadFile(Request $request)
    {
        $avatar = $request->file('avatar');
        $fileName = $avatar->hashName();
        $isS3Driver = $this->driver === 's3';
        $path = $request->file('avatar')->storePubliclyAs(
            'profile',
            $fileName,
            ($isS3Driver ? 's3' : [])
        );
        return $path;
    }

    public function removeFile($fileName) {
        $path = Storage::disk($this->driver)->delete("profile/{$fileName}");
        return !!$path;
    }
}
