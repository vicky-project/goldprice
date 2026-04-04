<?php

return [
  'name' => 'GoldPrice',
  "hooks" => [
    "enabled" => env("GOLDPRICE_HOOKS_ENABLED", true),
    "service" => \Modules\CoreUI\Services\UIService::class,
    "name" => "main-apps",
  ],
];