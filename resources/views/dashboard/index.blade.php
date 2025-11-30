@extends('layouts.app')

@section('content')
<div class="flex">
    <!-- Sidebar (panel "FileB" di kolom tengah halaman dashboard) -->
    <div class="w-1/5 min-h-screen bg-white border-r px-6 py-8">
        <h1 class="text-xl font-bold text-red-700 mb-8">FileB</h1>

        {{-- LIST BARU: tanpa "My Files" & "Favorites" --}}
        <ul class="space-y-4 text-sm font-semibold">
            {{-- Dashboard (tetap ada) --}}
            <li>
                <a href="{{ route('dashboard') }}"
                   class="{{ request()->routeIs('dashboard') ? 'text-red-700' : 'text-gray-700 hover:text-red-700' }}">
                   Dashboard
                </a>
            </li>

            {{-- SAG (tautan ke workflow approval sesuai role) --}}
            @if(auth()->check())
                @if(auth()->user()->hasRole('manager'))
                    <li>
                        <a href="{{ route('workflow.index') }}"
                           class="{{ request()->routeIs('workflow.*') ? 'text-red-700' : 'text-gray-700 hover:text-red-700' }}">
                           SAG
                        </a>
                    </li>
                @elseif(auth()->user()->hasRole('staff'))
                    <li>
                        <a href="{{ route('workflow.my-submissions') }}"
                           class="{{ request()->routeIs('workflow.*') ? 'text-red-700' : 'text-gray-700 hover:text-red-700' }}">
                           SAG
                        </a>
                    </li>
                @endif
            @endif

            {{-- ERP (tautan utama ke area ERP) --}}
            @if (Route::has('customers.index'))
            <li>
                <a href="{{ route('customers.index') }}"
                   class="{{ request()->routeIs('customers.*') ? 'text-red-700' : 'text-gray-700 hover:text-red-700' }}">
                   ERP
                </a>
            </li>
            @endif

            {{-- Customer Data --}}
            @if (Route::has('customers.index'))
            <li>
                <a href="{{ route('customers.index') }}"
                   class="{{ request()->routeIs('customers.*') ? 'text-red-700' : 'text-gray-700 hover:text-red-700' }}">
                   Customer Data
                </a>
            </li>
            @endif

            {{-- Transactions --}}
            @if (Route::has('transactions.index'))
            <li>
                <a href="{{ route('transactions.index') }}"
                   class="{{ request()->routeIs('transactions.*') ? 'text-red-700' : 'text-gray-700 hover:text-red-700' }}">
                   Transactions
                </a>
            </li>
            @endif

            {{-- KPI Dashboard --}}
            @if (Route::has('kpi.index'))
            <li>
                <a href="{{ route('kpi.index') }}"
                   class="{{ request()->routeIs('kpi.*') ? 'text-red-700' : 'text-gray-700 hover:text-red-700' }}">
                   KPI Dashboard
                </a>
            </li>
            @endif

            {{-- Account / My Profile --}}
            @if (Route::has('profile.edit'))
            <li class="pt-2 border-t border-gray-100"></li>
            <li class="text-[11px] uppercase tracking-wider text-gray-400">Account</li>
            <li>
                <a href="{{ route('profile.edit') }}"
                   class="{{ request()->routeIs('profile.edit') ? 'text-red-700' : 'text-gray-700 hover:text-red-700' }}">
                   My Profile
                </a>
            </li>
            @endif
        </ul>
    </div>

    <!-- Main Content -->
    <div class="w-4/5 p-8 bg-gray-100">

        <!-- 1. Storage Usage KPI -->
        <div class="mb-6 bg-white p-4 rounded shadow flex flex-col">
            <div class="flex justify-between mb-2">
                <span class="font-medium text-gray-700">Storage Usage</span>
                @php
                    function humanBytes($bytes) {
                        if ($bytes >= 1073741824) return number_format($bytes/1073741824,2).' GB';
                        if ($bytes >= 1048576)    return number_format($bytes/1048576,2).' MB';
                        return number_format($bytes/1024,2).' KB';
                    }
                @endphp
                <span class="text-gray-600 text-sm">
                    {{ humanBytes($usedBytes) }} of {{ humanBytes($quotaBytes) }}
                </span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
                @php
                    $pct = $quotaBytes > 0
                        ? min(100, round($usedBytes / $quotaBytes * 100))
                        : 0;
                @endphp
                <div class="bg-red-600 h-2 rounded-full" style="width: {{ $pct }}%"></div>
            </div>
        </div>

        <!-- Header & Upload Button -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-2xl font-semibold">My Cloud</h2>
                <p class="text-sm text-gray-500">You can securely upload and manage your folders and assets here</p>
            </div>
            <button 
                onclick="toggleModal(true)" 
                class="flex items-center gap-2 bg-gradient-to-r from-[#800000] to-[#b22222] text-white px-6 py-3 rounded-full shadow-lg hover:brightness-110 transition duration-200 ease-in-out text-base font-semibold focus:outline-none focus:ring-2 focus:ring-red-300"
                aria-label="Upload File"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                     viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 4v16m8-8H4" />
                </svg>
                Upload File
            </button>
        </div>

        <!-- 2. Folder Cards dengan badge count -->
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3 mb-10">
            @foreach ($categories as $cat)
                <a href="{{ route('category.show', $cat->id) }}"
                   class="relative bg-gradient-to-br from-[#800000] to-[#b22222] text-white rounded-lg shadow-md transition-transform duration-200 ease-in-out p-2 w-full h-[90px] flex flex-col justify-between hover:brightness-110 hover:scale-105 hover:shadow-xl"
                   aria-label="Kategori {{ $cat->name }}"
                >
                    <div class="absolute -top-1 left-1.5 w-10 h-2.5 bg-red-300/80 rounded-t-md"></div>
                    <div class="flex-1 flex flex-col justify-center items-center text-center z-10">
                        <h3 class="text-sm font-semibold truncate tracking-wide"
                            style="text-shadow: 0 1px 3px rgba(0,0,0,0.4);">
                            {{ $cat->name }}
                        </h3>
                        <span class="mt-1 inline-flex items-center bg-white text-red-800 text-xs font-semibold px-2 py-0.5 rounded-full shadow">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="currentColor"
                                 viewBox="0 0 20 20" aria-hidden="true">
                                <path d="M8 9a3 3 0 100-6 3 3 0 000 6zM2 17a6 6 0 1112 0H2z"/>
                            </svg>
                            {{ $cat->files_count }} file{{ $cat->files_count == 1 ? '' : 's' }}
                        </span>
                    </div>
                </a>
            @endforeach
        </div>

        <!-- 3. Recent Uploads + Search/Filter + Relative time & folder pill -->
        <div>
            <div class="flex justify-between items-center mb-3 flex-wrap gap-2">
                <h4 class="font-semibold text-lg mr-2">Recent Upload Files</h4>
                <form method="GET" action="{{ route('dashboard') }}" class="flex flex-wrap gap-2 items-center">
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Search title"
                           class="border rounded px-3 py-2 text-sm"
                           aria-label="Search by file title"/>
                    <select name="category_id" class="border rounded px-3 py-2 text-sm"
                            aria-label="Filter by category">
                        <option value="">All Categories</option>
                        @foreach ($categories as $c)
                            <option value="{{ $c->id }}" {{ request('category_id') == $c->id ? 'selected' : '' }}>
                                {{ $c->name }}
                            </option>
                        @endforeach
                    </select>
                    <input type="date" name="date_from" value="{{ request('date_from') }}"
                           class="border rounded px-2 py-2 text-sm"
                           aria-label="Date from"/>
                    <input type="date" name="date_to" value="{{ request('date_to') }}"
                           class="border rounded px-2 py-2 text-sm"
                           aria-label="Date to"/>
                    <button type="submit"
                            class="bg-gradient-to-r from-[#800000] to-[#b22222] text-white px-4 py-2 rounded text-xs shadow-sm hover:brightness-110 transition">
                        Filter
                    </button>
                    @if(request()->anyFilled(['search','category_id','date_from','date_to']))
                        <a href="{{ route('dashboard') }}"
                           class="text-gray-600 text-xs underline ml-1">Clear</a>
                    @endif
                    <button class="text-xs border px-2 py-1 rounded text-gray-600 ml-auto">View all</button>
                </form>
            </div>

            <div class="bg-white rounded-lg shadow overflow-x-auto">
                <table class="min-w-full table-auto text-left text-sm">
                    <thead class="border-b bg-gray-50">
                        <tr>
                            <th class="px-6 py-3">Name</th>
                            <th class="px-6 py-3">Owner</th>
                            <th class="px-6 py-3">Category</th>
                            <th class="px-6 py-3">Folder</th>
                            <th class="px-6 py-3">Uploaded</th>
                            <th class="px-6 py-3">Size</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recentFiles as $file)
                            <tr class="border-t hover:bg-gray-50">
                                <td class="px-6 py-2">{{ $file->title }}</td>
                                <td class="px-6 py-2">{{ $file->user->name }}</td>
                                <td class="px-6 py-2">{{ $file->category->name }}</td>
                                <td class="px-6 py-2">
                                    @if($file->folder)
                                        <span class="inline-block bg-gray-200 text-gray-800 text-xs px-2 py-1 rounded-full">
                                            {{ $file->folder->name }}
                                        </span>
                                    @else
                                        &mdash;
                                    @endif
                                </td>
                                <td class="px-6 py-2">{{ $file->created_at->diffForHumans() }}</td>
                                <td class="px-6 py-2">
                                    @php
                                        if (is_numeric($file->size)) {
                                            $bytes = $file->size;
                                            $disp = $bytes >= 1048576
                                                ? number_format($bytes/1048576,2).' MB'
                                                : number_format($bytes/1024,2).' KB';
                                        } else {
                                            $disp = $file->size;
                                        }
                                    @endphp
                                    {{ $disp }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal Upload -->
        <div id="uploadModal" class="fixed inset-0 bg-black/30 flex items-center justify-center z-50 hidden">
            <div class="bg-white w-full max-w-md p-6 rounded shadow">
                <h3 class="text-lg font-semibold mb-4">Upload File</h3>
                <form action="{{ route('files.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm mb-1">File Title</label>
                        <input type="text" name="title" class="w-full border rounded px-3 py-2" required>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm mb-1">Category</label>
                        <select name="category_id" class="w-full border rounded px-3 py-2" required>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm mb-1">File Upload</label>
                        <input type="file" name="file" class="w-full" required>
                    </div>
                    <div class="flex justify-end">
                        <button type="button" onclick="toggleModal(false)" class="mr-2 px-4 py-2 border rounded">Cancel</button>
                        <button type="submit"
                                class="bg-gradient-to-r from-[#800000] to-[#b22222] text-white px-4 py-2 rounded hover:brightness-110 transition">
                            Upload
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            function toggleModal(show) {
                const modal = document.getElementById('uploadModal');
                modal.classList.toggle('hidden', !show);
            }
        </script>
    </div>
</div>
@endsection
