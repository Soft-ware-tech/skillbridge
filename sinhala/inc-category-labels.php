<?php
$CATEGORY_LABELS = [
  'tutoring'        => 'ටියුෂන්',
  'photography'     => 'ඡායාරූප ශිල්පය',
  'web_design'      => 'වෙබ් නිර්මාණය',
  'electrical'      => 'විදුලි අළුත්වැඩියා',
  'plumbing'        => 'ජල නල කාර්මික',
  'graphic_design'  => 'ග්‍රැෆික් නිර්මාණය',
  'writing'         => 'අන්තර්ගත රචනය',
  'event_services'  => 'උත්සව සේවා',
];

function category_label($key, $fallback) {
    global $CATEGORY_LABELS;
    return $CATEGORY_LABELS[$key] ?? $fallback;
}