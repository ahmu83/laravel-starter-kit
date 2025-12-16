<?php

return [

  'allowed_emails' => array_filter(
    array_map('trim', explode(',', env('SANDBOX_ALLOWED_EMAILS', '')))
  ),

];
