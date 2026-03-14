<!DOCTYPE html>
<html lang="en">

<head>
    <title>Riwayat Penggajian</title>
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

<body class="bg-gray-50">
    @include('layout.sidebar')

    <main class="md:ml-64 xl:ml-72 2xl:ml-72">
        @include('layout.navbar')
        <div class="p-6 space-y-6">

            <!-- Header -->
            <div
                class="md:flex justify-between items-center bg-white p-5 rounded-xl shadow-sm border border-gray-100 space-y-2 md:space-y-0">
                <div>
                    <h1 class="font-bold text-2xl text-gray-800 flex items-center gap-2">
                        <i class="fas fa-money-check-alt text-indigo-600"></i> Riwayat Penggajian
                    </h1>
                    <p class="text-sm text-gray-500">Daftar periode penggajian yang telah dibuat</p>
                </div>
                <x-button href="{{ route('createpayroll') }}" size="lg" variant="primary"
                    icon="plus">Jalankan</x-button>
            </div>

            @if (session('success'))
                <div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50 flex items-center gap-2" role="alert">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                            clip-rule="evenodd" />
                    </svg>
                    <span class="font-medium">Success!</span> {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50" role="alert">
                    <span class="font-medium">Error!</span> {{ $errors->first() }}
                </div>
            @endif

            <!-- Table -->
            <div class="w-full bg-white rounded-xl shadow-md border border-gray-100">
                <div class="p-5 overflow-auto">
                    <table id="myTable" class="w-full text-left">
                        <thead class="bg-gray-100 text-gray-600 text-sm leading-normal">
                            <tr>
                                <th class="p-4 font-bold rounded-tl-lg text-center" width="5%">No</th>
                                <th class="p-4 font-bold">
                                    Periode
                                </th>
                                <th class="p-4 font-bold">
                                    Total Cabang
                                </th>
                                <th class="p-4 font-bold">
                                    Total Pengeluaran
                                </th>
                                <th class="p-4 font-bold text-center rounded-tr-lg">
                                    Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-700 text-sm divide-y divide-gray-200">
                            @php $no = 1; @endphp
                            @foreach ($batches as $batch)
                                <tr class="hover:bg-gray-50 transition duration-150">
                                    <td class="p-4 font-medium">{{ $no++ }}</td>
                                    <td class="p-4">
                                        <div class="flex justify-center items-center flex-col">
                                            <a href="{{ route('periodPayrollBranch', ['start' => $batch->pay_period_start, 'end' => $batch->pay_period_end]) }}"
                                                class="text-lg font-bold text-indigo-600 mb-1">
                                                {{ \Carbon\Carbon::parse($batch->pay_period_start)->format('d M Y') }} -
                                                {{ \Carbon\Carbon::parse($batch->pay_period_end)->format('d M Y') }}
                                            </a>
                                            <span class="text-xs text-gray-400">
                                                {{ \Carbon\Carbon::parse($batch->created_at)->diffForHumans() }}</span>
                                        </div>
                                    </td>
                                    <td class="p-4">
                                        <div class="flex justify-center">
                                            <span class="font-bold text-gray-800 text-base">
                                                {{ $batch->total_branches }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="p-4 text-center">
                                        <div class="font-bold text-gray-800 text-base">
                                            Rp {{ number_format($batch->total_spent, 0, ',', '.') }}
                                        </div>
                                    </td>

                                    <td class="p-4">
                                        <div class="flex justify-center items-center gap-2">

                                            <!-- TOMBOL EXPORT EXCEL -->
                                            <a href="{{ route('payrollExport', ['start' => $batch->pay_period_start, 'end' => $batch->pay_period_end]) }}"
                                                class="w-10 h-10 flex items-center justify-center bg-green-500 text-white rounded-lg shadow hover:bg-green-600 hover:scale-105 transition"
                                                title="Download Excel Rekap">
                                                <i class="fa-solid fa-download text-lg"></i>
                                            </a>

                                            <!-- TOMBOL EXPORT REPORT EXCEL -->
                                            <a href="{{ route('payrollReportExport', ['start' => $batch->pay_period_start, 'end' => $batch->pay_period_end]) }}"
                                                class="w-10 h-10 flex items-center justify-center bg-blue-500 text-white rounded-lg shadow hover:bg-blue-600 hover:scale-105 transition"
                                                title="Download Laporan Lengkap">
                                                <i class="fas fa-chart-pie text-lg"></i>
                                            </a>

                                            <!-- TOMBOL DELETE BATCH -->
                                            <form action="{{ route('delpayrollBatch') }}" method="POST"
                                                class="inline-block delete-batch-form delete-confirm">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="start" value="{{ $batch->pay_period_start }}">
                                                <input type="hidden" name="end" value="{{ $batch->pay_period_end }}">

                                                <button type="submit"
                                                    class="w-10 h-10 flex items-center justify-center bg-red-500 text-white rounded-lg shadow hover:bg-red-600 hover:scale-105 transition"
                                                    title="Delete Entire Period">
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

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="//cdn.datatables.net/2.0.2/js/dataTables.min.js"></script>
    <script>
        $(document).ready(function () {
            new DataTable('#myTable', {});

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