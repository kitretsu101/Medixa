<?php

namespace Tests\Feature;

use App\Console\Commands\ServeMedixa;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\TestWith;
use ReflectionMethod;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class DevelopmentUploadRuntimeTest extends TestCase
{
    #[TestWith([1024, UPLOAD_ERR_OK])]
    #[TestWith([3145728, UPLOAD_ERR_OK])]
    #[TestWith([10485760, UPLOAD_ERR_OK])]
    #[TestWith([10485761, UPLOAD_ERR_INI_SIZE])]
    public function test_development_server_receives_real_multipart_uploads_with_working_temporary_storage(int $size, int $expectedError): void
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0');
        $port = (int) substr(strrchr(stream_socket_get_name($socket, false), ':'), 1);
        fclose($socket);
        $command = Artisan::all()['serve'];
        $this->assertInstanceOf(ServeMedixa::class, $command);
        $command->setInput(new ArrayInput(['--host' => '127.0.0.1', '--port' => (string) $port], $command->getDefinition()));
        $arguments = (new ReflectionMethod($command, 'serverCommand'))->invoke($command);
        $temporaryDirectory = storage_path('framework/php-temp');
        $probe = tempnam($temporaryDirectory, 'probe-');
        $upload = tempnam($temporaryDirectory, 'report-');
        file_put_contents($probe, '<?php $temporary = tmpfile(); header("Content-Type: application/json"); echo json_encode(["error" => $_FILES["report_file"]["error"] ?? null, "size" => $_FILES["report_file"]["size"] ?? null, "temporary_storage" => is_resource($temporary)]);');
        file_put_contents($upload, str_pad(base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jzD0AAAAASUVORK5CYII='), $size, ' '));
        $arguments[array_key_last($arguments)] = $probe;
        $process = new Process($arguments, public_path());
        $process->setTimeout(15);
        $client = curl_init('http://127.0.0.1:'.$port.'/');

        try {
            $process->start();
            $this->assertTrue($process->waitUntil(fn (string $type, string $output): bool => str_contains($output, 'Development Server')));
            curl_setopt_array($client, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => ['report_file' => curl_file_create($upload, 'image/png', 'report.png')], CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10]);
            $body = curl_exec($client);
            $this->assertNotFalse($body, curl_error($client));
            $this->assertSame(200, curl_getinfo($client, CURLINFO_HTTP_CODE));
            $response = json_decode($body, true, flags: JSON_THROW_ON_ERROR);
            $this->assertSame($expectedError, $response['error']);
            $this->assertTrue($response['temporary_storage']);
            if ($expectedError === UPLOAD_ERR_OK) {
                $this->assertSame($size, $response['size']);
            }
        } finally {
            $process->stop();
            File::delete([$probe, $upload]);
        }
    }
}
