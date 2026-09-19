<?php
/**
 * Utilidades comunes de las pruebas HTTP (aislamiento.php, ministerio.php).
 * Requieren un servidor en marcha (TEST_BASE_URL, por defecto http://localhost:8080) y una
 * base de DESARROLLO: crean usuarios/empresas temporales y los borran al terminar.
 */

require_once dirname(__DIR__) . '/config/config.php';

$BASE = rtrim(getenv('TEST_BASE_URL') ?: 'http://localhost:8080', '/');
$PASS = 'Test-' . bin2hex(random_bytes(4));
$db = getDB();

$pasan = 0;
$fallan = [];

function prueba(string $nombre, callable $fn): void {
    global $pasan, $fallan;
    try {
        $fn();
        $pasan++;
        echo "  OK   $nombre\n";
    } catch (Throwable $e) {
        $fallan[] = [$nombre, $e->getMessage()];
        echo "  FAIL $nombre\n       " . $e->getMessage() . "\n";
    }
}

function verdadero($cond, string $msg): void {
    if (!$cond) {
        throw new RuntimeException($msg);
    }
}

function igual($esperado, $real, string $msg = ''): void {
    if ($esperado !== $real) {
        throw new RuntimeException(
            ($msg !== '' ? "$msg: " : '') . 'esperado ' . var_export($esperado, true) . ', obtenido ' . var_export($real, true)
        );
    }
}

/** Cliente HTTP con su propio jar de cookies (una "sesión de navegador"). */
class Cliente {
    public string $jar;
    public function __construct(public string $base) {
        $this->jar = tempnam(sys_get_temp_dir(), 'jar');
    }
    public function __destruct() {
        @unlink($this->jar);
    }
    /** @return array{code:int, body:string, url:string} */
    public function pedir(string $metodo, string $ruta, array $datos = [], bool $seguir = false): array {
        $ch = curl_init($this->base . $ruta);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_COOKIEJAR      => $this->jar,
            CURLOPT_COOKIEFILE     => $this->jar,
            CURLOPT_FOLLOWLOCATION => $seguir,
            CURLOPT_TIMEOUT        => 20,
        ]);
        if ($metodo === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($datos));
        }
        $body = (string) curl_exec($ch);
        $r = ['code' => (int) curl_getinfo($ch, CURLINFO_HTTP_CODE), 'body' => $body, 'url' => (string) curl_getinfo($ch, CURLINFO_EFFECTIVE_URL)];
        curl_close($ch);
        return $r;
    }
    public function get(string $ruta, bool $seguir = false): array {
        return $this->pedir('GET', $ruta, [], $seguir);
    }
    public function post(string $ruta, array $datos): array {
        return $this->pedir('POST', $ruta, $datos);
    }
    public function json(string $metodo, string $ruta, array $datos = []): array {
        $r = $this->pedir($metodo, $ruta, $datos);
        $r['json'] = json_decode($r['body'], true);
        return $r;
    }
    /** Token CSRF de una página (campo oculto). */
    public function csrf(string $ruta): string {
        $html = $this->get($ruta, true)['body'];
        if (!preg_match('/name="' . preg_quote(CSRF_TOKEN_NAME, '/') . '"\s+value="([^"]+)"/', $html, $m)
            && !preg_match('/value="([^"]+)"\s+name="' . preg_quote(CSRF_TOKEN_NAME, '/') . '"/', $html, $m)) {
            throw new RuntimeException("No se encontró el token CSRF en $ruta");
        }
        return html_entity_decode($m[1]);
    }
    /** POST multipart/form-data. Los valores pueden ser CURLFile (o 'archivos[0]' => CURLFile). */
    public function multipart(string $ruta, array $campos): array {
        $ch = curl_init($this->base . $ruta);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_COOKIEJAR => $this->jar, CURLOPT_COOKIEFILE => $this->jar,
            CURLOPT_TIMEOUT => 60, CURLOPT_POST => true, CURLOPT_POSTFIELDS => $campos,
        ]);
        $body = (string) curl_exec($ch);
        $r = ['code' => (int) curl_getinfo($ch, CURLINFO_HTTP_CODE), 'body' => $body, 'json' => json_decode($body, true)];
        curl_close($ch);
        return $r;
    }
    /** POST con cuerpo JSON (como hacen las APIs de lotes). $csrf va en la cabecera X-CSRF-Token. */
    public function postJson(string $ruta, array $datos, ?string $csrf = null): array {
        $ch = curl_init($this->base . $ruta);
        $cab = ['Content-Type: application/json'];
        if ($csrf !== null) {
            $cab[] = 'X-CSRF-Token: ' . $csrf;
        }
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_COOKIEJAR => $this->jar, CURLOPT_COOKIEFILE => $this->jar,
            CURLOPT_TIMEOUT => 20, CURLOPT_POST => true, CURLOPT_HTTPHEADER => $cab,
            CURLOPT_POSTFIELDS => json_encode($datos),
        ]);
        $body = (string) curl_exec($ch);
        $r = ['code' => (int) curl_getinfo($ch, CURLINFO_HTTP_CODE), 'body' => $body, 'json' => json_decode($body, true)];
        curl_close($ch);
        return $r;
    }
    public function login(string $email, string $pass, string $panel = '/empresa/dashboard.php'): void {
        $token = $this->csrf('/login.php');
        $this->post('/login.php', [CSRF_TOKEN_NAME => $token, 'email' => $email, 'password' => $pass]);
        $r = $this->get($panel);
        if ($r['code'] !== 200 || strpos($r['url'], 'login.php') !== false) {
            throw new RuntimeException("No se pudo iniciar sesión como $email (¿reCAPTCHA activo o servidor caído?)");
        }
    }
}


/** Crea un usuario (con empresa si rol=empresa). Registra los ids en $ids para la limpieza. */
function crear_usuario(PDO $db, string $tag, string $pass, array &$ids, string $rol = 'empresa'): array {
    $email = "zz_test_{$tag}_" . bin2hex(random_bytes(3)) . '@test.local';
    $db->prepare("INSERT INTO usuarios (email, password, rol, activo, email_verificado) VALUES (?, ?, ?, 1, 1)")
       ->execute([$email, password_hash($pass, PASSWORD_DEFAULT), $rol]);
    $uid = (int) $db->lastInsertId();
    $ids['usuarios'][] = $uid;
    $eid = null;
    if ($rol === 'empresa') {
        $db->prepare("INSERT INTO empresas (usuario_id, nombre, estado) VALUES (?, ?, 'activa')")
           ->execute([$uid, "zz_test_$tag"]);
        $eid = (int) $db->lastInsertId();
        $ids['empresas'][] = $eid;
    }
    return ['uid' => $uid, 'eid' => $eid, 'email' => $email];
}

/** Aborta con código 2 si no hay servidor. */
function exigir_servidor(string $base): void {
    $ch = curl_init($base . '/login.php');
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10]);
    curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code !== 200) {
        fwrite(STDERR, "No hay servidor en $base. Inícielo con: php -S localhost:8080 -t public\n");
        exit(2);
    }
}

function resumen(): void {
    global $pasan, $fallan;
    echo "\n";
    echo 'Resultado: ' . $pasan . ' de ' . ($pasan + count($fallan)) . " pruebas OK\n\n";
    exit($fallan ? 1 : 0);
}
