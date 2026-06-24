<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentAttendance extends Model
{
    use HasFactory;

    protected $table = 'student_attendances';

    protected $fillable = [
        'id_kbm_session',
        'id_siswa',
        'status',
        'waktu_presensi',
        'metode',
    ];

    protected $casts = [
        'waktu_presensi' => 'datetime',
    ];

    public function kbmSession()
    {
        return $this->belongsTo(KbmSession::class, 'id_kbm_session');
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'id_siswa', 'id_siswa');
    }
}
