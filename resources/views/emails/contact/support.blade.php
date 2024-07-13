<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Support Request - {{ config('app.name') }}</title>
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
        .details, .summary, .attachments {
            margin-bottom: 20px;
        }
        .details div, .summary div, .attachments div {
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
    <div class="container">
        <div class="header">{{ config('app.name') }}</div>
        
        <div class="details">
            <div><strong>Dear {{ $username }},</strong></div>
            <div>{{ $messageContent }}</div>
        </div>

        <div class="attachments">
            @if($attachment)
                @if(strpos($attachment, 'image') !== false)
                    <img src="{{ asset('storage/' . $attachment) }}" alt="Attachment Image" />
                @else
                    <a href="{{ asset('storage/' . $attachment) }}" download>Download Attachment</a>
                @endif
            @else
                <p>No attachments available.</p>
            @endif
        </div>

        <div class="footer">
            &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
        </div>
    </div>
</body>
</html>
