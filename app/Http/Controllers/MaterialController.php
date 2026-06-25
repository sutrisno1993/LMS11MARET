<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\Material;
use App\Models\ClassSubject;
use App\Models\Student;
use App\Models\Timetable;

class MaterialController extends Controller
{
    // ==========================================
    // GURU METHODS
    // ==========================================

    public function index()
    {
        $user = Auth::user();

        // Ambil daftar kelas dan mapel yang diajar guru melalui Timetable
        $timetableList = Timetable::with(['classSubject.clas', 'classSubject.subject'])
            ->where('id_guru', $user->id_guru)
            ->get();

        $kelasList = $timetableList->map(function($t) {
            return $t->classSubject->clas ?? null;
        })->filter()->unique('id_kelas')->values();

        $mapelList = $timetableList->map(function($t) {
            return $t->classSubject->subject ?? null;
        })->filter()->unique('id_mapel')->values();

        // Ambil materi yang diupload oleh guru ini
        $materials = Material::with(['clas', 'subject'])
            ->where('id_guru', $user->id_guru)
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Guru/Materi', [
            'kelasList' => $kelasList,
            'mapelList' => $mapelList,
            'materials' => $materials,
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'judul' => 'required|string|max:100',
            'deskripsi' => 'nullable|string',
            'id_kelas' => 'required|exists:classes,id_kelas',
            'id_mapel' => 'required|exists:subjects,id_mapel',
            'file' => 'required|file|max:20480', // Maks 20MB
        ]);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $path = $file->store('materials', 'public');

            Material::create([
                'id_guru' => $user->id_guru,
                'id_mapel' => $request->id_mapel,
                'id_kelas' => $request->id_kelas,
                'judul' => $request->judul,
                'deskripsi' => $request->deskripsi,
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
            ]);

            return redirect()->back()->with('message', 'Materi berhasil diunggah.');
        }

        return redirect()->back()->with('error', 'Gagal mengunggah file.');
    }

    public function destroy($id)
    {
        $user = Auth::user();
        $material = Material::where('id_guru', $user->id_guru)->findOrFail($id);

        // Hapus file fisik
        if (Storage::disk('public')->exists($material->file_path)) {
            Storage::disk('public')->delete($material->file_path);
        }

        $material->delete();

        return redirect()->back()->with('message', 'Materi berhasil dihapus.');
    }

    // ==========================================
    // SISWA METHODS
    // ==========================================

    public function siswaIndex()
    {
        $user = Auth::user();
        $siswa = Student::with('clas')->findOrFail($user->id_siswa);

        // Ambil materi yang ditujukan untuk kelas siswa ini
        $materials = Material::with(['teacher', 'subject'])
            ->where('id_kelas', $siswa->id_kelas)
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Siswa/Materi', [
            'materials' => $materials,
        ]);
    }

    public function download($id)
    {
        $material = Material::findOrFail($id);

        if (!Storage::disk('public')->exists($material->file_path)) {
            abort(404, 'File tidak ditemukan di server.');
        }

        return Storage::disk('public')->download($material->file_path, $material->file_name);
    }
}
