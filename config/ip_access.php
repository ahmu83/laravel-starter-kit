<?php

return [
  // Exact IPs (strict mode or class anchors)
  'allowed_ipv4' => array_filter(array_map('trim', explode(',', env('IP_ACCESS_ALLOWED_IPV4', '')))),
  'allowed_ipv6' => array_filter(array_map('trim', explode(',', env('IP_ACCESS_ALLOWED_IPV6', '')))),

  // Class-based IPs (CIDR recommended)
  'allowed_ipv4_class' => array_filter(array_map('trim', explode(',', env('IP_ACCESS_ALLOWED_IPV4_CLASS', '')))),
  'allowed_ipv6_class' => array_filter(array_map('trim', explode(',', env('IP_ACCESS_ALLOWED_IPV6_CLASS', '')))),
];
