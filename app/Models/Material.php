<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    use HasFactory;

    protected $table = 'materials';
    protected $primaryKey = 'id_materi';

    protected $fillable = [
        'id_guru',
        'id_mapel',
        'id_kelas',
        'judul',
        'deskripsi',
        'file_path',
        'file_name',
        'file_size'
    ];

    public function teacher()
    {
        return $this->belongsTo(Teacher::class, 'id_guru', 'id_guru');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'id_mapel', 'id_mapel');
    }

    public function clas()
    {
        return $this->belongsTo(Clas::class, 'id_kelas', 'id_kelas');
    }
}