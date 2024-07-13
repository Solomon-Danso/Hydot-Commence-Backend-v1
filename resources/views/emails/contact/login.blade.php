<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Welcome to {{ config('app.name') }}!</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .card {
            width: 100%;
            max-width: 600px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            overflow: hidden;
            background-color: #ffffff;
            color: #333333;
        }
        .header {
            padding: 1.5rem;
            text-align: center;
            color: #ffffff;
        }
        .header h1 {
            margin: 0;
            font-size: 1.75rem;
        }
        .content {
            padding: 1.5rem;
        }
        .content p {
            margin: 1rem 0;
            line-height: 1.5;
        }
        .credentials {
            background-color: #f9f9f9;
            padding: 1rem;
            border-radius: 4px;
            margin: 1rem 0;
        }
        .credentials p {
            margin: 0.5rem 0;
            color: #333333;
        }
        .footer {
            padding: 1rem;
            font-size: 0.875rem;
            color: #777777;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="card">
            <div class="header">
                <h1>{{ config('app.name') }}!</h1>
            </div>
            <div class="content">

                Your Verification Code is
                <div class="credentials">
                  
                    {{ $token }}
                
                </div>
                The Code Will Expire In 10 Minutes

                <p>Yours sincerely,<br>{{ config('app.name') }}</p>
            </div>
            <div class="footer">
                &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
            </div>
        </div>
    </div>
</body>
</html>
