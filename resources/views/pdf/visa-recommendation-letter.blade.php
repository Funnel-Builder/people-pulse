<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visa Recommendation Letter - {{ $request->ref_id }}</title>
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
            font-size: 10pt;
            font-weight: 400;
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

        .address-block {
            margin-bottom: 20px;
        }

        .subject {
            font-weight: 700;
            margin-bottom: 20px;
            font-size: 12pt; /* Sub-heading */
            font-weight: 500;
        }

        .salutation {
            font-weight: 400;
            margin-bottom: 20px;
        }

        .body-text {
            text-align: justify;
            margin-bottom: 15px;
            font-weight: 400;
            font-size: 10pt;
            line-height: 1.5;
        }

        .passport-details {
            margin-top: 15px;
            margin-bottom: 15px;
        }

        .passport-row {
            margin-bottom: 5px;
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

        .signature-phone {
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
            <div class="header-right">Date: {{ $issueDate->format('F d, Y') }}</div>
            <div class="clear"></div>
        </div>

        <div class="address-block">
            <p>To</p>
            <p>The High Commissioner</p>
            <p>High Commission of India</p>
            <p>Plot No: 1-3, Park Road, Baridhara,</p>
            <p>Dhaka 1212, Bangladesh</p>
        </div>

        <div class="subject">Subject: Visa Recommendation Letter</div>

        <p class="salutation">Dear Sir,</p>

        @php
            $empName = $user->name ? $user->name : "[Name]";
            $designation = $user->designation ? $user->designation : "[Current Designation]";
            $joinDate = $user->joining_date ? $user->joining_date->format('F d, Y') : "[joining date]";
            
            $passportNo = $request->passport_number ?? "(as per passport)";
            $issueDateStr = $request->passport_issue_date ? $request->passport_issue_date->format('F d, Y') : "(as per passport)";
            $expiryDateStr = $request->passport_expiry_date ? $request->passport_expiry_date->format('F d, Y') : "(as per passport)";
            $placeOfIssue = $request->passport_issue_place ?? "(as per passport)";
            
            $travelStart = $request->start_date ? $request->start_date->format('F d, Y') : "[date]";
            $travelEnd = $request->end_date ? $request->end_date->format('F d, Y') : "[to date]";
        @endphp

        <p class="body-text">
            This is to inform you that Mr./Ms. {{ $empName }} is serving as {{ $designation }} at {{ $company['name'] }} as a permanent employee since {{ $joinDate }}. {{ $company['name'] }} is a team of technology enthusiasts that specialize in e-commerce platform design, web design, software development, digital marketing, and branding on the web.
        </p>

        <p class="body-text">
            His/Her passport details are as follows:
        </p>

        <div class="passport-details">
            <div class="passport-row">Passport No: <strong>{{ $passportNo }}</strong></div>
            <div class="passport-row">Issue Date: <strong>{{ $issueDateStr }}</strong></div>
            <div class="passport-row">Expiry Date: <strong>{{ $expiryDateStr }}</strong></div>
            <div class="passport-row">Place of Issue: <strong>{{ $placeOfIssue }}</strong></div>
        </div>

        <p class="body-text">
            Mr./Ms. {{ $empName }} will be traveling to the Republic of India for the period from {{ $travelStart }} to {{ $travelEnd }}. We have no objection to him/her during the travel period. Any assistance in facilitating his/her travel document and/or visa will be highly appreciated.
        </p>

        <p class="closing">Sincerely,</p>

        <div class="signature-container">
            <div class="signature-underline"></div>
            <p class="signature-name">{{ $issuer['name'] }}</p>
            <p class="signature-title">{{ $issuer['title'] }}</p>
            <p class="signature-phone">Contact: {{ $issuer['phone'] }}</p>
        </div>
    </div>
</body>
</html>
