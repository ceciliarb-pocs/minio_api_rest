<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;

class ObjectController extends Controller
{
    private static $clientS3;
    private static $key;
    private static $secret;

    public function __construct(Request $request)
    {
        $header   = $request->header('Authorization');
        $header   = \explode(":", $header);
        if(count($header) !== 2) {
            throw new \Exception("[ERRO] Nenhuma credencial fornecida.", 412);
        }
        self::$key    = $header[0];
        self::$secret = $header[1];
        self::$clientS3 = S3Client::factory(array(
            'credentials' => array(
                'key'     => self::$key,
                'secret'  => self::$secret,
            ),
            'version'     => 'latest',
            'region'      => 'sa-east-1',
            'endpoint'    => env('MINIO_ENDPOINT'),
            'use_path_style_endpoint' => true
        ));
    }

    //------------------------- STORAGE do Laravel com driver = s3 e confs do minio ------------------------------/
    public function uploadFileStorage(Request $req)
    {
        $req->validate([
            'nome_arquivo' => 'required'
        ]);
        $objName = $req->nome_arquivo;
        $objStrB64 = $req->corpo_arquivo ? $req->corpo_arquivo : "";
        $objStrB64 = base64_decode($objStrB64);
        $override = $req->sobrescreve || false;

        try {
            $exists = Storage::cloud()->exists($objName);
            if(!$exists || $override) {
                Storage::cloud()->put($objName, $objStrB64);
                return "Arquivo salvo com sucesso!";
            } else if(!$override){
                return "[ERRO] O arquivo já existe e não pode ser sobrescrito.";
            }
        } catch (\Throwable $th) {
            return "[ERRO] Falha na operação. {$th->message}";
        }
    }
    public function getFileStorage(Request $req)
    {
        $req->validate([
            'nome_arquivo' => 'required'
        ]);
        $objName = $req->nome_arquivo;

        try {
            $exists = Storage::cloud()->exists($objName);
            if($exists) {
                $file = Storage::cloud()->get($objName);
                return $file;
            } else {
                return "[ERRO] O arquivo não existe no repositório.";
            }
        } catch (\Throwable $th) {
            return "[ERRO] Falha na operação. {$th->message}";
        }
    }
    public function downloadFileStorage(Request $req)
    {
        $req->validate([
            'nome_arquivo' => 'required'
        ]);
        $objName = $req->nome_arquivo;

        try {
            $exists = Storage::cloud()->exists($objName);
            if($exists) {
                $file = Storage::cloud()->download($objName);
                return $file;
            } else {
                return "[ERRO] O arquivo não existe no repositório.";
            }
        } catch (\Throwable $th) {
            return "[ERRO] Falha na operação. {$th->message}";
        }
    }
    public function getUrlFileStorage(Request $req)
    {
        $req->validate([
            'nome_arquivo' => 'required'
        ]);
        $objName = $req->nome_arquivo;

        try {
            $exists = Storage::cloud()->exists($objName);
            if($exists) {
                $url = Storage::cloud()->url($objName);
                return $url;
            } else {
                return "[ERRO] O arquivo não existe no repositório.";
            }
        } catch (\Throwable $th) {
            return "[ERRO] Falha na operação. {$th->message}";
        }
    }
    public function deleteFileStorage(Request $req)
    {
        $req->validate([
            'nome_arquivo' => 'required'
        ]);
        $objName = $req->nome_arquivo;

        try {
            $exists = Storage::cloud()->exists($objName);
            if($exists) {
                Storage::cloud()->delete($objName);
                return "Arquivo deletado com sucesso!";
            } else {
                return "[ERRO] O arquivo não existe no repositório.";
            }
        } catch (\Throwable $th) {
            return "[ERRO] Falha na operação. {$th->message}";
        }
    }


    //------------------------- AWS puro -------------------------------------------------------------------------/
    public function putObjAws(Request $request)
    {
        $request->validate(['arquivo' => 'required|file']);
        $resposta = [];
        $file     = $request->file('arquivo');

        try {
            $nome     = $file->getClientOriginalName();
            $conteudo = file_get_contents($file->path());

            $response = self::$clientS3->putObject([
                            'Bucket' => self::$key,
                            'Key'    => $nome,
                            'Body'   => $conteudo]);

            $extensao = $file->getClientOriginalExtension();
            $mimetype = \mime_content_type($file->getPathname());
            $hash_id  = \hash('sha256', $conteudo.\time());
            $hash_cont= \hash('sha256', $conteudo);
            $resposta = [ "nome"      => $nome,
                          "extensao"  => $extensao,
                          "mimetype"  => $mimetype,
                          "hash_id"   => $hash_id,
                          "hash_cont" => $hash_cont,
                          "ETag"      => $response->get('ETag'),
                          "url"       => $response->get('ObjectURL')];

            return json_encode($resposta);

        } catch (S3Exception $e) {
            throw new \Exception($e->getMessage() . "\n", 412);
        } catch (\Exception $e) {
            throw new \Exception("Error Processing Request", 412);
        }
    }

    public function getObjAws(Request $request) {
        $request->validate(['nome_arquivo' => 'required|string']);
        $nome     = $request->nome_arquivo;

        try {
            $r = fopen('php://temp/', 'wb');
            $e = new \GuzzleHttp\Psr7\Stream($r);
            // Save object to a file.
            self::$clientS3->getObject([
                'Bucket' => self::$key,
                'Key'    => $nome,
                'SaveAs' => $e
            ]);
            return $e;
        } catch (S3Exception $e) {
            throw new \Exception($e->getMessage() . "\n", 412);
        } catch (\Exception $e) {
            throw new \Exception("Error Processing Request", 412);
        }
    }

    public function getUrlAws(Request $request) {
        $request->validate(['nome_arquivo' => 'required|string']);
        $nome     = $request->nome_arquivo;
        $s3Client->getObjectUrl('my-bucket', 'my-key');
    }
}
