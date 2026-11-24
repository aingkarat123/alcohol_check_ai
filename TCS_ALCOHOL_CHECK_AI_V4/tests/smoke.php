<?php
declare(strict_types=1);

putenv('TCS_AC_DEMO=true');
require_once dirname(__DIR__) . '/includes/bootstrap.php';

function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$employees = demo_employees();
check(isset($employees['1001']), 'Employee 1001 is missing');
check(isset($employees['001001']), 'Leading-zero employee is missing');
check(result_status(0.00, 'mg/L')['code'] === 'PASS', 'PASS calculation failed');
check(result_status(0.08, 'mg/L')['code'] === 'FAIL', 'FAIL calculation failed');
check(result_status(0.00, '%BAC')['code'] === 'REVIEW', 'Unit mismatch must be REVIEW');

$png = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';
$imageInfo = validate_image_data_url($png);
check($imageInfo['mime'] === 'image/png', 'Image MIME validation failed');

echo "PHP smoke tests: PASS\n";
