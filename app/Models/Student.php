<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $table = 'students';
    protected $primaryKey = 'id_siswa';

    protected $guarded = [];

    public function user()
    {
        return $this->hasOne(User::class, 'id_siswa', 'id_siswa');
    }

    public function clas()
    {
        return $this->belongsTo(Clas::class, 'id_kelas', 'id_kelas');
    }

    public function attendances()
    {
        return $this->hasMany(StudentAttendance::class, 'id_siswa', 'id_siswa');
    }
}
