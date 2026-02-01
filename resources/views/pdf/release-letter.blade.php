<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Release Letter - {{ $request->ref_id }}</title>
    <style>
        @if(isset($isWebPreview) && $isWebPreview)
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
        @else
        @font-face {
            font-family: 'Inter';
            src: url('{{ public_path('fonts/Inter-Light.ttf') }}') format('truetype');
            font-weight: 300;
            font-style: normal;
        }

        @font-face {
            font-family: 'Inter';
            src: url('{{ public_path('fonts/Inter-Regular.ttf') }}') format('truetype');
            font-weight: 400;
            font-style: normal;
        }

        @font-face {
            font-family: 'Inter';
            src: url('{{ public_path('fonts/Inter-Medium.ttf') }}') format('truetype');
            font-weight: 500;
            font-style: normal;
        }

        @font-face {
            font-family: 'Inter';
            src: url('{{ public_path('fonts/Inter-Bold.ttf') }}') format('truetype');
            font-weight: 700;
            font-style: normal;
        }
        @endif

        @page {
            size: A4;
            margin: 0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            font-size: 11pt;
            font-weight: 300;
            line-height: normal;
            color: #000;
            padding: 2in 1in 1in 1in;
        }

        .container {
            width: 100%;
        }

        .header {
            width: 100%;
            margin-bottom: 40px;
            font-size: 11pt;
            font-weight: 400;
        }

        .header-left {
            float: left;
        }

        .header-right {
            float: right;
        }

        .clear {
            clear: both;
        }

        .recipient-block {
            margin-bottom: 20px;
        }

        .subject {
            font-weight: 700;
            font-size: 12pt;
            margin-bottom: 20px;
        }

        .salutation {
            font-weight: 400;
            margin-bottom: 20px;
        }

        .body-text {
            text-align: justify;
            margin-bottom: 15px;
            font-weight: 400;
            font-size: 11pt;
            line-height: 1.6;
            font-weight: 300;
        }

        .list-item {
            margin-bottom: 10px;
            padding-left: 20px;
            text-align: justify;
        }

        .closing {
            margin-top: 30px;
            margin-bottom: 60px;
            font-weight: 400;
            font-size: 11pt;
        }

        .signature-container {
            width: 250px;
        }

        .signature-underline {
            border-bottom: 1px solid #000;
            height: 30px;
            margin-bottom: 8px;
        }

        .signature-name {
            font-weight: 700;
            font-size: 11pt;
            line-height: normal;
        }

        .signature-title {
            font-weight: 400;
            font-size: 11pt;
            line-height: normal;
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-left">Ref: {{ $request->ref_id }}</div>
            <div class="header-right">{{ $issueDate->format('F d, Y') }}</div>
            <div class="clear"></div>
        </div>

        @php
            $empName = $user->name ? $user->name : "[Name]";
            $designation = $user->designation ? $user->designation : "[Current Designation]";
            // closing_date from user model
            $resignDate = $user->closing_date ? $user->closing_date->format('F d, Y') : "[date of resignation]";
        @endphp

        <div class="recipient-block">
            <p>{{ $empName }}</p>
            <p>{{ $designation }}</p>
            <p>{{ $company['name'] }}</p>
        </div>

        <div class="subject">Subject: Release Letter</div>

        <p class="salutation">Dear Mr./Ms. {{ $empName }},</p>

        <p class="body-text">
            This is to certify that Mr./Ms. {{ $empName }}, who was employed with us as {{ $designation }}, has been released from his/her position effective from the closing hours of {{ $resignDate }}.
        </p>

        <p class="body-text">
            We would also like to take this opportunity to remind you that, notwithstanding your resignation from {{ $company['name'] }}, you will continue to be bound by certain terms and conditions as well as obligations under your employment contract & NDA with {{ $company['name'] }}, which includes but is not limited to,
        </p>

        <div class="list-item">
            1. Obligations to maintain confidentiality in relation to all confidential information of {{ $company['name'] }}.
        </div>

        <div class="list-item">
            2. Avoidance of any kind of conflict of interest with {{ $company['name'] }} and
        </div>

        <div class="list-item">
            3. Obligations relating to any copyright, intellectual property, trade secrets, work that is produced or created while being employed at {{ $company['name'] }}.
        </div>

        <div class="list-item">
            4. You are hereby also reminded that you are not to involve yourself in any projects, tasks, or activities which has/had any connection to any current or past projects, tasks, clients, or activities of {{ $company['short_name'] ?? $company['name'] }} without the prior written official consent of {{ $company['name'] }} to avoid any conflict of interest or breach.
        </div>

        <p class="body-text">
            To maintain the highest standards in our community and to protect our employees, clients, stakeholders, customers, and partners we take these obligations and responsibilities very seriously. We reserve the right to take all legal actions necessary for any breach of your employment contract.
        </p>

        <p class="body-text">
            By accepting this Release Letter, you have once again confirmed the above with your free will without any kind of influence or inducement.
        </p>

        <p class="body-text">
            We wish you every success in your future endeavors!
        </p>

        <div class="signature-container">
            <div class="signature-underline"></div>
            <p class="signature-name">{{ $issuer['name'] }}</p>
            <p class="signature-title">{{ $issuer['title'] }}</p>
        </div>
    </div>
</body>
</html>
