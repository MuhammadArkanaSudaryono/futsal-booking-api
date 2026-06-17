<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Laporan Booking Lapangan Futsal</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            margin: 10px;
            color: #333;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }

        .header h2 {
            margin: 0;
            font-size: 18px;
            font-weight: bold;
        }

        .header p {
            margin: 5px 0;
            font-size: 10px;
        }

        .filter-info {
            margin-bottom: 15px;
            padding: 8px;
            background-color: #f5f5f5;
            border-left: 3px solid #2563eb;
        }

        .filter-info p {
            margin: 3px 0;
            font-size: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }

        table thead {
            background-color: #2563eb;
            color: white;
        }

        table th {
            padding: 8px;
            text-align: left;
            font-weight: bold;
            font-size: 10px;
            border: 1px solid #ddd;
        }

        table td {
            padding: 7px;
            border: 1px solid #ddd;
            font-size: 10px;
        }

        table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        table tbody tr:hover {
            background-color: #f0f0f0;
        }

        .status-pending {
            background-color: #fef3c7;
            color: #92400e;
            padding: 3px 6px;
            border-radius: 3px;
            font-weight: bold;
        }

        .status-confirmed {
            background-color: #dcfce7;
            color: #166534;
            padding: 3px 6px;
            border-radius: 3px;
            font-weight: bold;
        }

        .status-completed {
            background-color: #dbeafe;
            color: #1e40af;
            padding: 3px 6px;
            border-radius: 3px;
            font-weight: bold;
        }

        .status-cancelled {
            background-color: #fee2e2;
            color: #991b1b;
            padding: 3px 6px;
            border-radius: 3px;
            font-weight: bold;
        }

        .status-rejected {
            background-color: #fee2e2;
            color: #991b1b;
            padding: 3px 6px;
            border-radius: 3px;
            font-weight: bold;
        }

        .status-unpaid {
            background-color: #fed7aa;
            color: #7c2d12;
            padding: 3px 6px;
            border-radius: 3px;
        }

        .status-pending_verification {
            background-color: #fef3c7;
            color: #92400e;
            padding: 3px 6px;
            border-radius: 3px;
        }

        .status-verified {
            background-color: #dcfce7;
            color: #166534;
            padding: 3px 6px;
            border-radius: 3px;
        }

        .status-refunded {
            background-color: #e0e7ff;
            color: #312e81;
            padding: 3px 6px;
            border-radius: 3px;
        }

        .footer {
            margin-top: 30px;
            text-align: right;
            font-size: 9px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }

        .total-row {
            font-weight: bold;
            background-color: #e0e7ff;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>📋 LAPORAN BOOKING LAPANGAN FUTSAL</h2>
        <p>Sistem Manajemen Booking Lapangan</p>
    </div>

    <div class="filter-info">
        <p><strong>Periode:</strong>
            @if($date_from && $date_to)
                {{ \Carbon\Carbon::parse($date_from)->format('d M Y') }} - {{ \Carbon\Carbon::parse($date_to)->format('d M Y') }}
            @elseif($date_from)
                Dari {{ \Carbon\Carbon::parse($date_from)->format('d M Y') }}
            @elseif($date_to)
                Hingga {{ \Carbon\Carbon::parse($date_to)->format('d M Y') }}
            @else
                Semua Data
            @endif
        </p>
        <p><strong>Tanggal Cetak:</strong> {{ $generated }}</p>
        <p><strong>Total Booking:</strong> {{ count($bookings) }} record</p>
    </div>

    @if(count($bookings) > 0)
        <table>
            <thead>
                <tr>
                    <th class="text-center">No</th>
                    <th>Kode Booking</th>
                    <th class="text-center">Tanggal</th>
                    <th>Pelanggan</th>
                    <th>Email</th>
                    <th>Lapangan</th>
                    <th class="text-right">Total Jam</th>
                    <th class="text-right">Subtotal</th>
                    <th class="text-right">Diskon</th>
                    <th class="text-right">Total</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Bayar</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $totalSubtotal = 0;
                    $totalDiscount = 0;
                    $totalAmount = 0;
                @endphp

                @foreach($bookings as $key => $booking)
                    @php
                        $totalSubtotal += $booking->subtotal;
                        $totalDiscount += $booking->discount_amount;
                        $totalAmount += $booking->total_amount;
                    @endphp
                    <tr>
                        <td class="text-center">{{ $key + 1 }}</td>
                        <td><strong>{{ $booking->booking_code }}</strong></td>
                        <td class="text-center">{{ $booking->booking_date?->format('d/m/Y') ?? '-' }}</td>
                        <td>{{ $booking->user?->name ?? 'N/A' }}</td>
                        <td>{{ $booking->user?->email ?? 'N/A' }}</td>
                        <td>{{ $booking->field?->name ?? 'N/A' }}</td>
                        <td class="text-right">{{ $booking->total_hours }}</td>
                        <td class="text-right">Rp {{ number_format($booking->subtotal, 0, ',', '.') }}</td>
                        <td class="text-right">Rp {{ number_format($booking->discount_amount, 0, ',', '.') }}</td>
                        <td class="text-right"><strong>Rp {{ number_format($booking->total_amount, 0, ',', '.') }}</strong></td>
                        <td class="text-center">
                            <span class="status-{{ str_replace('_', '', $booking->status) }}">
                                {{ $booking->status_label }}
                            </span>
                        </td>
                        <td class="text-center">
                            <span class="status-{{ str_replace('_', '', $booking->payment?->payment_status ?? 'unpaid') }}">
                                {{ $booking->payment?->status_label ?? 'Belum Bayar' }}
                            </span>
                        </td>
                    </tr>
                @endforeach

                <tr class="total-row">
                    <td colspan="7" class="text-right"><strong>TOTAL:</strong></td>
                    <td class="text-right">Rp {{ number_format($totalSubtotal, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($totalDiscount, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($totalAmount, 0, ',', '.') }}</td>
                    <td colspan="2"></td>
                </tr>
            </tbody>
        </table>

        <div class="footer">
            <p>Dokumen ini dihasilkan secara otomatis oleh sistem pada {{ $generated }}</p>
            <p>Untuk informasi lebih lanjut, hubungi administrator sistem.</p>
        </div>
    @else
        <div style="text-align: center; padding: 40px; color: #999;">
            <p style="font-size: 14px; margin: 20px 0;">Tidak ada data booking untuk periode yang dipilih.</p>
        </div>
    @endif
</body>
</html>
