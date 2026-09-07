<?php

namespace App\Services\Export;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExcelExportService
{
    /**
     * Generate and stream an Excel file.
     *
     * @param string $title Main title of the sheet/report
     * @param array $headers Column header labels
     * @param array $rows Array of data rows (each row is an indexed or associative array matching headers)
     * @param array|null $totals Optional totals row (e.g. ['الإجمالي', '', 15000.00])
     * @param string|null $filename File name without or with .xlsx
     * @param array $meta Optional metadata (e.g. ['الفترة' => 'من 2026-09-01 إلى 2026-09-30', 'الفرع' => 'فرع القاهرة'])
     * @return StreamedResponse
     */
    public static function download(
        string $title,
        array $headers,
        array $rows,
        ?array $totals = null,
        ?string $filename = null,
        array $meta = []
    ): StreamedResponse {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Right to left orientation for Arabic
        $sheet->setRightToLeft(true);
        $sheet->setTitle(mb_substr($title, 0, 30));

        $colCount = count($headers);
        $lastColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colCount);

        // 1. Main Title Block
        $sheet->mergeCells("A1:{$lastColLetter}1");
        $sheet->setCellValue('A1', $title);
        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold'  => true,
                'size'  => 16,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1E293B'], // Dark slate
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(36);

        // 2. Metadata / Generation Info (Row 2)
        $metaText = 'تاريخ الإصدار: ' . date('Y-m-d H:i');
        if (!empty($meta)) {
            $metaParts = [];
            foreach ($meta as $k => $v) {
                $metaParts[] = "{$k}: {$v}";
            }
            $metaText .= ' | ' . implode(' | ', $metaParts);
        }

        $sheet->mergeCells("A2:{$lastColLetter}2");
        $sheet->setCellValue('A2', $metaText);
        $sheet->getStyle('A2')->applyFromArray([
            'font' => [
                'italic' => true,
                'size'   => 10,
                'color'  => ['rgb' => '64748B'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F8FAFC'],
            ],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(22);

        // Blank row 3
        $sheet->getRowDimension(3)->setRowHeight(10);

        // 3. Table Headers (Row 4)
        $headerRow = 4;
        $sheet->getRowDimension($headerRow)->setRowHeight(28);

        $colIndex = 1;
        foreach ($headers as $h) {
            $coord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex) . $headerRow;
            $sheet->setCellValue($coord, $h);
            $colIndex++;
        }

        $sheet->getStyle("A{$headerRow}:{$lastColLetter}{$headerRow}")->applyFromArray([
            'font' => [
                'bold'  => true,
                'size'  => 11,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4338CA'], // Indigo
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['rgb' => '6366F1'],
                ],
            ],
        ]);

        // 4. Data Rows
        $currentRow = 5;
        foreach ($rows as $row) {
            $colIndex = 1;
            $sheet->getRowDimension($currentRow)->setRowHeight(22);

            foreach (array_values($row) as $cellVal) {
                $coord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex) . $currentRow;
                $sheet->setCellValue($coord, $cellVal);

                // Align numbers right/center and text right
                $hAlign = is_numeric($cellVal) ? Alignment::HORIZONTAL_CENTER : Alignment::HORIZONTAL_RIGHT;
                $sheet->getStyle($coord)->getAlignment()->setHorizontal($hAlign)->setVertical(Alignment::VERTICAL_CENTER);

                $colIndex++;
            }

            // Light border for data row
            $sheet->getStyle("A{$currentRow}:{$lastColLetter}{$currentRow}")->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color'       => ['rgb' => 'CBD5E1'],
                    ],
                ],
            ]);

            $currentRow++;
        }

        // 5. Totals Row (if provided)
        if (!empty($totals)) {
            $sheet->getRowDimension($currentRow)->setRowHeight(26);
            $colIndex = 1;

            foreach (array_values($totals) as $tVal) {
                $coord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex) . $currentRow;
                $sheet->setCellValue($coord, $tVal);
                $colIndex++;
            }

            $sheet->getStyle("A{$currentRow}:{$lastColLetter}{$currentRow}")->applyFromArray([
                'font' => [
                    'bold'  => true,
                    'size'  => 11,
                    'color' => ['rgb' => '0F172A'],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E2E8F0'],
                ],
                'borders' => [
                    'top' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color'       => ['rgb' => '475569'],
                    ],
                    'bottom' => [
                        'borderStyle' => Border::BORDER_DOUBLE,
                        'color'       => ['rgb' => '475569'],
                    ],
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color'       => ['rgb' => '94A3B8'],
                    ],
                ],
            ]);

            $currentRow++;
        }

        // 6. Auto-fit column widths
        for ($c = 1; $c <= $colCount; $c++) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }

        // File download response
        $filename = $filename ? (str_ends_with($filename, '.xlsx') ? $filename : $filename . '.xlsx') : 'export_' . date('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control'       => 'max-age=0',
        ]);
    }
}
