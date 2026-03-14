<!DOCTYPE html>
<html lang="en">

<head>
    <title>Hasil Pencarian</title>
    @include('layout.head')
    <link href="//cdn.datatables.net/2.0.2/css/dataTables.dataTables.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .dataTables_wrapper .dataTables_length select {
            padding-right: 2rem;
            border-radius: 0.5rem;
        }

        .dataTables_wrapper .dataTables_filter input {
            padding: 0.5rem;
            border-radius: 0.5rem;
            border: 1px solid #d1d5db;
        }

        table.dataTable.no-footer {
            border-bottom: 1px solid #e5e7eb;
        }
    </style>
</head>

<body class="bg-gray-50 font-sans">
    @include('layout.sidebar')

    <main class="md:ml-64 xl:ml-72 2xl:ml-72">
        @include('layout.navbar')
        <div class="p-6 space-y-6">

            <!-- Header -->
            <div
                class="md:flex justify-between items-center bg-white p-5 rounded-xl shadow-sm border border-gray-100 space-y-2 md:space-y-0">
                <div>
                    <h1 class="font-bold text-2xl text-gray-800 flex items-center gap-2">
                        <i class="fas fa-magnifying-glass text-cyan-600"></i>
                        Hasil Pencarian
                    </h1>
                    <p class="text-sm text-gray-500">
                        Hasil berdasarkan kata kunci pencarian Anda di seluruh sistem
                    </p>
                </div>
            </div>

            <!-- Header -->
            <div
                class="md:flex justify-between items-center bg-white p-5 rounded-xl shadow-sm border border-gray-100 space-y-2 md:space-y-0">
                <div>
                    <h1 class="font-bold text-2xl text-gray-800">
                        <i class="fas fa-building text-cyan-600"></i>
                        Manajemen Cabang
                    </h1>
                    <p class="text-sm text-gray-500">
                        Kelola lokasi dan kategori cabang perusahaan
                    </p>
                </div>

                <x-button href="{{ route('branch') }}" size="lg" variant="primary" class="bg-slate-700 hover:bg-green-600 shadow-md" icon="external-link">Ke Halaman</x-button>

            </div>

            <!-- Table -->
            <div class="w-full bg-white rounded-xl shadow-md border border-gray-100">
                <div class="p-5 overflow-auto">
                    <table id="branchTable" class="w-full text-left">
                        <thead class="bg-gray-100 text-gray-600 text-sm leading-normal">
                            <tr>
                                <th class="p-4 font-bold rounded-tl-lg text-center" width="5%">No</th>
                                <th class="p-4 font-bold">Nama Cabang</th>
                                <th class="p-4 font-bold">Kategori</th>
                                <th class="p-4 font-bold">Kontak</th>
                                <th class="p-4 font-bold text-center">GPS</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-700 text-sm">
                            @php $no = 1; @endphp
                            @foreach ($branches as $item)
                                <tr class="hover:bg-gray-50 transition duration-150">
                                    <td class="p-4 font-medium">
                                        <div class="flex items-center justify-center">
                                            {{ $no++ }}
                                        </div>
                                    </td>
                                    <td class="p-4 space-y-1">
                                        <a href="{{ route('outlet', ['branchId' => $item->id]) }}" class="group block">
                                            <div class="font-bold text-gray-900 text-base group-hover:text-cyan-600">
                                                {{ $item->name }}</div>
                                            <div class="text-xs text-gray-400">Created:
                                                {{ $item->created_at ? $item->created_at->format('Y-m-d') : '-' }}</div>
                                        </a>
                                    </td>
                                    <td class="p-4">
                                        <span
                                            class="bg-cyan-100 text-cyan-800 text-xs px-3 py-1 rounded-full font-bold border border-cyan-200 uppercase">
                                            {{ str_replace('_', ' ', $item->category ?? 'General') }}
                                        </span>
                                    </td>
                                    <td class="p-4 text-xs space-y-1">
                                        <div class="flex items-center gap-2"><i
                                                class="fas fa-phone text-gray-400 w-4"></i> {{ $item->phone }}</div>
                                        <div class="flex items-center gap-2"><i
                                                class="fas fa-map-marker-alt text-gray-400 w-4"></i>
                                            {{ \Illuminate\Support\Str::limit($item->address, 30) }}</div>
                                    </td>
                                    <td class="p-4 text-center">
                                        @if ($item->latitude && $item->longitude)
                                            <div class="flex flex-col items-center gap-1">
                                                <span
                                                    class="text-xs font-mono text-gray-600 bg-green-50 px-2 py-1 rounded border border-green-200">
                                                    <i class="fas fa-check-circle text-green-600"></i> Set
                                                </span>
                                                <span
                                                    class="text-[10px] text-gray-400">{{ number_format($item->gps_radius ?? 5000) }}m</span>
                                            </div>
                                        @else
                                            <span
                                                class="text-xs font-bold text-red-600 bg-red-50 px-2 py-1 rounded border border-red-200">
                                                <i class="fas fa-times-circle"></i> Not Set
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Header -->
            <div
                class="md:flex justify-between items-center bg-white p-5 rounded-xl shadow-sm border border-gray-100 space-y-2 md:space-y-0">
                <div>
                    <h1 class="font-bold text-2xl text-gray-800 flex items-center gap-2">
                        <i class="fas fa-id-badge text-slate-600"></i> Daftar Jabatan
                    </h1>
                    <p class="text-sm text-gray-500 mt-1">Kelola jabatan dan gaji default</p>
                </div>
                <x-button href="{{ route('position') }}" size="lg" variant="primary" class="bg-slate-700 hover:bg-green-600 shadow-md" icon="external-link">Ke Halaman</x-button>
            </div>

            <!-- Table -->
            <div class="w-full bg-white rounded-xl shadow-md border border-gray-100">
                <div class="p-5 overflow-auto">
                    <table id="positionTable" class="w-full text-left">
                        <thead class="bg-gray-100 text-gray-600 text-sm leading-normal">
                            <tr>
                                <th class="p-4 font-bold rounded-tl-lg text-center" width="5%">No</th>
                                <th class="p-4 font-bold">Nama Jabatan</th>
                                <th class="p-4 font-bold">Kategori</th>
                                <th class="p-4 font-bold text-right">Gaji</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-700 text-sm divide-y divide-gray-200">
                            @php $no = 1; @endphp
                            @foreach ($positions as $item)
                                <tr class="hover:bg-gray-50 transition duration-150">
                                    <td class="p-4 font-medium text-center">{{ $no++ }}</td>
                                    <td class="p-4 font-bold text-gray-900">{{ $item->name }}</td>
                                    <td class="p-4">
                                        <span
                                            class="bg-gray-100 text-gray-600 text-xs px-3 py-1 rounded-full font-bold border border-gray-200 uppercase">
                                            {{ str_replace('_', ' ', $item->category) }}
                                        </span>

                                        @if ($item->is_head)
                                            <span
                                                class="bg-indigo-100 text-indigo-700 text-[10px] mx-2 px-2 py-0.5 rounded border border-indigo-200 w-fit font-bold">
                                                <i class="fas fa-crown mr-1"></i> HEAD / COORD
                                            </span>
                                        @endif
                                    </td>
                                    <td class="p-4 font-mono text-slate-600 ">
                                        Rp {{ number_format($item->base_salary_default, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Header -->
            <div
                class="md:flex justify-between items-center bg-white p-5 rounded-xl shadow-sm border border-gray-100 space-y-2 md:space-y-0">
                <div>
                    <h1 class="font-bold text-2xl text-gray-800 flex items-center gap-2">
                        <i class="fa-solid fa-bullhorn text-red-700"></i> Pengumuman Perusahaan
                    </h1>
                    <p class="text-sm text-gray-500">Pusat informasi untuk pengumuman internal perusahaan.</p>
                </div>
                <x-button href="{{ route('announcement') }}" size="lg" variant="primary" class="bg-slate-700 hover:bg-green-600 shadow-md" icon="external-link">Ke Halaman</x-button>
            </div>

            <!-- Table -->
            <div class="w-full bg-white rounded-xl shadow-md border border-gray-100">
                <div class="p-5 overflow-auto">
                    <table id="announcementTable" class="w-full text-left">
                        <thead class="bg-gray-100 text-gray-600 text-sm leading-normal">
                            <tr>
                                <th class="p-4 font-bold rounded-tl-lg text-center" width="5%">No</th>
                                <th class="p-4 font-bold">Tanggal</th>
                                <th class="p-4 font-bold">Isi Pengumuman</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-700 text-sm">
                            @php $no = 1; @endphp
                            @foreach ($announcements as $item)
                                <tr class="hover:bg-gray-50 transition duration-150">
                                    <td class="p-4 font-medium text-center">
                                        {{ $no++ }}
                                    </td>
                                    <td class="p-4 font-medium">
                                        {{ \Carbon\Carbon::parse($item->created_at)->format('d M Y') }}
                                    </td>
                                    <td class="p-4 text-xs text-gray-600 italic max-w-xs truncate">
                                        "{{ \Illuminate\Support\Str::limit($item->content, 40) }}"
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>


            <!-- Header -->
            <div class="flex justify-between items-center bg-white p-5 rounded-xl shadow-sm border border-gray-100">
                <div>
                    <h1 class="font-bold text-2xl text-gray-800">
                        <i class="fas fa-users text-indigo-600"></i> Manajemen Karyawan
                    </h1>
                    <p class="text-sm text-gray-500 ">Kelola anggota tim dan detail mereka</p>
                </div>
                <x-button href="{{ route('employee') }}" size="lg" variant="primary" class="bg-slate-700 hover:bg-green-600 shadow-md" icon="external-link">Ke Halaman</x-button>
            </div>

            <!-- Table Section -->
            <div class="w-full bg-white rounded-xl shadow-md border border-gray-100">
                <div class="p-5 overflow-auto">
                    <table id="employeeTable" class="w-full text-left">
                        <thead class="bg-gray-100 text-gray-600 text-sm leading-normal">
                            <tr>
                                <th class="p-4 font-bold" width="5%">No</th>
                                <th class="p-4 font-bold">Nama / Cabang</th>
                                <th class="p-4 font-bold">
                                    <div class="flex items-center justify-center">
                                        Posisi / Status
                                    </div>
                                </th>
                                <th class="p-4 font-bold">
                                    <div class="flex items-center justify-center">
                                        Kontak
                                    </div>
                                </th>
                                <th class="p-4 font-bold">
                                    <div class="flex items-center justify-center">
                                        Masa Kerja
                                    </div>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-700 text-sm">
                            @php $no = 1; @endphp
                            @foreach ($employees as $item)
                                <tr class="hover:bg-gray-50 transition duration-150">
                                    <td class="p-4 font-medium">
                                        <div class="flex items-center justify-center">
                                            {{ $no++ }}
                                        </div>
                                    </td>
                                    <td class="p-4 space-y-1">
                                        <div class="font-bold text-gray-900 text-base">{{ $item->name }}</div>
                                        <div class="text-xs text-gray-500"><i class="fas fa-building"></i>
                                            {{ $item->branch->name ?? '-' }} | {{ $item->outlet->name }}</div>
                                        <div class="text-xs text-gray-400">NIK: {{ $item->nik }}</div>
                                    </td>
                                    <td class="p-4 space-y-1">
                                        <div class="flex items-center justify-center flex-col">
                                            <div class="font-medium text-gray-700">{{ $item->position->name ?? '-' }}
                                            </div>
                                            <span
                                                class="bg-yellow-100 text-yellow-700 text-xs px-2 py-0.5 rounded-full font-bold border border-yellow-200">
                                                {{ ucfirst(str_replace('_', ' ', $item->status)) }}
                                            </span>

                                        </div>
                                    </td>
                                    <td class="p-4 text-xs">
                                        <div class="flex items-center gap-2 mb-1"><i
                                                class="fas fa-envelope text-gray-400 w-4"></i> {{ $item->email }}
                                        </div>
                                        <div class="flex items-center gap-2"><i
                                                class="fas fa-phone text-gray-400 w-4"></i> {{ $item->phone }}</div>
                                    </td>
                                    <td class="p-4">
                                        <div class="flex items-center justify-center">
                                            @php
                                                $joinDate = \Carbon\Carbon::parse($item->join_date);
                                                $diff = $joinDate->diff(\Carbon\Carbon::now());
                                            @endphp

                                            <span
                                                class="inline-flex items-center w-fit bg-indigo-50 text-indigo-700 text-xs font-bold px-2 py-0.5 rounded border border-indigo-100">
                                                <i class="far fa-clock mr-1"></i>
                                                {{ $diff->y }} Tahun {{ $diff->m }} Bulan
                                                {{ $diff->d }} Hari
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Header -->
            <div class="md:flex justify-between items-center bg-white p-5 rounded-xl shadow-sm border border-gray-100">
                <div>
                    <h1 class="font-bold text-2xl text-gray-800 flex items-center gap-2">
                        <i class="fas fa-business-time text-purple-600"></i> Manajemen Lembur
                    </h1>
                    <p class="text-sm text-gray-500">Data lembur dikelompokkan berdasarkan jadwal.</p>
                </div>
                <x-button href="{{ route('overtime') }}" size="lg" variant="primary" class="bg-slate-700 hover:bg-green-600 shadow-md" icon="external-link">Ke Halaman</x-button>
            </div>

            <!-- Table -->
            <div class="w-full bg-white rounded-xl shadow-md border border-gray-100">
                <div class="p-5 overflow-auto">
                    <table id="overtimeTable" class="w-full text-left">
                        <thead class="bg-gray-100 text-gray-600 text-sm leading-normal">
                            <tr>
                                <th class="p-4 font-bold" width="5%">No</th>
                                <th class="p-4 font-bold" width="15%">
                                    <div class="flex items-center justify-center">
                                        Jadwal
                                    </div>
                                </th>
                                <th class="p-4 font-bold" width="15%">
                                    <div class="flex items-center justify-center">
                                        Target Capaian
                                    </div>
                                </th>
                                <th class="p-4 font-bold">
                                    <div class="flex items-center justify-center">
                                        Daftar Karyawan
                                    </div>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-700 text-sm">
                            @php $no = 1; @endphp
                            @foreach ($overtimes as $key => $group)
                                @php
                                    $header = $group->first();
                                    $totalNominal = $group->sum('overtime_pay');
                                    $groupEmpIds = $group->pluck('employee_id')->toJson();
                                    $groupBranchId = $header->employee->branch_id ?? '';
                                    $groupOutletId =
                                        $header->employee->outlet_id ?? ($header->employee->outlet->id ?? '');
                                @endphp
                                <tr class="hover:bg-gray-50 transition border-b border-gray-100">
                                    <td class="p-4 font-medium">
                                        <div class="flex items-center justify-center">
                                            {{ $no++ }}
                                        </div>
                                    </td>

                                    <!-- Jadwal -->
                                    <td class="p-4 ">
                                        <div class="font-bold text-gray-800 text-base">
                                            {{ \Carbon\Carbon::parse($header->overtime_date)->format('d M Y') }}
                                        </div>
                                        <div
                                            class="mt-1 flex items-center gap-2 text-xs text-gray-500 font-mono bg-gray-100 px-2 py-1 rounded w-fit">
                                            <i class="far fa-clock"></i>
                                            {{ \Carbon\Carbon::parse($header->start_time)->format('H:i') }} -
                                            {{ \Carbon\Carbon::parse($header->end_time)->format('H:i') }}
                                        </div>

                                        <div class="mt-2 text-xs font-semibold text-gray-600">
                                            Total: Rp {{ number_format($totalNominal, 0, ',', '.') }}
                                        </div>
                                        @if ($header->employee->branch)
                                            <div
                                                class="mt-1 text-[10px] text-blue-600 bg-blue-50 px-2 py-0.5 rounded w-fit font-bold uppercase">
                                                {{ $header->employee->branch->name }}
                                            </div>
                                        @endif
                                    </td>

                                    <td class="p-4">
                                        <div
                                            class="text-sm text-gray-600 italic whitespace-pre-wrap break-words max-w-[200px] md:max-w-[250px] text-center">
                                            {{ $header->note ?? '-' }}
                                        </div>
                                    </td>

                                    <!-- Daftar Karyawan -->
                                    <td class="p-4 ">
                                        <div class="flex flex-col gap-2">
                                            @foreach ($group as $item)
                                                <div
                                                    class="employee-list-item flex justify-between items-center p-2 rounded border border-gray-100 bg-white shadow-sm gap-4">
                                                    <div class="min-w-[150px]">
                                                        <div class="font-bold text-gray-800">
                                                            {{ $item->employee->name ?? '-' }}
                                                        </div>
                                                        <div class="text-[10px] text-gray-500">
                                                            {{ $item->employee->position->name ?? '-' }}</div>
                                                    </div>

                                                </div>
                                            @endforeach
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Header -->
            <div
                class="md:flex justify-between items-center bg-white p-5 rounded-xl shadow-sm border border-gray-100 space-y-2 md:space-y-0">
                <div>
                    <h1 class="font-bold text-2xl text-gray-800 flex items-center gap-2">
                        <i class="fas fa-plane-departure text-yellow-500"></i> Permintaan Cuti
                    </h1>
                    <p class="text-sm text-gray-500 ">Kelola Pengajuan Cuti Karyawan</p>
                </div>
                <x-button href="{{ route('leave') }}" size="lg" variant="primary" class="bg-slate-700 hover:bg-green-600 shadow-md" icon="external-link">Ke Halaman</x-button>
            </div>

            <!-- Table Section -->
            <div class="w-full bg-white rounded-xl shadow-md border border-gray-100">
                <div class="p-5 overflow-auto">
                    <table id="leaveTable" class="w-full text-left">
                        <thead class="bg-gray-100 text-gray-600 text-sm leading-normal">
                            <tr>
                                <th class="p-4 font-bold rounded-tl-lg text-center" width="5%">No</th>
                                <th class="p-4 font-bold">Tanggal</th>
                                <th class="p-4 font-bold">Karyawan</th>
                                <th class="p-4 font-bold text-center">Durasi</th>
                                <th class="p-4 font-bold">Jenis</th>
                                <th class="p-4 font-bold">Catatan</th>
                                <th class="p-4 font-bold text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-700 text-sm">
                            @php $no = 1; @endphp
                            @foreach ($leaves as $item)
                                <tr class="hover:bg-gray-50 transition duration-150">
                                    <td class="p-4 font-medium">{{ $no++ }}</td>
                                    <td class="p-4 font-medium">
                                        {{ \Carbon\Carbon::parse($item->created_at)->format('d M Y') }}
                                    </td>
                                    <td class="p-4">
                                        <div class="font-bold text-gray-900">{{ $item->employee->name ?? 'N/A' }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            {{ $item->employee->position->name ?? '' }}</div>
                                    </td>
                                    <td class="p-4 text-center text-xs">
                                        <div class="font-semibold text-gray-700">
                                            {{ \Carbon\Carbon::parse($item->start_date)->format('d M') }} -
                                            {{ \Carbon\Carbon::parse($item->end_date)->format('d M') }}
                                        </div>
                                        {{-- Hitung durasi hari (Opsional) --}}
                                        <div class="text-gray-400">
                                            {{ \Carbon\Carbon::parse($item->start_date)->diffInDays(\Carbon\Carbon::parse($item->end_date)) + 1 }}
                                            Days
                                        </div>
                                    </td>
                                    <td class="p-4">
                                        <span class="font-semibold text-gray-700 uppercase">{{ $item->type }}</span>
                                    </td>
                                    <td class="p-4 text-xs text-gray-600 italic max-w-xs truncate">
                                        "{{ \Illuminate\Support\Str::limit($item->note, 30) }}"
                                    </td>
                                    <td class="p-4 text-center">
                                        @php
                                            $statusColor = match ($item->status) {
                                                'approved' => 'bg-green-100 text-green-700 border-green-200',
                                                'pending' => 'bg-yellow-100 text-yellow-700 border-yellow-200',
                                                'rejected' => 'bg-red-100 text-red-700 border-red-200',
                                                'cancelled' => 'bg-gray-100 text-gray-600 border-gray-200',
                                                default => 'bg-gray-100 text-gray-600',
                                            };
                                        @endphp
                                        <span
                                            class="{{ $statusColor }} px-3 py-1 rounded-full text-xs font-bold border uppercase shadow-sm">
                                            {{ $item->status }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>


            <!-- Header -->
            <div
                class="md:flex justify-between items-center bg-white p-5 rounded-xl shadow-sm border border-gray-100 space-y-2 md:space-y-0">
                <div>
                    <h1 class="font-bold text-2xl text-gray-800 flex items-center gap-2">
                        <i class="fas fa-sticky-note text-teal-600"></i> Catatan Karyawan
                    </h1>
                    <p class="text-sm text-gray-500">Kelola catatan, peringatan, dan penghargaan</p>
                </div>
                <x-button href="{{ route('note') }}" size="lg" variant="primary" class="bg-slate-700 hover:bg-green-600 shadow-md" icon="external-link">Ke Halaman</x-button>
            </div>

            <!-- Table Section -->
            <div class="w-full bg-white rounded-xl shadow-md border border-gray-100">
                <div class="p-5 overflow-auto">
                    <table id="noteTable" class="w-full text-left">
                        <thead class="bg-gray-100 text-gray-600 text-sm leading-normal">
                            <tr>
                                <th class="p-4 font-bold rounded-tl-lg text-center" width="5%">No</th>
                                <th class="p-4 font-bold">Tanggal</th>
                                <th class="p-4 font-bold">Karyawan</th>
                                <th class="p-4 font-bold">Tipe</th>
                                <th class="p-4 font-bold">Konteks</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-700 text-sm">
                            @php $no = 1; @endphp
                            @foreach ($notes as $item)
                                <tr class="hover:bg-gray-50 transition duration-150">
                                    <td class="p-4 font-medium">{{ $no++ }}</td>
                                    <td class="p-4 font-medium">
                                        {{ \Carbon\Carbon::parse($item->note_date)->format('d M Y') }}
                                    </td>
                                    <td class="p-4">
                                        <div class="font-bold text-gray-900">{{ $item->employee->name ?? 'N/A' }}
                                        </div>
                                        <div class="text-xs text-gray-500">{{ $item->employee->position->name ?? '' }}
                                        </div>
                                    </td>
                                    <td class="p-4">
                                        @php
                                            $typeColor = match ($item->type) {
                                                'warning' => 'bg-red-100 text-red-700 border-red-200',
                                                'reward' => 'bg-green-100 text-green-700 border-green-200',
                                                'performance' => 'bg-blue-100 text-blue-700 border-blue-200',
                                                'general' => 'bg-gray-100 text-gray-700 border-gray-200',
                                                default => 'bg-gray-100 text-gray-600',
                                            };
                                        @endphp
                                        <span
                                            class="{{ $typeColor }} px-3 py-1 rounded-full text-xs font-bold border uppercase shadow-sm">
                                            {{ ucfirst($item->type) }}
                                        </span>
                                    </td>
                                    <td class="p-4 text-xs text-gray-600 italic max-w-xs truncate">
                                        "{{ \Illuminate\Support\Str::limit($item->content, 40) }}"
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>


    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="//cdn.datatables.net/2.0.2/js/dataTables.min.js"></script>
    <script>
        $(document).ready(function() {
            new DataTable('#branchTable', {});
        });

        $(document).ready(function() {
            new DataTable('#positionTable', {});
        });

        $(document).ready(function() {
            new DataTable('#announcementTable', {});
        });

        $(document).ready(function() {
            new DataTable('#employeeTable', {});
        });

        $(document).ready(function() {
            new DataTable('#overtimeTable', {});
        });

        $(document).ready(function() {
            new DataTable('#leaveTable', {});
        });

        $(document).ready(function() {
            new DataTable('#noteTable', {});
        });
    </script>
    @include('sweetalert::alert')
    @include('layout.loading')
</body>

</html>
