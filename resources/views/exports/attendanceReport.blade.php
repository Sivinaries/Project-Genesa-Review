<table>
    <thead>
        <tr>
            <th colspan="7"
                style="font-weight: bold; font-size: 14px; text-align: center; background-color: #1e40af; color: #ffffff;">
                LAPORAN ABSENSI KARYAWAN
            </th>
        </tr>
        <tr>
            <th colspan="7"
                style="font-weight: bold; font-size: 12px; text-align: center; background-color: #3b82f6; color: #ffffff;">
                {{ Auth::user()->compani->company ?? 'Company Name' }}
            </th>
        </tr>
        <tr>
            <th colspan="7" style="font-size: 10px; text-align: center; background-color: #60a5fa; color: #ffffff;">
                Periode: {{ \Carbon\Carbon::parse($start)->format('d M Y') }} -
                {{ \Carbon\Carbon::parse($end)->format('d M Y') }}
            </th>
        </tr>
        <tr>
            <th colspan="7"></th>
        </tr>

        {{-- Header Row --}}
        <tr>
            <th
                style="border: 1px solid #000; font-weight: bold; background-color: #e5e7eb; text-align: center; vertical-align: middle;">
                NAMA</th>
            <th
                style="border: 1px solid #000; font-weight: bold; background-color: #86efac; text-align: center; vertical-align: middle;">
                PRESENT</th>
            <th
                style="border: 1px solid #000; font-weight: bold; background-color: #fde68a; text-align: center; vertical-align: middle;">
                LATE</th>
            <th
                style="border: 1px solid #000; font-weight: bold; background-color: #93c5fd; text-align: center; vertical-align: middle;">
                SICK</th>
            <th
                style="border: 1px solid #000; font-weight: bold; background-color: #c4b5fd; text-align: center; vertical-align: middle;">
                PERMISSION</th>
            <th
                style="border: 1px solid #000; font-weight: bold; background-color: #fca5a5; text-align: center; vertical-align: middle;">
                ALPHA</th>
            <th
                style="border: 1px solid #000; font-weight: bold; background-color: #a5f3fc; text-align: center; vertical-align: middle;">
                LEAVE</th>
        </tr>
    </thead>

    <tbody>
        @foreach ($branches as $branch)
            {{-- Branch Header --}}
            <tr>
                <td colspan="7"
                    style="border: 1px solid #000; font-weight: bold; background-color: #f3f4f6; padding: 5px;">
                    {{ strtoupper($branch['branch_name']) }}
                </td>
            </tr>

            {{-- Employees in this branch --}}
            @foreach ($branch['attendances'] as $attendance)
                <tr>
                    <td style="border: 1px solid #000; padding-left: 5px;">
                        {{ $attendance->employee->name }}
                    </td>
                    <td style="border: 1px solid #000; text-align: center; background-color: #dcfce7;">
                        {{ $attendance->total_present }}
                    </td>
                    <td style="border: 1px solid #000; text-align: center; background-color: #fef9c3;">
                        {{ $attendance->total_late }}
                    </td>
                    <td style="border: 1px solid #000; text-align: center; background-color: #dbeafe;">
                        {{ $attendance->total_sick }}
                    </td>
                    <td style="border: 1px solid #000; text-align: center; background-color: #ede9fe;">
                        {{ $attendance->total_permission }}
                    </td>
                    <td style="border: 1px solid #000; text-align: center; background-color: #fee2e2;">
                        {{ $attendance->total_alpha }}
                    </td>
                    <td style="border: 1px solid #000; text-align: center; background-color: #e0f2fe;">
                        {{ $attendance->total_leave }}
                    </td>
                </tr>
            @endforeach

            {{-- Subtotal per Branch --}}
            <tr>
                <td
                    style="border: 1px solid #000; font-weight: bold; text-align: right; padding-right: 10px; background-color: #e5e7eb;">
                    SUBTOTAL ({{ $branch['subtotal']['count'] }} karyawan):
                </td>
                <td style="border: 1px solid #000; font-weight: bold; text-align: center; background-color: #86efac;">
                    {{ $branch['subtotal']['total_present'] }}
                </td>
                <td style="border: 1px solid #000; font-weight: bold; text-align: center; background-color: #fde68a;">
                    {{ $branch['subtotal']['total_late'] }}
                </td>
                <td style="border: 1px solid #000; font-weight: bold; text-align: center; background-color: #93c5fd;">
                    {{ $branch['subtotal']['total_sick'] }}
                </td>
                <td style="border: 1px solid #000; font-weight: bold; text-align: center; background-color: #c4b5fd;">
                    {{ $branch['subtotal']['total_permission'] }}
                </td>
                <td style="border: 1px solid #000; font-weight: bold; text-align: center; background-color: #fca5a5;">
                    {{ $branch['subtotal']['total_alpha'] }}
                </td>
                <td style="border: 1px solid #000; font-weight: bold; text-align: center; background-color: #a5f3fc;">
                    {{ $branch['subtotal']['total_leave'] }}
                </td>
            </tr>

            {{-- Spacing --}}
            <tr>
                <td colspan="7" style="height: 5px;"></td>
            </tr>
        @endforeach

        {{-- Grand Total --}}
        <tr>
            <td
                style="border: 2px solid #000; font-weight: bold; text-align: right; padding-right: 10px; background-color: #6366f1; color: white;">
                TOTAL ({{ $grandTotal['count'] }} karyawan):
            </td>
            <td
                style="border: 2px solid #000; font-weight: bold; text-align: center; background-color: #22c55e; color: white;">
                {{ $grandTotal['total_present'] }}
            </td>
            <td
                style="border: 2px solid #000; font-weight: bold; text-align: center; background-color: #eab308; color: white;">
                {{ $grandTotal['total_late'] }}
            </td>
            <td
                style="border: 2px solid #000; font-weight: bold; text-align: center; background-color: #3b82f6; color: white;">
                {{ $grandTotal['total_sick'] }}
            </td>
            <td
                style="border: 2px solid #000; font-weight: bold; text-align: center; background-color: #8b5cf6; color: white;">
                {{ $grandTotal['total_permission'] }}
            </td>
            <td
                style="border: 2px solid #000; font-weight: bold; text-align: center; background-color: #ef4444; color: white;">
                {{ $grandTotal['total_alpha'] }}
            </td>
            <td
                style="border: 2px solid #000; font-weight: bold; text-align: center; background-color: #06b6d4; color: white;">
                {{ $grandTotal['total_leave'] }}
            </td>
        </tr>
    </tbody>
</table>