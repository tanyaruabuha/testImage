<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImageRequest;
use App\Models\ImageSetting;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class ImageController extends Controller
{
    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function index()
    {
        return view('image');
    }

    /**
     * @param ImageRequest $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function store(ImageRequest $request)
    {
        $data = $request->validated();

        $type_image = exif_imagetype($data['image']);
        if ($type_image == IMAGETYPE_JPEG) {
            $image = imagecreatefromjpeg($data['image']);
        }
        if ($type_image == IMAGETYPE_PNG) {
            $image = imagecreatefrompng($data['image']);
        }
        if ($type_image == IMAGETYPE_GIF) {
            $image = imagecreatefromgif($data['image']);
        }

        $thumb = imagecreatetruecolor(1, 1);
        imagecopyresampled($thumb, $image, 0, 0, 0, 0, 1, 1, imagesx($image), imagesy($image));
        $mainColor = strtoupper(dechex(imagecolorat($thumb, 0, 0)));

        $dec = hexdec($mainColor);
        $rgb = array(
            'red' => 0xFF & ($dec >> 0x10),
            'green' => 0xFF & ($dec >> 0x8),
            'blue' => 0xFF & $dec
        );

        $img = null;

        if ($rgb['red'] > $rgb['green'] && $rgb['red'] > $rgb['blue']) {
            $img = $this->watermark($data['image'], resource_path('assets/photo_2019-10-17_13-06-20.jpg'));
        } elseif ($rgb['blue'] > $rgb['red'] && $rgb['blue'] > $rgb['green']) {
            $img = $this->watermark($data['image'], resource_path('assets/photo_2019-10-17_13-06-22.jpg'));
        } elseif ($rgb['green'] > $rgb['red'] && $rgb['green'] > $rgb['blue']) {
            $img = $this->watermark($data['image'], resource_path('assets/photo_2019-10-17_13-06-23.jpg'));
        }

        return view('result', ['image' => $img]);
    }

    /**
     * @param $image
     * @param $watermark
     * @return string
     */
    public function watermark($photo, $watermark)
    {
        $img = Image::make($photo);
        $img->resize(500, 500);
        $image = Image::make($watermark)->opacity(50);
        $image->resize(500, 500);
        $img->insert($image, 'top-right');
        $name = $photo->getClientOriginalName();
        $path = 'images/'.$name;
        Storage::disk('public')->put($path, (string) $img->encode());
        return Storage::disk('public')->url($path);
    }
}
