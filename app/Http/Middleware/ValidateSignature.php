<?php
namespace App\Http\Middleware;

use Illuminate\Routing\Middleware\ValidateSignature as Middleware;

class ValidateSignature extends Middleware {
  /**
   * The names of the query string parameters that should be ignored.
   *
   * Signed URLs are used for temporary, secure links (unsubscribe, downloads, etc.)
   * These parameters should be ignored when validating the signature.
   *
   * @var array<int, string>
   */
  protected $except = [
    // 'fbclid',          // Facebook click ID
    // 'utm_campaign',    // Marketing tracking
    // 'utm_content',
    // 'utm_medium',
    // 'utm_source',
    // 'utm_term',
  ];
}
