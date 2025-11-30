<?php

namespace App\Http\Controllers;

use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class WorkflowController extends Controller
{
    /**
     * Halaman workflow approval (hanya manager)
     * Menampilkan file yang perlu di-approve
     */
    public function index(Request $request)
    {
        // File yang statusnya 'pending' atau belum ada status approval
        $query = File::with(['category', 'user', 'folder'])
            ->whereIn('approval_status', ['pending', 'submitted']);

        if ($request->filled('search')) {
            $query->where('title', 'like', '%'.$request->search.'%');
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $files = $query->latest()->paginate(15);
        $categories = \App\Models\Category::all();

        return view('workflow.index', compact('files', 'categories'));
    }

    /**
     * Approve file (hanya manager)
     */
    public function approve(Request $request, File $file)
    {
        $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        $file->update([
            'approval_status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'approval_notes' => $request->notes,
        ]);

        return redirect()->back()->with('success', 'File berhasil di-approve.');
    }

    /**
     * Reject file (hanya manager)
     */
    public function reject(Request $request, File $file)
    {
        $request->validate([
            'notes' => 'required|string|max:500',
        ]);

        $file->update([
            'approval_status' => 'rejected',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'approval_notes' => $request->notes,
        ]);

        return redirect()->back()->with('success', 'File berhasil di-reject.');
    }

    /**
     * Halaman untuk staff melihat status approval file mereka
     */
    public function mySubmissions()
    {
        $files = File::with(['category', 'folder'])
            ->where('user_id', Auth::id())
            ->whereIn('approval_status', ['draft', 'pending', 'submitted', 'approved', 'rejected'])
            ->latest()
            ->paginate(15);

        return view('workflow.my-submissions', compact('files'));
    }

    /**
     * Staff submit file untuk approval
     */
    public function submit(File $file)
    {
        if ($file->user_id !== Auth::id()) {
            abort(403);
        }

        $file->update([
            'approval_status' => 'submitted',
        ]);

        return redirect()->back()->with('success', 'File berhasil disubmit untuk approval.');
    }
}
