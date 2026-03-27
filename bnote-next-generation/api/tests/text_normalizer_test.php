<?php

require_once __DIR__ . '/../text_normalizer.php';

function assertSameValue($expected, $actual, $message) {
    if ($expected !== $actual) {
        fwrite(STDERR, "FAIL: " . $message . PHP_EOL);
        fwrite(STDERR, "Expected: " . var_export($expected, true) . PHP_EOL);
        fwrite(STDERR, "Actual:   " . var_export($actual, true) . PHP_EOL);
        exit(1);
    }
}

$changed = false;
$value = TextNormalizer::normalizeText('ben&ouml;tigt', $changed, true);
assertSameValue('benötigt', $value, 'HTML entities should be decoded');
assertSameValue(true, $changed, 'Entity decode should mark string as changed');

$changed = false;
$value = TextNormalizer::normalizeText('FÃ¼rstenfeldbruck', $changed, true);
assertSameValue('Fürstenfeldbruck', $value, 'Common mojibake should be fixed');
assertSameValue(true, $changed, 'Mojibake fix should mark string as changed');

$changed = false;
$value = TextNormalizer::normalizeText('Fürstenfeldbruck', $changed, true);
assertSameValue('Fürstenfeldbruck', $value, 'Valid UTF-8 should remain unchanged');
assertSameValue(false, $changed, 'Unchanged UTF-8 should not be marked as changed');

$stats = ['count' => 0, 'samples' => []];
$payload = [
    'title' => 'FÃ¼rstenfeldbruck',
    'id' => 7,
    'nested' => [
        'name' => 'ben&ouml;tigt',
        'code' => 'X-123',
    ],
];
$normalized = TextNormalizer::normalizeFieldsRecursive($payload, ['title', 'name'], $stats, true);
assertSameValue('Fürstenfeldbruck', $normalized['title'], 'Recursive field normalization should fix title');
assertSameValue('benötigt', $normalized['nested']['name'], 'Recursive field normalization should fix nested name');
assertSameValue(7, $normalized['id'], 'Numeric IDs must be unchanged');
assertSameValue(2, $stats['count'], 'Stats count should track changed fields');

fwrite(STDOUT, "TextNormalizer tests passed" . PHP_EOL);
