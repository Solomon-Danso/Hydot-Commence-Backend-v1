<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Payment Plan for {{ config('app.name') }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 0;
        }
        .wrapper {
            padding: 2rem;
        }
        .card {
            max-width: 600px;
            margin: 0 auto;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            background-color: #ffffff;
            color: #333;
        }
        .header {
            background-color: #4CAF50;
            color: white;
            text-align: center;
            padding: 1.5rem;
        }
        .header h1 {
            margin: 0;
        }
        .content {
            padding: 1.5rem;
        }
        .content p {
            line-height: 1.6;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        .table th, .table td {
            padding: 0.75rem;
            border: 1px solid #ddd;
            text-align: left;
        }
        .table th {
            background-color: #f2f2f2;
        }
        .footer {
            padding: 1rem;
            font-size: 0.875rem;
            text-align: center;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="card">
            <div class="header">
                <h1>Welcome to {{ config('app.name') }}!</h1>
            </div>
            <div class="content">
                <p>Hello {{ $c['Fullname'] }},</p>
                <p>Your credit sale with Order ID: {{ $c['OrderId'] }} has been approved. Below is your payment schedule:</p>

                <!-- Payment Plan Table -->
                <table class="table">
                    <thead>
                        <tr>
                            <th>Session</th>
                            <th>Amount</th>
                            <th>Payment Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($c['PaymentPlan'] as $session)
                        <tr>
                            <td>{{ $session['Session'] }}</td>
                            <td>{{ number_format($session['Amount'], 2) }}</td>
                            <td>{{ \Carbon\Carbon::parse($session['PaymentDate'])->format('d M, Y') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                <p><strong>Total Amount Due:</strong> {{ number_format($c['Total'], 2) }}</p>
                <p>Thank you for your business.</p>
                <p>Yours sincerely,<br>{{ config('app.name') }}</p>
            </div>
            <div class="footer">
                &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
            </div>
        </div>
    </div>
</body>
</html>
