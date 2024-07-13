<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Payment Invoice - {{ config('app.name') }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #fff;
            padding: 20px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 20px;
        }
        .details, .summary {
            margin-bottom: 20px;
        }
        .details div, .summary div {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .summary {
            text-align: center;
            font-size: 15px;
        }
        .summary p {
            margin: 5px 0;
        }
        .footer {
            display: flex;
            justify-content: space-between;
            border-top: 1px solid #dee2e6;
            padding-top: 10px;
            margin-top: 10px;
        }
    </style>

</head>
<body>
    <?php
    if (!function_exists('customDateFormat')) {
        function customDateFormat($dateString) {
            return date('F d \a\t Y \a\t H:i', strtotime($dateString));
        }
    }
    ?>

    <div class="container">
        <div class="header">{{ config('app.name') }}!</div>

        <div class="summary">
            <p>Date: {{ customDateFormat($Sales->StartDate) }}</p>
            <p>Payment Mode: {{ $Sales->PaymentMethod }}</p>
            <p>TransactionId: {{ $Sales->TransactionId }}</p>
            <p>Payment Reference: {{ $Sales->PaymentReference }}</p>
        </div>

        <div class="summary">
            <p>Customer Id: <b>{{ $Sales->CustomerId }}</b></p>
            <p>Customer Name: <b>{{ $Sales->CustomerName }}</b></p>
            <p>Product Id: <b>{{ $Sales->ProductId }}</b></p>
            <p>Product Name: <b>{{ $Sales->ProductName }}</b></p>
            <p>Start Date: <b>{{ customDateFormat($Sales->StartDate) }}</b></p>
            <p>Expire Date: <b>{{ customDateFormat($Sales->ExpireDate) }}</b></p>
        </div>

        <div class="summary">
            <p>Amount Paid: <b>{{ $Sales->Amount }}</b></p>
            <p>Authorized By: <b>{{ $Sales->Created_By_Name }}</b></p>
        </div>

        <div class="footer">
            &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
        </div>
    </div>
</body>
</html>
