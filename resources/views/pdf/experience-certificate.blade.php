<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Experience Certificate - {{ $request->ref_id }}</title>
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
            font-weight: 300;
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
            text-transform: uppercase;
        }

        .body-text {
            text-align: justify;
            margin-bottom: 15px;
            font-weight: 300;
            font-size: 11pt;
            line-height: 1.5;
        }

        .signature-container {
            margin-top: 60px;
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

        .signature-contact {
            font-weight: 400;
            font-size: 11pt;
            line-height: normal;
            margin-top: 10px;
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

        <div class="title">TO WHOM IT MAY CONCERN</div>

        @php
            $empName = $user->name ? $user->name : "[Name]";
            $fatherName = $user->fathers_name ? $user->fathers_name : "[Father's Name]";
            $motherName = $user->mothers_name ? $user->mothers_name : "[Mother's Name]";
            $nid = $user->nid_number ? $user->nid_number : "[NID Number]";
            $dept = $user->department ? $user->department->name : "[Department Name]";
            $subDept = $user->subDepartment ? "(" . $user->subDepartment->name . ")" : "(Sub-Department Name if applicable)";
            $designation = $user->designation ? $user->designation : "[Current Designation]";

            $joinDate = $user->joining_date ? $user->joining_date->format('F d, Y') : "[joining date]";
            $resignDate = $user->closing_date ? $user->closing_date->format('F d, Y') : "[resign date]";
        @endphp

        <p class="body-text">
            This is to certify that {{ $empName }}, son of {{ $fatherName }} and {{ $motherName }}, National ID Card No.
            {{ $nid }}, worked under the {{ $dept }} {{ $subDept }} at {{ $company['name'] }} as {{ $designation }} from
            {{ $joinDate }} to {{ $resignDate }}. During these days, he has proved to be a conscientious and hardworking
            individual.
        </p>

        <p class="body-text">
            I would like to reflect on his conduct during his stay with us. During his service he has been found
            sincere, reliable, trustworthy, sociable, pleasant and open to challenges. He has a genial temperament and
            can efficiently work in a team. All of our team members are pleased with him and feel comfortable in teaming
            and coordinating with him for the realization of organizational goals and objectives.
        </p>

        <p class="body-text">
            He has been released from his services as per the rules and conventions of
            {{ $company['short_name'] ?? $company['name'] }}.
        </p>

        <p class="body-text">
            We wish him all the best in his future endeavors.
        </p>

        <div class="signature-container">
            <div class="signature-underline"></div>
            <p class="signature-name">{{ $issuer['name'] }}</p>
            <p class="signature-title">{{ $issuer['title'] }}</p>

            <div class="signature-contact">
                <p>Cell: {{ $issuer['phone'] }}</p>
                <!-- Email placeholder if needed, using issuer name simplified for now or hardcoded if needed -->
            </div>
        </div>
    </div>
</body>

</html>