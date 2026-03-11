<!DOCTYPE html>
<html lang="en">

<head>
    <title>ESS | Pengajuan Cuti Tim</title>
    @include('ess.layout.head')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <style>
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }

        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
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

<body
    class="bg-gray-50 font-sans w-full md:max-w-sm mx-auto min-h-screen flex flex-col shadow-lg border-x border-gray-100">

    <!-- HEADER -->
    <div class="sticky top-0 bg-white/95 backdrop-blur-md z-20 border-b border-gray-200">
        <div class="p-3 flex items-center justify-between">
            <a href="{{ route('ess-home') }}"
                class="w-9 h-9 flex items-center justify-center rounded-xl bg-gray-50 text-gray-600 hover:bg-gray-100 transition">
                <i class="fas fa-arrow-left text-base"></i>
            </a>
            <h1 class="font-bold text-base text-gray-800">Pengajuan Cuti Tim</h1>
            <div class="w-9"></div>
        </div>

        <!-- Summary Stats -->
        <div class="px-4 pb-4 pt-2">
            <div class="grid grid-cols-3 gap-2">
                <div class="bg-amber-50 rounded-xl p-3 border border-amber-100 text-center">
                    <p class="text-[10px] uppercase font-bold text-amber-400 tracking-wider">Pending</p>
                    <p class="text-xl font-extrabold text-amber-600">{{ $leaves->where('status', 'pending')->count() }}
                    </p>
                </div>
                <div class="bg-emerald-50 rounded-xl p-3 border border-emerald-100 text-center">
                    <p class="text-[10px] uppercase font-bold text-emerald-400 tracking-wider">Disetujui</p>
                    <p class="text-xl font-extrabold text-emerald-600">
                        {{ $leaves->where('status', 'approved')->count() }}</p>
                </div>
                <div class="bg-red-50 rounded-xl p-3 border border-red-100 text-center">
                    <p class="text-[10px] uppercase font-bold text-red-400 tracking-wider">Ditolak</p>
                    <p class="text-xl font-extrabold text-red-600">{{ $leaves->where('status', 'rejected')->count() }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Info Banner -->
        <div class="mx-4 mb-4 bg-blue-50 border border-blue-200 rounded-lg p-3 flex items-start gap-2">
            <i class="fas fa-info-circle text-blue-500 mt-0.5"></i>
            <p class="text-xs text-blue-700">
                <strong>Info:</strong> Sebagai koordinator, Anda dapat mengajukan cuti untuk tim. Admin yang akan
                menyetujui/menolak pengajuan.
            </p>
        </div>
    </div>

    <!-- LIST CONTENT -->
    <div class="p-3 flex-grow space-y-3 pb-20">
        @forelse ($leaves as $item)
            @php
                $startDate = \Carbon\Carbon::parse($item->start_date);
                $duration = $startDate->diffInDays(\Carbon\Carbon::parse($item->end_date)) + 1;

                $statusColor = match ($item->status) {
                    'pending' => 'bg-amber-100 text-amber-700 border-amber-200',
                    'approved' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                    'rejected' => 'bg-red-100 text-red-700 border-red-200',
                    'cancelled' => 'bg-gray-100 text-gray-700 border-gray-200',
                    default => 'bg-gray-100 text-gray-600',
                };
            @endphp

            <div
                class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm hover:shadow-md transition group relative overflow-hidden">
                <!-- Status Strip -->
                <div
                    class="absolute left-0 top-0 bottom-0 w-1 {{ $item->status == 'pending' ? 'bg-amber-500' : ($item->status == 'approved' ? 'bg-emerald-500' : 'bg-red-500') }}">
                </div>

                <div class="pl-2">
                    <div class="flex justify-between items-start mb-2">
                        <div class="flex items-center gap-3">
                            <!-- Avatar -->
                            <div
                                class="w-10 h-10 rounded-full bg-gradient-to-br from-indigo-400 to-purple-500 flex items-center justify-center text-white font-bold text-sm shadow">
                                {{ substr($item->employee->name, 0, 1) }}
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-800 text-sm">{{ $item->employee->name }}</h3>
                                <p class="text-[10px] text-gray-500">{{ $item->employee->position->name ?? 'Staff' }}
                                </p>
                            </div>
                        </div>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase border {{ $statusColor }}">
                            {{ $item->status }}
                        </span>
                    </div>

                    <div class="bg-gray-50 rounded-lg p-3 border border-gray-100 mt-3 space-y-2">
                        <div class="flex justify-between items-center text-xs">
                            <div class="text-gray-500 font-medium">Jenis</div>
                            <div class="font-bold text-gray-700 uppercase">{{ str_replace('_', ' ', $item->type) }}
                            </div>
                        </div>
                        <div class="flex justify-between items-center text-xs">
                            <div class="text-gray-500 font-medium">Tanggal</div>
                            <div class="font-bold text-gray-700">
                                {{ $startDate->format('d M') }} -
                                {{ \Carbon\Carbon::parse($item->end_date)->format('d M Y') }}
                                <span class="ml-1 text-gray-400 font-normal">({{ $duration }} Hari)</span>
                            </div>
                        </div>
                        @if ($item->note)
                            <div class="pt-2 border-t border-gray-200 text-[11px] text-gray-600 italic">
                                <strong>Catatan:</strong> "{{ $item->note }}"
                            </div>
                        @endif
                    </div>

                    <div class="mt-3 flex justify-end gap-2">
                        @if ($item->status == 'pending')
                            <!-- Hanya tampilkan tombol Edit jika status masih pending -->
                            <button
                                class="editBtn px-3 py-2 bg-blue-500 text-white text-xs font-bold rounded-lg shadow-sm hover:bg-blue-600 transition flex items-center gap-1"
                                data-id="{{ $item->id }}" data-employee="{{ $item->employee_id }}"
                                data-name="{{ $item->employee->name }}" data-start_date="{{ $item->start_date }}"
                                data-end_date="{{ $item->end_date }}" data-type="{{ $item->type }}"
                                data-note="{{ $item->note }}">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                        @else
                            <!-- Tampilkan label jika sudah diproses -->
                            <span
                                class="px-3 py-2 bg-gray-100 text-gray-500 text-xs font-medium rounded-lg border border-gray-200">
                                <i class="fas fa-lock"></i> Diproses Admin
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="flex flex-col items-center justify-center h-[50vh] text-center p-6">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-3 text-gray-300">
                    <i class="fas fa-inbox text-3xl"></i>
                </div>
                <h3 class="text-base font-bold text-gray-700">Tidak Ada Pengajuan</h3>
                <p class="text-xs text-gray-400 mt-1">Belum ada pengajuan cuti dari tim Anda.</p>
            </div>
        @endforelse
    </div>

    <!-- BUTTON INPUT -->
    <div
        class="fixed bottom-0 left-0 w-full md:left-1/2 md:w-full md:max-w-sm md:-translate-x-1/2 bg-white border-t border-gray-200 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)] p-4 z-30">
        <button id="addBtn"
            class="w-full py-3.5 bg-indigo-600 text-white font-bold rounded-xl shadow-lg shadow-indigo-300 hover:bg-indigo-700 transition flex items-center justify-center gap-2 transform active:scale-95">
            <i class="fas fa-plus-circle text-lg"></i> Ajukan Cuti Baru
        </button>
    </div>

    <!-- ADD MODAL -->
    <div id="addModal"
        class="hidden fixed inset-0 bg-gray-900/60 backdrop-blur-sm flex items-end sm:items-center justify-center z-50 p-0 sm:p-4">
        <div
            class="bg-white rounded-t-2xl sm:rounded-2xl w-full max-w-lg shadow-2xl relative transform transition-all scale-100 sm:h-auto flex flex-col">
            <div
                class="p-5 border-b border-gray-100 flex justify-between items-center bg-gradient-to-r from-indigo-50 to-purple-50 rounded-t-2xl">
                <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                    <i class="fas fa-user-plus text-indigo-600"></i> Ajukan Cuti Baru
                </h2>
                <button id="closeAddModal" class="text-gray-400 hover:text-gray-600"><i
                        class="fas fa-times"></i></button>
            </div>

            <div class="p-6 overflow-y-auto flex-grow">
                <form action="{{ route('ess-coordinator-leave-store') }}" method="POST" class="space-y-4">
                    @csrf

                    <!-- Info Box -->
                    <div
                        class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 text-xs text-yellow-800 flex items-start gap-2">
                        <i class="fas fa-exclamation-triangle mt-0.5"></i>
                        <span>Pengajuan ini akan menunggu persetujuan Admin.</span>
                    </div>

                    <!-- SELECT EMPLOYEE -->
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1.5">Pilih Karyawan</label>

                        <div class="relative mb-2">
                            <input type="text" id="employeeSearchAdd" placeholder="Cari nama karyawan..."
                                class="w-full rounded-lg border-gray-300 p-2.5 pl-9 text-sm focus:ring-2 focus:ring-indigo-500">
                            <i class="fas fa-search absolute left-3 top-3 text-gray-400 text-xs"></i>
                        </div>

                        <div
                            class="border border-gray-200 rounded-xl overflow-hidden max-h-48 overflow-y-auto custom-scrollbar bg-gray-50 p-1">
                            @foreach ($employees as $emp)
                                @php
                                    $empQuota = $quotas->get($emp->id);
                                    $joinDate = \Carbon\Carbon::parse($emp->join_date);
                                    $isEligible = \Carbon\Carbon::now()->gte($joinDate->copy()->addYear());
                                @endphp
                                <label
                                    class="employee-item-add flex items-center p-2.5 hover:bg-white rounded-lg cursor-pointer transition border border-transparent hover:border-gray-200 group">
                                    <input type="radio" name="employee_id" value="{{ $emp->id }}"
                                        class="employee-radio-add w-4 h-4 text-indigo-600 border-gray-300 focus:ring-indigo-500 mr-3"
                                        required>
                                    <div>
                                        <div class="text-sm font-bold text-gray-700 group-hover:text-indigo-700">
                                            {{ $emp->name }}
                                        </div>
                                        <div class="text-[10px] text-gray-500">
                                            {{ $emp->position->name ?? '-' }}
                                        </div>
                                        <div class="text-[10px] mt-0.5">
                                            @if ($isEligible && $empQuota)
                                                <span class="text-emerald-600 font-semibold">
                                                    Sisa cuti: {{ $empQuota->remaining_days }}/{{ $empQuota->total_quota }}
                                                    hari
                                                </span>
                                            @elseif(!$isEligible)
                                                <span class="text-red-500">
                                                    Belum eligible
                                                </span>
                                            @else
                                                <span class="text-gray-400">Kuota penuh</span>
                                            @endif
                                        </div>
                                    </div>
                                </label>
                            @endforeach

                            @if ($employees->isEmpty())
                                <p class="text-center text-xs text-gray-400 py-4">Tidak ada karyawan ditemukan.</p>
                            @endif
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1.5">Tanggal Mulai</label>
                            <input type="date" name="start_date"
                                class="w-full rounded-xl border-gray-300 shadow-sm p-3 border focus:ring-2 focus:ring-indigo-500 text-sm"
                                required>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1.5">Tanggal
                                Selesai</label>
                            <input type="date" name="end_date"
                                class="w-full rounded-xl border-gray-300 shadow-sm p-3 border focus:ring-2 focus:ring-indigo-500 text-sm"
                                required>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1.5">Jenis Cuti</label>
                        <select name="type"
                            class="w-full rounded-xl border-gray-300 shadow-sm p-3 border focus:ring-2 focus:ring-indigo-500 bg-white text-sm"
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
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1.5">Catatan/Alasan</label>
                        <textarea name="note" rows="3"
                            class="w-full rounded-xl border-gray-300 shadow-sm p-3 border focus:ring-2 focus:ring-indigo-500 text-sm"
                            placeholder="Jelaskan alasan cuti..." required></textarea>
                    </div>

                    <button type="submit"
                        class="w-full py-3 bg-indigo-600 text-white font-bold rounded-xl shadow-lg hover:bg-indigo-700 transition">
                        <i class="fas fa-paper-plane mr-2"></i> Ajukan Permintaan
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- EDIT MODAL -->
    <div id="editModal"
        class="hidden fixed inset-0 bg-gray-900/60 backdrop-blur-sm flex items-end sm:items-center justify-center z-50 p-0 sm:p-4">
        <div
            class="bg-white rounded-t-2xl sm:rounded-2xl w-full max-w-lg shadow-2xl relative transform transition-all scale-100">
            <div
                class="p-5 border-b border-gray-100 flex justify-between items-center bg-gradient-to-r from-blue-50 to-cyan-50 rounded-t-2xl">
                <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                    <i class="fas fa-edit text-blue-600"></i> Edit Pengajuan Cuti
                </h2>
                <button id="closeModal" class="text-gray-400 hover:text-gray-600"><i
                        class="fas fa-times"></i></button>
            </div>

            <div class="p-6 overflow-y-auto">
                <form id="editForm" method="post" action="" class="space-y-4">
                    @csrf @method('put')

                    <!-- Info -->
                    <div
                        class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-xs text-blue-700 flex items-start gap-2">
                        <i class="fas fa-info-circle mt-0.5"></i>
                        <span>Anda hanya dapat mengedit pengajuan yang masih berstatus <strong>Pending</strong>.</span>
                    </div>

                    <!-- Employee Info (Read-only) -->
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1.5">Karyawan</label>
                        <input type="text" id="empName"
                            class="w-full rounded-xl bg-gray-50 border-gray-200 text-gray-700 text-sm font-medium p-3"
                            disabled>
                        <input type="hidden" name="employee_id" id="empId">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1.5">Tanggal Mulai</label>
                            <input type="date" id="editStartDate" name="start_date"
                                class="w-full rounded-xl border-gray-300 shadow-sm p-3 border focus:ring-2 focus:ring-blue-500 text-sm"
                                required>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1.5">Tanggal
                                Selesai</label>
                            <input type="date" id="editEndDate" name="end_date"
                                class="w-full rounded-xl border-gray-300 shadow-sm p-3 border focus:ring-2 focus:ring-blue-500 text-sm"
                                required>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1.5">Jenis Cuti</label>
                        <select id="editType" name="type"
                            class="w-full rounded-xl border-gray-300 shadow-sm p-3 border focus:ring-2 focus:ring-blue-500 bg-white text-sm"
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
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1.5">Catatan/Alasan</label>
                        <textarea id="editNote" name="note" rows="3"
                            class="w-full rounded-xl border-gray-300 shadow-sm p-3 border focus:ring-2 focus:ring-blue-500 text-sm" required></textarea>
                    </div>

                    <!-- Hidden status field (kept as pending) -->
                    <input type="hidden" name="status" value="pending">

                    <button type="submit"
                        class="w-full py-3.5 bg-blue-600 text-white font-bold rounded-xl shadow-lg hover:bg-blue-700 transition">
                        <i class="fas fa-save mr-2"></i> Simpan Perubahan
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        $(document).ready(function() {
            const addModal = $('#addModal');
            const editModal = $('#editModal');

            // Open Add Modal
            $('#addBtn').click(function() {
                addModal.removeClass('hidden').addClass('flex');
                $('body').addClass('overflow-hidden');
            });

            // Close Add Modal
            $('#closeAddModal').click(function() {
                addModal.addClass('hidden').removeClass('flex');
                $('body').removeClass('overflow-hidden');
            });

            // Employee Search - Add Modal
            $('#employeeSearchAdd').on('keyup', function() {
                var value = $(this).val().toLowerCase();
                $(".employee-item-add").filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                });
            });

            // Open Edit Modal
            $('.editBtn').click(function() {
                const btn = $(this);
                const id = btn.data('id');

                $('#empName').val(btn.data('name'));
                $('#empId').val(btn.data('employee'));
                $('#editStartDate').val(btn.data('start_date'));
                $('#editEndDate').val(btn.data('end_date'));
                $('#editType').val(btn.data('type'));
                $('#editNote').val(btn.data('note'));

                $('#editForm').attr('action', `/coordinator/leave/${id}`);

                editModal.removeClass('hidden').addClass('flex');
                $('body').addClass('overflow-hidden');
            });

            // Close Edit Modal
            $('#closeModal').click(function() {
                editModal.addClass('hidden').removeClass('flex');
                $('body').removeClass('overflow-hidden');
            });

            // Close modal on outside click
            $(window).click(function(e) {
                if ($(e.target).is(editModal)) {
                    editModal.addClass('hidden').removeClass('flex');
                    $('body').removeClass('overflow-hidden');
                }
                if ($(e.target).is(addModal)) {
                    addModal.addClass('hidden').removeClass('flex');
                    $('body').removeClass('overflow-hidden');
                }
            });
        });
    </script>

    @include('layout.loading')
    @include('sweetalert::alert')

</body>

</html>