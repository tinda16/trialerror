<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Folder;
use App\Models\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class FileController extends Controller
{
    public function index()
    {
        $categories = Category::all();
        return view('file.index', compact('categories'));
    }

    public function create()
    {
        $folders    = Folder::all();
        $categories = Category::all();

        return view('file.create', compact('folders', 'categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'file'        => 'required|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,jpg,jpeg,png',
            'category_id' => 'required|exists:categories,id',
            'folder_id'   => 'nullable|exists:folders,id',
        ]);

        $filePath    = null;
        $sizeInBytes = null;

        if ($request->hasFile('file')) {
            $uploaded    = $request->file('file');
            $filePath    = $uploaded->store('uploads', 'public');
            $sizeInBytes = $uploaded->getSize();
        }

        File::create([
            'title'       => $request->title,
            'file_path'   => $filePath,
            'category_id' => $request->category_id,
            'folder_id'   => $request->folder_id,
            'user_id'     => Auth::id(),
            'size'        => $sizeInBytes,
        ]);

        return redirect()->back()->with('success', 'File berhasil diunggah.');
    }

    /**
     * Halaman kategori (files + folders) berdasarkan category id.
     *
     * - Manager  : melihat semua file dalam kategori.
     * - Non-manager (staff, dll):
     *      - melihat file miliknya sendiri, berapa pun status approval-nya; dan
     *      - file milik orang lain yang sudah berstatus "approved".
     */
    public function show($id)
    {
        $category = Category::findOrFail($id);
        $folders  = Folder::where('category_id', $id)->get();

        $files = File::where('category_id', $id)
            ->when(!Auth::user()->hasRole('manager'), function ($query) {
                $userId = Auth::id();

                $query->where(function ($inner) use ($userId) {
                    $inner->where('approval_status', 'approved')
                          ->orWhere('user_id', $userId);
                });
            })
            ->get();

        return view('category.show', compact('category', 'folders', 'files'));
    }

    public function edit(File $file)
    {
        $folders = Folder::where('category_id', $file->category_id)->get();
        return view('file.edit', compact('file', 'folders'));
    }

    public function update(Request $request, File $file)
    {
        if ($file->user_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'title'     => 'required|string|max:255',
            'folder_id' => 'nullable|exists:folders,id',
        ]);

        $file->title     = $request->title;
        $file->folder_id = $request->folder_id;
        $file->save();

        return redirect()->back()->with('success', 'File berhasil diperbarui.');
    }

    public function destroy(File $file)
    {
        if ($file->user_id !== Auth::id()) {
            abort(403);
        }

        if ($file->file_path && Storage::disk('public')->exists($file->file_path)) {
            Storage::disk('public')->delete($file->file_path);
        }

        $file->delete();

        return redirect()->back()->with('success', 'File berhasil dihapus.');
    }

    /**
     * Buka file di tab baru (stream dari storage).
     */
    public function view(File $file)
    {
        if ($file->user_id !== Auth::id()) {
            abort(403);
        }

        if (!$file->file_path || !Storage::disk('public')->exists($file->file_path)) {
            abort(404, 'File tidak ditemukan.');
        }

        $fullPath = Storage::disk('public')->path($file->file_path);
        return response()->file($fullPath);
    }

    /**
     * Download file dengan nama sesuai judul (+ekstensi jika belum ada).
     */
    public function download(File $file)
    {
        if ($file->user_id !== Auth::id()) {
            abort(403);
        }

        if (!$file->file_path || !Storage::disk('public')->exists($file->file_path)) {
            abort(404, 'File tidak ditemukan.');
        }

        $extension      = pathinfo($file->file_path, PATHINFO_EXTENSION);
        $downloadName   = $file->title;
        $lowerTitle     = strtolower($downloadName);
        $expectedSuffix = '.' . strtolower($extension);

        if (!str_ends_with($lowerTitle, $expectedSuffix)) {
            $downloadName .= $expectedSuffix;
        }

        return Storage::disk('public')->download($file->file_path, $downloadName);
    }

    /**
     * Halaman "My Files" (list file milik user + filter/sort).
     * Tambahan: $favoritedMap untuk render ikon ⭐ di Blade.
     */
    public function myFiles(Request $request)
    {
        $query = File::with(['category', 'folder'])
            ->where('user_id', Auth::id());

        // Filter
        if ($request->filled('search')) {
            $query->where('title', 'like', '%'.$request->search.'%');
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereBetween('created_at', [
                $request->date_from.' 00:00:00',
                $request->date_to.' 23:59:59',
            ]);
        }

        // Sort
        $sort        = $request->get('sort', 'created_at');
        $dir         = $request->get('dir', 'desc');
        $allowedSort = ['title', 'created_at', 'size'];
        if (!in_array($sort, $allowedSort)) $sort = 'created_at';
        if (!in_array($dir, ['asc', 'desc'])) $dir = 'desc';

        // Data
        $files      = $query->orderBy($sort, $dir)->paginate(12)->withQueryString();
        $categories = Category::all();

        // Map favorit untuk ikon bintang
        // (asumsi relasi belongsToMany di User: favoriteFiles())
        $favoritedIds = Auth::user()->favoriteFiles()->pluck('files.id')->toArray();
        $favoritedMap = array_fill_keys($favoritedIds, true);

        return view('files.myfiles', compact('files', 'categories', 'sort', 'dir', 'favoritedMap'));
    }

    /**
     * Toggle favorit (AJAX). Hanya pemilik file yang boleh ubah.
     */
    public function toggleFavorite(File $file)
    {
        if ($file->user_id !== Auth::id()) {
            abort(403);
        }

        $changed = Auth::user()->favoriteFiles()->toggle($file->id);
        $status  = !empty($changed['attached']) ? 'added' : 'removed';

        return response()->json(['status' => $status]);
    }

    /**
     * Halaman daftar favorit user.
     */
    public function favorites(Request $request)
    {
        $files = Auth::user()
            ->favoriteFiles()
            ->with(['category', 'folder'])
            ->latest('favorites.created_at')
            ->paginate(12);

        // (Opsional) kalau view ingin tetap pakai kondisi yang sama
        $favoritedIds = Auth::user()->favoriteFiles()->pluck('files.id')->toArray();
        $favoritedMap = array_fill_keys($favoritedIds, true);

        return view('files.favorites', compact('files', 'favoritedMap'));
    }
}
