<?php
$CATEGORY_LABELS = [
  'tutoring'        => 'பயிற்சி',
  'photography'     => 'புகைப்படம்',
  'web_design'      => 'வெப் டிசைன்',
  'electrical'      => 'மின் பழுது',
  'plumbing'        => 'குழாய் பணி',
  'graphic_design'  => 'கிராஃபிக் டிசைன்',
  'writing'         => 'உள்ளடக்க எழுத்து',
  'event_services'  => 'நிகழ்வு சேவைகள்',
];

function category_label($key, $fallback) {
    global $CATEGORY_LABELS;
    return $CATEGORY_LABELS[$key] ?? $fallback;
}