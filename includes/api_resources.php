<?php
declare(strict_types=1);

function rb_api_error(int $status, string $message): void
{
    while (ob_get_level() > 0) ob_end_clean();
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode(['error'=>$message]);
    exit;
}

function rb_api_errors(): void
{
    ini_set('display_errors', '0');
    set_exception_handler(static function(Throwable $e): void {
        error_log('Resource API failure: '.$e->getMessage());
        rb_api_error(500, 'Unable to complete this request. Please try again.');
    });
}

function rb_bounded_json(string $body, int $limit): array
{
    if (strlen($body) > $limit) throw new LengthException('Request is too large');
    $object = json_decode($body, false, 32, JSON_THROW_ON_ERROR);
    if (!is_object($object)) throw new InvalidArgumentException('JSON object required');
    return json_decode($body, true, 32, JSON_THROW_ON_ERROR);
}

function rb_read_api_json(int $limit): array
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') rb_api_error(405, 'POST required');
    if (strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0])) !== 'application/json') rb_api_error(415, 'JSON request required');
    if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > $limit) rb_api_error(413, 'Request is too large');
    $body=file_get_contents('php://input', false, null, 0, $limit+1);
    try { return rb_bounded_json((string)$body,$limit); }
    catch (LengthException $e) { rb_api_error(413, 'Request is too large'); }
    catch (Throwable $e) { rb_api_error(400, 'Invalid JSON request'); }
}

/** Bound row counts/nested work. Report fields are plain text, never client HTML. */
function rb_pdf_data(array $data): array
{
    $nodes=0;
    $visit=static function($value, int $depth=0) use (&$visit,&$nodes) {
        if (++$nodes>50000 || $depth>24) throw new LengthException('Report structure is too large');
        if(is_array($value)) {
            if(count($value)>1000) throw new LengthException('Report has too many rows');
            foreach($value as $key=>$entry) $value[$key]=$visit($entry,$depth+1);
        } elseif(is_float($value) && !is_finite($value)) {
            throw new InvalidArgumentException('Nonfinite report number');
        } elseif(is_string($value)) {
            if(str_starts_with($value,'data:image/')) {
                rb_png_bytes($value);
            } else {
                if(strlen($value)>16384) throw new LengthException('Report text is too long');
                $value=strip_tags($value);
            }
        }
        return $value;
    };
    return $visit($data);
}

function rb_png_bytes(string $data): string
{
    if(!str_starts_with($data,'data:image/png;base64,') || strlen($data)>4194304) throw new InvalidArgumentException('Invalid chart');
    $bytes=base64_decode(substr($data,22),true);
    if($bytes===false || substr($bytes,0,8)!=="\x89PNG\r\n\x1a\n") throw new InvalidArgumentException('Invalid PNG');
    $size=@getimagesizefromstring($bytes);
    if(!$size || $size[2]!==IMAGETYPE_PNG || $size[0]<1 || $size[1]<1 || $size[0]>8192 || $size[1]>8192 || $size[0]*$size[1]>16000000) throw new LengthException('Chart dimensions unsupported');
    return $bytes;
}

/** Track the actual tempnam path, including exception/shutdown cleanup. */
function rb_pdf_chart_file(string $data): string
{
    $bytes=rb_png_bytes($data);
    $path=tempnam(sys_get_temp_dir(),'rb_chart_');
    if($path===false) throw new RuntimeException('Temporary chart unavailable');
    register_shutdown_function(static function() use($path):void {if(is_file($path)) unlink($path);});
    if(file_put_contents($path,$bytes)!==strlen($bytes)) throw new RuntimeException('Chart write failed');
    return $path;
}

/** Reserve an attempt atomically in the existing optional advisor ledger. Never create schema. */
function rb_ai_reserve_monthly(?mysqli $conn, int $subscriberId, int $cap): bool
{
    if (!$conn || $subscriberId <= 0 || $cap <= 0) return false;
    try {
        $period=gmdate('Ym');
        $stmt=$conn->prepare('INSERT IGNORE INTO ai_explain_usage (subscriber_id,period,used) VALUES (?,?,0)');
        $stmt->bind_param('is',$subscriberId,$period);$stmt->execute();$stmt->close();
        $stmt=$conn->prepare('UPDATE ai_explain_usage SET used=used+1 WHERE subscriber_id=? AND period=? AND used<?');
        $stmt->bind_param('isi',$subscriberId,$period,$cap);$stmt->execute();
        $reserved=$stmt->affected_rows===1;$stmt->close();return $reserved;
    } catch(Throwable $e) { return false; }
}

function rb_ai_data(array $data): array
{
    foreach(['results_summary'=>8000,'calculator_type'=>128,'follow_up_question'=>2000] as $key=>$limit) {
        if(isset($data[$key]) && (!is_string($data[$key]) || strlen($data[$key])>$limit)) throw new InvalidArgumentException('Invalid explanation text');
    }
    if(!isset($data['results_summary']) || trim($data['results_summary'])==='') throw new InvalidArgumentException('Missing summary');
    $conversation=$data['conversation']??[];
    if(!is_array($conversation)||count($conversation)>20) throw new InvalidArgumentException('Conversation too large');
    $total=0;
    foreach($conversation as $turn) {
        if(!is_array($turn)||!in_array($turn['role']??null,['user','assistant'],true)||!is_string($turn['content']??null)||strlen($turn['content'])>4000) throw new InvalidArgumentException('Invalid conversation');
        $total+=strlen($turn['content']);
    }
    if($total>32000) throw new LengthException('Conversation too large');
    return $data;
}

/** Single-host burst/concurrency guard; file lock is released even on exceptions. */
function rb_ai_lease(string $owner, ?string $directory=null, ?int $now=null)
{
    if(!preg_match('/^(user|cfa):[1-9][0-9]*$/D',$owner)) throw new InvalidArgumentException('Invalid owner');
    $directory=$directory??sys_get_temp_dir().'/rb-ai-requests';
    if(!is_dir($directory) && !@mkdir($directory,0700,true) && !is_dir($directory)) throw new RuntimeException('Rate storage unavailable');
    if(is_link($directory)) throw new RuntimeException('Unsafe rate storage');
    $file=fopen($directory.'/'.hash('sha256',$owner).'.json','c+');
    if(!$file) throw new RuntimeException('Rate storage unavailable');
    if(!flock($file,LOCK_EX|LOCK_NB)){fclose($file);return null;}
    $now=$now??time();$raw=stream_get_contents($file,4096);
    $state=$raw===''?['start'=>$now,'used'=>0]:json_decode($raw,true);
    if(!is_array($state)||!is_int($state['start']??null)||!is_int($state['used']??null)){fclose($file);throw new RuntimeException('Invalid rate state');}
    if($now-$state['start']>=60)$state=['start'=>$now,'used'=>0];
    if($state['used']>=6){fclose($file);return null;}
    $state['used']++;rewind($file);
    $encoded=json_encode($state);
    if(!ftruncate($file,0)||fwrite($file,$encoded)!==strlen($encoded)||!fflush($file)){fclose($file);throw new RuntimeException('Rate accounting failed');}
    return $file;
}
