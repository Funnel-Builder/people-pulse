<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LeaveRecordsExport implements FromCollection, WithHeadings, WithStyles
{
    protected Collection $data;

    public function __construct(Collection $data)
    {
        $this->data = $data;
    }

    public function collection(): Collection
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            'Employee ID',
            'Employee Name',
            'Department',
            'Sub-Department',
            'Designation',
            'Leave Type',
            'Leave Type Code',
            'Application Type',
            'Start Date',
            'End Date',
            'Days',
            'Reason',
            'Cover Person',
            'Status',
            'Applied On',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
