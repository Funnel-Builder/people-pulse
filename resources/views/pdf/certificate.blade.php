<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employment Certificate - {{ $request->ref_id }}</title>
    @if(isset($isWebView) && $isWebView)
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;700&display=swap" rel="stylesheet">
        <style>
            body {
                font-family: 'Inter', sans-serif;
            }

    @else <style>@font-face {
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

        @endif @page {
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
            font-weight: 400;
            line-height: normal;
            color: #000;
            background-color: #fff;
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

        .title {
            text-align: center;
            font-size: 14pt;
            font-weight: 700;
            text-decoration: underline;
            margin-bottom: 30px;
            margin-top: 20px;
        }

        .salutation {
            font-weight: 700;
            font-size: 11pt;
            margin-bottom: 20px;
        }

        .body-text {
            text-align: justify;
            margin-bottom: 15px;
            font-weight: 400;
            font-size: 11pt;
            line-height: normal;
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

        <!-- Title -->
        <div class="title">EMPLOYMENT CERTIFICATE</div>

        <!-- Salutation -->
        <p class="salutation">To Whom It May Concern,</p>

        <!-- Body Paragraph 1 -->
        @php
            $empName = $user->name ? $user->name : "[Name]";
            $empId = $user->employee_id ? $user->employee_id : "[ID]";
            $fatherName = $user->fathers_name ? $user->fathers_name : "[Father's Name]";
            $motherName = $user->mothers_name ? $user->mothers_name : "[Mother's Name]";
            $nid = $user->nid_number ? $user->nid_number : "[NID Number]";
            $joinDate = $user->joining_date ? $user->joining_date->format('F d, Y') : "[joining date]";
            $dept = $user->department ? $user->department->name : "[Department Name]";
            $subDept = $user->subDepartment ? "(" . $user->subDepartment->name . ")" : "(Sub-Department Name if applicable)";
            $designation = $user->designation ? $user->designation : "[Current Designation]";
        @endphp

        <p class="body-text">
            This is to certify that Mr/Mrs. {{ $empName }} ID {{ $empId }}, son of {{ $fatherName }} and
            {{ $motherName }}, National ID Card Number. {{ $nid }}, has been employed at {{ $company['name'] }} as a
            permanent employee since {{ $joinDate }}. Currently he is working in the {{ $dept }} {{ $subDept }}
            department as a {{ $designation }}.
        </p>

        <!-- Body Paragraph 2 -->
        <p class="body-text">
            This certification is being issued on the date of {{ $issueDate->format('F d, Y') }} upon his/her request
            and
            can be used for reference purposes.
        </p>

        <!-- Body Paragraph 3 -->
        <p class="body-text">
            I hereby certify that the above-mentioned information is correct and accurate to the best of my knowledge.
        </p>

        <!-- Closing -->
        <p class="closing">Sincerely,</p>

        <!-- Signature -->
        <div class="signature-container">
            <div class="signature-underline"></div>
            <p class="signature-name">{{ $issuer['name'] }}</p>
            <p class="signature-title">{{ $issuer['title'] }}</p>
            <p class="signature-phone">Cell: {{ $issuer['phone'] }}</p>
        </div>
    </div>
</body>

</html>