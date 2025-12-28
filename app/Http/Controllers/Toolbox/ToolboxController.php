<?php
namespace App\Http\Controllers\Toolbox;

use Illuminate\Http\Request;

class ToolboxController {
  public function index(Request $request) {
    $data = [
      'status'      => 'ok',
      'message'     => 'Toolbox root route is working',
      'true'        => true,
      'false'       => false,
      'null'        => null,
      'user'        => $request->user()?->email,
      'environment' => app()->environment(),
      'routes'      => [
        'logs'   => url('toolbox/log-viewer'),
        'queue'  => url('toolbox/queues'),
        'tinker' => url('toolbox/tinker'),
      ],
    ];

    printr($data, '$data');
    dd($data);

  }

  public function tools(Request $request) {
    $links = [
      'Logs Viewer'   => 'vendor-tools/log-viewer',
      'Queues Viewer' => 'vendor-tools/queues',
      'Tinker'        => 'vendor-tools/tinker',
      'Pulse APM'     => 'vendor-tools/pulse',
    ];

    $data = [];

    foreach ($links as $label => $path) {
      $href = url($path);
      $safeHref = htmlspecialchars($href, ENT_QUOTES, 'UTF-8');

      $data[$label] = '<a target="_blank" rel="noopener noreferrer" href="' . $safeHref . '">' . $safeHref . '</a>';
    }

    printr($data, '$data');
  }

  public function ping() {
    return 'toolbox pong';
  }
}
