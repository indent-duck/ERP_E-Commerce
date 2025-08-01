<?php include 'db_connection.php';

function textColor($percentage) {
    return $percentage >= 0 ? 'positive' : 'negative';
}

function numberSign($percentage) {
    return $percentage >= 0 ? "+" : "";
}

?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <title>Dashboard</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="dashboard.css">
    </head>
    <body>
        <div class="container-fluid">
            
            <div class="row">
                <div class="col">
                    <h4 class="text-secondary" style="margin-bottom: 0px;">This Month</h4>
                </div>
            </div>

            <!-- 1st card row -->
            <?php
            $monthly_revenue = $conn->query("SELECT SUM(a.total_payment) - SUM(a.quantity * b.cost_price) as total FROM invoices a
                INNER JOIN products b ON a.product_id = b.product_id WHERE MONTH(a.date_placed) = MONTH(CURRENT_DATE());")->fetch_assoc()['total'] ?? 0;
            $revenue_percentage = $conn->query("SELECT 100 * ((SELECT SUM(p.cost_price * i.quantity) FROM invoices i
                INNER JOIN products p ON p.product_id = i.product_id
                WHERE MONTH(i.date_placed) = MONTH(CURRENT_DATE() - INTERVAL 1 MONTH))
                - (SELECT SUM(p.cost_price * i.quantity) FROM invoices i
                INNER JOIN products p ON p.product_id = i.product_id
                WHERE MONTH(i.date_placed) = MONTH(CURRENT_DATE())))
                / (SELECT SUM(p.cost_price * i.quantity) FROM invoices i
                INNER JOIN products p ON p.product_id = i.product_id
                WHERE MONTH(i.date_placed) = MONTH(CURRENT_DATE() - INTERVAL 1 MONTH)) AS percentage;")->fetch_assoc()['percentage'];
            ?>
            <div class="row">
                <div class="col-6">
                    <div class="card">
                        <h5 class="text-secondary">Total Revenue</h5>
                        <h2 class="text-dark">₱ <?= number_format($monthly_revenue, 2) ?></h2>
                        <div class="card-text <?= textColor($revenue_percentage) ?>">
                            <caption class="card-caption"><?= numberSign($revenue_percentage).number_format($revenue_percentage, 0) ?>% than previous month</caption>
                        </div>
                    </div>
                </div>
                <?php

                $current_orders = $conn->query("SELECT COUNT(order_id) as month_orders FROM orders WHERE MONTH(date_placed) = MONTH(CURRENT_DATE());")->fetch_assoc()['month_orders'] ?? 0;
                $invoice_orders = $conn->query("SELECT COUNT(order_id) as invoice_orders FROM invoices WHERE MONTH(date_placed) = MONTH(CURRENT_DATE());")->fetch_assoc()['invoice_orders'] ?? 0;
                $current_month = $current_orders + $invoice_orders;

                $prev_orders = $conn->query("SELECT COUNT(order_id) as month_orders FROM orders WHERE MONTH(date_placed) = MONTH(CURRENT_DATE() - INTERVAL 1 MONTH);")->fetch_assoc()['month_orders'] ?? 0;
                $prev_invoice_orders = $conn->query("SELECT COUNT(order_id) as invoice_orders FROM invoices WHERE MONTH(date_placed) = MONTH(CURRENT_DATE() - INTERVAL 1 MONTH);")->fetch_assoc()['invoice_orders'] ?? 0;
                $prev_month = $prev_orders + $prev_invoice_orders;

                $order_percentage = 100 * ($prev_month != 0 ? (($current_month - $prev_month) / $prev_month) : 0);
                ?>
                <div class="col-6">
                    <div class="card">
                        <h5 class="text-secondary">Orders</h5>
                        <h2 class="text-dark"><?= $current_month ?></h2>
                        <div class="card-text <?= textColor($order_percentage); ?>">
                            <caption class="card-caption"><?= numberSign($order_percentage).number_format($order_percentage, 0); ?>% than previous month</caption>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2nd card row -->
            <?php
            $total_customers = $conn->query("SELECT COUNT(customer_id) AS total_customers FROM customers;")->fetch_assoc()['total_customers'];
            $new_customers = $conn->query("SELECT COUNT(customer_id) AS new_customers FROM customers
                    WHERE MONTH(date_created) = MONTH(CURRENT_DATE());")->fetch_assoc()['new_customers'];
            ?>
            <div class="row">
                <div class="col-6">
                    <div class="card">
                        <h5 class="text-secondary">Customers</h5>
                        <h2 class="text-dark"><?= $total_customers ?></h2>
                        <div class="card-text"" style="color: #27AC49">
                            <caption class="card-caption"><?= $new_customers ?> new this month</caption>
                        </div>
                    </div>
                </div>

                <?php
                $products_sold = $conn->query("SELECT COUNT(*) AS total_sold FROM invoices WHERE MONTH(date_placed) = MONTH(CURRENT_DATE() - INTERVAL 1 MONTH);")->fetch_assoc()['total_sold'];
                $products_percentage = $conn->query("SELECT 100 * ((SELECT COUNT(*) FROM invoices WHERE MONTH(date_placed) = MONTH(CURRENT_DATE()))
                    - (SELECT COUNT(*) FROM invoices WHERE MONTH(date_placed) = MONTH(CURRENT_DATE() - INTERVAL 1 MONTH)))
                    / (SELECT COUNT(*) FROM invoices WHERE MONTH(date_placed) = MONTH(CURRENT_DATE() - INTERVAL 1 MONTH)) as percentage;")->fetch_assoc()['percentage'];
                ?>
                <div class="col-6">
                    <div class="card">
                        <h5 class="text-secondary">Products Sold</h5>
                        <h2 class="text-dark"><?= $products_sold ?></h2>
                        <div class="card-text <?= textColor($products_percentage) ?>">
                            <caption class="card-caption"><?= numberSign($products_percentage).number_format($products_percentage, 0) ?>% than previous month</caption>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col">
                    <h4 class="text-secondary">Recent Orders</h4>
                    <div class="table-container">
                        <table class="table table-hover">
                            <colgroup>
                                <col style="width: 15%";>
                                <col style="width: 25%";>
                                <col style="width: 20%";>
                                <col style="width: 20%";>
                                <col style="width: 20%";>
                            </colgroup>
                            <thead class="table-light">
                                <th class="text-center">Order Id</th>
                                <th>Item</th>
                                <th>Total</th>
                                <th>Date Placed</th>
                                <th>Payment</th>
                            </thead>

                            <?php
                            $recent_order = $conn->query("SELECT o.order_id as order_id, p.product_name as product_name,
                                o.item_quantity as quantity, o.total_amount as total_amount, o.date_placed as date_placed, o.payment as payment
                                    FROM orders o INNER JOIN products p ON o.product_id = p.product_id LIMIT 5;");

                            if ($recent_order && $recent_order-> num_rows > 0) {
                                while ($row = $recent_order->fetch_assoc()) {
                                    $order_id = $row['order_id'];
                                    $item = $row['product_name'];
                                    $quantity = $row['quantity'];
                                    $total_amount = $row['total_amount'];
                                    $date_placed = $row['date_placed'];
                                    $payment = $row['payment'];
                            ?>
                            <tbody>
                                <tr>
                                    <th class="text-center"><?= $order_id ?></th>
                                    <td><?= $item ?> <label class="text-secondary" style="font-size: 14px">x<?=$quantity?></label></td>
                                    <td>₱ <?= number_format($total_amount, 2) ?></td>
                                    <td><?= $date_placed ?></td>
                                    <td><?= $payment ?></td>
                                </tr>
                            </tbody>
                            <?php }
                        } else { ?>
                            <tbody>
                                <tr>
                                    <td></td>
                                </tr>
                            </tbody>
                        <?php }  ?>
                        
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </body>
</html>