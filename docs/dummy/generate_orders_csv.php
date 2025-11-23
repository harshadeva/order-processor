<?php

$rows = 500; // number of rows
$filename = __DIR__."/large_orders.csv";

$fp = fopen($filename, 'w');

// header
fputcsv($fp, ['id','order_code', 'customer_id', 'product_id', 'quantity', 'unit_price', 'total']);

for ($i = 1; $i <= $rows; $i++) {
    $orderCode = "ORD-" . str_pad(rand(1,  max(intval($rows / 5),100)), 6, "0", STR_PAD_LEFT);
    $customerId = rand(1, 1000);
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
