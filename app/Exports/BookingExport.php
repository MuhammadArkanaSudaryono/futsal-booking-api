<?php

namespace App\Exports;

use App\Models\Booking;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class BookingsExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithStyles,
    WithTitle,
    ShouldAutoSize
{
    public function __construct(
        private ?string $dateFrom = null,
        private ?string $dateTo   = null,
        private ?string $status   = null,
    ) {}

    public function collection()
    {
        return Booking::with(['user:id,name,email,phone', 'field:id,name', 'payment'])
            ->when($this->dateFrom, fn ($q) => $q->whereDate('booking_date', '>=', $this->dateFrom))
            ->when($this->dateTo,   fn ($q) => $q->whereDate('booking_date', '<=', $this->dateTo))
            ->when($this->status,   fn ($q) => $q->where('status', $this->status))
            ->orderByDesc('booking_date')
            ->get();
    }

    public function headings(): array
    {
        return [
            'No',
            'Kode Booking',
            'Tanggal Booking',
            'Nama Pelanggan',
            'Email',
            'No HP',
            'Lapangan',
            'Total Jam',
            'Subtotal (Rp)',
            'Diskon (Rp)',
            'Total Bayar (Rp)',
            'Metode Bayar',
            'Status Booking',
            'Status Pembayaran',
            'Tanggal Dibuat',
        ];
    }

    public function map($booking): array
    {
        static $no = 0;
        $no++;

        return [
            $no,
            $booking->booking_code,
            $booking->booking_date?->format('d/m/Y'),
            $booking->user?->name,
            $booking->user?->email,
            $booking->user?->phone,
            $booking->field?->name,
            $booking->total_hours,
            number_format($booking->subtotal, 0, ',', '.'),
            number_format($booking->discount_amount, 0, ',', '.'),
            number_format($booking->total_amount, 0, ',', '.'),
            $booking->payment?->payment_method ?? '-',
            $booking->status_label,
            $booking->payment?->status_label ?? '-',
            $booking->created_at?->format('d/m/Y H:i'),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            // Header row — bold + background biru
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF2563EB'],
                ],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }

    public function title(): string
    {
        return 'Laporan Booking';
    }
}
