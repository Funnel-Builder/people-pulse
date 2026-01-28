<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employment Certificate - {{ $request->ref_id }}</title>
    <style>
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

        @page {
            margin: 0;
            size: A4;
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
            line-height: 1.6;
            color: #000;
        }

        /* Table-based vertical centering for PDF compatibility */
        .page-wrapper {
            display: table;
            width: 100%;
            height: 11.69in;
            /* A4 height */
        }

        .page-cell {
            display: table-cell;
            vertical-align: middle;
            padding: 0.8in 1in;
        }

        .container {
            width: 100%;
        }

        .header {
            width: 100%;
            margin-bottom: 50px;
            font-size: 11pt;
            font-weight: 300;
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
            margin-bottom: 45px;
        }

        .salutation {
            font-weight: 700;
            font-size: 11pt;
            margin-bottom: 25px;
        }

        .body-text {
            text-align: justify;
            margin-bottom: 18px;
            font-weight: 300;
            font-size: 11pt;
            line-height: normal;
        }

        /* Bold for injected values */
        b,
        strong {
            font-weight: 700;
        }

        .closing {
            margin-top: 35px;
            margin-bottom: 80px;
            font-weight: 300;
            font-size: 11pt;
        }

        .signature-line {
            border-top: 1px solid #000;
            width: 200px;
            padding-top: 8px;
            line-height: normal;
        }

        .signature-name {
            font-weight: 700;
            font-size: 11pt;
        }

        .signature-title,
        .signature-phone {
            white-space: nowrap;
            font-weight: 300;
            font-size: 11pt;
        }
    </style>
</head>

<body>
    <div class="page-wrapper">
        <div class="page-cell">
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
                    $subDept = $user->subDepartment ? "({$user->subDepartment->name})" : "(Sub-Department Name if applicable)";
                    $designation = $user->designation ? $user->designation : "[Current Designation]";
                @endphp

                <!-- Body Paragraph 1 -->
                <p class="body-text">
                    To Whom It May Concern, This is to certify that Mr. {{ $empName }} (ID: {{ $empId }}), son of
                    {{ $fatherName }} and {{ $motherName }}, National ID Card Number. {{ $nid }}, has been
                    employed at {{ $company['name'] }} as a permanent employee since {{ $joinDate }}. Currently he is
                    working in the {{ $dept }} {{ $subDept }} department as a {{ $designation }}.
                </p>

                <!-- Body Paragraph 2 -->
                <p class="body-text">
                    This certification is being issued on the date of {{ $issueDate->format('F d, Y') }} upon his
                    request and can be used for reference purposes.
                </p>

                <!-- Body Paragraph 3 -->
                <p class="body-text">
                    I hereby certify that the above-mentioned information is correct and accurate to the best of my
                    knowledge.
                </p>

                <!-- Closing -->
                <p class="closing">Sincerely,</p>

                <!-- Signature -->
                <div class="signature-line">
                    <p class="signature-name">{{ $issuer['name'] }}</p>
                    <p class="signature-title">{{ $issuer['title'] }}</p>
                    <p class="signature-phone">Cell: {{ $issuer['phone'] }}</p>
                </div>
            </div>
        </div>
    </div>
</body>

</html>