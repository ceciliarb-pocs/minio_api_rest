<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Aws\S3\S3Client;
use Aws\Common\Aws;
use Aws\S3\Exception\S3Exception;

class ObjectController extends Controller
{
    public function __construct()
    {

    }

    public function uploadObj($objStrB64="")
    {
        // O código de exemplo abaixo demonstra como as APIs de recursos funcionam
        $aws = new Aws($config);

        // Obter referências a objetos de recurso
        $bucket = $aws->s3->bucket('my-bucket');
        $object = $bucket->object('image/bird.jpg');

        // Acessar atributos dos recursos
        echo $object['LastModified'];

        // Chamar métodos de recursos para executar ações
        // $object->delete();
        // $bucket->delete();

        // Storage::cloud()->put('hello.json', '{"hello": "world"}');
    }
}
