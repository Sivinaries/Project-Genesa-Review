<!DOCTYPE html>
<html lang="en">

<head>
    <title>Manajemen Cuti</title>
    @include('layout.head')
    <!-- DataTables CSS -->
    <link href="//cdn.datatables.net/2.0.2/css/dataTables.dataTables.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        /* Override DataTables Style */
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

        /* Custom Scrollbar */
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>
</head>

<body class="bg-gray-50 font-sans">
    @include('layout.sidebar')

    <main class="md:ml-64 xl:ml-72 2xl:ml-72">
        @include('layout.navbar')
        <div class="p-6 space-y-6">

            <!-- Header Section -->
            <div class="md:flex justify-between items-center bg-white p-5 rounded-xl shadow-sm border border-gray-100 space-y-2 md:space-y-0">
                <div>
                    <h1 class="font-bold text-2xl text-gray-800 flex items-center gap-2">
                        <i class="fas fa-plane-departure text-yellow-500"></i> Permintaan Cuti
                    </h1>
                    <p class="text-sm text-gray-500 ">Kelola Pengajuan Cuti Karyawan</p>
                </div>
                <div class="flex items-center gap-2">
                    <x-button id="adjustQuotaBtn" size="lg" variant="purple" icon="sliders-h">Atur</x-button>
                    <x-button id="addBtn" size="lg" variant="yellow" icon="plus">Tambah</x-button>
                </div>
            </div>

            <!-- Table Section -->
            <div class="w-full bg-white rounded-xl shadow-md border border-gray-100">
                <div class="p-5 overflow-auto">
                    <table id="myTable" class="w-full text-left">
                        <thead class="bg-gray-100 text-gray-600 text-sm leading-normal">
                            <tr>
                                <th class="p-4 font-bold rounded-tl-lg text-center" width="5%">No</th>
                                <th class="p-4 font-bold">Tanggal</th>
                                <th class="p-4 font-bold">Karyawan</th>
                                <th class="p-4 font-bold text-center">Durasi</th>
                                <th class="p-4 font-bold">Jenis</th>
                                <th class="p-4 font-bold">Catatan</th>
                                <th class="p-4 font-bold text-center">
                                    <div class="flex items-center justify-center">
                                        Status
                                    </div>
                                </th>
                                <th class="p-4 font-bold text-center">
                                    <div class="flex items-center justify-center">
                                        Sisa Kuota
                                    </div>
                                </th>
                                <th class="p-4 font-bold text-center rounded-tr-lg" width="15%">
                                    <div class="flex items-center justify-center">
                                        Action
                                    </div>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-700 text-sm">
                            @php $no = 1; @endphp
                            @foreach ($leaves as $item)
                                <tr class="hover:bg-gray-50 transition duration-150">
                                    <td class="p-4 font-medium text-center">{{ $no++ }}</td>
                                    <td class="p-4 font-medium">
                                        {{ \Carbon\Carbon::parse($item->created_at)->format('d M Y') }}
                                    </td>
                                    <td class="p-4">
                                        <div class="font-bold text-gray-900 text-base group-hover:text-cyan-600">{{ $item->employee->name ?? 'N/A' }}</div>
                                        <div class="text-xs text-gray-400">{{ $item->employee->position->name ?? '' }}</div>
                                    </td>
                                    <td class="p-4 text-center text-xs">
                                        <div class="font-semibold text-gray-700">
                                            {{ \Carbon\Carbon::parse($item->start_date)->format('d M') }} -
                                            {{ \Carbon\Carbon::parse($item->end_date)->format('d M') }}
                                        </div>
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
                                    <td class="p-4 text-center">
                                        @if($item->type === 'cuti')
                                            @php
                                                $q = $quotas[$item->employee_id] ?? null;
                                            @endphp
                                            @if($q)
                                                <span class="inline-block px-2 py-1 rounded-full text-xs font-bold
                                                    {{ $q->remaining_days > 5 ? 'bg-green-100 text-green-700' : ($q->remaining_days > 0 ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700') }}">
                                                    {{ $q->remaining_days }}/{{ $q->total_quota }} hari
                                                </span>
                                            @else
                                                <span class="text-xs text-gray-400">Belum ada kuota</span>
                                            @endif
                                        @else
                                            <span class="text-xs text-gray-300">—</span>
                                        @endif
                                    </td>
                                    <td class="p-4">
                                        <div class="flex justify-center items-center gap-2">
                                            {{-- Approve Button --}}
                                            @if($item->status === 'pending')
                                                <form method="post" action="{{ route('updateleave', ['id' => $item->id]) }}" class="inline">
                                                    @csrf @method('put')
                                                    <input type="hidden" name="employee_id" value="{{ $item->employee_id }}">
                                                    <input type="hidden" name="start_date" value="{{ $item->start_date }}">
                                                    <input type="hidden" name="end_date" value="{{ $item->end_date }}">
                                                    <input type="hidden" name="type" value="{{ $item->type }}">
                                                    <input type="hidden" name="note" value="{{ $item->note }}">
                                                    <input type="hidden" name="status" value="approved">
                                                    <button type="submit"
                                                        class="approve-confirm w-10 h-10 flex items-center justify-center bg-green-500 text-white rounded-lg shadow hover:bg-green-600 hover:scale-105 transition"
                                                        title="Approve">
                                                        <i class="fas fa-check text-lg"></i>
                                                    </button>
                                                </form>

                                                {{-- Reject Button --}}
                                                <form method="post" action="{{ route('updateleave', ['id' => $item->id]) }}" class="inline">
                                                    @csrf @method('put')
                                                    <input type="hidden" name="employee_id" value="{{ $item->employee_id }}">
                                                    <input type="hidden" name="start_date" value="{{ $item->start_date }}">
                                                    <input type="hidden" name="end_date" value="{{ $item->end_date }}">
                                                    <input type="hidden" name="type" value="{{ $item->type }}">
                                                    <input type="hidden" name="note" value="{{ $item->note }}">
                                                    <input type="hidden" name="status" value="rejected">
                                                    <button type="button"
                                                        class="reject-confirm w-10 h-10 flex items-center justify-center bg-orange-500 text-white rounded-lg shadow hover:bg-orange-600 hover:scale-105 transition"
                                                        title="Reject">
                                                        <i class="fas fa-times text-lg"></i>
                                                    </button>
                                                </form>
                                            @endif

                                            {{-- Edit Button --}}
                                            <button
                                                class="editBtn w-10 h-10 flex items-center justify-center bg-blue-500 text-white rounded-lg shadow hover:bg-blue-600 hover:scale-105 transition"
                                                data-id="{{ $item->id }}" data-employee="{{ $item->employee_id }}"
                                                data-start_date="{{ $item->start_date }}"
                                                data-end_date="{{ $item->end_date }}" data-type="{{ $item->type }}"
                                                data-note="{{ $item->note }}" data-status="{{ $item->status }}"
                                                title="Edit">
                                                <i class="fas fa-edit text-lg"></i>
                                            </button>

                                            {{-- Delete Button --}}
                                            <form method="post" action="{{ route('delleave', ['id' => $item->id]) }}"
                                                class="inline deleteForm">
                                                @csrf
                                                @method('delete')
                                                <button type="button"
                                                    class="delete-confirm w-10 h-10 flex items-center justify-center bg-red-500 text-white rounded-lg shadow hover:bg-red-600 hover:scale-105 transition"
                                                    title="Delete">
                                                    <i class="fas fa-trash text-lg"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- ADJUST QUOTA MODAL -->
    <div id="adjustQuotaModal"
        class="hidden fixed inset-0 bg-gray-900/60 backdrop-blur-sm flex items-center justify-center z-50 overflow-y-auto px-4 py-6">
        <div class="bg-white rounded-2xl p-8 w-full max-w-lg shadow-2xl relative">
            <button id="closeAdjustModal" class="absolute top-5 right-5 text-gray-400 hover:text-gray-600 transition">
                <i class="fas fa-times text-xl"></i>
            </button>
            <h2 class="text-2xl font-bold mb-1 text-gray-800 flex items-center gap-2">
                <i class="fas fa-sliders-h text-purple-500"></i> Atur
            </h2>
            <p class="text-sm text-gray-500 mb-6">Kurangi atau tambah jatah cuti mandiri karyawan untuk periode aktif.</p>

            @if($errors->has('quota_adjust'))
                <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm flex items-center gap-2">
                    <i class="fas fa-exclamation-circle"></i> {{ $errors->first('quota_adjust') }}
                </div>
            @endif

            <form method="POST" action="{{ route('adjustQuota') }}" class="space-y-5">
                @csrf

                {{-- Pilih Karyawan --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Karyawan</label>

                    <div class="relative mb-2">
                        <input type="text" id="employeeSearchQuota" placeholder="Cari nama karyawan..."
                            class="w-full rounded-lg border-gray-300 p-2.5 pl-10 text-sm border focus:ring-2 focus:ring-purple-500">
                        <i class="fas fa-search absolute left-3 top-3 text-gray-400 text-sm"></i>
                    </div>

                    <div class="border border-gray-200 rounded-xl overflow-hidden max-h-52 overflow-y-auto custom-scrollbar bg-gray-50 p-1">
                        @foreach ($employee as $emp)
                            @php
                                $empQuota  = $quotas->get($emp->id);
                                $joinDate  = \Carbon\Carbon::parse($emp->join_date);
                                $isEligible = \Carbon\Carbon::now()->gte($joinDate->copy()->addYear());
                            @endphp
                            <label class="employee-item-quota flex items-center p-2.5 hover:bg-white rounded-lg cursor-pointer transition border border-transparent hover:border-gray-200 group">
                                <input type="radio" name="employee_id" value="{{ $emp->id }}"
                                    class="w-4 h-4 text-purple-600 border-gray-300 focus:ring-purple-500 mr-3"
                                    {{ !$isEligible ? 'disabled' : '' }} required>
                                <div class="flex-1">
                                    <div class="text-sm font-bold {{ !$isEligible ? 'text-gray-400' : 'text-gray-700 group-hover:text-purple-700' }}">
                                        {{ $emp->name }}
                                    </div>
                                    <div class="text-[10px] text-gray-500">
                                        {{ $emp->position->name ?? '-' }}
                                        @if($emp->branch) • {{ $emp->branch->name }} @endif
                                    </div>
                                    <div class="text-[10px] mt-0.5">
                                        @if(!$isEligible)
                                            <span class="text-red-400">Belum eligible (< 1 tahun)</span>
                                        @elseif($empQuota)
                                            <span class="text-purple-600 font-semibold">
                                                Kuota: {{ $empQuota->remaining_days }}/{{ $empQuota->total_quota }} hari
                                                (terpakai: {{ $empQuota->used_days }})
                                            </span>
                                        @else
                                            <span class="text-gray-400">Kuota belum terbuat</span>
                                        @endif
                                    </div>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- Aksi --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Aksi</label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="flex items-center gap-3 p-3 border-2 border-gray-200 rounded-xl cursor-pointer hover:border-red-400 transition has-[:checked]:border-red-500 has-[:checked]:bg-red-50">
                            <input type="radio" name="action" value="deduct" class="text-red-500" required>
                            <div>
                                <div class="text-sm font-bold text-gray-700">
                                    <i class="fas fa-minus-circle text-red-500 mr-1"></i> Kurangi
                                </div>
                                <div class="text-[10px] text-gray-500">Potong jatah cuti</div>
                            </div>
                        </label>
                        <label class="flex items-center gap-3 p-3 border-2 border-gray-200 rounded-xl cursor-pointer hover:border-green-400 transition has-[:checked]:border-green-500 has-[:checked]:bg-green-50">
                            <input type="radio" name="action" value="restore" class="text-green-500" required>
                            <div>
                                <div class="text-sm font-bold text-gray-700">
                                    <i class="fas fa-plus-circle text-green-500 mr-1"></i> Tambah
                                </div>
                                <div class="text-[10px] text-gray-500">Tambah jatah cuti</div>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- Jumlah Hari --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Jumlah Hari</label>
                    <div class="flex items-center gap-3">
                        <input type="number" name="days" min="1" max="365" placeholder="0"
                            class="w-32 rounded-lg border-gray-300 shadow-sm p-2.5 border focus:ring-2 focus:ring-purple-500 text-center font-bold text-lg"
                            required>
                        <span class="text-sm text-gray-500">hari</span>
                    </div>
                </div>

                {{-- Alasan --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Alasan / Keterangan</label>
                    <textarea name="reason" rows="2" placeholder="Contoh: Sanksi ketidakhadiran, bonus tambahan cuti, dll..."
                        class="w-full rounded-lg border-gray-300 shadow-sm p-2.5 border focus:ring-2 focus:ring-purple-500 text-sm"
                        required></textarea>
                </div>

                <x-button type="submit" variant="primary" icon="save" class="w-full bg-slate-700 hover:bg-slate-800 justify-center">Simpan</x-button>
            </form>
        </div>
    </div>

    <!-- ADD MODAL -->
    <div id="addModal"
        class="hidden fixed inset-0 bg-gray-900/60 backdrop-blur-sm flex items-center justify-center z-50 overflow-y-auto px-4 py-6">
        <div class="bg-white rounded-2xl p-8 w-full max-w-lg shadow-2xl relative transform transition-all scale-100">
            <button id="closeAddModal" class="absolute top-5 right-5 text-gray-400 hover:text-gray-600 transition">
                <i class="fas fa-times text-xl"></i>
            </button>
            <h2 class="text-2xl font-bold mb-6 text-gray-800 flex items-center gap-2">
                <i class="fas fa-plane-departure text-yellow-500"></i> Tambah 
            </h2>

            <form id="addForm" method="post" action="{{ route('postleave') }}" enctype="multipart/form-data"
                class="space-y-5">
                @csrf @method('post')

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Karyawan</label>
                    
                    <div class="relative mb-2">
                        <input type="text" id="employeeSearchAdd" placeholder="Cari nama karyawan..."
                            class="w-full rounded-lg border-gray-300 p-2.5 pl-10 text-sm focus:ring-2 focus:ring-yellow-500">
                        <i class="fas fa-search absolute left-3 top-3 text-gray-400 text-sm"></i>
                    </div>

                    <div class="border border-gray-200 rounded-xl overflow-hidden max-h-48 overflow-y-auto custom-scrollbar bg-gray-50 p-1">
                        @foreach ($employee as $emp)
                            <label
                                class="employee-item-add flex items-center p-2.5 hover:bg-white rounded-lg cursor-pointer transition border border-transparent hover:border-gray-200 group">
                                <input type="radio" name="employee_id" value="{{ $emp->id }}"
                                    class="employee-radio w-4 h-4 text-yellow-500 border-gray-300 focus:ring-yellow-500 mr-3" required>
                                <div>
                                    <div class="text-sm font-bold text-gray-700 group-hover:text-yellow-700">
                                        {{ $emp->name }}
                                    </div>
                                    <div class="text-[10px] text-gray-500">
                                        {{ $emp->position->name ?? '-' }} 
                                        @if($emp->branch)
                                            • {{ $emp->branch->name }}
                                        @endif
                                    </div>
                                </div>
                            </label>
                        @endforeach

                        @if ($employee->isEmpty())
                            <p class="text-center text-xs text-gray-400 py-4">Tidak ada karyawan ditemukan.</p>
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Mulai</label>
                        <input type="date" name="start_date"
                            class="w-full rounded-lg border-gray-300 shadow-sm p-2.5 border focus:ring-2 focus:ring-yellow-500"
                            required>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Berakhir</label>
                        <input type="date" name="end_date"
                            class="w-full rounded-lg border-gray-300 shadow-sm p-2.5 border focus:ring-2 focus:ring-yellow-500"
                            required>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Jenis</label>
                        <select name="type"
                            class="w-full rounded-lg border-gray-300 shadow-sm p-2.5 border focus:ring-2 focus:ring-yellow-500"
                            required>
                            <option value="izin">Izin</option>
                            <option value="sakit">Sakit</option>
                            <option value="cuti">Cuti</option>
                            <option value="meninggalkan_pekerjaan">Meninggalkan Pekerjaan</option>
                            <option value="tukar_shift">Tukar Shift</option>
                            <option value="other">Lainnya</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Status</label>
                        <select name="status"
                            class="w-full rounded-lg border-gray-300 shadow-sm p-2.5 border focus:ring-2 focus:ring-yellow-500"
                            required>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Catatan</label>
                    <textarea name="note" rows="3"
                        class="w-full rounded-lg border-gray-300 shadow-sm p-2.5 border focus:ring-2 focus:ring-yellow-500" required></textarea>
                </div>

                <x-button type="submit" variant="primary" icon="save" class="w-full bg-slate-700 hover:bg-slate-800 justify-center">Simpan</x-button>
            </form>
        </div>
    </div>

    <!-- EDIT MODAL -->
    <div id="editModal"
        class="hidden fixed inset-0 bg-gray-900/60 backdrop-blur-sm flex items-center justify-center z-50 overflow-y-auto px-4 py-6">
        <div class="bg-white rounded-2xl p-8 w-full max-w-lg shadow-2xl relative transform transition-all scale-100">
            <button id="closeModal" class="absolute top-5 right-5 text-gray-400 hover:text-gray-600 transition">
                <i class="fas fa-times text-xl"></i>
            </button>
            <h2 class="text-2xl font-bold mb-6 text-gray-800 flex items-center gap-2">
                <i class="fas fa-edit text-blue-600"></i> Edit 
            </h2>

            <form id="editForm" method="post" enctype="multipart/form-data" class="space-y-5">
                @csrf @method('put')

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Karyawan</label>
                    
                    <div class="relative mb-2">
                        <input type="text" id="employeeSearchEdit" placeholder="Cari nama karyawan..."
                            class="w-full rounded-lg border-gray-300 p-2.5 pl-10 text-sm focus:ring-2 focus:ring-blue-500">
                        <i class="fas fa-search absolute left-3 top-3 text-gray-400 text-sm"></i>
                    </div>

                    <div class="border border-gray-200 rounded-xl overflow-hidden max-h-48 overflow-y-auto custom-scrollbar bg-gray-50 p-1">
                        @foreach ($employee as $emp)
                            <label
                                class="employee-item-edit flex items-center p-2.5 hover:bg-white rounded-lg cursor-pointer transition border border-transparent hover:border-gray-200 group">
                                <input type="radio" name="employee_id" value="{{ $emp->id }}"
                                    class="employee-radio-edit w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500 mr-3" required>
                                <div>
                                    <div class="text-sm font-bold text-gray-700 group-hover:text-blue-700">
                                        {{ $emp->name }}
                                    </div>
                                    <div class="text-[10px] text-gray-500">
                                        {{ $emp->position->name ?? '-' }}
                                        @if($emp->branch)
                                            • {{ $emp->branch->name }}
                                        @endif
                                    </div>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Mulai</label>
                        <input type="date" id="editStartDate" name="start_date"
                            class="w-full rounded-lg border-gray-300 shadow-sm p-2.5 border focus:ring-2 focus:ring-blue-500"
                            required>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Berakhir</label>
                        <input type="date" id="editEndDate" name="end_date"
                            class="w-full rounded-lg border-gray-300 shadow-sm p-2.5 border focus:ring-2 focus:ring-blue-500"
                            required>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Jenis</label>
                        <select id="editType" name="type"
                            class="w-full rounded-lg border-gray-300 shadow-sm p-2.5 border focus:ring-2 focus:ring-blue-500"
                            required>
                            <option value="izin">Izin</option>
                            <option value="sakit">Sakit</option>
                            <option value="cuti">Cuti</option>
                            <option value="meninggalkan_pekerjaan">Meninggalkan Pekerjaan</option>
                            <option value="tukar_shift">Tukar Shift</option>
                            <option value="other">Lainnya</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Status</label>
                        <select id="editStatus" name="status"
                            class="w-full rounded-lg border-gray-300 shadow-sm p-2.5 border focus:ring-2 focus:ring-blue-500"
                            required>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Catatan</label>
                    <textarea id="editNote" name="note" rows="3"
                        class="w-full rounded-lg border-gray-300 shadow-sm p-2.5 border focus:ring-2 focus:ring-blue-500" required></textarea>
                </div>

                <x-button type="submit" variant="primary" icon="save" class="w-full bg-blue-600 hover:bg-blue-700 justify-center">Perbarui</x-button>
            </form>
        </div>
    </div>

    <!-- SCRIPTS -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"
        integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <script src="//cdn.datatables.net/2.0.2/js/dataTables.min.js"></script>
    <script>
        $(document).ready(function() {
            // Init DataTable
            new DataTable('#myTable', {});

            // Modal Logic
            const addModal = $('#addModal');
            const editModal = $('#editModal');
            const adjustModal = $('#adjustQuotaModal');

            $('#adjustQuotaBtn').click(() => {
                adjustModal.removeClass('hidden');
                $('body').addClass('overflow-hidden');
            });

            $('#closeAdjustModal').click(() => {
                adjustModal.addClass('hidden');
                $('body').removeClass('overflow-hidden');
            });

            $('#employeeSearchQuota').on('keyup', function() {
                var value = $(this).val().toLowerCase();
                $(".employee-item-quota").filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
                });
            });

            $('#addBtn').click(() => {
                addModal.removeClass('hidden');
                $('body').addClass('overflow-hidden');
            });
            
            $('#closeAddModal').click(() => {
                addModal.addClass('hidden');
                $('body').removeClass('overflow-hidden');
            });

            // Employee Search - Add Modal
            $('#employeeSearchAdd').on('keyup', function() {
                var value = $(this).val().toLowerCase();
                $(".employee-item-add").filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                });
            });

            // Employee Search - Edit Modal
            $('#employeeSearchEdit').on('keyup', function() {
                var value = $(this).val().toLowerCase();
                $(".employee-item-edit").filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                });
            });

            // Edit Logic
            $(document).on('click', '.editBtn', function() {
                const btn = $(this);
                
                // Set selected employee radio button
                $('input[name="employee_id"][value="' + btn.data('employee') + '"]').prop('checked', true);
                
                $('#editStartDate').val(btn.data('start_date'));
                $('#editEndDate').val(btn.data('end_date'));
                $('#editType').val(btn.data('type'));
                $('#editNote').val(btn.data('note'));
                $('#editStatus').val(btn.data('status'));

                $('#editForm').attr('action', `/leave/${btn.data('id')}/update`);
                editModal.removeClass('hidden');
                $('body').addClass('overflow-hidden');
            });

            $('#closeModal').click(() => {
                editModal.addClass('hidden');
                $('body').removeClass('overflow-hidden');
            });

            $(window).click((e) => {
                if (e.target === addModal[0]) {
                    addModal.addClass('hidden');
                    $('body').removeClass('overflow-hidden');
                }
                if (e.target === editModal[0]) {
                    editModal.addClass('hidden');
                    $('body').removeClass('overflow-hidden');
                }
                if (e.target === adjustModal[0]) {
                    adjustModal.addClass('hidden');
                    $('body').removeClass('overflow-hidden');
                }
            });

            $('#adjustSubmitBtn').click(function(e) {
                e.preventDefault();
                const form = $(this).closest('form');
                const action = $('input[name="action"]:checked').val();
                const days   = $('input[name="days"]').val();
                const emp    = $('input[name="employee_id"]:checked').closest('label').find('.text-sm.font-bold').text().trim();

                if (!action || !days || !emp) return form.submit();

                const isDeduct = action === 'deduct';
                Swal.fire({
                    title: isDeduct ? `Kurangi ${days} hari kuota?` : `Tambah ${days} hari kuota?`,
                    html: `Karyawan: <strong>${emp}</strong>`,
                    icon: isDeduct ? 'warning' : 'question',
                    showCancelButton: true,
                    confirmButtonColor: isDeduct ? '#9333ea' : '#16a34a',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Ya, Simpan!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) form.submit();
                });
            });

            // Approve confirmation
            $(document).on('click', '.approve-confirm', function(e) {
                e.preventDefault();
                const form = $(this).closest('form');
                Swal.fire({
                    title: 'Setujui Pengajuan Cuti?',
                    text: 'Kuota cuti karyawan akan dipotong jika tipe adalah Cuti.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#16a34a',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Ya, Setujui!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) form.submit();
                });
            });

            // Reject confirmation
            $(document).on('click', '.reject-confirm', function(e) {
                e.preventDefault();
                const form = $(this).closest('form');
                Swal.fire({
                    title: 'Tolak Pengajuan Cuti?',
                    text: 'Pengajuan ini akan ditolak.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ea580c',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Ya, Tolak!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) form.submit();
                });
            });

            // Delete confirmation
            $(document).on('click', '.delete-confirm', function(e) {
                e.preventDefault();
                const form = $(this).closest('form');
                Swal.fire({
                    title: 'Hapus Permintaan Cuti?',
                    text: "Tindakan ini tidak dapat dibatalkan!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) form.submit();
                });
            });
        });
    </script>

    @include('sweetalert::alert')
    @include('layout.loading')

</body>

</html>