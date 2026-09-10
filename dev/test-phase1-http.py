"""HTTP tests against real scenario API guards; no application database is accessed.
Usage: PHP_BIN=/path/to/php python3 dev/test-phase1-http.py
"""
import http.cookiejar
import json
import os
from pathlib import Path
import shutil
import socket
import subprocess
import tempfile
import time
import urllib.error
import urllib.request

root = Path(__file__).resolve().parents[1]
php = os.environ.get('PHP_BIN', shutil.which('php') or '/Applications/XAMPP/xamppfiles/bin/php')
checks = 0
with tempfile.TemporaryDirectory(prefix='rb-phase1-http-') as tmp:
    base = Path(tmp)
    (base / 'api').mkdir()
    (base / 'includes').mkdir()
    (base / 'sessions').mkdir()
    for name in ['session_bootstrap.php', 'csrf.php', 'scenario_request.php']:
        shutil.copy(root / 'includes' / name, base / 'includes' / name)
    for name in ['save_scenario.php', 'delete_scenario.php']:
        shutil.copy(root / 'api' / name, base / 'api' / name)
    # A hard stop proves rejected requests never reach the database include.
    (base / 'includes/db_config.php').write_text('<?php throw new RuntimeException("TEST DATABASE ACCESS FORBIDDEN");')
    (base / 'session.php').write_text('''<?php
require __DIR__ . '/includes/session_bootstrap.php';
require __DIR__ . '/includes/csrf.php';
rb_session_start();
header('Content-Type: application/json');
echo json_encode(['csrf' => rb_csrf_token()]);
''')
    (base / 'validate.php').write_text('''<?php
require __DIR__ . '/includes/session_bootstrap.php';
require __DIR__ . '/includes/scenario_request.php';
rb_session_start();
$data = rb_scenario_read_request('save');
header('Content-Type: application/json');
echo json_encode(['success' => true, 'data' => $data]);
''')
    with socket.socket() as sock:
        sock.bind(('127.0.0.1', 0))
        port = sock.getsockname()[1]
    with (base / 'server.log').open('w+') as log:
        server = subprocess.Popen([php, '-d', 'session.save_path=' + str(base / 'sessions'), '-S', f'127.0.0.1:{port}', '-t', str(base)], stdout=log, stderr=log)
        try:
            opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
            origin = f'http://127.0.0.1:{port}'
            def request(route, method='GET', body=None, headers=None):
                req = urllib.request.Request(origin + route, data=body, method=method, headers=headers or {})
                try:
                    response = opener.open(req, timeout=5)
                except urllib.error.HTTPError as error:
                    response = error
                return response.status, response.headers, response.read()
            for attempt in range(50):
                try:
                    status, _, payload = request('/session.php')
                    break
                except urllib.error.URLError:
                    if server.poll() is not None:
                        log.seek(0)
                        raise RuntimeError(log.read())
                    time.sleep(0.1)
            else:
                raise RuntimeError('Test server did not start')
            csrf = json.loads(payload)['csrf']
            good = {'Content-Type': 'application/json', 'X-CSRF-Token': csrf}
            for route in ['/api/save_scenario.php', '/api/delete_scenario.php']:
                cases = [
                    ('GET', None, {}, 405),
                    ('POST', b'{}', {'Content-Type': 'text/plain'}, 415),
                    ('POST', b'{}', {'Content-Type': 'application/json'}, 403),
                    ('POST', b'{}', {**good, 'X-CSRF-Token': 'wrong'}, 403),
                    ('POST', b'{', good, 400),
                    ('POST', b'[]', good, 400),
                    ('POST', b'{}', good, 400),
                    ('POST', b' ' * (1048576 + 1), good, 413),
                ]
                for method, body, headers, expected in cases:
                    status, response_headers, result = request(route, method, body, headers)
                    assert status == expected, (route, expected, status, result)
                    assert json.loads(result)['success'] is False
                    assert 'application/json' in response_headers['Content-Type']
                    assert 'no-store' in response_headers['Cache-Control']
                    if status == 405:
                        assert response_headers['Allow'] == 'POST'
                    checks += 1
            value = {'calculator_type': 'roth-conversion', 'scenario_name': 'Fixture', 'scenario_data': {'balance': 123}}
            status, _, result = request('/validate.php', 'POST', json.dumps(value).encode(), good)
            assert status == 200 and json.loads(result)['data'] == value
            checks += 1
            log.flush()
            log.seek(0)
            assert 'TEST DATABASE ACCESS FORBIDDEN' not in log.read()
            checks += 1
        finally:
            server.terminate()
            server.wait(timeout=5)
print(f'Phase 1 HTTP tests passed ({checks} request/integration checks).')
