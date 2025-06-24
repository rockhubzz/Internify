<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Log;
use App\Models\Mahasiswa;
use App\Models\Dosen;
use App\Models\Company;
use Illuminate\Support\Facades\Auth;

class LaporanController extends Controller
{
    public function index()
    {
        $breadcrumb = (object) [
            'title' => 'Laporan',
            'subtitle' => 'Laporan Harian'
        ];
        $user = Auth::user();

        $mahasiswa = Mahasiswa::where('user_id', $user->user_id)->first();

        if ($mahasiswa) {
            $logs = Log::with(['mahasiswa.user', 'dosen.user'])
                ->where('mahasiswa_id', $mahasiswa->mahasiswa_id)
                ->get();
        } else {
            $logs = collect();
        }

        return view('mahasiswa.laporan.index', compact('breadcrumb', 'logs'));
    }

    public function download($id)
{
    $log = Log::findOrFail($id);

    if (!$log->file_path) {
        return back()->with('error', 'Lampiran tidak tersedia.');
    }

    $filePath = storage_path('app/public/laporan/' . $log->file_path);

    if (!file_exists($filePath)) {
        return back()->with('error', 'File tidak ditemukan di server.');
    }

    return response()->download($filePath);
}



    public function create()
    {
        $mahasiswa = Mahasiswa::where('user_id', Auth::user()->user_id)->first();
        $dosen = Dosen::all();
        $company = Company::all();
        $breadcrumb = (object) [
            'title' => 'Buat Laporan',
            'subtitle' => 'Buat Laporan Magang'
        ];
        return view('mahasiswa.laporan.create', compact('breadcrumb', 'mahasiswa', 'dosen', 'company'));
    }

public function store(Request $request)
{
    // Validasi input
    $request->validate([
        'mahasiswa_id' => 'required|exists:mahasiswas,mahasiswa_id',
        'dosen_id' => 'required|exists:dosens,dosen_id',
        'company_id' => 'required|exists:companies,company_id',
        'report_title' => 'required|string',
        'report_text' => 'required|string',
        'file' => 'nullable|file|max:2048',
    ]);

    $filePath = null;

    if ($request->hasFile('file')) {
        $file = $request->file('file');
        $filename = time() . '_' . $file->getClientOriginalName();
        $file->storeAs('laporan', $filename, 'public');
        $filePath = $filename;
    }

    Log::create([
        'mahasiswa_id' => $request->mahasiswa_id,
        'dosen_id' => $request->dosen_id,
        'company_id' => $request->company_id,
        'report_title' => $request->report_title,
        'report_text' => $request->report_text,
        'file_path' => $filePath, // simpan nama file (atau null jika tidak ada file)
    ]);

    return redirect()->route('laporan')
        ->with('success', 'Laporan berhasil dibuat.');
}


    public function edit($id)
    {
        $logs = Log::findOrFail($id);
        $mahasiswa = Mahasiswa::where('user_id', Auth::user()->user_id)->first();
        $dosen = Dosen::all();
        $company = Company::all();
        $breadcrumb = (object) [
            'title' => 'Edit Laporan',
            'subtitle' => 'Edit Laporan Magang'
        ];
        return view('mahasiswa.laporan.edit', compact('breadcrumb', 'logs', 'mahasiswa', 'dosen', 'company'));
    }

public function update(Request $request, $id)
{
    // Validasi input
    $request->validate([
        'mahasiswa_id' => 'required|exists:mahasiswas,mahasiswa_id',
        'dosen_id' => 'required|exists:dosens,dosen_id',
        'company_id' => 'required|exists:companies,company_id',
        'report_title' => 'required|string',
        'report_text' => 'required|string',
        'file' => 'nullable|file|max:2048',
    ]);

    // Cari log berdasarkan ID
    $log = Log::findOrFail($id);

    // Start with current file_path
    $filePath = $log->file_path;

    if ($request->hasFile('file')) {
        // Delete old file if exists
        if ($filePath && \Storage::disk('public')->exists('laporan/' . $filePath)) {
            \Storage::disk('public')->delete('laporan/' . $filePath);
        }

        // Upload new file
        $file = $request->file('file');
        $filename = time() . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());
        $stored = $file->storeAs('laporan', $filename, 'public');

        if ($stored) {
            $filePath = $filename; // Only update if storeAs worked
        } else {
            return back()->with('error', 'Gagal mengunggah file baru.');
        }
    }

    // Update log
    $log->update([
        'mahasiswa_id' => $request->mahasiswa_id,
        'dosen_id' => $request->dosen_id,
        'company_id' => $request->company_id,
        'report_title' => $request->report_title,
        'report_text' => $request->report_text,
        'file_path' => $filePath
    ]);

    return redirect()->route('laporan')->with('success', 'Laporan berhasil diperbarui.');
}

    public function destroy($id)
    {
        // Cari log berdasarkan ID
        $log = Log::findOrFail($id);

        // Hapus log
        $log->delete();

        return redirect()->route('laporan')->with('success', 'Laporan berhasil dihapus.');
    }

    public function show($id)
    {
        $log = Log::findOrFail($id);
        $breadcrumb = (object) [
            'title' => 'Detail Laporan',
            'subtitle' => 'Detail Laporan Magang'
        ];
        return view('mahasiswa.laporan.show', compact('breadcrumb', 'log'));
    }
}
