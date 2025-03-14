<!-- filepath: /resources/views/payroll/payslip.blade.php -->
<!DOCTYPE html>
<html>
<head>
    <title>Payroll Payslip</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        .header, .footer {
            text-align: center;
            margin-bottom: 20px;
        }
        .content {
            margin: 0 20px;
        }
        .left {
            float: left;
        }
        .right {
            float: right;
        }
        .clear {
            clear: both;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table, th, td {
            border: 1px solid black;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        .subtotal, .total {
            text-align: right;
        }
        .total {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Payroll for Month {{ $month }}</h1>
    </div>
    <div class="content">
        <div class="left">
            <p>Company: {{ $company }}</p>
            <p>Employee ID: {{ $employeeId }}</p>
            <p>Employee Name: {{ $employeeName }}</p>
        </div>
        <div class="right">
            <p>Date: {{ $today }}</p>
        </div>
        <div class="clear"></div>
        <table>
            <tr>
                <th>Description</th>
                <th>Amount</th>
            </tr>
            <tr>
                <td>Hours Worked</td>
                <td>{{ $hoursWorked }}</td>
            </tr>
            <tr>
                <td>Overtime Hours Worked</td>
                <td>{{ $otHours }}</td>
            </tr>
            <tr>
                <td>Holidays/Leaves</td>
                <td>{{ $holidays }}</td>
            </tr>
            <tr>
                <td>Hourly Rate</td>
                <td>{{ $hourlyRate }}</td>
            </tr>
            <tr>
                <td>Bonus</td>
                <td>{{ $bonus }}</td>
            </tr>
            <tr>
                <td>Bonus Reason</td>
                <td>{{ $bonusReason }}</td>
            </tr>
            <tr>
                <td>Deduction</td>
                <td>{{ $deduction }}</td>
            </tr>
            <tr>
                <td>Deduction Reason</td>
                <td>{{ $deductionReason }}</td>
            </tr>
            <tr>
                <td class="subtotal">Subtotal</td>
                <td class="subtotal">{{ $subtotal }}</td>
            </tr>
            <tr>
                <td class="total">Total</td>
                <td class="total">{{ $total }}</td>
            </tr>
        </table>
    </div>
</body>
</html>