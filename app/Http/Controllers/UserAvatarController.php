<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserAvatarController extends Controller
{
    private $driver;

    public function __construct() {
        $this->driver = env('UPLOAD_DRIVER');
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
}
