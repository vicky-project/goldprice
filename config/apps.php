<?php

return [
  'id' => 'gold-price',
  'name' => 'Harga Emas Dunia',
  'description' => 'Pantau harga emas terkini',
  'icon_class' => 'bi bi-gem',
  'render_type' => 'iframe',
  'render_config' => [
    'url' => env('APP_URL') . '/apps/gold-prices'
  ]
];