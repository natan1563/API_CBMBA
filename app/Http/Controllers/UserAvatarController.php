<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UserAvatarController extends Controller
{
    private $driver;
    const STORAGE_FOLDER = 'profile';

    public function __construct() {
        $this->driver = env('UPLOAD_DRIVER');
    }

    public function updateProfileImage(Request $request) {
        $user = $request->user();

        if (!$request->file('avatar'))
            throw new BadRequestException('O avatar é obrigatório.');

        if ($request->file('avatar') && $request->file('avatar')->isValid()) {
            if (!!$user->avatar)
                $this->removeFile($user->avatar);

            $user->avatar = $request->file('avatar')->hashName();
            $path = $this->uploadFile($request);

            if (!$path)
                throw new Exception('Falhao ao salvar o avatar.');

            $user->save();
            $user->address;
        }

        return response($user, 200);
    }

    public function showProfile($imageName) {
        $filePath = self::STORAGE_FOLDER . '/' . $imageName;
        if (!Storage::disk($this->driver)->exists($filePath))
            throw new NotFoundHttpException('Não foi possível localizar o avatar.');

        $content = $this->getProfileImage($filePath);
        $mime = Storage::mimeType($filePath);
        $response = Response::make($content, 200);
        $response->header("Content-Type", $mime);

        return $response;
    }

    private function getProfileImage($filePath) {
        return Storage::disk($this->driver)->get($filePath);
    }

    public function uploadFile(Request $request)
    {
        $avatar = $request->file('avatar');
        $fileName = $avatar->hashName();
        $isS3Driver = $this->driver === 's3';
        $path = $request->file('avatar')->storePubliclyAs(
            self::STORAGE_FOLDER,
            $fileName,
            ($isS3Driver ? 's3' : [])
        );
        return $path;
    }

    public function removeFile($fileName) {
        $path = Storage::disk($this->driver)->delete(self::STORAGE_FOLDER . '/' . $fileName);
        return !!$path;
    }
}
