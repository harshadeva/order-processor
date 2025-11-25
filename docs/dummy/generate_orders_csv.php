<?php

$rows = 1025000;
$filename = __DIR__."/large_orders.csv";
$fp = fopen($filename, 'w');

fputcsv($fp, ['id','order_code', 'customer_id', 'product_id', 'quantity', 'unit_price', 'total']);

$orderCustomerMap = [];

for ($i = 1; $i <= $rows; $i++) {

    $orderCode = "ORD-" . str_pad(rand(1, 300000), 6, "0", STR_PAD_LEFT);

    if (!isset($orderCustomerMap[$orderCode])) {
        $orderCustomerMap[$orderCode] = rand(1, 1000);
    }

    $customerId = $orderCustomerMap[$orderCode];

    $productId  = rand(1, 200);
    $qty        = rand(1, 5);
    $price      = rand(100, 5000) / 100;
    $total      = $qty * $price;

    fputcsv($fp, [
        $i,
        $orderCode,
        $customerId,
        $productId,
        $qty,
        $price,
        $total
    ]);
}

fclose($fp);

echo "CSV generated: $filename\n";
echo "Unique orders: " . count($orderCustomerMap) . "\n";
