<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Workflow Approval - Manager') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                {{ session('success') }}
            </div>
            @endif

            <!-- Filter -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-4">
                <div class="p-6">
                    <form method="GET" action="{{ route('workflow.index') }}" class="flex gap-4">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari file..." class="flex-1 rounded-md border-gray-300">
                        <select name="category_id" class="rounded-md border-gray-300">
                            <option value="">Semua Kategori</option>
                            @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                            Filter
                        </button>
                        <a href="{{ route('workflow.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                            Reset
                        </a>
                    </form>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">File</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kategori</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Upload By</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($files as $file)
                            <tr>
                                <td class="px-6 py-4">
                                    <div>
                                        <p class="font-semibold">{{ $file->title }}</p>
                                        <p class="text-xs text-gray-500">{{ $file->size }}</p>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">{{ $file->category->name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">{{ $file->user->name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">{{ $file->created_at->format('d/m/Y H:i') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                        {{ ucfirst($file->approval_status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <button onclick="openApprovalModal({{ $file->id }}, '{{ $file->title }}')" class="text-green-600 hover:text-green-900 mr-3">
                                        Approve
                                    </button>
                                    <button onclick="openRejectModal({{ $file->id }}, '{{ $file->title }}')" class="text-red-600 hover:text-red-900 mr-3">
                                        Reject
                                    </button>
                                    <a href="{{ route('files.view', $file->id) }}" target="_blank" class="text-blue-600 hover:text-blue-900">
                                        View
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-gray-500">Tidak ada file yang perlu di-approve</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="mt-4">
                        {{ $files->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Approve Modal -->
    <div id="approveModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <h3 class="text-lg font-semibold mb-4">Approve File</h3>
            <p class="mb-4">Apakah Anda yakin ingin approve file: <strong id="approveFileName"></strong>?</p>
            <form id="approveForm" method="POST">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-2">Catatan (opsional)</label>
                    <textarea name="notes" rows="3" class="w-full border-gray-300 rounded-md"></textarea>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="closeApprovalModal()" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                        Batal
                    </button>
                    <button type="submit" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                        Approve
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Reject Modal -->
    <div id="rejectModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <h3 class="text-lg font-semibold mb-4">Reject File</h3>
            <p class="mb-4">Apakah Anda yakin ingin reject file: <strong id="rejectFileName"></strong>?</p>
            <form id="rejectForm" method="POST">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-2">Alasan Reject <span class="text-red-500">*</span></label>
                    <textarea name="notes" rows="3" required class="w-full border-gray-300 rounded-md"></textarea>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="closeRejectModal()" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                        Batal
                    </button>
                    <button type="submit" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                        Reject
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openApprovalModal(fileId, fileName) {
            document.getElementById('approveFileName').textContent = fileName;
            document.getElementById('approveForm').action = `/workflow/approve/${fileId}`;
            document.getElementById('approveModal').classList.remove('hidden');
        }

        function closeApprovalModal() {
            document.getElementById('approveModal').classList.add('hidden');
        }

        function openRejectModal(fileId, fileName) {
            document.getElementById('rejectFileName').textContent = fileName;
            document.getElementById('rejectForm').action = `/workflow/reject/${fileId}`;
            document.getElementById('rejectModal').classList.remove('hidden');
        }

        function closeRejectModal() {
            document.getElementById('rejectModal').classList.add('hidden');
        }
    </script>
</x-app-layout>
