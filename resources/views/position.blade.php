<!DOCTYPE html>
<html lang="en">

<head>
    <title>Manajemen Jabatan</title>
    @include('layout.head')
    <link href="//cdn.datatables.net/2.0.2/css/dataTables.dataTables.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    
    <style>
        .dataTables_wrapper .dataTables_length select { padding-right: 2rem; border-radius: 0.5rem; }
        .dataTables_wrapper .dataTables_filter input { padding: 0.5rem; border-radius: 0.5rem; border: 1px solid #d1d5db; }
        table.dataTable.no-footer { border-bottom: 1px solid #e5e7eb; }
    </style>
</head>

<body class="bg-gray-50 font-sans">
    @include('layout.sidebar')

    <main class="md:ml-64 xl:ml-72 2xl:ml-72">
        @include('layout.navbar')
        <div class="p-6 space-y-6">

            <!-- Header -->
            <div class="md:flex justify-between items-center bg-white p-5 rounded-xl shadow-sm border border-gray-100 space-y-2 md:space-y-0">
                <div>
                    <h1 class="font-bold text-2xl text-gray-800 flex items-center gap-2">
                        <i class="fas fa-id-badge text-slate-600"></i> Daftar Jabatan
                    </h1>
                    <p class="text-sm text-gray-500 mt-1">Kelola jabatan dan gaji default</p>
                </div>
                <x-button id="addBtn" size="lg" variant="primary" class="bg-slate-700 hover:bg-slate-800 shadow-md" icon="plus">Tambah</x-button>
            </div>

            <!-- Table -->
            <div class="w-full bg-white rounded-xl shadow-md border border-gray-100">
                <div class="p-5 overflow-auto">
                    <table id="myTable" class="w-full text-left">
                        <thead class="bg-gray-100 text-gray-600 text-sm leading-normal">
                            <tr>
                                <th class="p-4 font-bold rounded-tl-lg text-center" width="5%">No</th>
                                <th class="p-4 font-bold">Nama</th>
                                <th class="p-4 font-bold">Kategori</th>
                                <th class="p-4 font-bold text-right">Gaji</th>
                                <th class="p-4 font-bold text-center rounded-tr-lg" width="15%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-700 text-sm">
                            @php $no = 1; @endphp
                            @foreach ($positions as $item)
                                <tr class="hover:bg-gray-50 transition duration-150">
                                    <td class="p-4 font-medium text-center">{{ $no++ }}</td>
                                    <td class="p-4 space-y-1">

                                         <div class="font-bold text-gray-900 text-base group-hover:text-cyan-600">{{ $item->name }}</div>
                                            <div class="text-xs text-gray-400">Created:
                                                {{ $item->created_at ? $item->created_at->format('Y-m-d') : '-' }}</div>
                                    </td> 
                                       <td class="p-4">
                                        <span
                                            class="bg-cyan-100 text-cyan-800 text-xs px-3 py-1 rounded-full font-bold border border-cyan-200 uppercase">
                                            {{ str_replace('_', ' ', $item->category ?? 'General') }}
                                        </span>
                                        
                                        @if($item->is_head)
                                            <span class="bg-indigo-100 text-indigo-700 text-[10px] mx-2 px-2 py-0.5 rounded border border-indigo-200 w-fit font-bold">
                                                <i class="fas fa-crown mr-1"></i> HEAD / COORD
                                            </span>
                                        @endif
                                    </td>
                                    <td class="p-4 font-mono text-slate-600 ">
                                        Rp {{ number_format($item->base_salary_default, 0, ',', '.') }}
                                    </td>
                                    <td class="p-4">
                                        <div class="flex justify-center items-center gap-2">
                                            <button 
                                                class="editBtn w-10 h-10 flex items-center justify-center bg-blue-500 text-white rounded-lg shadow hover:bg-blue-600 hover:scale-105 transition"
                                                data-id="{{ $item->id }}"
                                                data-name="{{ $item->name }}"
                                                data-category="{{ $item->category }}"
                                                data-salary="{{ $item->base_salary_default }}"
                                                data-is-head="{{ $item->is_head }}"
                                                title="Edit">
                                                <i class="fas fa-edit text-lg"></i>
                                            </button>

                                            <form method="post" action="{{ route('desposition', $item->id) }}" class="inline deleteForm">
                                                @csrf @method('delete')
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

    <!-- ADD MODAL -->
    <div id="addModal" class="hidden fixed inset-0 bg-gray-900/60 backdrop-blur-sm flex items-center justify-center z-50 overflow-y-auto px-4 py-6">
        <div class="bg-white rounded-2xl p-8 w-full max-w-lg shadow-2xl relative transform transition-all scale-100">
            <button id="closeAddModal" class="absolute top-5 right-5 text-gray-400 hover:text-gray-600 transition"><i class="fas fa-times text-xl"></i></button>
            <h2 class="text-2xl font-bold mb-6 text-gray-800 flex items-center gap-2">
                <i class="fas fa-id-badge text-slate-600"></i> Tambah 
            </h2>

            <form action="{{ route('postposition') }}" method="POST" class="space-y-5">
                @csrf
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Nama</label>
                    <input type="text" name="name" class="w-full rounded-lg border-gray-300 shadow-sm p-2.5 border focus:ring-2 focus:ring-slate-500" required placeholder="e.g. Senior Barista">
                </div>

                <div class="grid grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Kategori</label>
                        <select name="category" class="w-full rounded-lg border-gray-300 shadow-sm p-2.5 border focus:ring-2 focus:ring-slate-500" required>
                           <option value="general">Umum</option>
                            <option value="food_beverage">Makanan & Minuman</option>
                            <option value="retail">Retail</option>
                            <option value="hospitality">Perhotelan</option>
                            <option value="education">Pendidikan</option>
                            <option value="creative">Kreatif</option>
                            <option value="health_beauty">Kesehatan & Kecantikan</option>
                            <option value="logistics">Logistik</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Gaji Default (Rp)</label>
                        <input type="text" name="base_salary_default" value="0" class="currency w-full rounded-lg border-gray-300 shadow-sm p-2.5 border focus:ring-2 focus:ring-slate-500" placeholder="0">
                    </div>
                </div>

                <div class="bg-indigo-50 p-3 rounded-lg border border-indigo-100">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="is_head" value="1" class="w-5 h-5 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500">
                        <div>
                            <span class="block text-sm font-bold text-indigo-800">Apakah Koordinator / Kepala?</span>
                            <span class="block text-xs text-indigo-500">Ijinkan akses ESS Koordinator.</span>
                        </div>
                    </label>
                </div>

                <x-button type="submit" variant="primary" icon="save" class="w-full bg-slate-700 hover:bg-slate-800 justify-center">Simpan</x-button>
            </form>
        </div>
    </div>

    <!-- EDIT MODAL -->
    <div id="editModal" class="hidden fixed inset-0 bg-gray-900/60 backdrop-blur-sm flex items-center justify-center z-50 overflow-y-auto px-4 py-6">
        <div class="bg-white rounded-2xl p-8 w-full max-w-lg shadow-2xl relative transform transition-all scale-100">
            <button id="closeModal" class="absolute top-5 right-5 text-gray-400 hover:text-gray-600 transition"><i class="fas fa-times text-xl"></i></button>
            <h2 class="text-2xl font-bold mb-6 text-gray-800 flex items-center gap-2">
                <i class="fas fa-edit text-blue-600"></i> Edit
            </h2>

            <form id="editForm" method="POST" class="space-y-5">
                @csrf @method('PUT')
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Nama</label>
                    <input type="text" id="editName" name="name" class="w-full rounded-lg border-gray-300 shadow-sm p-2.5 border focus:ring-2 focus:ring-blue-500" required>
                </div>

                <div class="grid grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Kategori</label>
                        <select id="editCategory" name="category" class="w-full rounded-lg border-gray-300 shadow-sm p-2.5 border" required>
                            <option value="general">Umum</option>
                            <option value="food_beverage">Makanan & Minuman</option>
                            <option value="retail">Retail</option>
                            <option value="hospitality">Perhotelan</option>
                            <option value="education">Pendidikan</option>
                            <option value="creative">Kreatif</option>
                            <option value="health_beauty">Kesehatan & Kecantikan</option>
                            <option value="logistics">Logistik</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Gaji Default (Rp)</label>
                        <input type="text" id="editSalary" name="base_salary_default" value="0" class="currency w-full rounded-lg border-gray-300 shadow-sm p-2.5 border focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div class="bg-indigo-50 p-3 rounded-lg border border-indigo-100">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" id="editIsHead" name="is_head" value="1" class="w-5 h-5 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500">
                        <div>
                            <span class="block text-sm font-bold text-indigo-800">Apakah Koordinator / Kepala?</span>
                            <span class="block text-xs text-indigo-500">Ijinkan akses ESS Koordinator.</span>
                        </div>
                    </label>
                </div>

                <x-button type="submit" variant="primary" icon="save" class="w-full bg-blue-600 hover:bg-blue-700 justify-center">Perbarui</x-button>
            </form>
        </div>
    </div>

    <!-- SCRIPTS -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="//cdn.datatables.net/2.0.2/js/dataTables.min.js"></script>
    <script>
        $(document).ready(function() {
            // Init DataTable
            new DataTable('#myTable', {
            });

            // Currency Formatter
            function formatCurrency(value) {
                let rawValue = value.replace(/\D/g, '');
                if (rawValue === '') return '';
                let numberValue = parseInt(rawValue, 10);
                return numberValue.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
            }

            $('.currency').on('input', function() {
                let val = $(this).val();
                $(this).val(formatCurrency(val));
            });

            $('form').on('submit', function() {
                $('.currency').each(function() {
                    let cleanVal = $(this).val().replace(/\./g, '');
                    $(this).val(cleanVal);
                });
            });

            // Modal Logic
            $('#addBtn').click(() => $('#addModal').removeClass('hidden'));
            $('#closeAddModal').click(() => $('#addModal').addClass('hidden'));
            $('#closeModal').click(() => $('#editModal').addClass('hidden'));

            $(window).click((e) => {
                if (e.target === $('#addModal')[0]) $('#addModal').addClass('hidden');
                if (e.target === $('#editModal')[0]) $('#editModal').addClass('hidden');
            });

            // Edit Logic
            $(document).on('click', '.editBtn', function() {
                const btn = $(this);
                $('#editName').val(btn.data('name'));
                $('#editCategory').val(btn.data('category'));
                
                // Format Salary
                let rawSalary = btn.data('salary');
                let salaryStr = String(rawSalary).split('.')[0];
                $('#editSalary').val(formatCurrency(salaryStr));
                $('#editIsHead').prop('checked', btn.data('is-head') == 1);

                $('#editForm').attr('action', `/position/${btn.data('id')}/update`);
                $('#editModal').removeClass('hidden');
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