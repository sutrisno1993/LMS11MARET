<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KbmSession extends Model
{
    use HasFactory;

    protected $table = 'kbm_sessions';

    protected $fillable = [
        'tanggal',
        'id_timetable',
        'id_kelas',
        'id_mapel',
        'jam_ke',
        'id_guru_terjadwal',
        'id_guru_aktual',
        'status_sesi',
        'status_guru',
        'waktu_scan_masuk',
        'waktu_scan_keluar',
        'materi_log'
    ];

    protected $casts = [
        'tanggal' => 'date',
        'waktu_scan_masuk' => 'datetime',
        'waktu_scan_keluar' => 'datetime',
    ];

    public function timetable()
    {
        return $this->belongsTo(Timetable::class, 'id_timetable', 'id_timetable');
    }

    public function clas()
    {
        return $this->belongsTo(Clas::class, 'id_kelas', 'id_kelas');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'id_mapel', 'id_mapel');
    }

    public function guruTerjadwal()
    {
        return $this->belongsTo(Teacher::class, 'id_guru_terjadwal', 'id_guru');
    }

    public function guruAktual()
    {
        return $this->belongsTo(Teacher::class, 'id_guru_aktual', 'id_guru');
    }

    public function attendances()
    {
        return $this->hasMany(StudentAttendance::class, 'id_kbm_session');
    }
}
