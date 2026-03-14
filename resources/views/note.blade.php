<!DOCTYPE html>
<html lang="en">

<head>
    <title>Manajemen Catatan</title>
    @include('layout.head')
    <link href="//cdn.datatables.net/2.0.2/css/dataTables.dataTables.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        .dataTables_wrapper .dataTables_length select { padding-right: 2rem; border-radius: 0.5rem; }
        .dataTables_wrapper .dataTables_filter input { padding: 0.5rem; border-radius: 0.5rem; border: 1px solid #d1d5db; }
        table.dataTable.no-footer { border-bottom: 1px solid #e5e7eb; }

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
                        <i class="fas fa-sticky-note text-teal-600"></i> Catatan Karyawan
                    </h1>
                    <p class="text-sm text-gray-500">Kelola catatan, peringatan, dan penghargaan</p>
                </div>
                <x-button id="addBtn" size="lg" variant="primary" class="bg-teal-600 hover:bg-teal-700 shadow-md" icon="plus">Tambah</x-button>
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
                                <th class="p-4 font-bold">Tipe</th>
                                <th class="p-4 font-bold">Konteks</th>
                                <th class="p-4 font-bold text-center rounded-tr-lg" width="15%">Aksi</th>
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
                                        <div class="font-bold text-gray-900 text-base group-hover:text-cyan-600">{{ $item->employee->name ?? 'N/A' }}</div>
                                        <div class="text-xs text-gray-400">{{ $item->employee->position->name ?? '' }}</div>
                                    </td>
                                    <td class="p-4">
                                        @php
                                            $typeColor = match($item->type) {
                                                'warning' => 'bg-red-100 text-red-700 border-red-200',
                                                'reward' => 'bg-green-100 text-green-700 border-green-200',
                                                'performance' => 'bg-blue-100 text-blue-700 border-blue-200',
                                                'general' => 'bg-gray-100 text-gray-700 border-gray-200',
                                                default => 'bg-gray-100 text-gray-600'
                                            };
                                        @endphp
                                        <span class="{{ $typeColor }} px-3 py-1 rounded-full text-xs font-bold border uppercase shadow-sm">
                                            {{ ucfirst($item->type) }}
                                        </span>
                                    </td>
                                    <td class="p-4 text-xs text-gray-600 italic max-w-xs truncate">
                                        "{{ \Illuminate\Support\Str::limit($item->content, 40) }}"
                                    </td>
                                    <td class="p-4">
                                        <div class="flex justify-center items-center gap-2">
                                            {{-- Edit Button --}}
                                            <button class="editBtn w-10 h-10 flex items-center justify-center bg-blue-500 text-white rounded-lg shadow hover:bg-blue-600 hover:scale-105 transition"
                                                data-id="{{ $item->id }}"
                                                data-employee="{{ $item->employee_id }}"
                                                data-date="{{ $item->note_date }}"
                                                data-type="{{ $item->type }}"
                                                data-content="{{ $item->content }}"
                                                title="Edit">
                                                <i class="fas fa-edit text-lg"></i>
                                            </button>

                                            {{-- Delete Button --}}
                                            <form method="post" action="{{ route('delnote', ['id' => $item->id]) }}" class="inline deleteForm">
                                                @csrf
                                                @method('delete')
                                                <button type="button" class="delete-confirm w-10 h-10 flex items-center justify-center bg-red-500 text-white rounded-lg shadow hover:bg-red-600 hover:scale-105 transition" title="Delete">
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

    <!-- ADD MODAL -->
    <div id="addModal" class="hidden fixed inset-0 bg-gray-900/60 backdrop-blur-sm flex items-center justify-center z-50 overflow-y-auto px-4 py-6">
        <div class="bg-white rounded-2xl p-8 w-full max-w-lg shadow-2xl relative transform transition-all scale-100">
            <button id="closeAddModal" class="absolute top-5 right-5 text-gray-400 hover:text-gray-600 transition">
                <i class="fas fa-times text-xl"></i>
            </button>
            <h2 class="text-2xl font-bold mb-6 text-gray-800 flex items-center gap-2">
                <i class="fas fa-sticky-note text-teal-600"></i> Tambah
            </h2>

            <form id="addForm" method="post" action="{{ route('postnote') }}" enctype="multipart/form-data" class="space-y-5">
                @csrf @method('post')

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Karyawan</label>
                    
                    <div class="relative mb-2">
                        <input type="text" id="employeeSearchAdd" placeholder="Cari nama karyawan..."
                            class="w-full rounded-lg border-gray-300 p-2.5 pl-10 text-sm focus:ring-2 focus:ring-teal-500">
                        <i class="fas fa-search absolute left-3 top-3 text-gray-400 text-sm"></i>
                    </div>

                    <div class="border border-gray-200 rounded-xl overflow-hidden max-h-48 overflow-y-auto custom-scrollbar bg-gray-50 p-1">
                        @foreach ($employee as $emp)
                            <label
                                class="employee-item-add flex items-center p-2.5 hover:bg-white rounded-lg cursor-pointer transition border border-transparent hover:border-gray-200 group">
                                <input type="radio" name="employee_id" value="{{ $emp->id }}"
                                    class="employee-radio-add w-4 h-4 text-teal-600 border-gray-300 focus:ring-teal-500 mr-3" required>
                                <div>
                                    <div class="text-sm font-bold text-gray-700 group-hover:text-teal-700">
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
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Tanggal</label>
                        <input type="date" name="note_date" class="w-full rounded-lg border-gray-300 shadow-sm p-2.5 border focus:ring-2 focus:ring-teal-500" required>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Tipe</label>
                        <select name="type" class="w-full rounded-lg border-gray-300 shadow-sm p-2.5 border focus:ring-2 focus:ring-teal-500" required>
                            <option value="general">General</option>
                            <option value="performance">Performance</option>
                            <option value="warning">Warning</option>
                            <option value="reward">Reward</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Konteks / Deskripsi</label>
                    <textarea name="content" rows="4" class="w-full rounded-lg border-gray-300 shadow-sm p-2.5 border focus:ring-2 focus:ring-teal-500" placeholder="Write note details here..." required></textarea>
                </div>

                <x-button type="submit" variant="primary" icon="save" class="w-full bg-slate-700 hover:bg-slate-800 justify-center">Simpan</x-button>
            </form>
        </div>
    </div>

    <!-- EDIT MODAL -->
    <div id="editModal" class="hidden fixed inset-0 bg-gray-900/60 backdrop-blur-sm flex items-center justify-center z-50 overflow-y-auto px-4 py-6">
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

                        @if ($employee->isEmpty())
                            <p class="text-center text-xs text-gray-400 py-4">Tidak ada karyawan ditemukan.</p>
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Tanggal</label>
                        <input type="date" id="editDate" name="note_date" class="w-full rounded-lg border-gray-300 shadow-sm p-2.5 border focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Tipe</label>
                        <select id="editType" name="type" class="w-full rounded-lg border-gray-300 shadow-sm p-2.5 border focus:ring-2 focus:ring-blue-500" required>
                            <option value="general">General</option>
                            <option value="performance">Performance</option>
                            <option value="warning">Warning</option>
                            <option value="reward">Reward</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Konteks / Deskripsi</label>
                    <textarea id="editContent" name="content" rows="4" class="w-full rounded-lg border-gray-300 shadow-sm p-2.5 border focus:ring-2 focus:ring-blue-500" required></textarea>
                </div>

                <x-button type="submit" variant="primary" icon="save" class="w-full bg-blue-600 hover:bg-blue-700 justify-center">Perbarui</x-button>
            </form>
        </div>
    </div>

    <!-- SCRIPTS -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <script src="//cdn.datatables.net/2.0.2/js/dataTables.min.js"></script>
    <script>
        $(document).ready(function() {
            // Init DataTable
            new DataTable('#myTable', {});

            // Modal Logic
            const addModal = $('#addModal');
            const editModal = $('#editModal');

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
                $('.employee-radio-edit[value="' + btn.data('employee') + '"]').prop('checked', true);
                
                $('#editDate').val(btn.data('date'));
                $('#editType').val(btn.data('type'));
                $('#editContent').val(btn.data('content'));
                
                $('#editForm').attr('action', `/note/${btn.data('id')}/update`);
                
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
            });

            // Delete Confirm
            $(document).on('click', '.delete-confirm', function(e) {
                e.preventDefault();
                const form = $(this).closest('form');
                Swal.fire({
                    title: 'Hapus?',
                    text: "Anda tidak akan dapat mengembalikan ini!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'Ya, hapus!'
                }).then((result) => { if (result.isConfirmed) form.submit(); });
            });
        });
    </script>

    @include('sweetalert::alert')
    @include('layout.loading')

</body>
</html>